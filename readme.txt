=== Live2DWebCanvas ===

Contributors: Weifang Chiang
Donate link: https://github.com/jiangweifang/wp-live2d
Tags: Live2D,看板娘,萌,moe,vtuber,comic,anime,live,2d,animation,ChatGPT
Tested up to: 6.8
Requires at least: 5.5
Stable tag: 1.9.16
Requires PHP: 7.4
License: GPLv3

== Changelog ==

= 1.9.16 = 
- 对GPT API和样式进行了一些调整，GPT对话的时候不会因为鼠标事件导致消息被冲掉。
- GPT对话会出现markdown内容，可以通过点击进入您的站点中其他页面。
- 现在Tips向上扩展而不是向下，以便于GPT回复较长内容。
- 由于服务端升级，所以旧版本将会出现严重重复的问题，只能更新到16版本才可以解决。
- 感谢[kpoi](http://kpoi.cn/) 的反馈，一言难尽的错误已修复。

= 1.9.15 = 
- 本次更新将会把验证转移给Wordpress服务端。
- 请确保您的REST API功能是开启的，它是默认开启的，如果您安装某些插件导致其关闭，您将会无法使用此插件。
- 由于在上个版本中，你获取了新的Key，通过Key将会自行处理token，您不会频繁依赖请求我的服务。
- Semantic Kernel 搜索集成增强，她可以通过关键词搜索整个站点中的所有内容。
- 本次进行了回归测试，看起来它不会对非付费用户带来麻烦。

= 1.9.14 = 
- 简化验证过程，新增了key，您可以通过访问 https://www.live2dweb.com/ 来获取Key来代替Token，下个版本我会全面改为使用Key验证，请各位尽快升级。
- 隐私增强，修复了一些隐私数据暴露的问题。
- Semantic Kernel 搜索集成，现在可以通过机器人图标和Live2D 问当前页面的问题。由于服务器改为上海，所以没有办法访问OPENAI了，各位之前维护的KEY我会删除。
- 关于OPENAI：目前我使用的是个人的Azure OPENAI，暂时没有考虑使用DeepSeek 因为DS的思考过程较长，对话框容不下。

= 1.9.13 = 
- 修复Wordpress安全警告
- 使用了新的访问API方式

= 1.9.12 = 
- 修复Wordpress安全警告
- 修复一个插件错误

= 1.9.11 =
- 服务器升级完成，由于最近比较忙乱，导致服务器升级出现了一些错误，如果支付失败请和我联系，我会尽快给你进行恢复。
- 预计Unity WebGL将作为2.0版本对模型进行加密访问，保护你的模型版权。

= 1.9.10 =
- 修复review代码时发现的一些PHP错误，并对其重用性进行优化，因为PHP我实在是有点苦手，工程量一大了我就有点晕头。
- 本次更新不会修改live2d.js中的内容
- 下个版本将增加热点触发功能，你可以通过CSS选择器，让新模型摆出特定的Motions中的动作。我觉得这样解释各位专业的Live2D模型制作者可以理解。

= 1.9.9 =
- framework 升级到 Cubism 5 SDK for Web R1 beta1 支持融合变形，[了解SDK版本区别](https://docs.live2d.com/zh-CHS/cubism-sdk-manual/cubism-5-new-functions/)
- 对异常情况增加捕获。
- 修复：函数 `register_rest_route` 的调用方法不正确。
- 修复`waifu-tool`的样式错误，感谢[peko](https://pekolove.com/) 的反馈。
- 修复V3模型触摸问题。

= 1.9.8 = 
- ChatGPT功能增强：支持GPT4。GPT4需要您自己申请。
- 您可以在https://www.live2dweb.com/ 中配置您自己的OpenAI API接口。
- 不再支持text-davinci-003，因为他太贵了，性价比很差。
- 现在看板娘可以记住最近的5轮对话，您不能设置我的预设，但是可以通过自己配置API来增加对话轮次。
- 显示的内容是Token限制，因为我免费提供功能，所以MaxToken是100，您可以通过接口自己设置最大数量，最大不能超过4000。
- 感情从0.5提升至0.8。
- 解决模型加载慢和服务器过载的问题。

= 1.9.7 = 
- 增加ChatGPT的开关功能。

= 1.9.6 = 
- 为了感谢各位付费用户这段时间的支持，我专款专用提升了API的CDN性能，改为全站CDN加速
- 修复了一个MOC3模型文件清单的问题，我发现很多人都在将model.json作为清单，我不再判断是否是model3.json文件。

= 1.9.5 = 
- 修复已知问题。

= 1.9.4 =
- 本次更新修改JS文件, 请注意CDN缓存
- 更新后支持在Wordpress购买域名并直接搭建的用户, 感谢[夏颜](https://talilla.com/) 这位出色的设计师创作了新的Live2D MOC3模型.
- 增加回车按钮回复ChatGPT.

= 1.9.3 =
- 修复moc3模型加载有点吃cpu的问题, 感谢 https://tajourney.games/ 提供的模型测试
- 去掉了老版本SDK的控制台提示. 污染控制台人人有责
- 我在做什么: 正在开发Chrome插件.

= 1.9.2 =
- 修复了一些bug
- 纠正ChatGPT被唤醒的时候样式的一些问题.

= 1.9.1 =
- 前端JS 完全抛弃jQuery 独立运行, 避免某些WP站点样式夺取wordpress官方的指定钩子(hook), 导致无法加载jQuery.
- 由于拖拽之前使用jQuery UI, 脱离jQuery后, 拖拽功能使用interactjs, 拖拽有惯性效果出现, 您可以给看板娘扔出去(回不来的话再后台设置一下不保存就回来了)
- ChatGPT使用了`gpt-3.5-turbo`模型, 但是去掉了上下文理解, 每一次提问都是一个新的问题, 她不会对之前的问题结合回覆了.
- 新增创意工坊, 站长可以通过下载到本地, 省去了部署服务的困扰.
- 您在使用创意工坊之前, 需要先去插件网站选择自己的模型, 然后在WP的创意工坊中下载才可以使用.
- 新增: 在您使用创意工坊API的时候, 系统可以通过下拉选项选择皮肤, 不是让您去猜测到底有什么了.
- 对老版本(Pio等模型)的SDK 进行解耦拆分, 完全与新的版本隔离, 避免代码混乱, 此模型SDK未来不会增加新的功能.
- 对Live2D Cubism 4 SDK for Web R6 进行支持 [変更履歴](https://docs.live2d.com/cubism-sdk-manual/cubism-web-framework-changelog/)
- Cubism 4 SDK for Web R6 支持高精度蒙版。
- 服务端取消了回滚功能, 这玩意有点太没有用了.
- api.live2dweb.com/model/v2 不再提供模型服务, 请自建或使用创意工坊.

= 1.8-1.9版本Bug修复情况 =
- 拖拽异常问题感谢(qwqpap.xyz)[https://qwqpap.xyz] 协助测试。
- 后台JS冲突问题已修复，感谢(ovololi.com)[https://www.ovololi.com/] 协助测试。
- 还有一个切换问题，后续我会再试试，因为最近工作较忙，没有来得及做这个测试。感谢Dream N_About(QQ:25********19) 协助测试.
- 同样上一条, 在联想浏览器中会出现错误, 暂时也没有时间修复(其实是太麻烦了, 我还得再看看, 我担心时间会很长.)
- 还修复了十余个没有人报告的BUG, 通过对js的重构发现的.
- Tips有的时候它一闪一闪的, 看起来很奇怪, 已经修复了.

= 还未完成的部分： =
- 创意工坊预制Live2D官方MOC3模型, 未来您可以通过 https://www.live2dweb.com/ 上传自己的模型, 并向其他人出售或分享.

= 1.8.7 =
- 在ovololi站点中发现了一个问题. 在waifu-tips.ts文件中 第347行有一个错误 已经修复了
- 错误会导致看板娘无法显示。同样的错误也发生在flysheep6中。
- ChatGPT功能已恢复，暂时没有配额限制，后续将对站长进行配额限制，避免各位流量不对等。
- 各位可能有人已经看到插件官网增加了新的1.9.0功能，但是需要插件更新后才可以使用，目前还在自测中，等测试差不多了再给各位发。
- 另外，使用最新模型的站长和玩家，应该可以看到Live2D官网有一个安全公告，请勿使用篡改过了moc3模型，会导致出现安全漏洞，不过我发现好像没什么人再用MOC3模型。
- 本次更新插件价格将调整为49元CNY，早期购买者无需补款，并再次感谢各位的支持。

= 1.8.6 =
- 更新了一个说明文件

= 1.8.5 =
- 为不能登录的用户开启了一个新的功能;
- 对服务器进行了一些压力测试后发现有一部分内容不适合使用ChatGPT. 感谢[flysheep](https://www.flysheep6.com/) 提供压力测试;
- 修改了ChatGPT的上下文, 降低成本, 我被flysheep拉爆了, 2天用了80块钱;
- 拆分JS, live2d.min.js 日益臃肿, 本次更新后live2d.min.js将与其核心组件分离. 多线加载速度更快一些.
- 为新手制作了docker, 您如果自己不方便搭建API, 可以使用docker容器[live2d_api](https://hub.docker.com/r/jwf8732/live2d_api)

= 1.8.4 =
- 在PHP8中有一个函数错误, 已经修复了.
- 在保存的时候有个判断错误, 会强制你用我的 API 实在是抱歉.
- 切换功能目前处于可用状态, 我的模型库仍在升级中, CDN不稳定, 谨慎使用.
- 重构部分JS文件, 如果您使用的是CDN, 请在更新后务必更新CDN缓存
- 本次更新后JS将会改为 live2d.min.js waifu-tips.min.js waifu-admin.min.js 
- 新增JS moment.min.js, 摆脱了一部分jquery, 下个版本将完全不再依赖jquery, 避免应用顺序错误.
- 去掉了生成JSON文件的过程, 避免你的服务器权限不够

= 1.8.3 = 
- 根据付费用户的反馈，去掉了讨厌的保存验证。
- 去掉验证后不登录也可以正常保存了。
- 本次更新有一部分代码是由ChatGPT写的，我PHP苦手。
- ChatGPT需要从上下文聊天，请不要和他说过于简单的话，他不太懂，他说的代码和我服务器无关。
- 请尽量在PHP7.x环境中使用插件，目前8.x会有未知错误。
- 请仔细查看FAQ。

= 1.8.2 =
- 付费用户功能(测试): 实装ChatGPT, 目前处于试用状态, 我需要观察一段时间数据后才可以知道是否付费.
- 更新ICON, 改为使用fontawesome 6.4免费版, 您可以通过fontawesome 来更换图标.
- 更新后支持PHP8.2 , 修复之前在PHP8.x中会出现的错误, 感谢[七院(QQ:74*****10)] 协助测试。
- 修复切换的错误, 插件官网API切换功能目前有点问题, 可以继续使用以前的.
- 修复弹出Tips闪烁问题, 和控制台报错的异常.

= 1.8.1 =
- 本次更新是为了初学者，感谢[水以动为常(QQ:25*****45)] [λ^Maxwell(QQ:87*****93)] 提供的建议。
- 更新后直接启用插件就可以使用本插件。
- API更新：在使用PHP接口时可能会导致301目标永久移除的错误，感谢[い葒塵_儚斷(QQ:37*****45)] 对插件的配合测试。
- 感谢各位付费用户的提醒, 支付宝接口的问题还在修复中。
- 已知问题：LiteSpeed CDN缓存时，不要压缩live2d.js 文件，本文件已经压缩过了，再压缩会出错的。

= 1.8.0 =
- 新增登陆功能, 用户登录成功后可与官网 https://www.live2dweb.com/ 通讯。
- 与株式会社Live2D（ Live2D Inc. ）签订合约, 本软件是使用Cubism 4 SDK for Web核心制作的可扩展性应用程序, 本软件为正版授权。
- 所有使用moc3模型的demo 必须购买后使用, 如果私自修改live2d.js代码, 属于违法行为。
- https://www.live2dweb.com/ 中, 付费为支付宝。
- 付费后您可使用站点中的回滚功能, 如果您的设置出现错误, 回滚将会给您的站点恢复到上一次的备份中, 备份有6个历史版本。
- 对API进行优化, 由于调用fghrsh的API,可能会导致其服务器请求过多, 本软件将改为调用自己的API, API部分非开源。
- 关于退款: 您可以发送邮件至 support@live2dweb.com, 我将符合我国规定 7天无理由退款。
- 下一个版本将增加model3.json的整理功能, 您可以使用本插件官网中的整理, 将model3.json整理为网页可以用的格式。

= 1.7.9 =
- 首先，感谢各位的支持，由于Live2D官方授权问题，下次更新，本插件将会进行部分收费，价格不会很贵，希望各位谅解。
- 收费内容包含Cubism 4 SDK for Web相关功能。
- 不会对waifu-tips和原fghrsh.net以及Live2D.moc文件相关的内容收费。
- 本次更新新增看板娘位置调整功能，可以通过X，Y轴，对画板中的人物进行位置调整，配合放大功能，可以显示上半身 或者下半（?）身

= 1.7.8 =
- 新增IndexedDB缓存操纵功能，模型加载时会对加载的所有文件（包括MOC和MOC3类型模型）进行缓存，清除缓存功能将会在下个版本添加。目前想手动清除缓存请去Google了解更多内容。
- 引入此功能后，如果使用本插件人数多了之后，互相访问相同插件，将不会再请求网络。
- 本次升级后可以正确在6.0版本中使用。
- 脚本钩子降级。
- 更新后将兼容Cubism 4 SDK for Web R5

= 1.7.7 = 
- 新增小工具功能, 给看板娘关押起来吧!(这是一个测试功能, 可能会有很多问题, 后续继续完善)

= 1.7.6 =

- 将live2D 4.0 SDK进行动态加载, 避免禁止分发的SDK在页面中加载时间过长 感谢[baysonfox](https://github.com/baysonfox)
- 修改看板娘最小尺寸逻辑, 当小于指定的最小尺寸时直接退出后续步骤, 停止加载live2D插件渲染(仅在页面重新加载后有效) 感谢[ydecl](https://github.com/ydecl) [Project-458](https://github.com/Project-458) [DogeZen](https://github.com/DogeZen),此功能需要再下个版本继续调整
- 修复在WP 5.5后台设置报错的问题
- 修改停用一言选项的文案, 本来这个功能就是禁用功能的~ 感谢[ygdm123](https://github.com/ygdm123)
- 修复兼容问题 感谢[国木田葉羽](https://github.com/aquausora) [我爱喝北冰洋](https://www.bengalcat.cn/)

= 1.7.5 =

- 对moc3模型加载进行了优化，在model3.json中没有动作组命名的moc3模型，预加载时不再对动作文件进行加载，改为随用随取
- 插件已支持WordPress 5.5

= 1.7.4 =

- 新增后台设置：moc3模型自定义动作，提供给Cubism Editor 3.x版本的模型明确指定动作文件使用
- `lapplive2dmanager.ts`的`onTap`方法：增加判断自定义的`hitAreaList`，用户可在WP后台对`hitAreaList`进行设置
- 对缩放算法进行修正，由原有`lapplive2dmanager.ts`的`onUpdate`方法中计算`projection.scale(zoom, (zoom * width) / height);`改为使用`Framework/math/cubismmodelmatrix.ts`中的构造方法`this.setHeight(zoom);`进行控制，以确保缩放时触摸区域同比例缩放。
- 修改`lappmodel.ts`的`preLoadMotionGroup`方法，在model3.json中动作分组未命名的情况下将文件名作为动作名称。
- 在`lappmodel.ts`新增`startMotionFile`方法，用于直接读取`preLoadMotionGroup`方法中保存在内存中的动作。`startMotionFile`方法在`lapplive2dmanager.ts`的`onTap`被调用。
- 此版本为本地调整版本，下一个版本将直接修改API，从源头来保证生成的文件可被动作调用。
- 一言 API、看板娘截图文件名、关于页地址选项移动至工具栏设置TAB中。
- 新增基础设置帮助内容，请注意查看。

= 1.7.3 =

- 修复https://github.com/mashirozx/Sakura 的兼容性问题
- 本来兼容是正确的，被我改错了，实在抱歉...
- 修正了moc3中读取motions的报错，但是没有实际解决问题。
- 提醒各位使用者，为了您可以完全干净的删除我的插件，我会将保存在数据库中的设置一并全部删除。如果您做了很多的设置，请尽量不要进行删除操作。

= 1.7.2 =

- 修复加载顺序错误问题，感谢 [LemoFire][6] 和 [picpo][7] 
- 更新了禁止分发的引用，感谢 [railzy][8]

  [6]: https://www.ito.fun/
  [7]: http://picpo.top/ 
  [8]: https://github.com/railzy

= 1.7.1 =

本周更新提前啦
- 新增多语言支持，目前支持繁体中文和英文。
- Added multilingual version support
- Added Traditional Chinese of Taiwan
- Added English

= 1.7.0 =

1. 对moc3 鼠标事件进行算法修改，现在模型头部可正确的看鼠标行动了
2. 新增moc3截图功能，可以点击截图按钮拍下看板娘了
3. 去掉对moc3模型自动x2倍的缩放算法，改为用户自行调整
4. 追加了帮助信息，您可以通过后台查看帮助了解具体内容。

= 1.6.3 =

今后大部分更新针对live2d.js文件，请更新之后清理您的cdn加速，以便缓存新版本js文件

1. 对于Cubism Live2D SDK 4.0的鼠标事件进行算法修改
2. moc3模型的鼠标跟随视角更宽广
3. moc3模型背景透明
4. 您可以直接将后台api地址更换为model3.json的相对地址，以展示moc3的模型，这个地址可以是一个jsdelivr.com

= 1.6.2 =

1. 本次更新将会实装 Cubism Live2D SDK 4.0 以便测试版本
2. 由于打包JS文件变大，我会尽量在2.0上线之前进行拆分
3. 新增：模型缩放大小控制，您可以在后台自由设置模型在画布中的缩放倍数
4. 修正：默认模型 ID改为手动填写（我通过来访页面找到了各位的网站，发现我如果固定这个选项会给各位带来不便）
5. 如果有问题欢迎在Github上反馈[issues](https://github.com/jiangweifang/wp-live2d/issues)
6. 本次更新不会改变您当前的任何设置。
7. 请在使用之前清理之前安装的Live2D功能避免JS冲突

= 1.6.1 =

- 请注意：本次更新需要您重新设置所有数值，前端显示不正常时，请务必对数值进行默认值设置，感谢

1. 新增工具栏图标颜色和鼠标经过时的颜色控制
2. 放开看板娘提示框的尺寸控制
3. 修正设置文案准确性
4. 修正文本框与数字类型内容，强类型语言应该有的样子
5. type="range" 不是很好用，我觉得不够直观，只在一个功能上使用了
6. 减少了设置项：
- waifu-tips.js位置没有必要进行设置，有可能带来不必要的麻烦
- 主页地址设置，您已经在WordPress中设置过了，没有必要再设置一次，我将会自己读取他
7. 删除了一些没有什么用处的JS判断，精简waifu-tips.js的代码
8. 修正了一个Chrome浏览器中的警告
9. Live2D容器z轴样式提升至20，Tips的z轴提升至21，从视觉上可以看出消息提示显示在人物上方。

以下是默认值：
- 工具栏图标颜色：#5b6c7d
- 鼠标触碰时图标颜色：#34495e
- 工具栏图标大小(px)：14
- 工具栏行高(px)：12
- 工具栏顶部边距(px)：0
- 提示框大小：250x70
- 提示框字号(px)：14
- 看板娘大小：280x240
- 面页小于指定宽度(px)隐藏看板娘：760
- 看板娘贴边距离(px)：0

= 1.6.0 =

1. 增加提示框的颜色设置，可对提示框的底色，边框，阴影，进行rgba设置，可以对文字颜色进行rgb设置
2. 新增高亮显示方式，可在设置中修改高亮显示的颜色
3. 新增帮助菜单，对高级设置进行了一些说明
4. 修正了代码中冗余的一些内容
5. 更新请注意，更新完成后请重新设置提示框的颜色，否则提示框是透明的。

以下是默认值：
提示框背景色：rgba(236, 217, 188, 0.5)
边框颜色：rgba(224, 186, 140, 0.62)
阴影颜色：rgba(191, 158, 118, 0.2)
字体颜色：#32373c
高亮提醒颜色：#0099cc

= 1.5.2 =

修复保存文件的异常情况，并在无法保存文件时给出明确错误提示

= 1.5.1 =
1. 增加了设置的快捷按钮
2. 修正了设置页面保存按钮位置不对的问题

= 1.5.0 =
*支持高级设置
*去除了一个鼠标事件`.waifu #live2d`可以避免鼠标每次经过看板娘的时候他就混乱的说各种话。

= 1.3 =
*支持基础设置

= 1.0 =
*支持基础显示

== Description ==

支持moc和moc3模型的插件。

## 特性
- 与株式会社Live2D（ Live2D Inc. ）签订合约, 本软件是使用Cubism SDK for Web核心制作的可扩展性应用程序, 本软件为正版授权。
- 使用 Cubism 4+ 生成的模型必须购买此插件才可以使用。
- 插件可以通过后台直接对Live2D进行设置，无需复杂的修改代码。
- 可视化设置并生成waifu-tips.json，避免手动修改JSON


## 版权声明

[live2d_src / ©journey-ad / GPL v2.0][2]  

  [2]: https://github.com/journey-ad/live2d_src "基于 #fea64e4 的修改版"
  
- 请遵循GPL v3.0授权协议
- Live2DWebCanvas 插件不属于 Live2D Inc. 它是一个非官方产品

## 软件许可协议

[Live2D Proprietary Software License Agreement][4]  
[Live2D Open Software License Agreement][5]  

  [4]: https://www.live2d.com/eula/live2d-proprietary-software-license-agreement_en.html
  [5]: https://www.live2d.com/eula/live2d-open-software-license-agreement_en.html

== Installation ==

1. Upload the plugin folder to the "/wp-content/plugins/" directory of your WordPress site
2. Activate the plugin through the 'Plugins' menu in WordPress
3. See Settings -> Live 2D 设置


### 我该怎么做
- 查看帮助：[https://github.com/jiangweifang/wp-live2d/wiki](https://github.com/jiangweifang/wp-live2d/wiki)
- 了解SDK版本区别：[https://docs.live2d.com/zh-CHS/cubism-sdk-manual/cubism-5-new-functions/](https://docs.live2d.com/zh-CHS/cubism-sdk-manual/cubism-5-new-functions/)
1. 请在理解相对路径等基础知识之后与我探讨如何指向moc3模型，作为一个Wordpress用户，请不要做伸手党。
2. 如果上传Moc3模型不知道传到哪里，可以在补丁文件夹中创建一个文件夹进行指向。
3. 1.8版本的插件请付费后使用, 如果中间遇到问题, 随时和我联系QQ：85838607, 我很愿意帮你解决。


### 设置参数
*Tips：保存设置后仅进行了部分设置，以下是作者原文*

- 后端接口
  - `live2d_settings['modelAPI']`<br>看板娘 API 地址，默认值 `'//live2d.fghrsh.net/api/'`
  - `live2d_settings['hitokotoAPI']`<br>一言 API 接口，可选 `'lwl12.com'`，`'hitokoto.cn'`，`'jinrishici.com'` (古诗词)
- 默认模型
  - `live2d_settings['modelId']`<br>默认模型(分组) ID，可在 Demo 页 `[F12]` 呼出 `控制台(Console)` 找到
  - `live2d_settings['modelTexturesId']`<br>默认材质(模型) ID，可在 Demo 页 `[F12]` 呼出 `控制台(Console)` 找到
- 工具栏设置
  - `live2d_settings['showToolMenu']`，      显示工具栏，     `true` | `false`
  - `live2d_settings['canCloseLive2d']`，    关闭看板娘 按钮，`true` | `false`
  - `live2d_settings['canSwitchModel']`，    切换模型 按钮，  `true` | `false`
  - `live2d_settings['canSwitchTextures']`， 切换材质 按钮，  `true` | `false`
  - `live2d_settings['canSwitchHitokoto']`， 切换一言 按钮，  `true` | `false`
  - `live2d_settings['canTakeScreenshot']`， 看板娘截图 按钮，`true` | `false`
  - `live2d_settings['canTurnToHomePage']`， 返回首页 按钮，  `true` | `false`
  - `live2d_settings['canTurnToAboutPage']`，跳转关于页 按钮，`true` | `false`
- 模型切换模式
  - `live2d_settings['modelStorage']`，记录 ID (刷新后恢复)，`true` | `false`
  - `live2d_settings['modelRandMode']`，模型切换，可选 `'rand'` (随机) | `'switch'` (顺序)
  - `live2d_settings['modelTexturesRandMode']`，材质切换，可选 `'rand'` | `'switch'`
- 提示消息选项
  - `live2d_settings['showHitokoto']`，空闲时一言，`true` | `false`
  - `live2d_settings['showF12Status']`，控制台显示加载状态，`true` | `false`
  - `live2d_settings['showF12Message']`，提示消息输出到控制台，`true` | `false`
  - `live2d_settings['showF12OpenMsg']`，控制台被打开触发提醒，`true` | `false`
  - `live2d_settings['showCopyMessage']`，内容被复制触发提醒，`true` | `false`
  - `live2d_settings['showWelcomeMessage']`，进入面页时显示欢迎语，`true` | `false`
- 看板娘样式设置
  - `live2d_settings['waifuSize']`，看板娘大小，例如 `'280x250'`，`'600x535'`
  - `live2d_settings['waifuTipsSize']`，提示框大小，例如 `'250x70'`，`'570x150'`
  - `live2d_settings['waifuFontSize']`，提示框字体，例如 `'12px'`，`'30px'`
  - `live2d_settings['waifuToolFont']`，工具栏字体，例如 `'14px'`，`'36px'`
  - `live2d_settings['waifuToolLine']`，工具栏行高，例如 `'20px'`，`'36px'`
  - `live2d_settings['waifuToolTop']`，工具栏顶部边距，例如 `'0px'`，`'-60px'`
  - `live2d_settings['waifuMinWidth']`<br>面页小于 指定宽度 隐藏看板娘，例如 `'disable'` (停用)，`'768px'`
  - `live2d_settings['waifuEdgeSide']`<br>看板娘贴边方向，例如 `'left:0'` (靠左 0px)，`'right:30'` (靠右 30px)
  - `live2d_settings['waifuDraggable']`<br>拖拽样式，可选 `'disable'` (禁用)，`'axis-x'` (只能水平拖拽)，`'unlimited'` (自由拖拽)
  - `live2d_settings['waifuDraggableRevert']`，松开鼠标还原拖拽位置，`true` | `false`
- 其他杂项设置
  - `live2d_settings['l2dVersion']`，当前版本 (无需修改)
  - `live2d_settings['l2dVerDate']`，更新日期 (无需修改)
  - `live2d_settings['homePageUrl']`，首页地址，可选 `'auto'` (自动)，`'{URL 网址}'`
  - `live2d_settings['aboutPageUrl']`，关于页地址，`'{URL 网址}'`
  - `live2d_settings['screenshotCaptureName']`，看板娘截图文件名，例如 `'live2d.png'`
### 定制提示语
*Tips： `waifu-tips.json` 已自带默认提示语，如无特殊要求可跳过*
- `"waifu"` 系统提示
  - `"console_open_msg"` 控制台被打开提醒（支持多句随机）
  - `"copy_message"` 内容被复制触发提醒（支持多句随机）
  - `"screenshot_message"` 看板娘截图提示语（支持多句随机）
  - `"hidden_message"` 看板娘隐藏提示语（支持多句随机）
  - `"load_rand_textures"` 随机材质提示语（暂不支持多句）
  - `"hour_tips"` 时间段欢迎语（支持多句随机）
  - `"referrer_message"` 请求来源欢迎语（不支持多句）
  - `"referrer_hostname"` 请求来源自定义名称（根据 host，支持多句随机）
  - `"model_message"` 模型切换欢迎语（根据模型 ID，支持多句随机）
  - `"hitokoto_api_message"`，一言 API 输出模板（不支持多句随机）
- `"mouseover"` 鼠标触发提示（根据 CSS 选择器，支持多句随机）
- `"click"` 鼠标点击触发提示（根据 CSS 选择器，支持多句随机）
- `"seasons"` 节日提示（日期段，支持多句随机）

== Frequently Asked Questions ==

1. 1.8版本已更新完成，下面讲解如何使用moc3（最高支持到live2D 4.0）模型
2. 将你准备好的moc3模型上传至目录当中，如果您准备存放github中，可以直接存储至Public项目。
3. 然后请了解存放路径，并给材质API选项中填写此路径至*.model3.json。例如：https://cdn.jsdelivr.net/gh/jiangweifang/live2d_api@live2d_api_v4/model/kiritan/kiritan.model3.json
4. 刷新页面后，您将会看到moc3模型展示至前台。
5. moc3的缩放比例可能会比较小，所以您需要将模型缩放比例调整至合适大小，建议2.0，我没有进行控制是因为如果我在代码中写入2.0，其计算方式将会x2 ，例如1.1 实际计算结果将会是2.2。
6. 请务必保证您的Wordpress REST API (rest_route) 可以被正常访问，如果你在保存时出现异常，请检查您的站点是否将Wordpress REST API设置白名单，如果是Cloudflare用户，很有可能出现这种情况。
7. 使用CDN加速的朋友请注意，更新插件后记得刷新CDN加速缓存
8. live2d.js 无需JS压缩，如果压缩后出现错误，请自行查看是否已排除。
9. 内网用户，请使用公网IP访问您的网站，并配置好域名，可以是花生壳免费域名。本地测试，插件无法保存。
10. 您可以在[nizima](https://nizima.com/) 中购买到正版MOC3模型。
11. 您可以在官方 [https://www.live2d.jp/](https://www.live2d.jp/showcase/live2dwebcanvas/) 中看到我的插件介绍。
12. 更新日志将会于[https://weibo.com/nagatosaki](https://weibo.com/nagatosaki) 或[bilibili](https://space.bilibili.com/70444) 更新。
13. 付费后如果没有反应请及时联系我的QQ：85838607， 我会尽快给你解决

== Screenshots ==
None

