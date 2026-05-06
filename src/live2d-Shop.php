<?php
if (!defined('ABSPATH')) {
    exit;
}
include_once(dirname(__FILE__)  . '/live2d-SDK.php');
include_once(dirname(__FILE__)  . '/live2d-V2Api.php');
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

// V3 模型本地缓存(protectV2='local')— 后台 AJAX 入口。
// nonce 沿用 'live2d_shop_action'(与 live2d-SDK.php 内一致);capability=manage_options。
// 入参 modelApi 直接来自 admin TS,服务端用 wp_unslash + 简单 URL 校验,不依赖白名单。
add_action('wp_ajax_v2_local_status',   'live2d_v2_ajax_local_status');
add_action('wp_ajax_v2_download_model', 'live2d_v2_ajax_download_model');
add_action('wp_ajax_v2_delete_model',   'live2d_v2_ajax_delete_model');

function live2d_v2_ajax_verify()
{
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        wp_send_json_error(array('errorCode' => 403, 'errorMsg' => 'forbidden'), 403);
    }
    check_ajax_referer('live2d_shop_action', '_wpnonce');
}

function live2d_v2_ajax_local_status()
{
    live2d_v2_ajax_verify();
    $modelApi = isset($_POST['modelApi']) ? wp_unslash($_POST['modelApi']) : '';
    wp_send_json(live2d_V2Api::get_local_status($modelApi));
}

function live2d_v2_ajax_download_model()
{
    live2d_v2_ajax_verify();
    $modelApi = isset($_POST['modelApi']) ? wp_unslash($_POST['modelApi']) : '';
    // 大模型可能下几十秒;给 PHP 关掉默认 30s 限制(后端 streaming 下载本身有 60s 单文件 timeout)
    @set_time_limit(0);
    $result = live2d_V2Api::download_model_to_local($modelApi);
    wp_send_json($result);
}

function live2d_v2_ajax_delete_model()
{
    live2d_v2_ajax_verify();
    $modelApi = isset($_POST['modelApi']) ? wp_unslash($_POST['modelApi']) : '';
    wp_send_json(live2d_V2Api::delete_model_local($modelApi));
}

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
        // 把 nonce 以 PHP 字面量直接入 inline 脚本，避免依赖 window.settings.nonce
        // (admin_js 是 ES module + 多处 wp_localize_script 可能产生读到空 nonce 的竞态)
        $shopNonceLiteral = wp_create_nonce('live2d_shop_action');
        add_action('admin_footer', function () use ($shopNonceLiteral) {
            model_shop_scripts($shopNonceLiteral);
        });

        // 缩略图位于插件 assets/v1/imgs/{name}.png(由 live2d_sdk/src/Chromium/public/v1 复制而来)
        $assetsBaseUrl = plugin_dir_url(dirname(__FILE__)) . 'assets/';
        // 解压目标目录(OpenZip 解到 DOWNLOAD_DIR/{modelName}),用来判断是否"已下载"
        $downloadDir   = defined('DOWNLOAD_DIR') ? DOWNLOAD_DIR : (plugin_dir_path(dirname(__FILE__)) . 'model/');
?>
        <div id="live2d-shop">
            <div class="wp-filter">
                <h2><?php esc_html_e('列表中是您可以使用的模型。', 'live-2d'); ?></h2>
                <p><?php echo wp_kses(
                    __('下载方式与浏览器扩展一致:直接 GET <code>download.live2dweb.com/model/{name}.zip</code>,由后端 PHP 解压到 <code>model/{name}</code>。', 'live-2d'),
                    array('code' => array())
                ); ?></p>
            </div>
            <?php
            $userInfo = $this->userInfo;
            // 准入门槛: 1) 必须已登录(sign != ''); 2) 必须已完成邮箱验证(role == 2)。
            // 与 live2d-Login.php 中的判定保持完全一致(role==2 即"已激活邮箱"),
            // 不再要求 userLevel>=1 (付费用户)。
            // 后端 AJAX (live2d-SDK.php DownloadV1Model / OpenZip / ClearFiles ...)
            // 仅校验 manage_options capability + nonce + sign != '', 与此 UI 门槛
            // 同等; 真正的资源访问能否拿到 zip 由上游 download.live2dweb.com 的
            // Bearer/Referer 鉴权决定, 与本地 userLevel 无关。
            if (!isset($userInfo) || empty($userInfo["sign"])) {
            ?>
                <div>
                    <?php esc_html_e('您需要登录并完成邮箱验证才可以使用此功能。', 'live-2d'); ?>
                </div>
            <?php
            } else if (intval($userInfo["role"]) != 2) {
            ?>
                <div>
                    <?php echo wp_kses(
                        __('您的邮箱未激活, 请<a href="https://www.live2dweb.com/Email" target="_blank">点击此处</a>激活邮箱', 'live-2d'),
                        array('a' => array('href' => array(), 'target' => array()))
                    ); ?>
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
                        echo '<div class="title">' . esc_html($item['label']) . '</div>';
                        echo '<div class="downBtn">';
                        if ($isCached) {
                            echo '<button type="button" class="install-now button button-disabled" disabled data-model-name="' . esc_attr($item['name']) . '">' . esc_html__('已启用', 'live-2d') . '</button>';
                        } else {
                            echo '<button type="button" class="install-now button" data-model-name="' . esc_attr($item['name']) . '">' . esc_html__('下载', 'live-2d') . '</button>';
                        }
                        echo '</div>';
                        echo '</div>';
                    }
                    echo '</div>';
                } else {
                    echo '<div>' . esc_html__('没有可用的模型。', 'live-2d') . '</div>';
                }
            }
            ?>
        </div>

    <?php
    }
}
function model_shop_scripts($shopNonceLiteral = '')
{
    // 后兼容: 如果调用者未传 nonce(老代码路径)，则从 window.settings.nonce 取
    $nonceJs = $shopNonceLiteral !== ''
        ? wp_json_encode($shopNonceLiteral)
        : '((window.settings && window.settings.nonce) ? window.settings.nonce : "")';
    // 交互文案一次性以 JSON 下发，避免在 inline JS 中混插 PHP 调用。
    $i18n = array(
        'downloading' => __('正在下载...', 'live-2d'),
        'extracting'  => __('正在解压...', 'live-2d'),
        'enabled'     => __('已启用', 'live-2d'),
        'download'    => __('下载', 'live-2d'),
        'unzipFailed' => __('解压失败,文件可能损坏。', 'live-2d'),
        'downloadFailed'  => __('下载失败', 'live-2d'),
        'requestFailed'   => __('下载请求失败,请检查网络或登录状态。', 'live-2d'),
    );
    ?>
    <script>
        // 模型下载流程对齐 Chromium 扩展 v1ModelCache.ts:
        //   1) POST download_v1_model { modelName } -> 后端直链下载 zip
        //   2) POST zip_model { fileName }          -> 后端 PHP ZipArchive 解压(前端不做解压)
        //   3) 失败时 POST clear_files 清理半成品 zip
        // 与扩展不同:这里不再走 Model/ModelInfo / Model/Downloaded 两次额外往返。
        //
        // 改用原生 JS 与 fetch,彻底避开 jQuery / jquery-migrate 的弃用 API
        // (例如 .click() 短链),避免 WP 后台 console 出现 JQMIGRATE 告警。
        (function () {
            'use strict';
            var thisAjaxUrl = (typeof ajaxurl !== 'undefined') ? ajaxurl : '/wp-admin/admin-ajax.php';
            var live2dShopNonce = <?php echo $nonceJs; ?>;
            var live2dShopI18n = <?php echo wp_json_encode($i18n); ?>;

            function setBtnState(btn, opts) {
                if (!btn) return;
                if (opts.text !== undefined) btn.textContent = opts.text;
                if (opts.disabled !== undefined) btn.disabled = !!opts.disabled;
                if (opts.addClass) {
                    opts.addClass.split(/\s+/).forEach(function (c) { if (c) btn.classList.add(c); });
                }
                if (opts.removeClass) {
                    opts.removeClass.split(/\s+/).forEach(function (c) { if (c) btn.classList.remove(c); });
                }
            }

            // 用 application/x-www-form-urlencoded 发,符合 WP admin-ajax 的入参约定
            function postForm(action, payload) {
                var body = new URLSearchParams();
                body.set('action', action);
                body.set('_wpnonce', live2dShopNonce);
                Object.keys(payload || {}).forEach(function (k) {
                    if (payload[k] !== undefined && payload[k] !== null) {
                        body.set(k, String(payload[k]));
                    }
                });
                return fetch(thisAjaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: body.toString(),
                });
            }

            function onClick(e) {
                var btn = e.target.closest('.install-now');
                if (!btn) return;
                if (btn.disabled || btn.classList.contains('button-disabled')) return;
                e.preventDefault();
                var modelName = btn.getAttribute('data-model-name');
                if (!modelName) return;

                setBtnState(btn, { text: live2dShopI18n.downloading, disabled: true, addClass: 'updating-message' });

                postForm('download_v1_model', { modelName: modelName })
                    .then(function (r) { return r.json().catch(function () { return {}; }); })
                    .then(function (rsp) {
                        var rspInfo = rsp || {};
                        if (rspInfo.errorCode === 200) {
                            setBtnState(btn, { text: live2dShopI18n.extracting });
                            return postForm('zip_model', { fileName: rspInfo.fileName })
                                .then(function (r) { return r.text(); })
                                .then(function (zipRspText) {
                                    if (Number(zipRspText) === 1) {
                                        setBtnState(btn, {
                                            text: live2dShopI18n.enabled,
                                            disabled: true,
                                            removeClass: 'updating-message',
                                            addClass: 'button-disabled',
                                        });
                                    } else {
                                        // 半成品 zip 清理，失败也不阻塞用户
                                        postForm('clear_files', { fileName: rspInfo.fileName }).catch(function () {});
                                        window.alert(live2dShopI18n.unzipFailed);
                                        setBtnState(btn, {
                                            text: live2dShopI18n.download,
                                            disabled: false,
                                            removeClass: 'updating-message',
                                        });
                                    }
                                });
                        }
                        var msg = (rspInfo && rspInfo.errorMsg) ? rspInfo.errorMsg : live2dShopI18n.downloadFailed;
                        window.alert(msg);
                        setBtnState(btn, {
                            text: live2dShopI18n.download,
                            disabled: false,
                            removeClass: 'updating-message',
                        });
                    })
                    .catch(function () {
                        window.alert(live2dShopI18n.requestFailed);
                        setBtnState(btn, {
                            text: live2dShopI18n.download,
                            disabled: false,
                            removeClass: 'updating-message',
                        });
                    });
            }

            function bind() {
                var root = document.getElementById('live2d-shop');
                if (!root) return;
                root.addEventListener('click', onClick);
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', bind, { once: true });
            } else {
                bind();
            }
        })();
    </script>
<?php
}
?>
