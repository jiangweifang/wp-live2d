# Copilot Instructions — wp-live2d

## 工程范围 / Project Scope

- **本工程（wp-live2d）的主要产出物是 WordPress 插件。** 仓库根目录就是插件目录：`wordpress-live2d.php`、`src/*.php`、`assets/*`、`languages/*`、`readme.txt` 等。
- 与 WordPress 产出相关的构建配置位于 `live2d_sdk/`：
  - `live2d_sdk/vite.config.ts` —— WP 主插件多入口构建，输出到 `live2d_sdk/dist/`，最终落到仓库根 `assets/`（`live2dv1.min.js`、`live2dv2.min.js`、`live2dwebsdk.min.js`、`waifu-admin.min.js`、`waifu.css` 等）。入口集中在 `live2d_sdk/src/v1/`、`live2d_sdk/src/v2/`、`live2d_sdk/src/Wordpress/`。
  - `live2d_sdk/vite.config.package.ts` + `live2d_sdk/scripts/build-package.mjs` —— per-site 独立站点包，由 wp-live2d-api 的 `SitesController.Package` 通过 `npm run build:core -- --env OUT_DIR=... --env API_URL=...` 调用；入口仅 `live2d_sdk/src/v2/only-core.js`，构建期把 `process.env.API_URL` 内联，运行时校验 origin。
- **`live2d_sdk/src/Chromium/` 与 `live2d_sdk/vite.config.extension.ts` 是 Chrome/Edge MV3 浏览器扩展的产出物，不属于本工程的主线关注范围**，仅与 WP 插件共用同一份 `live2d_sdk` 仓库与 v1/v2 SDK 代码。
- 讨论"产出物 / 构建 / 发布"时默认指 **WordPress 插件侧**；涉及浏览器扩展请显式说明。

## SDK 子目录补充说明

- `live2d_sdk/src/v2/Framework/` 是 Live2D Cubism SDK 官方 Framework 源码，**不要修改**；需要修复请在 Framework 目录之外（如 `live2d_sdk/src/v2/lappdelegate.ts`）绕过。
- `moment` 在所有 vite 配置中均被声明为 external，由宿主页面以 `<script>` 全局加载，不打进产物。
