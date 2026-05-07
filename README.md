# Live2D Web Canvas - WordPress 插件

[![sdk](https://img.shields.io/badge/Live2D-v5.r5-blue?color=%23ff6e2d)](https://www.live2d.com/download/cubism-sdk/download-web/)  [![showcase](https://img.shields.io/badge/Live2dWebCanvas-v2.1.3-blue)](https://www.live2d.com/showcase/title/live2dwebcanvas/)  [![wordpress](https://img.shields.io/badge/Wordpress-v6.9.5-blue)](https://wordpress.org/download/releases/)  [![plugin](https://img.shields.io/badge/wordpress.org-live--2d-blue?logo=wordpress&logoColor=white)](https://wordpress.org/plugins/live-2d/)

> 📖 **使用帮助 / 文档中心：** **<https://jiangweifang.github.io/wp-live2d/>**
>
> 收录：[自定义模型教程](https://jiangweifang.github.io/wp-live2d/wiki/custom-model.html) · [ChatGPT 配置](https://jiangweifang.github.io/wp-live2d/wiki/chatgpt.html) · [浏览器扩展隐私政策](https://jiangweifang.github.io/wp-live2d/privacy/extension.html) · 注意事项与常见问题。

- 基于Live2D 看板娘前端 HTML 源码改写
- 插件可在WordPress后台通过插件搜索获得 https://wordpress.org/plugins/live-2d/ 记得给个好评！

<img src='https://user-images.githubusercontent.com/38683169/216086597-6acf9f5e-f694-4c14-85cc-08cbbdfbfd4c.png' width='300px' />



以下是默认值：
- 提示框背景色：rgba(236, 217, 188, 0.5)
- 边框颜色：rgba(224, 186, 140, 0.62)
- 阴影颜色：rgba(191, 158, 118, 0.2)
- 字体颜色：#32373c
- 高亮提醒颜色：#0099cc

## 进阶玩法

> 本节面向已经懂得 OSS / CDN / WordPress hook 的站长。普通用户按"基础设置"用默认配置即可,可以跳过。

### 关于"防盗链(Cubism 4+ 模型)"开关的两种取舍

设置页 → "基础设置" → **防盗链(Cubism 4+ 模型)** 只提供两个选项,对应两种完全不同的部署思路:

| 选项 | 谁负责防盗链 | 适用场景 |
|---|---|---|
| **不缓存(默认)** | 你自己(在 OSS / CDN 控制台配) | 模型托管在阿里云 OSS / 腾讯云 COS / 七牛 / 又拍 / 自家 CDN |
| **缓存到本地(推荐)** | 插件(alias + HMAC 签名 URL) | 模型托管在裸 HTTP 目录(如 GitHub Pages / 自建 nginx / 第三方静态托管) |

两者**互斥**,不要试图叠加。

### 场景 A:把模型放在对象存储上(走 `不缓存`)

如果你已经把 `*.model3.json / *.moc3 / *.png` 全部上传到 OSS / COS,**最省力的做法是让对象存储自己处理防盗链**,插件这边选 `不缓存` 即可。常见三种姿势:

1. **公共读 + Referer 白名单**(零代码,推荐入门)
   - 阿里云 OSS:Bucket → 数据安全 → 防盗链,把你的站点域名加白,空 Referer 视情况禁止。
   - 腾讯云 COS:存储桶 → 安全管理 → 防盗链设置,同上。
   - 浏览器加载模型时会自带 `Referer: https://你的站/`,白名单外的引用一律 403。
   - 局限:能看到 `modelAPI` 直链的人换个 UA / 抓包改 Referer 就能盗。

2. **CDN + Token 鉴权**(中等复杂度)
   - 把 OSS 套一层自家 CDN(阿里云 CDN / 腾讯云 CDN / Cloudflare),在 CDN 侧开 URL 鉴权 / Token 校验。
   - 插件的 `modelAPI` 填 CDN 域名加签名后的 URL 即可。

3. **私有读 + 签名 URL**(最严格,但需要自己写代码)
   - Bucket 设私有读,所有 GET 都必须带 `OSSAccessKeyId / Expires / Signature`。
   - 这种模式无法直接配进插件 —— 需要在你自己的 mu-plugin / 主题 functions.php 里 hook 一段 PHP,在每次输出页面前用 AccessKey 现签一批临时 URL,替换 `modelAPI`。
   - **本插件不内置这层封装**,原因见下文。

### 场景 B:模型托管在没有防盗链能力的地方(走 `缓存到本地`)

如果源站只是个裸 HTTP 目录、GitHub Pages、jsDelivr 之类,本身没法配防盗链,选 `缓存到本地`:插件会把当前 `modelAPI` 指向的 `model3.json` + 全部子资源拉到 `wp-content/plugins/live-2d/model/` 下,运行时通过 `…/wp-json/live2d/v2/m/{token}/{alias}?e=&s=` 的临时签名 URL 透出,访客 F12 看不到真实文件名 / 路径 / 源站。

适合自家 1TB/月小流量服务器:**仅首次同步消耗一次源站带宽**,之后访客全程吃本站磁盘 → 出网带宽,行为与 V1 模型托管在站内一致。

### 为什么插件不内置 OSS / COS 私有读签名 URL?

技术上可行,而且能复用现有 `local` 模式的 alias / token / 过期机制 —— 把 `readfile($localPath)` 换成 `wp_redirect($ossSignedUrl, 302)` 就够了。但有三条硬伤让我们决定不做:

1. **AccessKey 必须落库**。WP options 是明文存储,一旦数据库泄漏(WP 漏洞 / 备份外泄 / 共享主机)= 整个 bucket 沦陷,后果远大于模型被盗。
2. **多云适配不归插件管**。阿里云 V1 签名、腾讯云 V5、AWS SigV4、R2 / MinIO …… 每家算法都不一样,任选其一都会引来"加 XX 云支持"的 issue 雪球。
3. **真有这需求的人不需要插件帮忙**。愿意搞 OSS 私有读的站长,自己在 mu-plugin 里写十几行签名函数比配置面板更顺手,也更安全(凭据可走环境变量 / Secrets Manager,不进数据库)。

### 想自己实现 OSS 签名 URL 转发?

目前插件**没有暴露 `apply_filters` 钩子**让你拦截 alias resolve 流程。如果你确实想把 `缓存到本地` 改造成 `缓存到 OSS`,只能 fork [src/live2d-V2Api.php](src/live2d-V2Api.php) 的 `get_asset()` / `stream_local()`,在命中私有 OSS 路径时改返回 302 到 OSS 预签名 URL。后续如果有足够多用户提需求,我们会考虑加一个稳定的 `live2d_v2_resolve_alias` filter,但**插件本体仍不会内置任何具体云厂商的 SDK**。

请务必使用最小权限的 RAM 子账号(只授予 `oss:GetObject`,限定到具体 Bucket/Prefix),并且**不要把 SecretKey 写进 `wp_options`**。

## 版权声明

- 本插件本体遵循 **MIT 许可证**（见仓库根目录 [`LICENSE`](LICENSE)）。
- [live2d_src / ©journey-ad / GPL v2.0][1] —— 上游原作品仍受其 GPL v2.0 约束。
  
  [1]: https://github.com/journey-ad/live2d_src "基于 #fea64e4 的修改版"
  
- Live2D Web Canvas - WordPress 插件不属于 Live2D Inc. 它是一个非官方产品

## Live2D Cubism Core 再配布声明

本插件随包分发 Live2D Cubism Core 6.x（位于 [`assets/cubism-core/`](assets/cubism-core/)，包含 `live2dcubismcore.min.js` 与随附的 [`LICENSE.md`](assets/cubism-core/LICENSE.md) / [`RedistributableFiles.txt`](assets/cubism-core/RedistributableFiles.txt)）。该目录下的文件遵循 **Live2D Proprietary Software License Agreement**，与本插件主体的 MIT 许可独立，未随本插件重新许可。

- 协议全文：<https://www.live2d.com/eula/live2d-proprietary-software-license-agreement_cn.html>
- 该目录下的 `live2dcubismcore.min.js` 依据官方 `RedistributableFiles.txt` 清单允许再配布；二次分发者请保留该目录内原始 LICENSE / RedistributableFiles 文件，不得删改。
- 二次分发者需要求 downstream distributor 与终端用户接受与原协议同等效力的保护条款（EULA 5.2.2）。
- 与本插件及 Cubism Core 相关的第三方费用 / 诉讼须由二次分发者自行免责、抗辩与补偿 Live2D 公司（EULA 5.2.3）。
- 插件作者与 Live2D Inc. 无雇佣关系，但保持官方合作关系。本插件支持加载任意第三方 model3.json / 切换皮肤 / 所有 V2 区存仓库模型，按 EULA 1.5 / 2.1 / 6.3 定义属于“拓展性应用 (拡張性アプリ)”；出版派生作品前（包括在 wordpress.org / GitHub Releases / 自有站点公开使用）请参照 EULA 第 2 条、第 6.3 条自行与 Live2D 公司签订 *Live2D 出版许可协议*（符合 EULA 2.2 “一般用户 / 小规模事业者 / 适格教育机构”资格且获 Live2D 书面批准者可免除）。

## 软件许可协议
[![MIT](https://img.shields.io/badge/license-MIT-green)](LICENSE)
[![Proprietary Software](https://img.shields.io/badge/license-Live2D%20Proprietary%20Software-blue)](https://www.live2d.com/eula/live2d-proprietary-software-license-agreement_en.html)
[![Open Software](https://img.shields.io/badge/license-Live2D%20Open%20Software-blue)](https://www.live2d.com/eula/live2d-open-software-license-agreement_en.html)
