# Live2D Web Canvas - WordPress 插件

[![sdk](https://img.shields.io/badge/Live2D-v5.r5-blue?color=%23ff6e2d)](https://www.live2d.com/download/cubism-sdk/download-web/)  [![showcase](https://img.shields.io/badge/Live2dWebCanvas-v2.1.3-blue)](https://www.live2d.com/showcase/title/live2dwebcanvas/)  [![wordpress](https://img.shields.io/badge/Wordpress-v6.9.5-blue)](https://wordpress.org/download/releases/)  [![plugin](https://img.shields.io/badge/wordpress.org-live--2d-blue?logo=wordpress&logoColor=white)](https://wordpress.org/plugins/live-2d/)

> 📖 **使用帮助 / 文档中心：** **<https://jiangweifang.github.io/wp-live2d/>**
>
> 收录：[自定义模型教程](https://jiangweifang.github.io/wp-live2d/wiki/custom-model.html) · [ChatGPT 配置](https://jiangweifang.github.io/wp-live2d/wiki/chatgpt.html) · [浏览器扩展隐私政策](https://jiangweifang.github.io/wp-live2d/privacy/extension.html) · 注意事项与常见问题。

- 基于 Live2D 看板娘前端 HTML 源码改写。
- 在 WordPress 后台 → 插件 → 搜索 "live 2d" 即可一键安装：<https://wordpress.org/plugins/live-2d/>。觉得好用的话欢迎给个好评！

<img src='https://user-images.githubusercontent.com/38683169/216086597-6acf9f5e-f694-4c14-85cc-08cbbdfbfd4c.png' width='300px' />

## 这是个什么插件？

安装并启用后，你的 WordPress 站点右下角会出现一个会动的二次元小人（也就是常说的"看板娘"），可以摆姿势、说话、被点击互动。

- **零配置就能跑**：装完即用，自带几个示例模型。
- **想换造型 / 换皮肤**：到设置页里换一个 `modelAPI` 地址就行，详细教程见 [自定义模型教程](https://jiangweifang.github.io/wp-live2d/wiki/custom-model.html)。
- **想让她会聊天**：可以接 ChatGPT，参考 [ChatGPT 配置](https://jiangweifang.github.io/wp-live2d/wiki/chatgpt.html)。

更多说明、截图、常见问题都在 **<https://jiangweifang.github.io/wp-live2d/>**。

### 默认外观（可在设置里改）

这些是对话气泡的默认配色，看不顺眼可以到设置页随便改：

- 提示框背景色：rgba(236, 217, 188, 0.5)
- 边框颜色：rgba(224, 186, 140, 0.62)
- 阴影颜色：rgba(191, 158, 118, 0.2)
- 字体颜色：#32373c
- 高亮提醒颜色：#0099cc

## 进阶玩法（普通用户可以跳过）

> 本节是给已经在用对象存储 / CDN、并且担心模型被别人盗链的站长看的。
> 如果你只是想让看板娘正常显示，**默认配置就够了，下面这些都不用看**。

### "防盗链" 开关怎么选？

所谓"盗链"，就是别人发现你的模型文件地址后，直接拿到自己网站上去用，白嫖你的流量。

设置页 → "基础设置" → **防盗链 (Cubism 4+ 模型)** 只有两个选项，分别对应两种完全不同的思路，**二选一，不要叠加**：

| 选项 | 谁来挡盗链？ | 什么时候选 |
|---|---|---|
| **不缓存（默认）** | 你自己（去对象存储 / CDN 控制台开防盗链） | 模型已经放在阿里云 OSS、腾讯云 COS、七牛、又拍、自家 CDN 等地方 |
| **缓存到本地（推荐）** | 插件自己处理（自动给文件起别名 + 临时签名链接） | 模型放在 GitHub Pages、jsDelivr、自己搭的 nginx 这种没有防盗链功能的地方 |

#### 情况 A：模型已经在 OSS / COS / CDN 上 → 选 "不缓存"

这时让对象存储自己挡盗链最省事。常见三种做法（从简单到复杂）：

1. **公共读 + Referer 白名单（最简单，零代码）**
   - 阿里云 OSS：Bucket → 数据安全 → 防盗链，把你的网站域名加进白名单。
   - 腾讯云 COS：存储桶 → 安全管理 → 防盗链设置，操作类似。
   - 原理：浏览器加载模型时会带上 "我从哪个网站来的"（Referer），不在白名单的来源直接拒绝。
   - 局限：稍微懂点抓包的人能伪造来源绕过。

2. **套一层自家 CDN，开 URL 鉴权（中等）**
   - 在 OSS 前面套阿里云 CDN / 腾讯云 CDN / Cloudflare，开启 "URL 鉴权" 或 "Token 校验"。
   - 然后把插件设置页里的 `modelAPI` 填成 CDN 那边给的带签名地址。

3. **私有读 + 签名链接（最严格，需要自己写代码）**
   - Bucket 设成私有，每次访问都必须带签名才能下载。
   - 这种模式没法直接在插件设置页里配置，需要懂 PHP 的人自己在主题 `functions.php` 里写一段代码动态生成签名地址。
   - **插件不内置这层功能**，原因见下面。

#### 情况 B：模型放在 GitHub Pages 之类没法配防盗链的地方 → 选 "缓存到本地"

选这个之后，插件会把模型文件全部下载到你自己的 WordPress 站点里（`wp-content/plugins/live-2d/model/`），并且对外只露出一个加密过的临时链接，访客 F12 看到的也是这个临时链接，不会暴露源站地址。

- **只在第一次下载时占用源站流量**，之后访客都是从你自己的服务器拿，源站再也不会被白嫖。
- 行为和老版本（V1）模型放在站内一样，对小流量服务器（比如月 1TB 带宽）很友好。

#### 为什么插件不直接帮你接 OSS 私有读？

经常有人问 "为啥不能让插件自己拿我的 OSS AccessKey 去签名转发？" 三个原因：

1. **AccessKey 不能存数据库**。WordPress 的设置项是明文存的，万一数据库泄漏（被黑、备份外泄、共享主机出事），你的整个 OSS Bucket 就全没了，比模型被盗严重得多。
2. **每家云的签名算法都不一样**。阿里云、腾讯云、AWS、Cloudflare R2、MinIO 各搞一套，做完一家就有人提"加 XX 云支持"，没完没了。
3. **真要用的人不需要插件帮忙**。能搞定 OSS 私有读的站长，自己写几行 PHP 比点设置页方便得多，也更安全（凭据可以走环境变量，不进数据库）。

#### 想自己改代码接 OSS 签名链接？

目前插件**没有提供专门的钩子**给你拦截这个流程。如果实在想改，只能 fork 仓库，去 [src/live2d-V2Api.php](src/live2d-V2Api.php) 里改 `get_asset()` / `stream_local()` 这两个函数，让它在命中你的私有 OSS 路径时返回一个 302 跳转到 OSS 预签名地址。如果以后用这个功能的人多了，我们会考虑加一个正式的 `live2d_v2_resolve_alias` 钩子，但**插件本身永远不会内置任何具体云厂商的 SDK**。

如果你真打算这么干，请务必：
- 用最小权限的子账号（只给 "读取对象" 权限，限定到具体 Bucket / 目录）；
- **千万不要把 SecretKey 直接写进 WordPress 设置项里**，用环境变量或专门的密钥管理服务。

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
