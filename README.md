# Live2D Web Canvas - Live2D WordPress 插件

- 基于Live2D 看板娘前端 HTML 源码改写
- 插件可在WordPress后台通过插件搜索获得 https://wordpress.org/plugins/live-2d/ 记得给个好评！

![1](https://user-images.githubusercontent.com/38683169/216086597-6acf9f5e-f694-4c14-85cc-08cbbdfbfd4c.png)

### 更新
= 1.8.2 =
- 更新后支持PHP8.2 , 修复之前在PHP8.x中会出现的错误, 感谢[七院(QQ:74*****10)] 协助测试。
- 修复切换的错误。

= 1.8.1 =
- 本次更新是为了初学者，感谢[水以动为常(QQ:25***45)] [λ^Maxwell(QQ:87***93)] 提供的建议。
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
