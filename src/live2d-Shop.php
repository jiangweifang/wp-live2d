<?php
if (!defined('ABSPATH')) {
    exit;
}
include_once(dirname(__FILE__)  . '/live2d-SDK.php');
//下载模型ajax(V1 直链下载,对齐 Chromium 扩展 v1ModelCache.ts 的 downloadAndCacheV1Model)
add_action("wp_ajax_download_v1_model", array(new live2d_SDK, 'DownloadV1Model'));
//解压缩ajax
add_action("wp_ajax_zip_model", array(new live2d_SDK, 'OpenZip'));
//清理文件ajax
add_action("wp_ajax_clear_files", array(new live2d_SDK, 'ClearFiles'));
//设置页 modelId 下拉框拉取模型列表
add_action("wp_ajax_get_model_list", array(new live2d_SDK, 'GetModelMotions'));
//设置页 modelTexturesId 下拉框拉取材质列表
add_action("wp_ajax_get_texture_list", array(new live2d_SDK, 'GetTextureList'));

class live2d_Shop
{
    private $userInfo;
    public function __construct()
    {
        $this->userInfo = get_option('live_2d_settings_user_token');
    }
    public function live2d_shop_init()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('您没有访问该页面的权限。', 'live-2d'));
        }
        wp_enqueue_style('live2d_admin', plugin_dir_url(dirname(__FILE__)) . '/assets/waifu.css', array(), defined('LIVE2D_VERSION') ? LIVE2D_VERSION : false); //css
        wp_enqueue_script('admin_js', plugin_dir_url(dirname(__FILE__)) . '/assets/waifu-admin.min.js', array('jquery'), defined('LIVE2D_VERSION') ? LIVE2D_VERSION : false, true);
        wp_localize_script('admin_js', 'settings', array(
            'userInfo' => array(
                'sign' => isset($this->userInfo["sign"]) ? $this->userInfo["sign"] : '',
                'userName' => isset($this->userInfo["userName"]) ? $this->userInfo["userName"] : '',
                'certserialnumber' => isset($this->userInfo["certserialnumber"]) ? intval($this->userInfo["certserialnumber"]) : 0,
            ),
            'homeUrl' => get_home_url(),
            'settings' => get_option('live_2d_settings_option_name'),
            'nonce' => wp_create_nonce('live2d_shop_action'),
        ));
        add_action('admin_footer', 'model_shop_scripts');

        // 缩略图位于插件 assets/v1/imgs/{name}.png(由 live2d_sdk/src/Chromium/public/v1 复制而来)
        $assetsBaseUrl = plugin_dir_url(dirname(__FILE__)) . 'assets/';
        // 解压目标目录(OpenZip 解到 DOWNLOAD_DIR/{modelName}),用来判断是否"已下载"
        $downloadDir   = defined('DOWNLOAD_DIR') ? DOWNLOAD_DIR : (plugin_dir_path(dirname(__FILE__)) . 'model/');
?>
        <div id="live2d-shop">
            <div class="wp-filter">
                <h2>列表中是您可以使用的模型。</h2>
                <p>下载方式与浏览器扩展一致:直接 GET <code>download.live2dweb.com/model/{name}.zip</code>,由后端 PHP 解压到 <code>model/{name}</code>。</p>
            </div>
            <?php
            $userInfo = $this->userInfo;
            if (!isset($userInfo) || empty($userInfo["sign"])) {
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
                // 直接读取本地 V1 catalog,不再向 Model/List 发请求
                $catalog = live2d_SDK::GetV1Catalog();
                if (!empty($catalog)) {
                    echo '<div class="live2d-container">';
                    foreach ($catalog as $item) {
                        // sanitize_file_name 与 OpenZip 中保持一致,确保下载/校验路径同源
                        $sanName     = sanitize_file_name($item['name']);
                        $extractPath = $downloadDir . $sanName;
                        $isCached    = is_dir($extractPath);
                        $thumbUrl    = $assetsBaseUrl . $item['thumbnail'];

                        echo '<div class="model-item">';
                        echo '<div class="thumb">';
                        echo '<img src="' . esc_url($thumbUrl) . '" alt="' . esc_attr($item['label']) . '" loading="lazy">';
                        echo '</div>';
                        echo '<div class="title">' . esc_html($item['label']);
                        echo ' <code style="font-size:12px;color:#666;">' . esc_html($item['name']) . '</code>';
                        echo '</div>';
                        echo '<div class="downBtn">';
                        if ($isCached) {
                            echo '<button type="button" class="install-now button button-disabled" disabled data-model-name="' . esc_attr($item['name']) . '">已启用</button>';
                        } else {
                            echo '<button type="button" class="install-now button" data-model-name="' . esc_attr($item['name']) . '">下载</button>';
                        }
                        echo '</div>';
                        echo '</div>';
                    }
                    echo '</div>';
                } else {
                    echo '<div>没有可用的模型。</div>';
                }
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
        // 模型下载流程对齐 Chromium 扩展 v1ModelCache.ts:
        //   1) POST download_v1_model { modelName } -> 后端直链下载 zip
        //   2) POST zip_model { fileName }          -> 后端 PHP ZipArchive 解压(前端不做解压)
        //   3) 失败时 POST clear_files 清理半成品 zip
        // 与扩展不同:这里不再走 Model/ModelInfo / Model/Downloaded 两次额外往返。
        (function () {
            const $ = jQuery;
            const thisAjaxUrl = ajaxurl;
            const live2dShopNonce = (window.settings && window.settings.nonce) ? window.settings.nonce : '';
            $(function () {
                $('#live2d-shop').on('click', '.install-now:not([disabled])', function (e) {
                    e.preventDefault();
                    const installBtn = $(this);
                    const modelName = installBtn.data('model-name');
                    if (!modelName) {
                        return;
                    }
                    installBtn.addClass('updating-message').text('正在下载...').prop('disabled', true);
                    $.post(thisAjaxUrl, {
                        action: 'download_v1_model',
                        modelName: modelName,
                        _wpnonce: live2dShopNonce,
                    }, function (rsp) {
                        // DownloadV1Model 用 wp_send_json 返回 object,无需 JSON.parse
                        const rspInfo = rsp || {};
                        if (rspInfo.errorCode === 200) {
                            installBtn.text('正在解压...');
                            $.post(thisAjaxUrl, {
                                action: 'zip_model',
                                fileName: rspInfo.fileName,
                                _wpnonce: live2dShopNonce,
                            }, function (zipRsp) {
                                if (Number(zipRsp) === 1) {
                                    installBtn.removeClass('updating-message').text('已启用').prop('disabled', true).addClass('button-disabled');
                                } else {
                                    $.post(thisAjaxUrl, {
                                        action: 'clear_files',
                                        fileName: rspInfo.fileName,
                                        _wpnonce: live2dShopNonce,
                                    });
                                    alert('解压失败,文件可能损坏。');
                                    installBtn.removeClass('updating-message').prop('disabled', false).text('下载');
                                }
                            });
                        } else {
                            const msg = (rspInfo && rspInfo.errorMsg) ? rspInfo.errorMsg : '下载失败';
                            alert(msg);
                            installBtn.removeClass('updating-message').prop('disabled', false).text('下载');
                        }
                    }).fail(function () {
                        alert('下载请求失败,请检查网络或登录状态。');
                        installBtn.removeClass('updating-message').prop('disabled', false).text('下载');
                    });
                });
            });
        })();
    </script>
<?php
}
?>
