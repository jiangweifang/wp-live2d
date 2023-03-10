<?php
include_once(dirname(__FILE__)  . '/live2d-SDK.php');
add_action('admin_footer', 'model_shop_scripts');
//下载模型ajax
add_action("wp_ajax_download_model", array(new live2d_SDK, 'DownloadModel'));
//解压缩ajax
add_action("wp_ajax_zip_model", array(new live2d_SDK, 'OpenZip'));
//清理文件ajax
add_action("wp_ajax_clear_files", array(new live2d_SDK, 'ClearFiles'));
//获取下方列表的ajax
add_action("wp_ajax_get_model_list", array(new live2d_SDK, 'GetModelList'));
class live2d_Shop
{
    private $userInfo;
    public function __construct()
    {
        $this->userInfo = get_option('live_2d_settings_user_token');
    }
    public function live2d_shop_init()
    {
?>
        <div id="live2d-shop">
            <div class="wp-filter">
                <h2>列表中是您可以使用的模型。</h2>
            </div>
            <?php
            $userInfo = $this->userInfo;
            if (empty($userInfo["sign"]) && intval($userInfo["userLevel"]) < 1) {
            ?>
                <div>
                    您需要登陆并付费才可以使用此功能。
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