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

## 调查产出物时的优先级（重要）

- **不要直接读 `assets/*.min.js`**（混淆 + 体积大，浪费 token，且常常误导）。需要理解某个产物的行为时，先回到 `live2d_sdk/src/` 看源码，按 `vite.config.ts` 的 `input` 表反查入口：
  - `live2d_sdk/src/v1/main.js` → `assets/live2dv1.min.js`
  - `live2d_sdk/src/v2/main.js` → `assets/live2dv2.min.js`
  - `live2d_sdk/src/Wordpress/waifu-tips.js` → `assets/live2dwebsdk.min.js`
  - `live2d_sdk/src/Wordpress/waifu-admin.js` → `assets/waifu-admin.min.js`（入口里 `import { Live2dAdmin } from './live2d-admin'`，**真实逻辑在 `live2d-admin.ts`**）
  - `live2d_sdk/src/Wordpress/waifu.scss` → `assets/waifu.css`
  - 公共依赖被抽到 `assets/chunks/`
- 产出物是 **ES module**（`vite.config.ts` 的 `output.format: 'es'`，源码里有 `import`/`export`）。WordPress 端 `wp_enqueue_script` 必须配合 `script_loader_tag` 把 `<script>` 改成 `type="module"`，否则浏览器抛 `SyntaxError`，进而导致依赖该脚本的功能（如设置页 tab 切换）退化为锚点跳转。
  - 仓库内已提供 `live2d_mark_script_as_module($handle)`（`wordpress-live2d.php`），任何走 `wp_enqueue_script` 加载的 vite 产物都要调一次。
- 只有在确认 PHP / 浏览器实际加载行为时，才需要瞥一眼 `assets/*.min.js`，且只读首行 `import` 头部，不读混淆体。

## WP 端 PHP 入口（仓库根）

- `wordpress-live2d.php`（插件主文件）
- `src/live2d-Main.php`、`src/live2d-Widget.php`、`src/live2d-SDK.php`、`src/waifu-Settings*.php`、`src/jwt/*`
- 这些 PHP 引用 `assets/` 下由 `vite.config.ts` 产出的 JS/CSS。

## live2d_sdk/package.json 关键脚本

- `build` → `vite build`（WP 主构建）
- `build:core` → `node scripts/build-package.mjs`（per-site 包）
- `build:extension*` → Chromium 扩展（不属本工程）

