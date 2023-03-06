<?php
include_once(dirname(__FILE__)  . '/live2d-SDK.php');
add_action('admin_footer', 'model_shop_scripts');
add_action("wp_ajax_download_model", array(new live2d_SDK, 'DownloadModel'));
add_action("wp_ajax_zip_model", array(new live2d_SDK, 'OpenZip'));
class live2d_Shop
{
    public function live2d_shop_init()
    {
?>
        <div id="live2d-shop">
            <div>
                列表中是您可以使用的模型。
            </div>
            <div class="live2d-container">

            </div>
            <div>
                您还没有登录，请登录后查看。
            </div>
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
            jQuery.post(ajaxurl, {
                'action': 'download_model',
                'modelId': 1
            }, function(rsp) {
                console.log(rsp);
                if(rsp.fileName){
                    console.log("下载完成");
                }
            });
        })
    </script>
<?php
}
?>