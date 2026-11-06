=== Live2DWebCanvas ===

Contributors: jiangweifang
Donate link: https://github.com/jiangweifang/wp-live2d
Tags: live2d, vtuber, anime, animation, chatgpt
Tested up to: 7.1
Requires at least: 5.5
Stable tag: 2.3.0
Requires PHP: 7.4
License: MIT
License URI: https://opensource.org/licenses/MIT

Display an interactive Live2D character on your WordPress site, with model settings, speech bubbles, a toolbar, and optional chat services.

== Description ==

Live2DWebCanvas adds an interactive character to your site. Configure its model, size, position, toolbar, and messages from the WordPress administration screen.

Features include:

* Classic Live2D models without requiring the Pro add-on.
* Page and widget layouts.
* Configurable messages and classic model textures.
* Administrator-managed classic model downloads.
* Persistent model storage and shared file validation.
* Optional external quote and chat services.
* Optional WordPress AI Client integration with administrator-selected providers and models. This backend is callable only when explicitly enabled and requires a logged-in WordPress user.

[Documentation](https://jiangweifang.github.io/wp-live2d/) and [source code](https://github.com/jiangweifang/wp-live2d) are available online. Chinese instructions are included in README.md and readme.zh-CN.txt. This directory readme uses English as required by WordPress.org.

Classic rendering, layout, toolbar, messages and shared features remain in Free. Models requiring Cubism Core, modern model hosting and exclusive rendering controls are supplied by the separate Pro add-on, which requires Free. [Get Pro from the official website](https://www.live2dweb.com/Downloads/WordPressPro). Existing customers can use their original account to check their entitlement; the split does not require buying the same entitlement again.

Free does not bundle or automatically download Pro code. Its settings page links to the standard WordPress upload page, where an administrator can upload and activate the Pro ZIP. Pro features appear on the existing settings page after compatibility and account entitlement are verified; Free files are not overwritten.

Optional external services may require an account and separate service entitlement. Without a compatible Pro add-on, a previously configured modern model is paused, not replaced with a different character.

== Installation ==

1. Upload the live-2d directory to wp-content/plugins, or install the plugin through WordPress.
2. Activate Live2DWebCanvas.
3. Open Settings > Live 2D and configure a model.
4. Download classic models through the administrator workshop or configure a classic model URL.

Before upgrading an old combined plugin, back up its database and model directory. Do not deactivate or delete the old plugin first. Eligible Pro users can install the Pro migration bridge while the old plugin remains active, then copy and verify models into uploads/live2d-models before replacing Free. Other users must arrange an administrator-verified copy and backup before upgrading. A post-upgrade migration cannot recover files already deleted by the updater. Multisite legacy storage requires a network administrator's backup and per-site migration.

PHP cURL with TLS support is required for Pro server-side remote model retrieval. These requests are pinned to validated public IPv4 destinations. HTTP proxies are not supported for that path; upload the model locally instead. Locally hosted models do not require requests to an external model host. ZIP model installation uses the WordPress filesystem API, with ZipArchive where available.

== Frequently Asked Questions ==

= Can visitors download models onto the server? =

No. Downloads require administrator authorization. Pro viewing sessions are limited to configured models and cannot enqueue downloads. Public page nonces and one-time session tokens are not administrator credentials.

= Which filenames are supported? =

Model paths use letters, numbers, underscores, hyphens, and dots. Cubism compound filenames such as Haru.model3.json and idle.motion3.json are supported. Executable files, hidden path components, traversal, and symbolic links are rejected. File contents must match the supported model, JSON, texture, audio, or text format.

= What should I do after upgrading from 2.2.1 or earlier? =

Refresh pages to obtain new viewing sessions. Re-enqueue any old model download jobs from the administration screen. If the affected mode was enabled, inspect existing model directories and other site files for compromise. Updating the plugin does not remove previously planted files.

== External Services ==

The following services may be contacted according to the features and models you configure:

* Live2DWeb (www.live2dweb.com and api.live2dweb.com): account login, license verification, model services, and optional chat. Requests can include the site URL, account key or signed token, and data required by the selected feature. See the [service website](https://www.live2dweb.com/) for its current terms and privacy information.
* download.live2dweb.com: classic model archive downloads initiated by an administrator. The service receives the requested model identifier and authentication information.
* Configured model hosts: browsers or the WordPress server request the manifest and resources at the administrator-selected model URL. The host receives normal HTTP connection information. Pro server-side model proxy requests reject non-public destinations.
* Optional quote providers, including hitokoto.cn, jinrishici.com, and fghrsh.net: the browser requests quotations when that provider is selected. Review the selected provider's terms and privacy information before enabling it.
* Optional chat services: when chat is enabled, prompts and the configured conversation context are sent to the selected service. Do not send sensitive information without the necessary consent.
* WordPress AI providers: when the built-in backend is selected, this plugin passes the user's message and the saved system instruction to the site's configured WordPress AI provider. The provider handles credentials and its own external service requests. This path does not use the Live2DWeb chat backend.

== Source and Build Information ==

The WordPress PHP source is included in this plugin. JavaScript and CSS build sources are in the ordinary live2d_sdk directory of the [project repository](https://github.com/jiangweifang/wp-live2d), not a submodule. Install its locked dependencies and run npm run build from that directory. The private Pro SDK is not required to build Free. WordPress supplies jQuery and Moment.js.

== Third-Party Licenses ==

The plugin's own code is distributed under the MIT license. Third-party components retain their own licenses.

The modern Cubism Core runtime is not included in Free. Older Live2D runtime/framework and model-resource licenses must be reviewed separately; the product being free of charge does not establish GPL compatibility. This unpublished split is not a claim of WordPress.org license approval. Preserve upstream notices and obtain any required permissions.

This plugin is not an official product of Live2D Inc.

== Changelog ==

= 2.3.0 =
* Split classic/shared Free functionality from the separately distributed Cubism Core Pro add-on.
* Preserve model settings and account state when Pro is unavailable or the plugin is deactivated.
* Add verified persistent-storage migration and prominent edition, acquisition and upload information.
* Security: restrict viewing sessions to configured models and stop trusting client-supplied manifests.
* Security: remove visitor-triggered downloads; require administrator-authorized download jobs.
* Security: validate paths, extensions, and contents before saving model files.
* Security: validate redirect destinations and pin remote connections to public addresses.
* Security: harden administrator output and remove credential-bearing diagnostic logs.
* Security: require administrator authorization for account-binding callbacks; use the existing Key settings form to log in.
* Security: require HTTPS for classic model downloads and validate archive members before either extraction backend writes files.
* Security: require explicit opt-in before using the site's WordPress AI provider.
* Security: sanitize message HTML and reject unsafe toolbar navigation URLs.
* Change: keep classic/shared features independent of Pro; modern-only implementation is not bundled.
* Compatibility: preserve Pro administrator uploads, public viewing sessions, signed resources, and local model caching.
* Maintenance: use WordPress-provided Moment.js and update plugin-directory metadata.

= 2.2.1 =
* Fix classic multi-skin model loading and shared resources.
* Improve model lifecycle cleanup and IndexedDB asset caching.
* Update Cubism shader delivery.

= 2.2.0 =
* Add self-hosted custom model protection and update optional chat integration.

For earlier changes, see the [repository history](https://github.com/jiangweifang/wp-live2d).

== Upgrade Notice ==

= 2.3.0 =
Free/Pro split and security fixes for CVE-2026-92895. Back up settings and migrate old plugin-directory models before replacing the old combined plugin. Modern models require the separate Pro add-on. Inspect previously affected sites for compromise.
