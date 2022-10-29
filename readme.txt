=== Live 2D ===

Contributors: Weifang Chiang
Donate link: https://blog.csdn.net/jiangweifang
Tags: Live2D,看板娘,萌,moe,vtuber,comic,anime,live,2d,animation
Tested up to: 6.1
Requires at least: 5.5
Stable tag: 1.7.9
License: MIT

== Changelog ==

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

- 基于Live2D 看板娘插件 (https://www.fghrsh.net/post/123.html) 的前端 HTML 源码改写
- 基于 API 加载模型，支持 定制 提示语
- 增加：可通过WordPress后台进行参数设置，易用性++
- 增加：可后台设置看板娘样式，可直接设置宽高度等
- 支持多种一言接口，基于 JQuery UI 实现拖拽，JQuery UI引用WordPress内置，无需担心加载延迟
- 增加：可视化设置并生成waifu-tips.json，避免手动修改JSON

## 版权声明

[Flat UI Free][1]  
[live2d_src / ©journey-ad / GPL v2.0][2]  
[fghrsh.net][3]  

  [1]: https://designmodo.com/flat-free/ "Flat UI Free"
  [2]: https://github.com/journey-ad/live2d_src "基于 #fea64e4 的修改版"
  [3]: https://www.fghrsh.net/post/123.html "fghrsh.net"
  
- 请遵循GPL v3.0授权协议
- WordPress Live 2D 插件不属于 Live2D Inc. 它是一个非官方产品
- 我的QQ：八五八三八六零七，欢迎加我反馈信息。

## 软件许可协议

[Live2D Proprietary Software License Agreement][4]  
[Live2D Open Software License Agreement][5]  

  [4]: https://www.live2d.com/eula/live2d-proprietary-software-license-agreement_en.html
  [5]: https://www.live2d.com/eula/live2d-open-software-license-agreement_en.html

== Installation ==

1. Upload the plugin folder to the "/wp-content/plugins/" directory of your WordPress site
2. Activate the plugin through the 'Plugins' menu in WordPress
3. See Settings -> Live 2D 设置


### 食用方法

1. 在WordPress后台添加插件压缩包安装
2. 点击启用按钮开始使用看板娘。


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

- 1.7版本已更新完成，下面讲解如何使用moc3（最高支持到live2D 4.0）模型
1. 将你准备好的moc3模型上传至目录当中，如果您准备存放github中，可以直接存储至Public项目。
2. 然后请了解存放路径，并给材质API选项中填写此路径至*.model3.json。例如：https://cdn.jsdelivr.net/gh/jiangweifang/live2d_api@live2d_api_v4/model/kiritan/kiritan.model3.json
3. 刷新页面后，您将会看到moc3模型展示至前台。
4. moc3的缩放比例可能会比较小，所以您需要将模型缩放比例调整至合适大小，建议2.0，我没有进行控制是因为如果我在代码中写入2.0，其计算方式将会x2 ，例如1.1 实际计算结果将会是2.2。

== Screenshots ==
None

