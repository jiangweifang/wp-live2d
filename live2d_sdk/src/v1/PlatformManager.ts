/**
 *
 *  You can modify and use this source freely
 *  only for the development of application related Live2D.
 *
 *  (c) Live2D Inc. All rights reserved.
 */
import { getContext } from "./LAppDefine"
import { cachedFetchBytes } from "../lib/DbCache";
import { IDB_V1_PREFIX, makeIdbObjectUrl } from "../lib/idb-v1";

export class PlatformManager {
    /**
     * PlatformManager # loadBytes()
     * 走 IndexedDB 缓存层：URL 主键 + ETag/Last-Modified 协商 + TTL + LRU + 并发去重。
     * @param path
     */
    public async loadBytes(path: string, token?: string): Promise<ArrayBuffer> {
        try {
            return await cachedFetchBytes(path, {
                init: token
                    ? { headers: { Authorization: `Bearer ${token}` } }
                    : undefined
            });
        } catch (e) {
            console.error('Failed to load: ' + path, e);
            return null!;
        }
    }

    /**
     * 返回路径对应的内容（字符串类型）
     * @param path 
     * @param callback 
     */
    public async loadString(path: string, token?: string): Promise<string> {
        let buf = await this.loadBytes(path, token);
        let arrayStr = new TextDecoder('utf-8').decode(buf);
        return arrayStr;
    }

    public loadLive2DModel(path: string, callback: (model: any) => void, token?: string) {
        this.loadBytes(path, token).then((buf) => {
            var model = (<any>window).Live2DModelWebGL.loadModel(buf);
            callback(model);
        });
    }

    //============================================================
    //    PlatformManager # loadTexture()
    //============================================================
    public loadTexture(model/*ALive2DModel*/, no: number, path: string, callback) {
        // load textures
        var loadedImage = new Image();
        // idb-v1:// 是合成 scheme,Image 元素无法直接消费,需要先把 IndexedDB 里
        // 的字节流出 Blob URL 再喂给 .src;onload/onerror 后必须 revoke 释放。
        // 走真实 http(s) 路径时 crossOrigin='Anonymous' 是必要的(WebGL 纹理要可读),
        // Blob URL 同源不需要,反而设了会让某些浏览器警告。
        let blobUrl: string | null = null;
        const releaseBlob = () => {
            if (blobUrl) { URL.revokeObjectURL(blobUrl); blobUrl = null; }
        };

        var thisRef = this;
        loadedImage.onload = function () {
            // create texture
            if ((this as HTMLImageElement).complete) {
                var gl = getContext();
                var texture = gl.createTexture();
                if (!texture) { console.error("Failed to generate gl texture name."); releaseBlob(); return -1; }

                if (model.isPremultipliedAlpha() == false) {
                    // 乗算済アルファテクスチャ以外の場合
                    gl.pixelStorei(gl.UNPACK_PREMULTIPLY_ALPHA_WEBGL, 1);
                }
                gl.pixelStorei(gl.UNPACK_FLIP_Y_WEBGL, 1);
                gl.activeTexture(gl.TEXTURE0);
                gl.bindTexture(gl.TEXTURE_2D, texture);
                gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, gl.RGBA,
                    gl.UNSIGNED_BYTE, loadedImage);
                gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, gl.LINEAR);
                gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, gl.LINEAR_MIPMAP_NEAREST);
                gl.generateMipmap(gl.TEXTURE_2D);



                model.setTexture(no, texture);

                // テクスチャオブジェクトを解放
                texture = null;
            } else {
                console.log("材质图片加载失败, 请刷新页面后重试");
            }
            releaseBlob();
            if (typeof callback == "function") callback();
        };

        loadedImage.onerror = function () {
            console.error("Failed to load image : " + path);
            releaseBlob();
        }

        if (path.startsWith(IDB_V1_PREFIX)) {
            // 从 IndexedDB 取字节 -> Blob URL -> Image.src
            makeIdbObjectUrl(path).then((u) => {
                if (!u) {
                    console.error("Failed to load image (idb miss): " + path);
                    return;
                }
                blobUrl = u;
                loadedImage.src = u;
            });
        } else {
            // 真实 http(s) 路径:与 V2 lapptexturemanager 对齐 ——
            // cachedFetchBytes(path) -> Blob URL -> Image.src,让贴图也进 IDB,
            // 切 textureId / 二次访问就不必重新走网络。
            // 跨域是默认情况(扩展远端 V1 / WP 远端 V1),与原 <img crossOrigin> 等价
            // (都需要 ACAO);同源仅 V1 wp-content 本地下载这一种例外。
            // 失败时退回原始 <img crossOrigin> 直链兜底,保证渲染不阻断。
            cachedFetchBytes(path).then((buf) => {
                const blob = new Blob([buf], { type: 'image/png' });
                blobUrl = URL.createObjectURL(blob);
                // Blob URL 同源,**不要**设 crossOrigin。
                loadedImage.src = blobUrl;
            }).catch((err) => {
                console.warn('[PlatformManager] cached fetch failed, fallback to direct img: ' + path, err);
                loadedImage.crossOrigin = 'Anonymous';
                loadedImage.src = path;
            });
        }
    }


    //============================================================
    //    PlatformManager # parseFromBytes(buf)

    //============================================================
    public jsonParseFromBytes(buf) {

        var jsonStr;



        var bomCode = new Uint8Array(buf, 0, 3);
        if (bomCode[0] == 239 && bomCode[1] == 187 && bomCode[2] == 191) {
            jsonStr = String.fromCharCode.apply(null, new Uint8Array(buf, 3));
        } else {
            jsonStr = String.fromCharCode.apply(null, new Uint8Array(buf));
        }

        var jsonObj = JSON.parse(jsonStr);

        return jsonObj;
    }


    //============================================================
    //    PlatformManager # log()
    //============================================================
    public log(txt: string) {
        //console.log(txt);
    }


}
