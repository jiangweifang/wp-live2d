=== Live2DWebCanvas 中文说明 ===

Contributors: jiangweifang
Donate link: https://github.com/jiangweifang/wp-live2d
Tags: live2d, vtuber, anime, animation, chatgpt
Tested up to: 7.1
Requires at least: 5.5
Stable tag: 2.3.0
Requires PHP: 7.4
License: MIT
License URI: https://opensource.org/licenses/MIT

为 WordPress 网站添加可交互的 Live2D 看板娘，支持模型设置、对话气泡、工具栏和可选的聊天服务。

== Description ==

Live2DWebCanvas 可以在网站上显示可交互的看板娘。您可以在 WordPress 后台配置模型、尺寸、位置、工具栏和提示消息。

主要功能：

* 加载旧版 Live2D 模型，无须安装 Pro 增强插件。
* 支持页面布局和小工具布局。
* 自定义提示消息及旧版模型贴图。
* 由管理员下载和管理旧版模型。
* 提供模型持久存储及共用文件校验。
* 可选接入外部一言和聊天服务。
* 可选接入 WordPress AI Client，由管理员选择服务提供方及模型。只有明确启用此后端后，已登录的 WordPress 用户才能调用。

您可以查阅[使用文档](https://jiangweifang.github.io/wp-live2d/)和[免费版源代码](https://github.com/jiangweifang/wp-live2d)。

旧版模型渲染、布局、工具栏、提示消息及共用功能保留在免费基础版（Free）。依赖 Cubism Core 的新版模型、新版模型托管及专用渲染控制，由独立的 Pro 增强插件提供；Pro 需要配合 Free 使用。[前往官网获取 Pro](https://www.live2dweb.com/Downloads/WordPressPro)。已购用户可使用原账号核对对应权益，不需要为同一权益重复付款。

Free 不包含或自动下载 Pro 代码。您可以从 Free 设置页进入 WordPress 官方上传页面，手动上传 Pro ZIP 并启用。版本兼容性及账号权益验证通过后，同一设置页会显示 Pro 功能，无须覆盖 Free 目录。

外部服务可能需要账号及单独的服务权益。没有兼容的 Pro 时，已配置的新版模型只会暂停显示，不会自动替换为其他角色。

== Installation ==

1. 将 live-2d 目录上传到 wp-content/plugins，或通过 WordPress 安装本插件。
2. 启用 Live2DWebCanvas。
3. 打开“设置 > Live 2D”配置模型。
4. 在管理员创意工坊下载旧版模型，或填写旧版模型的网址。

升级旧完整版本之前，请备份数据库及模型目录。不要先停用或删除旧插件。具备 Pro 权益的用户可以在旧插件仍启用时安装 Pro 迁移桥，先将模型复制、校验到 uploads/live2d-models，再更新 Free。其他用户应由管理员在升级前完成经过校验的模型复制和备份。升级后的迁移程序无法恢复已被更新器删除的文件。多站点环境需要网络管理员备份，并逐站迁移旧模型存储。

Pro 的服务器端远程模型读取需要支持 TLS 的 PHP cURL，并固定到已校验的公网 IPv4 地址。该读取链路不支持 HTTP 代理；受限环境可改由管理员上传本地模型。使用站内模型不需要向外部模型源请求资源。旧版模型 ZIP 安装使用 WordPress 文件系统接口，并在环境支持时使用 ZipArchive。

== Frequently Asked Questions ==

= 访客可以把模型下载到服务器吗？ =

不可以。下载需要管理员授权。Pro 的访客会话仅限于已经配置的模型，不能创建下载任务。公开页面中的 nonce 和一次性会话令牌不是管理员凭据。

= 支持哪些文件名？ =

模型路径可以包含字母、数字、下划线、短横线和点，支持 Haru.model3.json、idle.motion3.json 等复合文件名。不允许可执行文件、隐藏路径、目录穿越或符号链接。文件内容必须符合受支持的模型、JSON、贴图、音频或文本格式。

= 从 2.2.1 或更早版本升级后，还需要做什么？ =

刷新页面以获取新的访客会话，并由管理员重新提交旧下载任务。如果曾启用受影响的功能，请检查模型目录和其他站点文件是否被入侵。更新插件不会自动删除攻击者此前植入的文件。

== 外部服务 ==

插件可能根据您选择的功能和模型访问以下服务：

* Live2DWeb（www.live2dweb.com 和 api.live2dweb.com）：提供账号登录、授权验证、模型服务及可选聊天。请求可能包含站点网址、账号 Key 或签名令牌，以及所选功能必需的数据。当前条款和隐私说明请参阅[服务官网](https://www.live2dweb.com/)。
* download.live2dweb.com：提供管理员主动发起的旧版模型压缩包下载。服务会接收所请求的模型标识及身份验证信息。
* 管理员配置的模型服务器：浏览器或 WordPress 服务器根据设置请求模型清单和资源；模型服务器会接收到正常的 HTTP 连接信息。Pro 的服务器端模型代理会拒绝非公网目标。
* 可选的一言服务，包括 hitokoto.cn、jinrishici.com 和 fghrsh.net：选择相应提供方后，浏览器会请求句子内容。启用前请了解其服务条款和隐私说明。
* 可选聊天服务：启用聊天时，用户消息和配置的对话上下文会发送给所选服务。未经必要同意，请勿发送敏感信息。
* WordPress AI 提供方：选择内置后端后，插件会将用户消息及已保存的系统指令交给站点配置的 WordPress AI 提供方。该提供方自行处理凭据和外部请求，此路径不使用 Live2DWeb 聊天后端。

== 源代码与构建 ==

安装包包含 WordPress PHP 源代码。JavaScript 和 CSS 构建源码位于[项目仓库](https://github.com/jiangweifang/wp-live2d)的普通 live2d_sdk 目录中，不再使用 Git 子模块。安装锁定的依赖后，在该目录运行 npm run build 即可构建。构建 Free 不需要私有 Pro SDK。jQuery 和 Moment.js 使用 WordPress 自带版本。

== 第三方许可 ==

插件自有代码使用 MIT 许可，第三方组件保留各自的许可。

Free 不包含新版 Cubism Core。旧版 Live2D 运行库、框架及模型资源的许可仍需要分别确认；免费提供不等于符合 GPL 兼容要求。当前尚未发布的拆分版本不代表已获得 WordPress.org 的许可审核批准。请保留上游声明，并取得使用及分发所需的授权。

本插件不是 Live2D Inc. 的官方产品。

== Changelog ==

= 2.3.0 =
* 将旧版模型和共用功能保留在 Free，将依赖 Cubism Core 的功能拆分为单独分发的 Pro 增强插件。
* Pro 不可用或插件停用时，保留模型设置和账号状态。
* 增加经过校验的持久存储迁移，以及醒目的版本差异、Pro 获取和上传入口。
* 安全：访客会话仅限于已配置模型，不再信任客户端提交的模型清单。
* 安全：取消访客触发下载，下载任务必须由管理员授权。
* 安全：保存模型文件前校验路径、扩展名及文件内容。
* 安全：校验重定向目标，将远程连接固定到已确认的公网地址。
* 安全：加强后台输出转义，移除包含凭据的诊断日志。
* 安全：账号绑定需要管理员授权，继续使用现有 Key 设置表单登录。
* 安全：旧版模型下载必须使用 HTTPS，两种 ZIP 解压方式均须先校验压缩包内容。
* 安全：只有站长明确启用后，才允许使用本站 WordPress AI 提供方。
* 安全：净化消息 HTML，拒绝工具栏中的不安全跳转网址。
* 调整：旧版和共用功能不依赖 Pro，免费包不包含新版模型的专用实现。
* 兼容：保留 Pro 的管理员上传、访客会话、签名资源和本地模型缓存功能。
* 维护：使用 WordPress 自带的 Moment.js，更新插件目录元信息。

= 2.2.1 =
* 修复旧版模型的多皮肤加载和共享资源问题。
* 改进模型生命周期清理及 IndexedDB 资源缓存。
* 更新 Cubism 着色器的分发方式。

= 2.2.0 =
* 增加自定义模型的本站托管保护，更新可选聊天接入。

更早的变化请参阅[仓库历史](https://github.com/jiangweifang/wp-live2d)。

== Upgrade Notice ==

= 2.3.0 =
拆分 Free 和 Pro，并修复 CVE-2026-92895 相关安全问题。替换旧完整插件之前，务必备份设置并迁移插件目录中的旧模型。新版模型需要独立 Pro 增强插件。曾受影响的站点仍需检查是否被入侵。
