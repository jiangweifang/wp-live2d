<?php
include_once(dirname(__FILE__)  . '/live2d-SDK.php');
class live2d_Shop
{
    public function live2d_shop_init()
    {
        wp_enqueue_style( 'live2d_admin' ,plugin_dir_url(dirname(__FILE__)).'assets/waifu-admin.scss');//css
?>
        <div id="live2d-shop">
            <div>
                列表中是您可以使用的模型。
            </div>
            <div class="live2d-container">
                <div class="model-item">
                    <div class="thumb"><img /></div>
                    <div class="title">这里是名称</div>
                    <div class="downBtn"><a class="install-now button">下载</a></div>
                </div>
                <div class="model-item">
                    <div class="thumb"><img /></div>
                    <div class="title">这里是名称</div>
                    <div class="downBtn"><a class="install-now button">下载</a></div>
                </div>
                <div class="model-item">
                    <div class="thumb"><img /></div>
                    <div class="title">这里是名称</div>
                    <div class="downBtn"><a class="install-now button">下载</a></div>
                </div>
                <div class="model-item">
                    <div class="thumb"><img /></div>
                    <div class="title">这里是名称</div>
                    <div class="downBtn"><a class="install-now button">下载</a></div>
                </div>
                <div class="model-item">
                    <div class="thumb"><img /></div>
                    <div class="title">这里是名称</div>
                    <div class="downBtn"><a class="install-now button">下载</a></div>
                </div>
            </div>
            <div>
                您还没有登录，请登录后查看。
            </div>
        </div>
<?php
live2d_SDK::DownloadModel("https://www.live2dweb.com/imgs/logo-full.png",plugin_dir_path(dirname(__FILE__)).'model/logo-full.png');
    }
}
?>