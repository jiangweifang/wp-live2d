/**
 * 模型 API URL 形态判别 —— 共享给 Wordpress/live2d-tips.ts 与 Chromium/live2d-tips.ts。
 *
 * 历史教训:这块逻辑此前在两份 live2d-tips.ts 里各写了一份,导致同一个 bug
 * (g flag 的 stateful RegExp 复用、ModelDir 判定优先级)要修两次,而且非常容易漏。
 * 已抽到本模块统一维护,任何修复都自动应用到所有 3 个产出物
 * (WP 插件 / per-site only-core / Chrome 扩展)。
 */

export enum ApiUrlType {
    JsonFile,
    Live2dWeb,
    Other,
    ModelDir,
}

const LIVE2DWEB_API = 'https://api.live2dweb.com';

/**
 * 不要带 g 标志:带 g 的正则在跨调用复用同一实例时 lastIndex 会残留,连续
 * .test() 同一字符串会出现一次 true 一次 false 的 stateful 坑,曾导致
 * .model3.json 直链被误判为非 JSON,从而退化到 V1 PHP 的 /get/?id=... 路径。
 *
 * 也不要限制成只匹配 `model3.json`:Cubism 2 时代的 `.model.json` 直链同样
 * 应该被识别为 JsonFile,版本号由 model.json 自身的 Version 字段决定。
 */
const RE_MOD3_JSON = /\.json$/i;

/**
 * 砍掉末尾的 `/`。仅给需要拼 `?id=...` / `/get/?id=...` / `/NextModel/?id=...`
 * 的旧 PHP / Live2dWeb 分支用;形态判别(classifyModelApi)请直接传原始 URL。
 */
export function trimTrailingSlash(apiUrl: string): string {
    if (!apiUrl) return apiUrl;
    const len = apiUrl.length - 1;
    const lidx = apiUrl.lastIndexOf('/');
    return len === lidx ? apiUrl.substring(0, lidx) : apiUrl;
}

/**
 * 根据 *原始* modelAPI 字符串(未经 trimTrailingSlash 砍掉末尾 `/`)判断加载形态。
 * 优先级:
 *   ① `.json` / `.model3.json` 结尾                       → JsonFile     直链,直接交给 loadv1/loadv2。
 *   ② 命中 live2dweb 后端                                → Live2dWeb    需要走授权清单 API。
 *   ③ 以 `/` 结尾 *且* modelDir 是非空数组              → ModelDir     根目录 + modelDir[] 拼 model3.json。
 *   ④ 其它                                                → Other        兼容旧 WordPress / PHP 后端的 /get/?id=...。
 *
 * ⚠ 不要把"以 `/` 结尾"单独当作 ModelDir 的充分条件:WordPress 插件下部署在
 *   D:\live-2d\trunk 的老 PHP API,modelAPI 习惯上就是 "https://site/live2d-api/" 这种
 *   以 `/` 结尾的根路径,且不会填 modelDir 列表。必须让这种场景 fall through 到 Other,
 *   才能拼出 /get/?id=... 走老 PHP 接口。
 *
 * Chrome 扩展场景调用方不传 modelDir(undefined),自动跳过 ModelDir 分支,
 * 与原 Chromium/live2d-tips.ts 不存在 ModelDir 类型时的语义一致。
 */
export function classifyModelApi(rawModelAPI: string, modelDir?: unknown): ApiUrlType {
    if (!rawModelAPI) return ApiUrlType.Other;
    const url = new URL(rawModelAPI, 'https://live2d.invalid/');
    if (!['https:', 'http:'].includes(url.protocol)) throw new TypeError('Model URL must use HTTP or HTTPS.');
    if (RE_MOD3_JSON.test(url.pathname)) return ApiUrlType.JsonFile;
    if (url.origin === LIVE2DWEB_API && /\/v2(?:\/|$)/i.test(url.pathname)) return ApiUrlType.Live2dWeb;
    if (url.pathname.endsWith('/') && Array.isArray(modelDir) && modelDir.some(dir => typeof dir === 'string' && dir.trim())) {
        return ApiUrlType.ModelDir;
    }
    return ApiUrlType.Other;
}
