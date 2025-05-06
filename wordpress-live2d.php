<?php
/*
 * Plugin Name: Live 2D
 * Plugin URI: https://www.live2dweb.com/
 * Description: 看板娘插件
 * Version: 1.9.14
 * Requires PHP: 7.4
 * Author: Weifang Chiang
 * Author URI: https://github.com/jiangweifang/wp-live2d
 * Text Domain: live-2d
 * Domain Path: /languages
 */

//定义目录
define('LIVE2D_ASSETS', plugin_dir_url(__FILE__) . 'assets/'); //资源目录
define('LIVE2D_LANGUAGES', basename(dirname(__FILE__)) . '/languages'); //基础目录

// 加载设置组件
include_once(dirname(__FILE__)  . '/src/live2d-Main.php');
// 加载小工具
include_once(dirname(__FILE__)  . '/src/live2d-Widget.php');
// 加载登录确认API
include_once(dirname(__FILE__)  . '/src/live2d-SDK.php');

//添加样式（初始化）
function live2D_style()
{
    $live2dSettings = get_option('live_2d_settings_option_name');
    $live2dUserInfo = get_option('live_2d_settings_user_token');
    wp_enqueue_style('waifu_css', LIVE2D_ASSETS . "waifu.css"); //css
    wp_enqueue_style('fontawesome_css', LIVE2D_ASSETS . "fontawesome/css/all.min.css"); //css
    wp_enqueue_script('moment', LIVE2D_ASSETS . 'moment.min.js'); //
    wp_enqueue_script('live2dv1core', LIVE2D_ASSETS . 'live2dv1.min.js');
    wp_enqueue_script('live2dv2core', $live2dSettings["sdkUrl"]);
    wp_enqueue_script('live2dv2sdk', LIVE2D_ASSETS . 'live2dv2.min.js',array('live2dv2core'));
    wp_enqueue_script('live2dweb', LIVE2D_ASSETS . 'live2dwebsdk.min.js', array('live2dv1core', 'live2dv2sdk', 'moment'));
    wp_localize_script('live2dweb', 'live2d_settings', array(
        'userInfo' => array(
            'sign' => $live2dUserInfo["sign"],
            'userName' => $live2dUserInfo["userName"],
            'certserialnumber'=> intval($live2dUserInfo["certserialnumber"]),
        ),
        'waifuTips' => get_option('live_2d_advanced_option_name'),
        'settings' => $live2dSettings,
        'localPath' => plugin_dir_url(__FILE__) . 'model',
        'currentPage'=> array('get_the_id'=>get_the_id(),'is_home'=>is_front_page(),'is_single'=>is_single())
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

// 停用插件
register_deactivation_hook(__FILE__, 'live_2d_stop');
function live_2d_stop()
{
    delete_option('live_2d_settings_option_name');
    //delete_option( 'live_2d_advanced_option_name' );
    delete_option('live_2d_settings_user_token');
}

//卸载插件
register_uninstall_hook(__FILE__, 'live_2d_uninstall');
function live_2d_uninstall()
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
    register_rest_route('live2d/v1', '/rollback_set', array(
        'methods' => 'POST',
        'callback' => array($sdk, 'rollback_set'),
        'permission_callback' => '__return_true'
    ));
});

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
        <div class="gptInput"><input type="text" id="live2dChatText" /><span><button class="wp-element-button" id="live2dSend">发送</button></span></div>
    </div>
    <script type="text/javascript">
        window.onload = initLive2dWeb();
    </script>
<?php
}

function live_2d_link($url, $text = '', $ext = '')
{
    if (empty($text)) $text = $url;
    $button = stripos($ext, 'button') !== false ? " class='button'" : "";
    $target = stripos($ext, 'blank') !== false ? " target='_blank'" : "";
    $link = "<a href='{$url}'{$button}{$target}>{$text}</a>";
    return stripos($ext, 'p') !== false ? "<p>{$link}</p>" : "{$link} ";
}

?>