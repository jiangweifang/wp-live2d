<?php
include_once(dirname(__FILE__)  . '/live2d-SDK.php');
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
        <script>
            window.onload = function() {
                window.getModelList(settings)
            }
        </script>
<?php
        //live2d_SDK::DownloadModel("https://www.live2dweb.com/imgs/logo-full.png",plugin_dir_path(dirname(__FILE__)).'model/logo-full.png');
    }
}
?>