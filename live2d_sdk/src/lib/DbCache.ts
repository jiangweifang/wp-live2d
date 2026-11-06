import Dexie, { Table } from 'dexie';

/**
 * 资产缓存 — 以完整 URL 为主键，支持 ETag / Last-Modified 条件请求、
 * TTL、LRU 淘汰。能像 Unity Web 那样把模型/动作/纹理等二进制资源全部
 * 装进 IndexedDB。
 *
 * ============================================================
 *  同源/跨域分布（用于理解 IDB 命中率与 ETag/Last-Modified 协商可用性）
 * ============================================================
 * IndexedDB 是严格 per-origin 隔离的存储,所以这里描述的"同源/跨域"是
 * 站在浏览器加载 SDK 那个页面 origin 的视角:
 *
 *  V2 模型(Cubism 4+):
 *   - **WP `protectV2='local'`** → 同源(PHP 通过 wp-json 代理子资源)。
 *     当前实现把 token+alias 放进 URL,会导致 per-session URL 抖动 → 单纯
 *     按 URL 当 IDB key 落盘也命不中。后续 Phase B 会让该路径走"签名
 *     manifest + SHA-256 内容寻址"(见 cachedFetchByHash),与本通用路径
 *     正交叠加。
 *   - 其他(扩展 Customize / core-only / WP `protectV2='direct'`) → 跨域,
 *     URL 由站长/用户自己填(任意 OSS/CDN/GitHub Pages),稳定 → 直接进 IDB
 *     是正收益。跨域响应只能读到 CORS-safelisted 头(Cache-Control /
 *     Last-Modified 可读、ETag 不可读除非服务端发 Access-Control-Expose-Headers)。
 *
 *  V1 模型(Cubism 2~3):
 *   - **WP 下载到本地** → 同源(`wp-content/.../live-2d/model/...` 静态资源)。
 *   - `idb-v1://` 是合成 scheme(扩展端用户主动下载到 IndexedDB),不发任何
 *     HTTP 请求,在 cachedFetch 入口被 PINNED_PREFIX 短路到 db.assets.get。
 *   - 其他(扩展远端 V1 / WP 远端 V1) → 跨域,与 V2 跨域同理。
 *
 * 因此对本模块而言:**跨域是默认路径,同源仅 V2 local + V1 本地两种**。
 * 所有路径都走 db.assets put(URL key),只有 `idb-v1://` 走特殊读路径。
 */
export interface CachedAsset {
    url: string;            // primary key（normalize 后的绝对 URL）
    data: ArrayBuffer;
    size: number;
    contentType: string;
    etag?: string;
    lastModified?: string;
    cachedAt: number;       // 最近一次写入或刷新的时间戳，用作 LRU 排序
    expiresAt: number;      // 在此之前直接走 fast-path，不发请求
}

export interface CachedFetchOptions {
    /** 透传给 fetch 的 init（不参与缓存键） */
    init?: RequestInit;
    /** 命中且未过期就直接返回，不发条件请求；默认 true */
    fastPath?: boolean;
    /** 强制绕过缓存（等价于浏览器 reload） */
    bypass?: boolean;
    /** 命中已过期或 fastPath=false 时是否做条件请求；默认 true */
    revalidate?: boolean;
    /** 单次请求超时（ms），0/省略表示不限 */
    timeoutMs?: number;
}

const DB_NAME = 'Live2D-JWF';
const DEFAULT_MAX_BYTES = 256 * 1024 * 1024;     // 总大小上限：256MB
const DEFAULT_TTL_MS = 24 * 60 * 60 * 1000;      // 默认 TTL：24h

export class DbCache extends Dexie {
    assets!: Table<CachedAsset, string>;

    constructor() {
        super(DB_NAME);
        // v1：历史 schema，仅有 mocJson 表。保留声明以便 Dexie 走升级链。
        this.version(1).stores({
            mocJson: '++id, [name+key]'
        });
        // v2：新增 assets 表，url 主键，cachedAt/expiresAt 索引服务于淘汰策略
        this.version(2).stores({
            mocJson: '++id, [name+key]',
            assets: '&url, cachedAt, expiresAt'
        });
        // v3：废弃 mocJson 表（已无任何读写方），把它显式置 null 让 Dexie
        // 在旧用户的 IndexedDB 里自动 drop。
        this.version(3).stores({
            mocJson: null,
            assets: '&url, cachedAt, expiresAt'
        });
    }
}

export const db = new DbCache();

// ---------------- 配置 ----------------
let maxBytes = DEFAULT_MAX_BYTES;
let defaultTtlMs = DEFAULT_TTL_MS;

/** 在应用启动时可选地调整缓存策略。 */
export function configureCache(opts: { maxBytes?: number; ttlMs?: number }) {
    if (typeof opts.maxBytes === 'number' && opts.maxBytes > 0) maxBytes = opts.maxBytes;
    if (typeof opts.ttlMs === 'number' && opts.ttlMs >= 0) defaultTtlMs = opts.ttlMs;
}

// -------------- 并发去重 --------------
const inflight = new Map<string, Promise<CachedAsset>>();

function normalizeUrl(url: string): string {
    try { return new URL(url, typeof location !== 'undefined' ? location.href : undefined).toString(); }
    catch { return url; }
}

// -------------- 内容寻址 resolver --------------
/**
 * URL → SHA-256(hex) 解析器。WP `protectV2='local'` 接入路径见
 * Wordpress/live2d-tips.ts。返回 undefined → 走 URL key 老路径。
 *
 * Chrome 扩展 / only-core / WP `protectV2='direct'` 都不会注册 resolver,
 * cachedFetch 入口 fast-skip 这一段,行为与之前完全一致。
 */
type HashResolver = (url: string) => string | undefined;
let _hashResolver: HashResolver | null = null;
export function setHashResolver(fn: HashResolver | null): void {
    _hashResolver = fn;
}

/**
 * idb-v1://{modelName}/... 是用户显式下载的 V1 模型整包,属于 "pinned"
 * 资产,绝不能被 LRU 淘汰掉(否则用户切回该模型就是空白)。这里在统计总量
 * 与挑选淘汰目标时都豁免该前缀。
 */
const PINNED_PREFIX = 'idb-v1://';

/**
 * 折叠 idb-v1:// URL 里的 `./` 与 `../` 到规范形式。
 * 动机:V1 多皮肤模型(如 HyperdimensionNeptunia)的子皮肤 index.json
 * 中许多资源路径带 `../general/...`(多个皮肤共享同一份动作/姿势文件),
 * LAppModel 拼出的结果是像 `idb-v1://Name/blanc_classic/../general/pose.json`
 * 这样的字面 URL,与 zip 解压后实际落盘的
 * `idb-v1://Name/general/pose.json` 不一致,直接 db.assets.get 必 miss。
 * 在查库前拍平路径可以让这类引用正常命中。
 */
export function canonicalizeIdbV1Url(url: string): string {
    if (!url.startsWith(PINNED_PREFIX)) return url;
    const rest = url.slice(PINNED_PREFIX.length);
    const parts = rest.split('/');
    const stack: string[] = [];
    for (const p of parts) {
        if (p === '' || p === '.') continue;
        if (p === '..') {
            if (stack.length > 0) stack.pop();
            continue;
        }
        stack.push(p);
    }
    return PINNED_PREFIX + stack.join('/');
}

/**
 * 内容寻址键前缀。`sha:{hex}` 形式,与 http(s) URL / `idb-v1://` 互不冲突。
 *
 * 用途:WP `protectV2='local'` 模式下,每次会话 URL 含随机 token+alias 抖动,
 * 直接以 URL 当主键会让 IDB 永远命不中。改用此前缀 + 文件 SHA-256 做主键后,
 * 跨会话拿到新签名 URL → resolver 查到 hash → IDB 直接命中,本地命中率 ≈ 100%。
 *
 * 同 `idb-v1://`,内容寻址条目按"pinned"对待 — hash 是稳定指纹,
 * 不应被 LRU 淘汰以免下次会话重新下载浪费带宽。
 */
const HASH_PREFIX = 'sha:';

function isPinnedKey(key: string): boolean {
    return key.startsWith(PINNED_PREFIX) || key.startsWith(HASH_PREFIX);
}

/** 写入超过总配额时按 cachedAt 升序淘汰，腾出空间。pinned 条目跳过。 */
async function evictIfNeeded(addingBytes: number): Promise<void> {
    try {
        const all = await db.assets.toArray();
        const evictable = all.filter((c) => !isPinnedKey(c.url));
        let sum = evictable.reduce((a, c) => a + (c.size || 0), 0) + addingBytes;
        if (sum <= maxBytes) return;
        evictable.sort((a, b) => a.cachedAt - b.cachedAt);
        for (const item of evictable) {
            if (sum <= maxBytes) break;
            await db.assets.delete(item.url);
            sum -= item.size || 0;
        }
    } catch (e) {
        console.warn('[DbCache] evict failed', e);
    }
}

/**
 * 带 IndexedDB 缓存的 fetch：
 *  - 命中 & 未过期        → 直接返回缓存（fast-path）
 *  - 命中 & 已过期        → 发条件请求；304 复用缓存并刷新 cachedAt
 *  - 未命中               → 真请求并写入缓存
 *  - 同 URL 并发          → 合并成一个真实请求
 *  - 网络失败而本地有副本 → 回退到旧缓存（离线可用）
 */
export async function cachedFetch(url: string, opts: CachedFetchOptions = {}): Promise<CachedAsset> {
    // idb-v1:// 是合成 scheme,数据由 v1ModelCache.downloadAndCacheV1Model 预先
    // 写入 db.assets。这里短路掉所有 fetch / TTL / revalidate 流程,直接读库。
    // normalizeUrl 走 new URL() 会把未注册 scheme 处理掉斜杠,索性提前判断。
    if (url.startsWith(PINNED_PREFIX)) {
        const canon = canonicalizeIdbV1Url(url);
        const asset = await db.assets.get(canon);
        if (asset) return asset;
        // 跨 origin 兜底:扩展 main.js 在 globalThis 上挂 __live2dIdbProxy,
        // 经 SW 代理读取扩展 origin 的真数据。WP 不会注册该 proxy,自然落到
        // 下面的 throw,与原行为一致 —— 因此对 WP 完全透明。
        const proxy = (globalThis as unknown as {
            __live2dIdbProxy?: { get?(u: string): Promise<{ data: ArrayBuffer; contentType: string } | null> };
        }).__live2dIdbProxy;
        if (proxy?.get) {
            const r = await proxy.get(canon).catch(() => null);
            if (r) {
                const now = Date.now();
                return {
                    url: canon,
                    data: r.data,
                    size: r.data.byteLength,
                    contentType: r.contentType || 'application/octet-stream',
                    cachedAt: now,
                    expiresAt: Number.MAX_SAFE_INTEGER
                };
            }
        }
        throw new Error(`idb-v1 asset missing: ${canon}`);
    }

    // 内容寻址路径:resolver 命中即把 URL 路由到 cachedFetchByHash,
    // miss 时 fallback 到本会话的签名 URL 真请求 + SHA-256 验证后 pinned 落盘。
    // resolver 没注册 / 返 undefined 时这一段是 zero-cost no-op。
    if (_hashResolver) {
        const hash = _hashResolver(url);
        if (hash) {
            return cachedFetchByHash(hash, url, opts);
        }
    }

    const key = normalizeUrl(url);
    const fastPath = opts.fastPath !== false;
    const revalidate = opts.revalidate !== false;
    const bypass = !!opts.bypass;

    if (!bypass) {
        const flying = inflight.get(key);
        if (flying) return flying;
    }

    const release = () => { inflight.delete(key); };
    const task = (async (): Promise<CachedAsset> => {
        const now = Date.now();
        const cached = bypass ? undefined : await db.assets.get(key).catch(() => undefined);
        if (cached && fastPath && cached.expiresAt > now) {
            return cached;
        }

        const headers = new Headers(opts.init?.headers as HeadersInit | undefined);
        if (cached && revalidate && !bypass) {
            if (cached.etag) headers.set('If-None-Match', cached.etag);
            if (cached.lastModified) headers.set('If-Modified-Since', cached.lastModified);
        }

        let controller: AbortController | undefined;
        let timer: ReturnType<typeof setTimeout> | undefined;
        if (opts.timeoutMs && opts.timeoutMs > 0) {
            controller = new AbortController();
            timer = setTimeout(() => controller!.abort(), opts.timeoutMs);
        }

        let resp: Response;
        try {
            resp = await fetch(key, {
                ...opts.init,
                headers,
                signal: controller?.signal ?? opts.init?.signal
            });
        } catch (e) {
            if (cached) return cached;   // 离线/网络故障 → 退回旧缓存
            throw e;
        } finally {
            if (timer) clearTimeout(timer);
        }

        if (resp.status === 304 && cached) {
            const refreshed: CachedAsset = {
                ...cached,
                cachedAt: now,
                expiresAt: now + defaultTtlMs
            };
            await db.assets.put(refreshed).catch(() => { });
            return refreshed;
        }

        if (!resp.ok) {
            if (cached) return cached;   // 4xx/5xx 时尽量不阻塞渲染
            throw new Error(`Failed to load (${resp.status}) : ${key}`);
        }

        const buf = await resp.arrayBuffer();
        const item: CachedAsset = {
            url: key,
            data: buf,
            size: buf.byteLength,
            contentType: resp.headers.get('Content-Type') || '',
            etag: resp.headers.get('ETag') || undefined,
            lastModified: resp.headers.get('Last-Modified') || undefined,
            cachedAt: now,
            expiresAt: now + defaultTtlMs
        };
        await evictIfNeeded(item.size);
        await db.assets.put(item).catch(() => { });
        return item;
    })();
    task.then(release, release);

    inflight.set(key, task);
    return task;
}

/** 便捷封装：只关心 ArrayBuffer。 */
export async function cachedFetchBytes(url: string, opts?: CachedFetchOptions): Promise<ArrayBuffer> {
    return (await cachedFetch(url, opts)).data;
}

// ============================================================
//  内容寻址 (Content-Addressed) 缓存
// ============================================================
/**
 * 按 SHA-256(hex) 主键命中 / 落盘。WP `protectV2='local'` 专用 —— 服务端
 * 把每个本地资源的 sha256 通过 hashes.json 提前下发,前端拿到后注册到
 * `setHashResolver`,后续 cachedFetch 入口自动走这条路径。
 *
 * 为什么需要这条路径(对比直接用 URL 当主键):
 *  - protectV2=local 的子资源 URL 形如 `…/m/{token}/{alias}?e=&s=`,token 与
 *    alias 都是 per-session 随机 → 同一文件每次会话 URL 都不同 → URL-keyed
 *    永远 miss,IDB 写入只是占位,完全无命中收益。
 *  - SHA-256 是文件内容指纹,跨会话稳定。命中本地就 0 字节网络。
 *
 * 流程:
 *  1. 查 IDB(key=`sha:{hash}`) → 命中直接返
 *  2. miss → fetch fallbackUrl(本会话签名 URL) → 算 SHA-256 验证
 *  3. 验证通过 → put(pinned,不被 LRU 淘汰)→ 返回
 *  4. 验证失败 → 不落盘 + 抛错(不应静默,让上层 fallback 决策)
 *
 * 失败兜底:同 cachedFetch — 网络故障若本地有副本则返旧条目;否则抛。
 */
export async function cachedFetchByHash(
    hash: string,
    fallbackUrl: string,
    opts: CachedFetchOptions = {}
): Promise<CachedAsset> {
    if (!hash) throw new Error('cachedFetchByHash: empty hash');
    const key = HASH_PREFIX + hash.toLowerCase();
    const fastPath = opts.fastPath !== false;
    const bypass = !!opts.bypass;

    if (!bypass) {
        // 同 hash 并发去重(包括来自不同 fallbackUrl 的同一文件请求)
        const flying = inflight.get(key);
        if (flying) return flying;
    }

    const release = () => { inflight.delete(key); };
    const task = (async (): Promise<CachedAsset> => {
        const now = Date.now();
        const cached = bypass ? undefined : await db.assets.get(key).catch(() => undefined);
        if (cached && fastPath) {
            return cached;
        }

        let controller: AbortController | undefined;
        let timer: ReturnType<typeof setTimeout> | undefined;
        if (opts.timeoutMs && opts.timeoutMs > 0) {
            controller = new AbortController();
            timer = setTimeout(() => controller!.abort(), opts.timeoutMs);
        }

        let resp: Response;
        try {
            resp = await fetch(fallbackUrl, {
                ...opts.init,
                signal: controller?.signal ?? opts.init?.signal
            });
        } catch (e) {
            if (cached) return cached;
            throw e;
        } finally {
            if (timer) clearTimeout(timer);
        }

        if (!resp.ok) {
            if (cached) return cached;
            throw new Error(`Failed to load (${resp.status}) : ${fallbackUrl}`);
        }

        const buf = await resp.arrayBuffer();
        // SHA-256 验证 — 服务端给的指纹必须与下载到的字节一致,否则:
        //  - 服务端发错了文件(理论上不会发生);
        //  - 中间代理篡改了 body(MITM / 透明缓存出 bug);
        //  - 上游文件已变但服务端 hash 表未刷新(应通过新会话拿新 hashes 修复)。
        // 任意一种都不该静默落盘 → 抛错让上层 fallback 决策。
        const actualHash = await sha256Hex(buf);
        if (actualHash !== hash.toLowerCase()) {
            throw new Error(
                `cachedFetchByHash: hash mismatch — expected ${hash}, got ${actualHash} (url=${fallbackUrl})`
            );
        }

        const item: CachedAsset = {
            url: key,
            data: buf,
            size: buf.byteLength,
            contentType: resp.headers.get('Content-Type') || '',
            etag: resp.headers.get('ETag') || undefined,
            lastModified: resp.headers.get('Last-Modified') || undefined,
            cachedAt: now,
            // pinned —— 内容寻址条目按定义就是稳定指纹,设到 MAX 让 fast-path
            // 永远命中,跳过 304 协商(服务端的签名 URL 也不会发 304,无意义)。
            // LRU 淘汰也由 isPinnedKey() 豁免。
            expiresAt: Number.MAX_SAFE_INTEGER
        };
        await evictIfNeeded(item.size);
        await db.assets.put(item).catch(() => { });
        return item;
    })();
    task.then(release, release);

    inflight.set(key, task);
    return task;
}

/**
 * SHA-256(ArrayBuffer) → 小写十六进制字符串。
 * 走 SubtleCrypto.digest,所有现代浏览器均原生支持 → 大文件也只在 GPU/CPU
 * 寄存器层上算,不阻塞主线程渲染(WP local 模式典型样本 5~30MB,< 50ms)。
 */
async function sha256Hex(buf: ArrayBuffer): Promise<string> {
    const digest = await crypto.subtle.digest('SHA-256', buf);
    const bytes = new Uint8Array(digest);
    let hex = '';
    for (let i = 0; i < bytes.length; i++) {
        hex += bytes[i].toString(16).padStart(2, '0');
    }
    return hex;
}

/**
 * 清空缓存（保留库句柄，Dexie 实例可继续使用）。
 * 旧实现是 `db.delete()` 直接删整库，删完再用 `db` 会报错，这里改为清表。
 */
export async function ClearDb(): Promise<void> {
    await db.assets.clear().catch(() => { });
}
