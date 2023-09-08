<?php
include_once(dirname(__FILE__)  . '/live2d-SDK.php');
//下载模型ajax
add_action("wp_ajax_download_model", array(new live2d_SDK, 'DownloadModel'));
//解压缩ajax
add_action("wp_ajax_zip_model", array(new live2d_SDK, 'OpenZip'));
//清理文件ajax
add_action("wp_ajax_clear_files", array(new live2d_SDK, 'ClearFiles'));
//获取下方列表的ajax
add_action("wp_ajax_get_model_list", array(new live2d_SDK, 'GetModelList'));
//获取下方材质的ajax
add_action("wp_ajax_get_texture_list", array(new live2d_SDK, 'GetTextureList'));
//标记下载完成
add_action("wp_ajax_downloaded", array(new live2d_SDK, 'Downloaded'));
//获取新模型的可用动作列表
add_action("wp_ajax_get_motions", array(new live2d_SDK, 'GetModelMotions'));
class live2d_Shop
{
    private $userInfo;
    public function __construct()
    {
        $this->userInfo = get_option('live_2d_settings_user_token');
    }
    public function live2d_shop_init()
    { 
        wp_enqueue_style('live2d_admin', plugin_dir_url(dirname(__FILE__)) . '/assets/waifu.css'); //css
        wp_enqueue_script('admin_js', plugin_dir_url(dirname(__FILE__)) .'/assets/waifu-admin.min.js');
		wp_localize_script('admin_js', 'settings', array(
			'userInfo' => get_option('live_2d_settings_user_token'),
			'homeUrl' => get_home_url(),
			'settings'=> get_option('live_2d_settings_option_name'),
		));
        add_action('admin_footer', 'model_shop_scripts');
?>
        <div id="live2d-shop">
            <div class="wp-filter">
                <h2>列表中是您可以使用的模型。</h2>
                <p>您可以通过<a href="https://www.live2dweb.com/Model/Workshop" target="_blank">插件官网</a>增加您的可选列表</p>
            </div>
            <?php
            $userInfo = $this->userInfo;
            if (empty($userInfo["sign"]) && empty($userInfo["userLevel"])) {
            ?>
                <div>
                    您需要登陆并付费才可以使用此功能。
                </div>
            <?php
            } else if (intval($userInfo["userLevel"]) < 1) {
            ?>
                <div>
                    您需要付费才可以使用此功能。
                </div>
            <?php
            } else {
            ?>
                <div class="live2d-container"></div>
            <?php
            }
            ?>
        </div>

    <?php
    }
}
function model_shop_scripts()
{
    ?>
    <script>
        jQuery(function() {
            window.getModelList(settings)
        });
    </script>
<?php
}
?>