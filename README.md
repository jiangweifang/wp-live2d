# Live2D Web Canvas - WordPress 插件

- 基于Live2D 看板娘前端 HTML 源码改写
- 插件可在WordPress后台通过插件搜索获得 https://wordpress.org/plugins/live-2d/ 记得给个好评！

[![Static Badge](https://img.shields.io/badge/Live2D-v5.r1.bate.1-blue?color=%23ff6e2d)](https://www.live2d.com/download/cubism-sdk/download-web/)

![1](https://user-images.githubusercontent.com/38683169/216086597-6acf9f5e-f694-4c14-85cc-08cbbdfbfd4c.png)

### 更新
== Changelog ==

= 1.9.9 =
- framework 升级到 Cubism 5 SDK for Web R1 beta1
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
- 切换功能目前处于可用状态, 但是我的接口返回的JSON格式有问题, 会导致模型黑掉. 谨慎使用.
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
- 更新后支持PHP8.2 , 修复之前在PHP8.x中会出现的错误, 感谢[七院(QQ:74******10)] 协助测试。
- 修复切换的错误, 插件官网API切换功能目前有点问题, 可以继续使用以前的.
- 修复弹出Tips闪烁问题, 和控制台报错的异常.

= 1.8.1 =
- 本次更新是为了初学者，感谢[水以动为常(QQ:25*******45)] [λ^Maxwell(QQ:87*******93)] 提供的建议。
- 更新后直接启用插件就可以使用本插件。
- 已知问题：LiteSpeed CDN缓存时，不要压缩live2d.js 文件，本文件已经压缩过了，再压缩会出错的。

= 1.8.0 =
- 与株式会社Live2D（ Live2D Inc. ）签订合约, 本软件是使用Cubism 4 SDK for Web核心制作的可扩展性应用程序. 购买本软件为正版授权。
- 新增登陆功能, 用户登录成功后可与官网 https://www.live2dweb.com/ 通讯。
- 所有使用moc3模型的demo 必须购买后使用, 如果私自修改live2d.js代码, 属于违法行为, 本软件核心为live2d.js, 此代码非开源。
- https://www.live2dweb.com/ 中, 付费为支付宝接口。
- 付费后您可使用站点中的回滚功能, 如果您的设置出现错误, 回滚将会给您的站点恢复到上一次的备份中, 备份有6个历史版本。
- 下一个版本将增加model3.json的整理功能, 您可以使用本插件官网中的整理, 将model3.json整理为网页可以用的格式。
- 对API进行优化, 由于调用fghrsh的API,可能会导致其服务器请求过多, 本软件将改为调用自己的API, API部分非开源。

= 1.7.8 =
- 更新SDK到 Cubism 4 SDK for Web R5
- 增加IndexedDB缓存支持和Unity一样，打开模型时会将文件缓存到本地，这样如果是不直接清空缓存的话是不会有任何更新的！
- 当然上一条这个内容的KEY是分开的，他和模型名称有关系，如果出错记得给我提Issues

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

= 1.7.3 =
- 修复https://github.com/mashirozx/Sakura 的兼容性问题
- 本来兼容是正确的，被我改错了，实在抱歉...
- 修正了moc3中读取motions的报错，但是没有实际解决问题。
- 提醒各位使用者，为了您可以完全干净的删除我的插件，我会将保存在数据库中的设置一并全部删除。如果您做了很多的设置，请尽量不要进行删除操作。
- 对moc3我无法处理的部分做了屏蔽，不是try catch的屏蔽，不用担心性能问题。

= 1.7.2 =
- 修复加载顺序错误问题，感谢 [LemoFire][6] 和 [picpo][7] 
- 更新了禁止分发的引用，感谢 [railzy][8]

  [6]: https://www.ito.fun/
  [7]: http://picpo.top/ 
  [8]: https://github.com/railzy

以下是默认值：
- 提示框背景色：rgba(236, 217, 188, 0.5)
- 边框颜色：rgba(224, 186, 140, 0.62)
- 阴影颜色：rgba(191, 158, 118, 0.2)
- 字体颜色：#32373c
- 高亮提醒颜色：#0099cc

### 特性

- 基于 API 加载模型，支持 定制 提示语
- 增加：可通过WordPress后台进行参数设置，易用性++
- 增加：可后台设置看板娘样式，可直接设置宽高度等
- 支持多种一言接口，基于 JQuery UI 实现拖拽，JQuery UI引用WordPress内置，无需担心加载延迟
- 增加：可视化设置并生成waifu-tips.json，避免手动修改JSON

### FAQ

- 1.7版本已更新完成，下面讲解如何使用moc3（最高支持到live2D 4.0）模型
1. 将你准备好的moc3模型上传至目录当中，如果您准备存放github中，可以直接存储至Public项目。
2. 然后请了解存放路径，并给材质API选项中填写此路径至*.model3.json。例如：https://cdn.jsdelivr.net/gh/jiangweifang/live2d_api@live2d_api_v4/model/kiritan/kiritan.model3.json
3. 刷新页面后，您将会看到moc3模型展示至前台。
4. moc3的缩放比例可能会比较小，所以您需要将模型缩放比例调整至合适大小，建议2.0，我没有进行控制是因为如果我在代码中写入2.0，其计算方式将会x2 ，例如1.1 实际计算结果将会是2.2。

- 为了不打扰您的使用，除严重bug外，版本更新每周进行一次，感谢各位的支持。

- 1.8版本将进行繁体中文版本开发。从而便于多语言支持

### 食用方法

1. 在WordPress后台添加插件压缩包安装
2. 点击启用按钮开始使用看板娘。


## 版权声明

- [Flat UI Free][1]  
- [live2d_src / ©journey-ad / GPL v2.0][2]  
- [fghrsh.net][3]  

  [1]: https://designmodo.com/flat-free/ "Flat UI Free"
  [2]: https://github.com/journey-ad/live2d_src "基于 #fea64e4 的修改版"
  [3]: https://www.fghrsh.net/post/123.html "fghrsh.net"
  
- WordPress Live 2D 插件不属于 Live2D Inc. 它是一个非官方产品

## 软件许可协议

- [Live2D Proprietary Software License Agreement][4]  
- [Live2D Open Software License Agreement][5]  

  [4]: https://www.live2d.com/eula/live2d-proprietary-software-license-agreement_en.html
  [5]: https://www.live2d.com/eula/live2d-open-software-license-agreement_en.html
