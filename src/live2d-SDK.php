<?php
if (!defined('ABSPATH')) {
    exit; // 阻止直接访问
}
require_once(ABSPATH . 'wp-admin/includes/plugin.php');
require_once(__DIR__ . '/live2d-ModelFiles.php');
require_once(__DIR__  . '/jwt/BeforeValidException.php');
require_once(__DIR__  . '/jwt/CachedKeySet.php');
require_once(__DIR__  . '/jwt/ExpiredException.php');
require_once(__DIR__  . '/jwt/JWK.php');
require_once(__DIR__  . '/jwt/JWT.php');
require_once(__DIR__  . '/jwt/Key.php');
require_once(__DIR__  . '/jwt/SignatureInvalidException.php');

use Firebase\JWT\BeforeValidException;
use Firebase\JWT\CachedKeySet;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;

define('LIVE2D_IS_PLUGIN_ACTIVE', is_plugin_active(plugin_basename(dirname(__DIR__) . '/wordpress-live2d.php'))); //补丁启用
define('LIVE2D_API_URL', "https://api.live2dweb.com"); //API地址
define('LIVE2D_DOMAIN', "https://www.live2dweb.com"); //API地址
define('LIVE2D_DOWNLOAD_DIR', live2d_ModelFiles::root_dir());
// V1 模型下载现在要求有效的 HTTPS;旧客户端地址不能用于服务器下载。
//   GET http://download.live2dweb.com/model/{modelName}.zip,需要 Bearer {sign}
// 带授权信息的下载不能回退至 HTTP,也不能关闭证书校验。
define('LIVE2D_V1_ZIP_BASE', 'https://download.live2dweb.com/model');
class live2d_SDK
{
    private $userInfo;
    private $apiKey;
    public function __construct()
    {
        $this->userInfo = get_option('live_2d_settings_user_token');
        if (isset($this->userInfo["key"])) {
            $this->apiKey = $this->userInfo["key"];
        }
    }

    public function check_login_permission(WP_REST_Request $request)
    {
        if (!current_user_can('manage_options') || !wp_verify_nonce($request->get_header('X-WP-Nonce'), 'wp_rest')) {
            return new WP_Error('rest_forbidden', 'Administrator authorization is required.', array('status' => 403));
        }
        return true;
    }
    /**
     * 获取用户登录结果
     * 用这个方法可以通过key获取到token
     */
    public function user_login($request)
    {
        $allowed = $this->check_login_permission($request);
        if (is_wp_error($allowed)) return $allowed;
        $homeUrl = get_home_url();
        $errCode = intval($request["errorCode"]);
        $sign = $request["sign"];
        $key = $request["key"];

        if (is_string($sign) && $sign !== '' && is_string($key) && $key !== '' && $errCode === 200) {
            $signInfo = $this->JwtDecode($sign, $key);
            if (!is_array($signInfo)) {
                return new WP_Error('invalid_token', 'Invalid account token.', array('status' => 403));
            }
            $userInfo = array();
            $userInfo["key"] = $key;
            $userInfo["sign"] = $sign;
            $userInfo["userName"] = $signInfo["email"];
            $userInfo["role"] = intval($signInfo["role"]);
            $userInfo["certserialnumber"] = intval($signInfo["certserialnumber"]);
            $userInfo["userLevel"] = intval($signInfo["http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata"]);
            $userInfo["errorCode"] = $errCode;
            $userInfo["hosts"] = $signInfo["aud"];
            if ($homeUrl == $signInfo["aud"] && LIVE2D_IS_PLUGIN_ACTIVE) {
                update_option('live_2d_settings_user_token', $userInfo);
                echo "1";
            } else {
                echo "0";
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational failure reporting, not debug output; credentials are never logged.
                error_log('用户登录结果:false');
            }
        } else {
            if ($errCode) {
                echo absint($errCode);
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational failure reporting, not debug output; credentials are never logged.
                error_log('用户登录结果:false ' . $errCode);
            } else {
                echo "1005";
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational failure reporting, not debug output; credentials are never logged.
                error_log('用户登录结果:false [1005]');
            }
        }
    }

    public function rollback_set($request)
    {
        // 预留接口, 当前未实现; 调用者不应依赖返回值.
        return new WP_Error('not_implemented', 'rollback_set is not implemented', array('status' => 501));
    }
    /**
     * 刷新token
     * 通过或这个方法更新token, 目前是2小时过期
     */
    public function refresh_token($request)
    {
        $sign = $request["sign"];
        if (!is_string($sign) || $sign === '') {
            return new WP_Error('invalid_token', 'Invalid token.', array('status' => 403));
        }
        $signInfo = $this->JwtDecode($sign, $this->apiKey);
        if ($signInfo === false) {
            return new WP_Error('invalid_token', 'Invalid token.', array('status' => 403));
        }
        if ($signInfo === 0 || (int) ($signInfo['exp'] ?? 0) <= time() + 300) {
            $newToken = $this->get_refresh_token($sign);
            if (!$newToken) {
                status_header(403);
            } else {
                status_header(200);
                header('Content-Type: text/plain; charset=UTF-8');
                echo esc_html($newToken);
            }
            exit;
        } else {
            status_header(200);
            header('Content-Type: text/plain; charset=UTF-8');
            echo esc_html($sign);
            exit;
        }
    }
    /**
     * JS验证token
     * 这个方法是给前端使用的, 通过这个方法可以验证token是否过期
     */
    public function verify_token($request)
    {
        $auth_header = $request->get_header('Authorization');
        if (empty($auth_header)) {
            status_header(403);
            exit;
        }
        // 通常 Authorization 的格式为 "Bearer token"
        if (preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
            $token = $matches[1];
            // 使用你的密钥解码 JWT
            $decoded = $this->JwtDecode($token, $this->apiKey);
            if ($decoded === false) {
                status_header(403);
                exit;
            }

            if ($decoded === 0) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational failure reporting, not debug output; credentials are never logged.
                error_log('JS验证成功但是已经过期');
                $newToken = $this->get_refresh_token($token);
                if (!$newToken) {
                    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational failure reporting, not debug output; credentials are never logged.
                    error_log('JS验证失败Token获取时出错。');
                    status_header(403);
                } else {
                    status_header(200);
                    header('Content-Type: text/plain; charset=UTF-8');
                    echo esc_html($newToken);
                }
                exit;
            }

            if (is_array($decoded)) {
                status_header(200);
                header('Content-Type: text/plain; charset=UTF-8');
                echo esc_html($token);
                exit;
            } else {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational failure reporting, not debug output; credentials are never logged.
                error_log('Live2D token verification failed');
                status_header(403);
            }
        } else {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational failure reporting, not debug output; credentials are never logged.
            error_log('JS验证收到不正确的请求。');
            status_header(400);
        }
    }

    private function get_refresh_token($sign)
    {
        if (!empty($this->userInfo['sign']) && $this->userInfo['sign'] !== $sign) {
            $current = $this->JwtDecode($this->userInfo['sign'], $this->apiKey);
            if (is_array($current) && (int) ($current['exp'] ?? 0) > time() + 300) return $this->userInfo['sign'];
        }
        try {
            // An expired bearer cannot call the provider's authorized RefreshToken endpoint.
            $token = $this->GetToken($this->apiKey);
            $claims = $this->JwtDecode($token, $this->apiKey);
            if (!is_array($claims) || ($claims['aud'] ?? '') !== get_home_url()) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational failure reporting, not debug output; credentials are never logged.
                error_log('Live2D refreshed token failed verification');
                return false;
            }
            $this->userInfo["sign"] = $token;
            $this->userInfo['userLevel'] = (int) ($claims['http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata'] ?? 0);
            $this->userInfo['role'] = (int) ($claims['role'] ?? 0);
            $this->userInfo['hosts'] = $claims['aud'];
            update_option('live_2d_settings_user_token', $this->userInfo);
            return $token;
        } catch (RuntimeException $error) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational failure; no credentials or provider response are logged.
            error_log('Live2D token renewal failed.');
            return false;
        }
    }

    /**
     * V1 模型清单(对齐 Chromium 扩展 v1Catalog.ts 中的 V1_CATALOG)。
     *  - name:与 download.live2dweb.com/model/{name}.zip 路由一致,
     *         也是 *.textures.json 的文件前缀,严禁改写。
     *  - label:UI 显示名。
     *  - texturesJson:扩展自带 assets/v1/{name}.textures.json 时为 true。
     *  - thumbnail:相对插件 assets/ 的预览图路径。
     *  - skins:「1 ID 多皮肤」型(子目录区分独立模型)的可选项,与 texturesJson 互斥。
     */
    public static function GetV1Catalog()
    {
        return array(
            array('name' => 'bilibili-live_22',       'label' => 'Bilibili 22 娘',                'texturesJson' => true,  'thumbnail' => 'v1/imgs/bilibili-live_22.png'),
            array('name' => 'bilibili-live_33',       'label' => 'Bilibili 33 娘',                'texturesJson' => true,  'thumbnail' => 'v1/imgs/bilibili-live_33.png'),
            array('name' => 'Potion-Maker_Pio',       'label' => 'Potion Maker · Pio',            'texturesJson' => true,  'thumbnail' => 'v1/imgs/Potion-Maker_Pio.png'),
            array('name' => 'Potion-Maker_Tia',       'label' => 'Potion Maker · Tia',            'texturesJson' => true,  'thumbnail' => 'v1/imgs/Potion-Maker_Tia.png'),
            array(
                'name' => 'HyperdimensionNeptunia',
                'label' => '超次元海莉娜 (Hyperdimension Neptunia)',
                'texturesJson' => false,
                'thumbnail' => 'v1/imgs/HyperdimensionNeptunia.png',
                'skins' => array(
                    'blanc_classic', 'blanc_normal', 'blanc_swimwear',
                    'general', 'histoire', 'histoirenohover',
                    'nepgear', 'nepgearswim', 'nepgear_extra',
                    'nepmaid', 'nepnep', 'nepswim',
                    'neptune_classic', 'neptune_santa',
                    'noir', 'noireswim', 'noir_classic', 'noir_santa',
                    'vert_classic', 'vert_normal', 'vert_swimwear',
                ),
            ),
            array(
                'name' => 'KantaiCollection',
                'label' => '舰これ (Kantai Collection)',
                'texturesJson' => false,
                'thumbnail' => 'v1/imgs/KantaiCollection.png',
                'skins' => array('murakumo'),
            ),
            array(
                'name' => 'ShizukuTalk',
                'label' => 'Shizuku Talk',
                'texturesJson' => false,
                'thumbnail' => 'v1/imgs/ShizukuTalk.png',
                'skins' => array('shizuku-48', 'shizuku-pajama'),
            ),
        );
    }

    /**
     * V1 模型下载(对齐 Chromium 扩展 v1ModelCache.ts 的 downloadAndCacheV1Model 行为):
     *   1. 由前端传入 catalog 中的 modelName,而不是 modelId,免去 Model/ModelInfo 一次往返。
     *   2. 直接 GET LIVE2D_V1_ZIP_BASE/{name}.zip,Bearer 用本地保存的 sign。
     *   3. zip 落到 LIVE2D_DOWNLOAD_DIR/{name}.zip,等前端再发 zip_model 调 OpenZip 解压。
     * 解压依旧由 PHP ZipArchive 处理,前端不做任何解压。
     */
    public function DownloadV1Model()
    {
        $this->verify_admin_ajax('live2d_shop_action');
        check_ajax_referer('live2d_shop_action', '_wpnonce');
        $rawName = isset($_POST['modelName']) && is_string($_POST['modelName']) ? sanitize_text_field(wp_unslash($_POST['modelName'])) : '';
        $modelName = $this->ResolveV1ModelName($rawName);
        if ($modelName === '') {
            wp_send_json(array('errorCode' => 9400, 'errorMsg' => '未知的 V1 模型名'));
            return;
        }
        if (empty($this->userInfo['sign'])) {
            wp_send_json(array('errorCode' => 9500, 'errorMsg' => '没有登录信息'));
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational failure reporting, not debug output; credentials are never logged.
            error_log('DownloadV1Model:[9500]没有登录信息');
            return;
        }
        if (!file_exists(LIVE2D_DOWNLOAD_DIR) && !wp_mkdir_p(LIVE2D_DOWNLOAD_DIR)) {
            wp_send_json(array('errorCode' => 9500, 'errorMsg' => '文件夹创建失败'));
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational failure reporting, not debug output; credentials are never logged.
            error_log('DownloadV1Model:[9500]文件夹创建失败');
            return;
        }

        $fileUrl = LIVE2D_V1_ZIP_BASE . '/' . rawurlencode($modelName) . '.zip';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        $tmpfname = wp_tempnam($fileUrl);
        if (!$tmpfname) {
            wp_send_json_error(array('errorCode' => 500, 'errorMsg' => 'Cannot create download temporary file.'), 500);
        }

        // 鉴权要求(对照 wp-live2d-api Live2dJwtBearerEvents.TokenValidated):
        //   1) Authorization: Bearer {sign} —— sign 是 ApiKey 自身做密钥的 HS256 JWT。
        //   2) Origin 或 Referer 的 host 必须等于 sign.aud 的 host。
        //   3) COS (download.live2dweb.com) 走回源鉴权/防盗链,会根据 Referer 决定是否放行。
        //      老版本 DownloadModel 能下成功,headers 与之保持一致(Authorization + Origin + Referer + UA + Accept)。
        $homeUrl = trailingslashit(get_home_url());
        $headers = array(
            'Authorization' => 'Bearer ' . $this->userInfo['sign'],
            'Origin'        => get_home_url(),
            'Referer'       => $homeUrl,
            'User-Agent'    => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36',
            'Accept'        => '*/*',
        );

        $response = wp_safe_remote_get($fileUrl, array(
            'headers'  => $headers,
            'timeout'  => 300,
            'redirection' => 0,
            'sslverify' => true,
            'limit_response_size' => 100 * 1024 * 1024 + 1,
            'stream'   => true,
            'filename' => $tmpfname,
        ));

        if (is_wp_error($response)) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Delete only a validated model file or a generated temporary file.
            @unlink($tmpfname);
            wp_send_json(array('errorCode' => 500, 'errorMsg' => $response->get_error_message()));
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational failure reporting, not debug output; credentials are never logged.
            error_log('DownloadV1Model:[请求失败: ' . $response->get_error_message() . ']');
            return;
        }
        $httpCode = wp_remote_retrieve_response_code($response);
        clearstatcache(true, $tmpfname);
        if (filesize($tmpfname) > 100 * 1024 * 1024) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Delete only a validated model file or a generated temporary file.
            unlink($tmpfname);
            wp_send_json_error(array('errorCode' => 413, 'errorMsg' => 'Model archive exceeds 100 MB.'), 413);
        }
        if ($httpCode === 401 || $httpCode === 403) {
            // stream=true 时 body 不在 response,只有响应头;手工读临时文件首段查看错误说明(如有)
            // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Delete only a validated model file or a generated temporary file.
            @unlink($tmpfname);
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational failure reporting, not debug output; credentials are never logged.
            error_log('DownloadV1Model: authorization failed, HTTP ' . $httpCode);
            wp_send_json(array(
                'errorCode' => $httpCode,
                'errorMsg'  => '服务器未授权此访问 (HTTP ' . $httpCode . '),详情见 WP error_log',
            ));
            return;
        }
        if ($httpCode !== 200) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Delete only a validated model file or a generated temporary file.
            @unlink($tmpfname);
            wp_send_json(array('errorCode' => $httpCode, 'errorMsg' => '下载失败 HTTP ' . $httpCode));
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational failure reporting, not debug output; credentials are never logged.
            error_log('DownloadV1Model:[请求失败: HTTP ' . $httpCode . ']');
            return;
        }

        // 写到 model/{name}.zip,与 OpenZip 的入参约定保持一致(zip 同名 + 解压目录同名)。
        $sanfilename = sanitize_file_name($modelName . '.zip');
        $targetPath  = LIVE2D_DOWNLOAD_DIR . $sanfilename;
        // PHP rename() 跨文件系统/挂载点会失败(临时文件常在 /tmp,目标在 plugins/),
        // 失败时退回到 copy + unlink,作为最后兜底。
        // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- Publish a validated local model atomically on the same filesystem.
        $renamed = @rename($tmpfname, $targetPath);
        if (!$renamed) {
            $copyOk = @copy($tmpfname, $targetPath);
            // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Delete only a validated model file or a generated temporary file.
            @unlink($tmpfname);
            if (!$copyOk) {
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable -- Read-only diagnostics for the local extraction directory.
                $perm = is_writable(LIVE2D_DOWNLOAD_DIR) ? 'writable' : 'NOT writable';
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational failure reporting, not debug output; credentials are never logged.
                error_log('DownloadV1Model:[9501]保存文件失败 target=' . $targetPath
                    . ' tmp=' . $tmpfname
                    . ' tmp_exists=' . (file_exists($tmpfname) ? '1' : '0')
                    . ' dir=' . LIVE2D_DOWNLOAD_DIR . '(' . $perm . ')');
                wp_send_json(array('errorCode' => 9501, 'errorMsg' => '保存文件失败,请确认 ' . LIVE2D_DOWNLOAD_DIR . ' 可写'));
                return;
            }
        }
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod -- Set permissions on a validated local model file.
        @chmod($targetPath, 0644);

        wp_send_json(array(
            'errorCode' => 200,
            'fileName'  => $sanfilename,
            'modelName' => $modelName,
        ));
    }

    /**
     * 把前端传入的 modelName 校验为 V1 catalog 中的合法条目,防止任意 URL 拼接。
     * 通过则返回 catalog 中的精确 name(大小写敏感),否则返回空串。
     */
    private function ResolveV1ModelName($candidate)
    {
        $candidate = is_string($candidate) ? trim($candidate) : '';
        if ($candidate === '') {
            return '';
        }
        foreach (self::GetV1Catalog() as $item) {
            if ($item['name'] === $candidate) {
                return $item['name'];
            }
        }
        return '';
    }

    /**
     * 设置页 modelId 下拉框数据源。
     * 原本走 Model/List 以远程 API，现在对齐 Chromium 扩展直接返回本地 V1 catalog:
     *   id   = catalog.name (与下载路由 / 本地目录 / textures.json 前缀一致)
     *   name = catalog.label (UI 显示名)
     *
     * 只回传已下载并解压到 LIVE2D_DOWNLOAD_DIR/{name}/ 的条目,避免用户在下拉框里
     * 选到尚未下载的模型,导致前端取不到 model.json/textures。判断标准与
     * live2d_Shop 卡片上"已启用"完全一致(sanitize_file_name + is_dir)。
     */
    public function GetModelMotions()
    {
        $this->verify_admin_ajax('live2d_shop_action');
        check_ajax_referer('live2d_shop_action', '_wpnonce');
        $list = array();
        foreach (self::GetV1Catalog() as $item) {
            $sanName     = sanitize_file_name($item['name']);
            $extractPath = LIVE2D_DOWNLOAD_DIR . $sanName;
            if (!is_dir($extractPath)) {
                continue;
            }
            $list[] = array(
                'id'   => $item['name'],
                'name' => $item['label'],
            );
        }
        wp_send_json($list);
    }

    /**
     * 设置页 modelTexturesId 下拉框数据源。读本地 assets/v1/{name}.textures.json 或 catalog.skins:
     *  - skins 优先(多皮肤型)
     *  - texturesJson=true 时读三方 JSON，每项是单个贴图或一组贴图文件名的数组
     *  - 都没有时只返 #0
     */
    public function GetTextureList()
    {
        $this->verify_admin_ajax('live2d_shop_action');
        check_ajax_referer('live2d_shop_action', '_wpnonce');
        $candidate = isset($_POST['modelId']) && is_string($_POST['modelId']) ? sanitize_text_field(wp_unslash($_POST['modelId'])) : '';
        $modelName = $this->ResolveV1ModelName($candidate);
        if ($modelName === '') {
            wp_send_json(array());
            return;
        }
        $item = null;
        foreach (self::GetV1Catalog() as $c) {
            if ($c['name'] === $modelName) { $item = $c; break; }
        }
        $list = array();
        if (!empty($item['skins'])) {
            foreach ($item['skins'] as $i => $skin) {
                $list[] = array('id' => $i, 'name' => '#' . $i . ' · ' . $skin);
            }
        } elseif (!empty($item['texturesJson'])) {
            // 用 dirname(__DIR__) 直接定位插件根,绕开 plugin_dir_path 在某些
            // 环境(符号链接 / mu-plugins / Windows 路径)里拼不到插件根的边界情况
            $jsonPath = dirname(__DIR__) . '/assets/v1/' . $modelName . '.textures.json';
            $exists   = file_exists($jsonPath);
            if ($exists) {
                $raw  = file_get_contents($jsonPath);
                // 去掉 UTF-8 BOM (EF BB BF) 与首尾空白,
                // 否则 PHP json_decode 会以 "Syntax error" 失败
                if (strncmp($raw, "\xEF\xBB\xBF", 3) === 0) {
                    $raw = substr($raw, 3);
                }
                $raw  = trim($raw);
                $data = json_decode($raw, true);
                if (is_array($data)) {
                    foreach ($data as $i => $entry) {
                        $list[] = array('id' => $i, 'name' => self::DescribeTextureEntry($entry, $i));
                    }
                } else {
                    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational failure reporting, not debug output; credentials are never logged.
                    error_log('GetTextureList:[json_decode 失败] modelName=' . $modelName
                        . ' json_last_error=' . json_last_error_msg());
                }
            }
        }
        if (empty($list)) {
            $list[] = array('id' => 0, 'name' => '#0');
        }
        wp_send_json($list);
    }

    /**
     * 对齐 Chromium 扩展 v1Catalog.ts 中的 describeTextureEntry:
     * 数组 -> 取每项 basename 用 + 连起来(超长截断);字符串 -> 取 basename。
     */
    private static function DescribeTextureEntry($entry, $index)
    {
        $baseOf = function ($s) {
            $name = preg_replace('#\.[^./\\\\]+$#', '', basename((string)$s));
            return preg_replace('#-costume$#i', '', $name);
        };
        if (is_array($entry)) {
            if (count($entry) === 0) return '#' . $index;
            $joined = implode(' + ', array_map($baseOf, $entry));
            if (mb_strlen($joined) > 60) {
                $joined = mb_substr($joined, 0, 57) . '…';
            }
            return '#' . $index . ' · ' . $joined;
        }
        return '#' . $index . ' · ' . $baseOf($entry);
    }

    /**
     * 对ZIP进行解压缩
     *
     * 优先用 WordPress 内置的 unzip_file()(wp-admin/includes/file.php),它会:
     *   - 若环境有 ext-zip 则走 ZipArchive,否则回落到随 WP 自带的纯 PHP 库 PclZip;
     *   - 走 WP_Filesystem 写盘,失败时返回 WP_Error(有 code/message/data),信息更完整;
     *   - 自带 zip slip 防护、自动跳过 __MACOSX/.DS_Store、按需建中间目录。
     * 如果 WP_Filesystem 拿不到 direct 模式(罕见,例如纯 FTP 站点),回落到原本的
     * ZipArchive 直接调用,保证旧行为不退化。
     *
     * 失败时除了 echo 0(协议保持不变),会把所有可定位"为什么失败"的诊断信息写进 error_log:
     *   - zip 路径/大小/权限位/属主、解压目标目录是否可写
     *   - PHP 进程的 euid/egid + 用户名(POSIX 可用时);常见现象:
     *       * 文件由 ubuntu 用户手动放进去,umask 027/077 导致 www 读不到
     *       * 目标目录属主非 www 且无 group/other 写权限
     *   - WP_Error code/message,或 ZipArchive::open() 的真实返回码(int)
     * 注意:权限/不存在类错误故意 *不* 删除 zip,保留现场让运维 ls -l 查证;
     *      仅在确认是 ZIP 内容损坏(ER_NOZIP/ER_INCONS/ER_CRC 等)或越权条目时才删。
     */
    public function OpenZip()
    {
        $this->verify_admin_ajax('live2d_shop_action');
        check_ajax_referer('live2d_shop_action', '_wpnonce');
        $sanfilename = isset($_POST['fileName']) && is_string($_POST['fileName']) ? sanitize_file_name(wp_unslash($_POST['fileName'])) : '';
        if ($sanfilename === '' || strtolower(pathinfo($sanfilename, PATHINFO_EXTENSION)) !== 'zip') {
            wp_die('0', '', array('response' => 400));
        }
        $zipFile = LIVE2D_DOWNLOAD_DIR . $sanfilename;
        $zipFileName = pathinfo($zipFile, PATHINFO_FILENAME);
        $extractTarget = LIVE2D_DOWNLOAD_DIR . $zipFileName;
        $archiveCheck = $this->validate_v1_archive($zipFile, $extractTarget);
        if (is_wp_error($archiveCheck)) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational failure reporting, not debug output; credentials are never logged.
            error_log('Live2D archive rejected: ' . $archiveCheck->get_error_message());
            wp_die('0', '', array('response' => 400));
        }

        // 1) 首选:WP 官方 unzip_file()
        $wpResult = $this->TryUnzipWithWpFilesystem($zipFile, $extractTarget);
        if ($wpResult === 'ok') {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Delete only a validated model file or a generated temporary file.
            @unlink($zipFile);
            // 必须 wp_die,不然 admin-ajax.php 会追加 "0",前端拿到 "10" 会被判失败
            wp_die('1', '', array('response' => null));
        }
        if ($wpResult === 'fail') {
            // unzip_file 已识别这是错误(不是"环境不可用"),日志已写;不要再用 ZipArchive 试一遍
            wp_die('0', '', array('response' => null));
        }
        // $wpResult === 'unavailable' —— WP_Filesystem direct 模式拿不到,走兜底

        // 2) 兜底:ZipArchive 直接调用
        $zip = new ZipArchive;
        $res = $zip->open($zipFile);
        if ($res === TRUE) {
            // 防 zip slip: 逐条校验条目, 任一跳出目录则拒绝解压
            for ($i = 0, $n = $zip->numFiles; $i < $n; $i++) {
                $entryName = $zip->getNameIndex($i);
                if ($entryName === false) {
                    $zip->close();
                    // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Delete only a validated model file or a generated temporary file.
                    @unlink($zipFile);
                    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational failure reporting, not debug output; credentials are never logged.
                    error_log('OpenZip:[读取 zip 条目失败] file=' . $zipFile);
                    wp_die('0', '', array('response' => null));
                }
                if (strpos($entryName, '..') !== false || strpos($entryName, '/..') !== false || preg_match('#^([a-zA-Z]:)?[\\/]#', $entryName)) {
                    $zip->close();
                    // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Delete only a validated model file or a generated temporary file.
                    @unlink($zipFile);
                    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational failure reporting, not debug output; credentials are never logged.
                    error_log('OpenZip:[检测到非法条目路径: ' . $entryName . '] file=' . $zipFile);
                    wp_die('0', '', array('response' => null));
                }
            }
            $extractRes = $zip->extractTo($extractTarget);
            $zip->close();
            if ($extractRes !== true) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational failure reporting, not debug output; credentials are never logged.
                error_log('OpenZip:[ZipArchive::extractTo 失败] ' . self::DescribeFsContext($zipFile, $extractTarget));
                // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Delete only a validated model file or a generated temporary file.
                @unlink($zipFile);
                wp_die('0', '', array('response' => null));
            }
            // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Delete only a validated model file or a generated temporary file.
            @unlink($zipFile);
            wp_die('1', '', array('response' => null));
        } else {
            $errCode = is_int($res) ? $res : -1;
            $errLabel = self::ZipOpenErrorLabel($errCode);
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational failure reporting, not debug output; credentials are never logged.
            error_log('OpenZip:[ZipArchive::open 失败] code=' . $errCode . '(' . $errLabel . ') ' . self::DescribeFsContext($zipFile, $extractTarget));
            if (in_array($errCode, array(ZipArchive::ER_NOZIP, ZipArchive::ER_INCONS, ZipArchive::ER_CRC), true)) {
                // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Delete only a validated model file or a generated temporary file.
                @unlink($zipFile);
            }
            wp_die('0', '', array('response' => null));
        }
    }

    /** 两种解压后端之前统一检查条目及内容,避免 ZIP 中的可执行文件落入站点。 */
    private function validate_v1_archive($zipFile, $target)
    {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        $root = realpath(LIVE2D_DOWNLOAD_DIR);
        $realZip = realpath($zipFile);
        if ($root === false || $realZip === false || is_link($zipFile)
            || strpos(wp_normalize_path($realZip), trailingslashit(wp_normalize_path($root))) !== 0
            || is_link($target) || is_link(rtrim(LIVE2D_DOWNLOAD_DIR, '/\\'))) {
            return new WP_Error('invalid_archive_path', 'Invalid model archive path.');
        }
        if (!wp_mkdir_p($target)) return new WP_Error('mkdir_failed', 'Cannot create model directory.');
        $zip = null;
        $entries = array();
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($realZip) !== true) return new WP_Error('invalid_archive', 'Cannot open model archive.');
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->statIndex($i);
                if ($entry === false) {
                    $zip->close();
                    return new WP_Error('invalid_archive', 'Cannot read archive entry.');
                }
                $opsys = $attributes = 0;
                $zip->getExternalAttributesIndex($i, $opsys, $attributes);
                $entries[] = array('name' => $entry['name'], 'size' => $entry['size'], 'link' => (($attributes >> 16) & 0170000) === 0120000);
            }
        } else {
            require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';
            $pclzip = new PclZip($realZip);
            $list = $pclzip->listContent();
            if (!is_array($list)) return new WP_Error('invalid_archive', 'Cannot list model archive.');
            foreach ($list as $entry) $entries[] = array('name' => $entry['filename'], 'size' => $entry['size'], 'link' => false);
        }
        $total = 0;
        try {
            if (count($entries) > 5000) return new WP_Error('archive_too_large', 'Too many model archive entries.');
            foreach ($entries as $i => $entry) {
                $directory = substr($entry['name'], -1) === '/';
                $relative = $directory ? $entry['name'] . 'live2d-check.json' : $entry['name'];
                $validationName = $relative;
                if (in_array(strtoupper(basename($relative)), array('LICENSE', 'README', 'COPYING'), true)) $validationName .= '.txt';
                $valid = live2d_ModelFiles::validate_relative_file($validationName);
                if (is_wp_error($valid) || $entry['link']) return new WP_Error('unsafe_archive', 'Unsafe file in model archive.');
                $dest = live2d_ModelFiles::confined_path($target, $validationName, true);
                if (is_wp_error($dest) || is_link($target . '/' . $relative)) return new WP_Error('unsafe_archive', 'Unsafe model destination.');
                if ($directory) continue;
                $total += $entry['size'];
                if ($entry['size'] > 50 * 1024 * 1024 || $total > 512 * 1024 * 1024) {
                    return new WP_Error('archive_too_large', 'Model archive exceeds size limits.');
                }
                if ($zip) {
                    $bytes = $zip->getFromIndex($i, 50 * 1024 * 1024 + 1);
                } else {
                    $extracted = $pclzip->extract(PCLZIP_OPT_BY_INDEX, $i, PCLZIP_OPT_EXTRACT_AS_STRING);
                    $bytes = is_array($extracted) ? ($extracted[0]['content'] ?? false) : false;
                }
                if (!is_string($bytes) || strlen($bytes) !== (int) $entry['size']) return new WP_Error('invalid_archive', 'Invalid archive entry size.');
                $tmp = wp_tempnam('live2d-archive');
                if (!$tmp) return new WP_Error('temp_failed', 'Cannot create validation file.');
                try {
                    if (file_put_contents($tmp, $bytes) !== strlen($bytes)) return new WP_Error('write_failed', 'Cannot validate archive entry.');
                    $valid = live2d_ModelFiles::validate_file_content($tmp, $validationName);
                    if (is_wp_error($valid)) return $valid;
                } finally {
                    // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Delete only a validated model file or a generated temporary file.
                    unlink($tmp);
                }
            }
            return true;
        } finally {
            if ($zip) $zip->close();
        }
    }

    /**
     * 用 WP 官方 unzip_file() 解压。
     * 返回三态字符串:
     *   'ok'          —— 解压成功
     *   'fail'        —— unzip_file 明确失败,日志已写,zip 已按错误类型决定是否删除;
     *                    调用方应直接 echo 0,不要再回落,因为同一个文件用 ZipArchive 大概率
     *                    遇到完全相同的失败原因(权限/损坏),再来一遍只会多一份日志。
     *   'unavailable' —— WP 核心函数缺失或 WP_Filesystem direct 模式拿不到;调用方回落到
     *                    ZipArchive 直接调用。
     */
    private function TryUnzipWithWpFilesystem($zipFile, $extractTarget)
    {
        if (!function_exists('unzip_file') || !function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        if (!function_exists('unzip_file') || !function_exists('WP_Filesystem')) {
            return 'unavailable';
        }
        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            // 第三个参数 true 强制 direct,避免在某些环境弹出 FTP 凭据表单
            if (!WP_Filesystem(false, false, true)) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational failure reporting, not debug output; credentials are never logged.
                error_log('OpenZip:[WP_Filesystem 初始化失败,回落到 ZipArchive] ' . self::DescribeFsContext($zipFile, $extractTarget));
                return 'unavailable';
            }
        }
        // unzip_file 内部已做 zip slip 防护、跳过 __MACOSX、按需创建目标目录;
        // 在 ext-zip 不可用时会自动用纯 PHP 的 PclZip(随 WP 自带)
        $result = unzip_file($zipFile, $extractTarget);
        if ($result === true) {
            return 'ok';
        }
        if (is_wp_error($result)) {
            $code = $result->get_error_code();
            $msg  = $result->get_error_message();
            $data = $result->get_error_data();
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational failure reporting, not debug output; credentials are never logged.
            error_log('OpenZip:[unzip_file 失败] code=' . $code
                . ' msg=' . $msg
                . ' data=' . (is_scalar($data) ? (string)$data : wp_json_encode($data))
                . ' ' . self::DescribeFsContext($zipFile, $extractTarget));
            // 仅在确认是 zip 损坏/空包时才删,权限/磁盘类保留现场
            if (in_array($code, array('incompatible_archive', 'empty_archive_pclzip', 'empty_archive_ziparchive', 'corrupt_zip'), true)) {
                // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Delete only a validated model file or a generated temporary file.
                @unlink($zipFile);
            }
        } else {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational failure reporting, not debug output; credentials are never logged.
            error_log('OpenZip: unexpected unzip result, ' . self::DescribeFsContext($zipFile, $extractTarget));
        }
        return 'fail';
    }


    /**
     * 把 ZipArchive::open() 的 int 返回码翻成可读名字。
     * 常量值见 https://www.php.net/manual/en/class.ziparchive.php
     */
    private static function ZipOpenErrorLabel($code)
    {
        static $map = null;
        if ($map === null) {
            $map = array(
                ZipArchive::ER_OK      => 'ER_OK',
                ZipArchive::ER_MULTIDISK => 'ER_MULTIDISK',
                ZipArchive::ER_RENAME  => 'ER_RENAME',
                ZipArchive::ER_CLOSE   => 'ER_CLOSE',
                ZipArchive::ER_SEEK    => 'ER_SEEK',
                ZipArchive::ER_READ    => 'ER_READ/读失败(常见:权限不足)',
                ZipArchive::ER_WRITE   => 'ER_WRITE',
                ZipArchive::ER_CRC     => 'ER_CRC/校验失败(zip 损坏)',
                ZipArchive::ER_ZIPCLOSED => 'ER_ZIPCLOSED',
                ZipArchive::ER_NOENT   => 'ER_NOENT/文件不存在',
                ZipArchive::ER_EXISTS  => 'ER_EXISTS',
                ZipArchive::ER_OPEN    => 'ER_OPEN/打开失败(常见:权限不足或被占用)',
                ZipArchive::ER_TMPOPEN => 'ER_TMPOPEN',
                ZipArchive::ER_ZLIB    => 'ER_ZLIB',
                ZipArchive::ER_MEMORY  => 'ER_MEMORY',
                ZipArchive::ER_CHANGED => 'ER_CHANGED',
                ZipArchive::ER_COMPNOTSUPP => 'ER_COMPNOTSUPP',
                ZipArchive::ER_EOF     => 'ER_EOF/文件被截断(下载未完成?)',
                ZipArchive::ER_INVAL   => 'ER_INVAL',
                ZipArchive::ER_NOZIP   => 'ER_NOZIP/不是 zip 格式(很可能是 HTML/XML 错误页)',
                ZipArchive::ER_INTERNAL => 'ER_INTERNAL',
                ZipArchive::ER_INCONS  => 'ER_INCONS/zip 不一致(损坏)',
                ZipArchive::ER_REMOVE  => 'ER_REMOVE',
                ZipArchive::ER_DELETED => 'ER_DELETED',
            );
        }
        return isset($map[$code]) ? $map[$code] : ('UNKNOWN(' . $code . ')');
    }

    /**
     * 把"PHP 这一刻看到的文件系统状态"打成一行字,集中诊断 own/perm/size 类问题。
     */
    private static function DescribeFsContext($zipFile, $extractTarget)
    {
        $exists = file_exists($zipFile);
        $size = $exists ? @filesize($zipFile) : -1;
        $readable = $exists && is_readable($zipFile);
        $perms = $exists ? substr(sprintf('%o', @fileperms($zipFile)), -4) : '----';
        $owner = '?';
        $group = '?';
        if ($exists && function_exists('fileowner')) {
            $uid = @fileowner($zipFile);
            $gid = @filegroup($zipFile);
            if (function_exists('posix_getpwuid')) {
                $pw = $uid !== false ? @posix_getpwuid($uid) : false;
                $gr = $gid !== false ? @posix_getgrgid($gid) : false;
                $owner = ($pw && isset($pw['name'])) ? $pw['name'] . '(' . $uid . ')' : (string)$uid;
                $group = ($gr && isset($gr['name'])) ? $gr['name'] . '(' . $gid . ')' : (string)$gid;
            } else {
                $owner = (string)$uid;
                $group = (string)$gid;
            }
        }
        $proc = '?';
        if (function_exists('posix_geteuid')) {
            $euid = @posix_geteuid();
            $egid = function_exists('posix_getegid') ? @posix_getegid() : -1;
            if (function_exists('posix_getpwuid')) {
                $pw = @posix_getpwuid($euid);
                $proc = ($pw && isset($pw['name'])) ? $pw['name'] . '(' . $euid . '/' . $egid . ')' : ($euid . '/' . $egid);
            } else {
                $proc = $euid . '/' . $egid;
            }
        } elseif (function_exists('get_current_user')) {
            $proc = (string) get_current_user();
        }
        $dir = dirname($zipFile);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable -- Read-only diagnostics for the local extraction directory.
        $dirWritable = is_dir($dir) && is_writable($dir);
        $dirPerms = is_dir($dir) ? substr(sprintf('%o', @fileperms($dir)), -4) : '----';
        $extDirExists = is_dir($extractTarget);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable -- Read-only diagnostics for the local extraction directory.
        $extDirWritable = $extDirExists ? is_writable($extractTarget) : is_writable($dir);
        return 'file=' . $zipFile
            . ' exists=' . ($exists ? '1' : '0')
            . ' size=' . $size
            . ' readable=' . ($readable ? '1' : '0')
            . ' perms=' . $perms
            . ' owner=' . $owner
            . ' group=' . $group
            . ' proc_user=' . $proc
            . ' dir=' . $dir
            . ' dir_perms=' . $dirPerms
            . ' dir_writable=' . ($dirWritable ? '1' : '0')
            . ' extract_target=' . $extractTarget
            . ' extract_target_exists=' . ($extDirExists ? '1' : '0')
            . ' extract_target_writable=' . ($extDirWritable ? '1' : '0');
    }

    /**
     * 清理文件: 用户如果没有下载成功, 会下载一个XML是.ZIP格式的, 需要给它清除
     * 执行此方法可以清除
     */
    public function ClearFiles()
    {
        $this->verify_admin_ajax('live2d_shop_action');
        check_ajax_referer('live2d_shop_action', '_wpnonce');
        if (!current_user_can('manage_options')) {
            wp_die('0', '', array('response' => null));
        }
        $sanfilename = isset($_POST['fileName']) && is_string($_POST['fileName']) ? sanitize_file_name(wp_unslash($_POST['fileName'])) : '';
        if ($sanfilename === '') {
            wp_die('0', '', array('response' => 400));
        }
        $filePath = LIVE2D_DOWNLOAD_DIR . $sanfilename;
        // 确保路径仍位于下载目录之内
        $realDir = realpath(LIVE2D_DOWNLOAD_DIR);
        $realFile = realpath($filePath);
        if ($realDir === false || $realFile === false || strpos(wp_normalize_path($realFile), trailingslashit(wp_normalize_path($realDir))) !== 0) {
            wp_die('0', '', array('response' => null));
        }
        if (file_exists($realFile)) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Delete only a validated model file or a generated temporary file.
            unlink($realFile);
            wp_die('1', '', array('response' => null));
        }
        // 文件本来就不在,从前端视角等同于"已清理",仍返回 1 也合理;
        // 但保留旧语义返回 0,以免破坏调用方对"是否真的删过"的依赖
        wp_die('0', '', array('response' => null));
    }

    /**
     * 后台 AJAX 权限校验;各入口直接校验其操作 nonce。
     */
    private function verify_admin_ajax($action)
    {
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational failure reporting, not debug output; credentials are never logged.
            error_log('verify_admin_ajax:[FAIL/cap] action=' . $action
                . ' user_id=' . get_current_user_id()
                . ' logged_in=' . (is_user_logged_in() ? '1' : '0')
                . ' can_manage=' . (current_user_can('manage_options') ? '1' : '0'));
            wp_send_json_error(array('errorCode' => 403, 'errorMsg' => 'forbidden'), 403);
        }
    }

    //排查错误使用
    public function Save_Options($value)
    {
        // 已废弃: 不再向后端 /Options/UpdateOpt 同步设置
        unset($value);
    }

    public function JwtDecode($jwt, $key)
    {
        try {
            $setArr = (array)JWT::decode($jwt, new Key($key, 'HS256'));
            if (($setArr['aud'] ?? '') !== get_home_url()) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational failure reporting, not debug output; credentials are never logged.
                error_log('Live2D token audience mismatch');
                return false;
            }
            return $setArr;
        } catch (ExpiredException $e) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational failure reporting, not debug output; credentials are never logged.
            error_log("Token 已过期: " . $e->getMessage());
            return 0;
        } catch (Exception $e) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational failure reporting, not debug output; credentials are never logged.
            error_log('Live2D JWT signature verification failed');
            return false;
        }
    }

    public function JwtEncode()
    {
        $key = $this->userInfo['key'];
        $issuedAt = time(); // 当前时间作为 iat
        $notBefore = $issuedAt; // JWT 立即生效
        $expire = $issuedAt + 7200; // 1 小时后过期
        $payload = [
            'iss' => LIVE2D_DOMAIN,
            'aud' => get_home_url(),
            'iat' => $issuedAt,
            'nbf' => $notBefore,
            'exp' => $expire,
            'email' => $this->userInfo["userName"],
            'role' => intval($this->userInfo["role"]),
            'certserialnumber' => intval($this->userInfo["certserialnumber"]),
            'http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata' => intval($this->userInfo["userLevel"]),
        ];
        $jwt = JWT::encode($payload, $key, 'HS256');
        return $jwt;
    }

    public function GetToken($key)
    {
        if (empty($key)) {
            $key = $this->userInfo["key"];
        }
        $bare_url = LIVE2D_API_URL . "/Verify/GetToken";
        $param = ['key' => $key];
        $complete_url = add_query_arg($param, $bare_url);
        $response = wp_remote_get($complete_url, array(
            'redirection' => 0,
            'sslverify' => true,
            'headers' => array(
                'referer' => get_home_url()
            )
        ));
        if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) {
            throw new RuntimeException('Live2D account verification failed.');
        }
        $body = wp_remote_retrieve_body($response);
        if ($body === '') throw new RuntimeException('Live2D account verification returned an empty response.');
        return $body;
    }

    public function DoPost($param, $api_name, $jwt)
    {
        $complete_url = LIVE2D_API_URL . "/" . $api_name;
        $response = wp_remote_post($complete_url, array(
            'body' => $param,
            'headers' => array(
                'Authorization' => 'Bearer ' . $jwt,
                'Origin' =>  get_home_url(),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
        ));

        if (is_wp_error($response)) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational failure reporting, not debug output; credentials are never logged.
            error_log('Live2D POST request failed: ' . $response->get_error_message());
            return array(
                'errorCode' => 500,
                'errorMsg'  => $response->get_error_message(),
            );
        }
        $httpCode = wp_remote_retrieve_response_code($response);
        if ($httpCode === 401 || $httpCode === 403) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational failure reporting, not debug output; credentials are never logged.
            error_log('Live2D POST authorization failed: ' . $httpCode);
            return array(
                'errorCode' => $httpCode,
                'errorMsg' => '登录不正确, 请检查是否和域名匹配'
            );
        }
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    public function DoGet($param, $api_name, $jwt)
    {
        $bare_url = LIVE2D_API_URL . "/" . $api_name . "/";

        if (!empty($param)) {
            $bare_url = add_query_arg($param, $bare_url);
        }

        $response = wp_remote_get($bare_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $jwt,
                'Origin' =>  get_home_url(),
            ),
        ));

        if (is_wp_error($response)) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational failure reporting, not debug output; credentials are never logged.
            error_log('Live2D GET request failed: ' . $response->get_error_message());
            return array(
                'errorCode' => 500,
                'errorMsg'  => $response->get_error_message(),
            );
        }
        $httpCode = wp_remote_retrieve_response_code($response);
        if ($httpCode === 401 || $httpCode === 403) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational failure reporting, not debug output; credentials are never logged.
            error_log('Live2D GET authorization failed: ' . $httpCode);
            return array(
                'errorCode' => $httpCode,
                'errorMsg' => '登录不正确, 请检查是否和域名匹配'
            );
        }
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
}
