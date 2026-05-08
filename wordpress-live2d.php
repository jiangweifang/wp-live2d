<?php
/*
 * Plugin Name: Live 2D
 * Plugin URI: https://www.live2dweb.com/
 * Description: 看板娘插件
 * Version: 2.2.0
 * Requires PHP: 7.4
 * Author: Weifang Chiang
 * Author URI: https://github.com/jiangweifang/wp-live2d
 * Text Domain: live-2d
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // 阻止直接访问
}

//定义目录
define('LIVE2D_ASSETS', plugin_dir_url(__FILE__) . 'assets/'); //资源目录
define('LIVE2D_LANGUAGES', basename(dirname(__FILE__)) . '/languages'); //基础目录
define('LIVE2D_VERSION', '2.2.0'); //资源版本号, 用于缓存破坏

/**
 * 把 wp_enqueue_script 注册的脚本标记为 ES module。
 * 通过 wp_script_add_data + script_loader_tag filter,在 <script> 标签上输出 type="module"。
 * 用于 vite 产物(包含 import/export 语法)在 WordPress 中正确加载。
 */
function live2d_mark_script_as_module($handle)
{
    global $wp_scripts;
    if (!isset($wp_scripts) || !is_a($wp_scripts, 'WP_Scripts')) {
        // 还未初始化时延迟到 wp_default_scripts 之后挂
        add_action('wp_default_scripts', function () use ($handle) {
            live2d_mark_script_as_module($handle);
        }, 99);
        return;
    }
    wp_script_add_data($handle, 'live2d_module', true);
}

add_filter('script_loader_tag', 'live2d_module_script_tag', 10, 3);
function live2d_module_script_tag($tag, $handle, $src)
{
    global $wp_scripts;
    if (!isset($wp_scripts->registered[$handle])) {
        return $tag;
    }
    $is_module = $wp_scripts->get_data($handle, 'live2d_module');
    if (!$is_module) {
        return $tag;
    }
    // 普通 <script src="..."></script> -> <script type="module" src="..."></script>
    // 避免对 inline <script>...code...</script> 误判:必须含有 src 属性
    if (strpos($tag, ' src=') === false) {
        return $tag;
    }
    if (strpos($tag, ' type=') !== false) {
        $tag = preg_replace('#\stype=([\"\']).*?\1#', ' type="module"', $tag);
    } else {
        $tag = str_replace('<script ', '<script type="module" ', $tag);
    }
    return $tag;
}

/**
 * 输出 ES module importmap, 把裸说明符 "moment" 解析到一个从 window.moment
 * 取值的 data: shim. vite 配置把 moment 标成 external 并以 ES 格式输出, 产物里
 * 保留了 `import x from "moment"`; 浏览器加载 module 时必须按规范解析裸说明符,
 * 没有 importmap 会抛 "Failed to resolve module specifier 'moment'".
 *
 * 必须在任何 <script type="module"> 之前出现, 因此挂在 wp_head 优先级 0,
 * 早于 live2D_style(priority 1) 入队的脚本(实际由 wp_print_head_scripts
 * 在优先级 9 渲染).
 */
add_action('wp_head', 'live2d_print_module_importmap', 0);
function live2d_print_module_importmap()
{
    echo '<script type="importmap">{"imports":{"moment":"data:text/javascript,export default window.moment;"}}</script>' . "\n";
}

// 加载设置组件
include_once(dirname(__FILE__)  . '/src/live2d-Main.php');
// 加载小工具
include_once(dirname(__FILE__)  . '/src/live2d-Widget.php');
// 加载登录确认API
include_once(dirname(__FILE__)  . '/src/live2d-SDK.php');
// 加载本地 V1 模型 API(取代 https://api.live2dweb.com/model/v2 的清单/切换/换装服务)
include_once(dirname(__FILE__)  . '/src/live2d-V1Api.php');
// 加载 V2 模型(model3.json)防盗链 API(对齐 nizima.LIVE 与 wp-live2d-api 的 /Model/Session)
include_once(dirname(__FILE__)  . '/src/live2d-V2Api.php');

//添加样式（初始化）
function live2D_style()
{
    $live2dSettings = get_option('live_2d_settings_option_name');
    $live2dUserInfo = get_option('live_2d_settings_user_token');
    // apiType=local(本地部署旧版模型): 不再走 https://api.live2dweb.com/model/v2,
    // 把 modelAPI 重写到本插件提供的本地 V1 接口(详见 src/live2d-V1Api.php)。
    // 该 URL 不带 .json 结尾、不命中 LIVE2DWEB_API,会被 classifyModelApi 判为
    // ApiUrlType.Other,前端复用旧 PHP 后端的 /get/?id=X-Y 等路径。
    // 'remote' / 'custom' 模式下,用户配置的 modelAPI 不被覆盖。
    //
    // 持久化逻辑在 live2d_Settings::live_2d_settings_sanitize 里;此处的运行时
    // 覆盖只是兜底:覆盖 site URL 改了之后 DB 里旧值过期、或老用户从远端恢复
    // 配置后还来不及保存设置就刷新了页面这两种情况。
    $isLocal = is_array($live2dSettings)
        && function_exists('live2d_api_type_is_local')
        && live2d_api_type_is_local(isset($live2dSettings['apiType']) ? $live2dSettings['apiType'] : null);
    if ($isLocal && function_exists('live2d_v1api_local_url')) {
        $live2dSettings['modelAPI'] = live2d_v1api_local_url();
    }
    // 把 apiType 四态字符串在传给 JS 前压成 bool 之前, 先保留原始字符串值.
    // 下面 wp_localize 里 'v2SessionUrl' 需要用原字符串判断 'custom-local',
    // 不能等压成 bool 后再判 (那会永远 false, 导致 V2 防盗链 endpoint 不注入,
    // 前端 signOneModel/signAllModelDirs 短路 fallback 到裸 URL, 暴露源站地址).
    $apiTypeRaw = (is_array($live2dSettings) && isset($live2dSettings['apiType']))
        ? (string) $live2dSettings['apiType']
        : '';
    // 把四态字符串 apiType 在传给 JS 前压成 bool, 与 live2d-tips.ts 既有的
    // truthy 判断 / `isWorkshop=${settings.apiType}` 拼接保持兼容.
    if (is_array($live2dSettings) && function_exists('live2d_api_type_is_local')) {
        $live2dSettings['apiType'] = live2d_api_type_is_local(isset($live2dSettings['apiType']) ? $live2dSettings['apiType'] : null);
    }
    // shaderDir 不再由 PHP 注入: SDK (live2d_sdk/src/v2/lappdefine.ts) 默认以
    // import.meta.url 为基准解析到同级 ./shaders/WebGL/, vite.config.wordpress.ts
    // 会把 shader 文件拷贝到 assets/shaders/WebGL/,跟随插件实际位置/站点 URL。
    // 注意目录名必须全小写 shaders/(不是 Shaders/),
    // 避免 Linux 部署下大小写敏感文件系统 404。
    //
    // 但旧版插件曾把 shaderDir(默认值 '../../Framework/Shaders/WebGL/') 写进
    // live_2d_settings_option_name; sanitize 已经不再回写, 但 get_option() 拿到的
    // 数组里仍可能残留这个老字段, 经 wp_localize_script -> live2d_settings.settings
    // -> LAppDelegate.initialize 的 `Oe.value = G.shaderDir || Oe.value` 一行,
    // 会覆盖掉 import.meta.url 算出来的正确 assets/shaders/WebGL/ 绝对 URL,
    // 让 fetch 退回到相对路径 '../../Framework/...' 命中 404。这里在传给 JS
    // 前显式剔掉, 避免老站点必须重新打开设置页 Save 一次才能恢复。
    if (is_array($live2dSettings)) {
        unset($live2dSettings['shaderDir']);
    }
    wp_enqueue_style('waifu_css', LIVE2D_ASSETS . "waifu.css", array(), LIVE2D_VERSION); //css
    wp_enqueue_style('fontawesome_css', LIVE2D_ASSETS . "fontawesome/css/all.min.css", array(), LIVE2D_VERSION); //css
    wp_enqueue_script('moment', LIVE2D_ASSETS . 'moment.min.js', array(), LIVE2D_VERSION); //
    wp_enqueue_script('live2dv1core', LIVE2D_ASSETS . 'live2dv1.min.js', array(), LIVE2D_VERSION);
    live2d_mark_script_as_module('live2dv1core');
    // Live2D Cubism Core: 当前固定走插件本地 6.x 副本(assets/cubism-core/)。
    //   - 官方 CDN https://cubism.live2d.com/sdk-web/cubismcore/live2dcubismcore.min.js
    //     还停留在 5.1.0,不暴露 csmGetMocVersion(mocBytes) 单参重载;仓库内 Framework
    //     已按 6.x 单参形式调用,命中老 CDN 会抛 'Cannot read properties of undefined'。
    //   - live2d_sdk/src/v2/Core/RedistributableFiles.txt 已把 live2dcubismcore.min.js
    //     列入 Live2D Proprietary Software License Agreement 允许 redistribute 的清单,
    //     LICENSE.md / RedistributableFiles.txt 已并入 assets/cubism-core/。
    // 待官方 CDN 升级到 6.x 后,把下面这行恢复为优先用户配置即可(sdkUrl 仍由设置页持久化):
    //     $live2dCubismCoreUrl = (is_array($live2dSettings) && !empty($live2dSettings['sdkUrl']))
    //         ? $live2dSettings['sdkUrl']
    //         : LIVE2D_ASSETS . 'cubism-core/live2dcubismcore.min.js';
    $live2dCubismCoreUrl = LIVE2D_ASSETS . 'cubism-core/live2dcubismcore.min.js';
    wp_enqueue_script('live2dv2core', $live2dCubismCoreUrl, array(), LIVE2D_VERSION);
    wp_enqueue_script('live2dv2sdk', LIVE2D_ASSETS . 'live2dv2.min.js', array('live2dv2core'), LIVE2D_VERSION);
    live2d_mark_script_as_module('live2dv2sdk');
    wp_enqueue_script('live2dweb', LIVE2D_ASSETS . 'live2dwebsdk.min.js', array('live2dv1core', 'live2dv2sdk', 'moment'), LIVE2D_VERSION);
    live2d_mark_script_as_module('live2dweb');
    wp_localize_script('live2dweb', 'live2d_settings', array(
        'userInfo' => array(
            'sign' => isset($live2dUserInfo["sign"]) ? $live2dUserInfo["sign"] : '',
            'userName' => isset($live2dUserInfo["userName"]) ? $live2dUserInfo["userName"] : '',
            'certserialnumber' => isset($live2dUserInfo["certserialnumber"]) ? intval($live2dUserInfo["certserialnumber"]) : 0,
        ),
        'waifuTips' => get_option('live_2d_advanced_option_name'),
        'settings' => $live2dSettings,
        'localPath' => plugin_dir_url(__FILE__) . 'model',
        // V2 模型(Cubism 4/5)防盗链 endpoint(对应 src/live2d-V2Api.php)。
        // 仅在 apiType='custom-local' 时注入完整 URL;其他三种 apiType
        // (local / remote / custom-remote) 都不走防盗链 → 注入空串
        // → 前端 Wordpress/live2d-tips.ts 的 signOneModel 早退到裸链。
        // 注意: 此处必须用 $apiTypeRaw (上面在压 bool 之前保留的字符串),
        // 不能用 $live2dSettings['apiType'] (那已经是 bool, 永远 != 'custom-local').
        // 2026-05 之前用独立 protectV2 字段控制,现已合并进 apiType。
        //
        // 同时下发两件配套件 (V2 防爬虫):
        //   v2WpNonce       - WP REST nonce, 前端 fetch 时放 X-WP-Nonce 头
        //                     (rest_cookie_check_errors 双重校验, 拦裸调 API)
        //   v2OneTimeTokens - 8 个一次性 token, 每签 1 模型用 1 个,
        //                     服务端再补 1 个 nextToken 进池 → 池永不枯竭
        //                     (拦"抓 nonce 后批量爬"的脚本: 没访问页面就拿不到初始池)
        'v2SessionUrl'    => ($apiTypeRaw === 'custom-local')
            ? rest_url('live2d/v2/session')
            : '',
        'v2WpNonce'       => ($apiTypeRaw === 'custom-local')
            ? wp_create_nonce('wp_rest')
            : '',
        'v2OneTimeTokens' => ($apiTypeRaw === 'custom-local' && class_exists('live2d_V2Api'))
            ? live2d_V2Api::mint_one_time_tokens(8)
            : array(),
        'currentPage' => array('get_the_id' => get_the_id(), 'is_home' => is_front_page(), 'is_single' => is_single())
    ));
}
add_action('wp_head', 'live2D_style', 1);

// 启用插件
register_activation_hook(__FILE__, 'live_2d_install');
function live_2d_install()
{
    $live_2d_Settings = new live2D_Settings();
    $live_2d_Settings->install_Default_Settings();
    $live_2d_Settings->install_Default_Advanced();
}

// 停用插件 — 用户要求: 停用时清理本插件设置过的所有 option, 重新启用即干净状态.
// 模型文件 (model/) 不在此处理: 卸载时 WP 会连同插件目录一并删除; 仅停用时保留下载的
// 模型文件无碍 (重新启用后照常可用).
register_deactivation_hook(__FILE__, 'live_2d_stop');
function live_2d_stop()
{
    live_2d_purge_options();
}

//卸载插件 — 走同一份清理逻辑.
register_uninstall_hook(__FILE__, 'live_2d_uninstall');
function live_2d_uninstall()
{
    live_2d_purge_options();
}

/**
 * 清理本插件创建的所有 option (主设置 / 高级提示 / 登录 token).
 */
function live_2d_purge_options()
{
    delete_option('live_2d_settings_option_name');
    delete_option('live_2d_advanced_option_name');
    delete_option('live_2d_settings_user_token');
}

// 设置面板设置按钮的钩子
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'live_2d_settings_link');
function live_2d_settings_link($links)
{
    if (is_multisite() && (!is_main_site() || !is_super_admin())) return $links;
    $setlink = array(live_2d_link('options-general.php?page=live-2d-options', __('设置', 'live-2d')));
    return array_merge($setlink, $links);
}

// 实例化设置组件
if (is_admin()) {
    $live_2d_ = new live2D();
}

add_action('plugins_loaded', 'live2D_Init');
add_action('rest_api_init', function () {
    $sdk = new live2d_SDK();
    register_rest_route('live2d/v1', '/token', array(
        'methods' => 'POST',
        'callback' => array($sdk, 'user_login'),
        'permission_callback' => '__return_true'
    ));

    register_rest_route('live2d/v1', '/refresh_token', array(
        'methods' => 'POST',
        'callback' => array($sdk, 'refresh_token'),
        'permission_callback' => '__return_true'
    ));

    register_rest_route('live2d/v1', '/verify_token', array(
        'methods' => 'POST',
        'callback' => array($sdk, 'verify_token'),
        'permission_callback' => '__return_true'
    ));

    // 本地 V1 模型清单 / 切换 / 换装(取代 api.live2dweb.com/model/v2)
    live2d_V1Api::register_routes();

    // V2 模型(Cubism 4/5)防盗链 session/manifest/asset
    live2d_V2Api::register_routes();
});

// V2 模型本地缓存 — Cron hook 注册(异步任务队列入口,详见 live2d-V2Api.php 末尾)
// 必须挂在 init 之前生效,否则 wp_schedule_single_event 触发的 cron 进程跑到这里
// 时 hook 还没绑定就会丢任务。
live2d_V2Api::register_cron();

// 初始化加载
function live2D_Init()
{
    // 多语言加载
    load_plugin_textdomain('live-2d', false, LIVE2D_LANGUAGES);
    // Array of All Options
    $live_2d_options = get_option('live_2d_settings_option_name');
    // ** 用来避免更新后出现错误的判断 **
    $live2dLayoutType = false;
    if (!isset($live_2d_options['live2dLayoutType'])) {
        //如果没有设置则为页面显示
        $live2dLayoutType = true;
    } else {
        //如果设置按设置进行显示
        $live2dLayoutType = $live_2d_options['live2dLayoutType'];
    }
    // ** 如果是返回true显示为浏览器内 false显示为插件 **
    if ($live2dLayoutType) {
        add_action('wp_footer', 'live2D_DefMod');
    } else {
        add_action("widgets_init", function () {
            register_widget("Live2D_Widget");
        });
    }
}

//进行设置
function live2D_DefMod()
{
    // Retrieve this value with:
?>
    <div class="waifu">
        <div class="waifu-tips"></div>
        <canvas id="live2d" class="live2d"></canvas>
        <div class="waifu-tool">
            <span class="fui-home"><i class="fa-solid fa-house"></i></span>
            <span class="fui-chat"><i class="fa-solid fa-message"></i></span>
            <span class="fui-bot"><i class="fa-solid fa-robot"></i></span>
            <span class="fui-eye"><i class="fa-solid fa-eye"></i></span>
            <span class="fui-user"><i class="fa-solid fa-user"></i></span>
            <span class="fui-photo"><i class="fa-solid fa-image"></i></span>
            <span class="fui-info-circle"><i class="fa-solid fa-circle-info"></i></span>
            <span class="fui-cross"><i class="fa-solid fa-circle-xmark"></i></span>
        </div>
        <div class="gptInput">
            <input type="text" id="live2dChatText" />
            <span>
                <button class="wp-element-button" id="live2dSend"><?php esc_html_e('发送', 'live-2d'); ?></button>
            </span>
            <span style="width: 30px;font-size: 15px;display: flex;justify-content: center;align-items: center;">
                <i id="live2dSendClose" class="fa-solid fa-circle-xmark"></i>
            </span>
        </div>
    </div>
    <?php // 必须用 type="module": live2dwebsdk.min.js 是 ES module(defer 执行),
          // 经典 inline <script> 会在 module 之前同步执行, 那时 window.initLive2dWeb
          // 还未被赋值. inline module 与 src module 共用 defer 队列, 按文档顺序执行,
          // 因此能保证此时 initLive2dWeb 已经挂到 window 上. ?>
    <script type="module">
        if (typeof window.initLive2dWeb === 'function') {
            if (document.readyState === 'complete') {
                window.initLive2dWeb();
            } else {
                window.addEventListener('load', window.initLive2dWeb);
            }
        }
    </script>
<?php
}

function live_2d_link($url, $text = '', $ext = '')
{
    if (empty($text)) $text = $url;
    $button = stripos($ext, 'button') !== false ? " class='button'" : "";
    $target = stripos($ext, 'blank') !== false ? " target='_blank' rel='noopener noreferrer'" : "";
    $link = "<a href='" . esc_url($url) . "'{$button}{$target}>" . esc_html($text) . "</a>";
    return stripos($ext, 'p') !== false ? "<p>{$link}</p>" : "{$link} ";
}

?>