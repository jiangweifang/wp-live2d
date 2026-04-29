<?php
if (!defined('ABSPATH')) {
    exit; // 阻止直接访问
}
require_once(ABSPATH . 'wp-admin/includes/plugin.php');
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

$dir = explode('/', plugin_dir_url(dirname(__FILE__)));
$dir_len = count($dir);
define('IS_PLUGIN_ACTIVE', is_plugin_active($dir[$dir_len - 2] . "/wordpress-live2d.php")); //补丁启用
define('API_URL', "https://api.live2dweb.com"); //API地址
define('DOMAIN', "https://www.live2dweb.com"); //API地址
define('DOWNLOAD_DIR', plugin_dir_path(dirname(__FILE__)) . 'model/'); //服务器下载的路径
// V1 模型 zip 直链。对齐 Chromium 扩展 v1ModelCache.ts 的 ZIP_BASE:
//   GET http://download.live2dweb.com/model/{modelName}.zip,需要 Bearer {sign}
// 注意:下载域名是 http(不是 https),路径 model 全小写;与 api.live2dweb.com
// 不是同一个服务,不要随手改回 https 或 /Model。
define('LIVE2D_V1_ZIP_BASE', 'http://download.live2dweb.com/model');
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
    /**
     * 获取用户登录结果
     * 用这个方法可以通过key获取到token
     */
    public function user_login($request)
    {
        $homeUrl = get_home_url();
        $errCode = intval($request["errorCode"]);
        $sign = $request["sign"];
        $key = $request["key"];

        if (!empty($sign) && !empty($key) && $errCode === 200) {
            $signInfo = $this->JwtDecode($sign, $key);
            $userInfo = array();
            $userInfo["key"] = $key;
            $userInfo["sign"] = $sign;
            $userInfo["userName"] = $signInfo["email"];
            $userInfo["role"] = intval($signInfo["role"]);
            $userInfo["certserialnumber"] = intval($signInfo["certserialnumber"]);
            $userInfo["userLevel"] = intval($signInfo["http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata"]);
            $userInfo["errorCode"] = $errCode;
            $userInfo["hosts"] = $signInfo["aud"];
            if ($homeUrl == $signInfo["aud"] && IS_PLUGIN_ACTIVE) {
                update_option('live_2d_settings_user_token', $userInfo);
                echo "1";
                error_log('用户登录结果:true');
            } else {
                echo "0";
                error_log('用户登录结果:false');
            }
        } else {
            if ($errCode) {
                echo $errCode;
                error_log('用户登录结果:false ' . $errCode);
            } else {
                echo "1005";
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
        $signInfo = $this->JwtDecode($sign, $this->apiKey);
        if ($signInfo === 0) {
            $newToken = $this->get_refresh_token($sign);
            if (!$newToken) {
                status_header(403);
            } else {
                status_header(200);
                echo $newToken;
            }
            exit;
        } else {
            status_header(200);
            echo $sign;
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
            if (!$decoded) {
                status_header(403);
                exit;
            }

            if ($decoded === 0) {
                error_log('JS验证成功但是已经过期');
                $newToken = $this->get_refresh_token($token);
                if (!$newToken) {
                    error_log('JS验证失败Token获取时出错。');
                    status_header(403);
                } else {
                    error_log('JS验证成功已获取到新Token: ' . $newToken);
                    status_header(200);
                    echo $newToken;
                }
                exit;
            }

            if (is_array($decoded)) {
                error_log('JS验证成功 Token: ' . $token);
                status_header(200);
                echo $token;
            } else {
                error_log('JS验证失败 Token: ' . $token);
                status_header(403);
            }
        } else {
            error_log('JS验证收到不正确的请求。');
            status_header(400);
        }
    }

    private function get_refresh_token($sign)
    {
        $result = $this->DoGet(['key' => $this->apiKey], "Verify/RefreshToken", $sign);
        if ($result["errorCode"] == 200) {
            $token = $result["errorMsg"];
            $this->userInfo["sign"] = $token;
            update_option('live_2d_settings_user_token', $this->userInfo);
            error_log('verify_token:token: ' . $token);
            return $token;
        } else {
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
     *   3. zip 落到 DOWNLOAD_DIR/{name}.zip,等前端再发 zip_model 调 OpenZip 解压。
     * 解压依旧由 PHP ZipArchive 处理,前端不做任何解压。
     */
    public function DownloadV1Model()
    {
        $this->verify_admin_ajax('live2d_shop_action');
        $rawName = isset($_POST['modelName']) ? wp_unslash($_POST['modelName']) : '';
        $modelName = $this->ResolveV1ModelName($rawName);
        if ($modelName === '') {
            wp_send_json(array('errorCode' => 9400, 'errorMsg' => '未知的 V1 模型名'));
            return;
        }
        if (empty($this->userInfo['sign'])) {
            wp_send_json(array('errorCode' => 9500, 'errorMsg' => '没有登录信息'));
            error_log('DownloadV1Model:[9500]没有登录信息');
            return;
        }
        if (!file_exists(DOWNLOAD_DIR) && !mkdir(DOWNLOAD_DIR, 0777, true)) {
            wp_send_json(array('errorCode' => 9500, 'errorMsg' => '文件夹创建失败'));
            error_log('DownloadV1Model:[9500]文件夹创建失败');
            return;
        }

        $fileUrl = LIVE2D_V1_ZIP_BASE . '/' . rawurlencode($modelName) . '.zip';
        $tmpfname = wp_tempnam($fileUrl);
        error_log('DownloadV1Model:[开始下载 ' . $fileUrl . ']');
        error_log('DownloadV1Model:[Origin ' . get_home_url() . ']');
        error_log('DownloadV1Model:[临时文件 ' . $tmpfname . ']');

        $response = wp_safe_remote_get($fileUrl, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->userInfo['sign'],
                'Origin'        => get_home_url(),
                'Host'          => 'download.live2dweb.com',
                'User-Agent'    => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36',
                'Accept'        => '*/*',
                'Connection'    => 'keep-alive',
            ),
            'timeout'  => 300,
            'stream'   => true,
            'filename' => $tmpfname,
        ));

        if (is_wp_error($response)) {
            @unlink($tmpfname);
            wp_send_json(array('errorCode' => 500, 'errorMsg' => $response->get_error_message()));
            error_log('DownloadV1Model:[请求失败: ' . $response->get_error_message() . ']');
            return;
        }
        $httpCode = wp_remote_retrieve_response_code($response);
        if ($httpCode === 401 || $httpCode === 403) {
            @unlink($tmpfname);
            wp_send_json(array('errorCode' => $httpCode, 'errorMsg' => '服务器未授权此访问'));
            error_log('DownloadV1Model:[请求失败: ' . $httpCode . ']服务器未授权此访问');
            return;
        }
        if ($httpCode !== 200) {
            @unlink($tmpfname);
            wp_send_json(array('errorCode' => $httpCode, 'errorMsg' => '下载失败 HTTP ' . $httpCode));
            error_log('DownloadV1Model:[请求失败: HTTP ' . $httpCode . ']');
            return;
        }

        // 写到 model/{name}.zip,与 OpenZip 的入参约定保持一致(zip 同名 + 解压目录同名)。
        $sanfilename = sanitize_file_name($modelName . '.zip');
        $targetPath  = DOWNLOAD_DIR . $sanfilename;
        if (!@rename($tmpfname, $targetPath)) {
            @unlink($tmpfname);
            wp_send_json(array('errorCode' => 9501, 'errorMsg' => '保存文件失败'));
            error_log('DownloadV1Model:[9501]保存文件失败 ' . $targetPath);
            return;
        }
        @chmod($targetPath, 0777);
        error_log('DownloadV1Model:[文件保存到 ' . $targetPath . ']');

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
     * 去服务器获取列表, 被ts中 getModelList 方法调用
     * 这个方法可以通过PHP过滤已下载的路径, 避免前端重复下载
     */
    public function GetModelList()
    {
        if (!empty($this->userInfo["sign"])) {
            $result = $this->DoGet([], "Model/List", $this->userInfo["sign"]);
            if (is_array($result)) {
                foreach ($result as &$value) {
                    $fileName = str_replace(array('/', '.'), '_', $value["name"]);
                    $sanfilename = sanitize_file_name($fileName);
                    $filePath = DOWNLOAD_DIR . $sanfilename;
                    $value["downloaded"] = file_exists($filePath);
                }
                return $result;
            } else {
                // 返回空数组或错误信息
                return [];
            }
        } else {
            return [];
        }
    }

    public function GetModelMotions()
    {
        $this->verify_admin_ajax('live2d_shop_action');
        $result = $this->GetModelList();
        wp_send_json($result);
    }

    public function GetTextureList()
    {
        $this->verify_admin_ajax('live2d_shop_action');
        $param = ['modelId' => intval($_POST["modelId"])];
        $result = $this->DoGet($param, "Model/Textures", $this->userInfo["sign"]);
        wp_send_json($result);
    }

    /**
     * 对ZIP进行解压缩
     */
    public function OpenZip()
    {
        $this->verify_admin_ajax('live2d_shop_action');
        $zip = new ZipArchive;
        $sanfilename = sanitize_file_name($_POST["fileName"]);
        $zipFile = DOWNLOAD_DIR . $sanfilename;
        $zipFileName = pathinfo($zipFile, PATHINFO_FILENAME);
        $extractTarget = DOWNLOAD_DIR . $zipFileName;
        $res = $zip->open($zipFile);
        if ($res === TRUE) {
            // 防 zip slip: 逐条校验条目, 任一跳出目录则拒绝解压
            for ($i = 0, $n = $zip->numFiles; $i < $n; $i++) {
                $entryName = $zip->getNameIndex($i);
                if ($entryName === false) {
                    $zip->close();
                    @unlink($zipFile);
                    error_log('OpenZip:[读取 zip 条目失败]');
                    echo 0;
                    return;
                }
                // 拒绝绝对路径与 ../ 跳转
                if (strpos($entryName, '..') !== false || strpos($entryName, '/..') !== false || preg_match('#^([a-zA-Z]:)?[\\/]#', $entryName)) {
                    $zip->close();
                    @unlink($zipFile);
                    error_log('OpenZip:[检测到非法条目路径: ' . $entryName . ']');
                    echo 0;
                    return;
                }
            }
            $zip->extractTo($extractTarget);
            $zip->close();
            @unlink($zipFile);
            echo 1;
        } else {
            echo 0;
        }
    }

    /**
     * 清理文件: 用户如果没有下载成功, 会下载一个XML是.ZIP格式的, 需要给它清除
     * 执行此方法可以清除
     */
    public function ClearFiles()
    {
        $this->verify_admin_ajax('live2d_shop_action');
        if (!current_user_can('manage_options')) {
            echo 0;
            return;
        }
        $sanfilename = sanitize_file_name($_POST["fileName"]);
        $filePath = DOWNLOAD_DIR . $sanfilename;
        // 确保路径仍位于下载目录之内
        $realDir = realpath(DOWNLOAD_DIR);
        $realFile = realpath($filePath);
        if ($realDir === false || $realFile === false || strpos($realFile, $realDir) !== 0) {
            echo 0;
            return;
        }
        if (file_exists($realFile)) {
            unlink($realFile);
            echo 1;
        } else {
            echo 0;
        }
    }

    /**
     * 统一的后台 AJAX 鉴权: 要求登录 + 管理员权限 + nonce 有效
     */
    private function verify_admin_ajax($action)
    {
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            wp_send_json_error(array('errorCode' => 403, 'errorMsg' => 'forbidden'), 403);
        }
        // check_ajax_referer 在失败时会自动调 wp_die
        check_ajax_referer($action, '_wpnonce');
    }

    //排查错误使用
    public function Save_Options($value)
    {
        if (!empty($this->userInfo["sign"])) {
            $param = [
                'new_value' => json_encode($value)
            ];
            $result = $this->DoPost($param, "Options/UpdateOpt", $this->userInfo["sign"]);
            error_log('Save_Options:设置保存完成' . print_r($result));
        } else {
            error_log('Save_Options:设置保存完成, 但是用户没有登陆。');
        }
    }

    /**
     * 删除这段有可能会导致保存成功后调用不了我的API
     */
    public function Update_Options($value, $old_value)
    {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'live_2d_settings_base_group-options')) {
            add_settings_error('live_2d_sdk_error', 500, '保存失败，非法操作。');
            return $old_value; // 验证失败，返回旧值
        }
        $url = $value["modelAPI"];
        if (preg_match('/^https:\/\/api\.live2dweb\.com/i', $url) && empty($this->userInfo["sign"])) {
            add_settings_error('live_2d_sdk_error', 500, '保存成功，但是您必须登录才可以使用官方API。');
        }
        return $value;
    }

    public function Update_Advanced_Options($value, $old_value)
    {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'live_2d_advanced_option_group-options')) {
            add_settings_error('live_2d_sdk_error', 500, '保存失败，非法操作。');
            return $old_value; // 验证失败，返回旧值
        }
        return $value;
    }

    public function Update_Login_Options($value, $old_value)
    {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'live_2d_login_option_group-options')) {
            add_settings_error('live_2d_sdk_error', 500, '保存失败，非法操作。');
            return $old_value; // 验证失败，返回旧值
        }
        return $value;
    }

    public function JwtDecode($jwt, $key)
    {
        try {
            $setArr = (array)JWT::decode($jwt, new Key($key, 'HS256'));
            return $setArr;
        } catch (ExpiredException $e) {
            error_log("Token 已过期: " . $e->getMessage());
            return 0;
        } catch (Exception $e) {
            error_log('JwtDecode:签名错误' . $e, 5);
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
            'iss' => DOMAIN,
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
        $bare_url = API_URL . "/Verify/GetToken";
        $param = ['key' => $key];
        $complete_url = add_query_arg($param, $bare_url);
        error_log('GetToken:请求地址: ' . $complete_url);
        $response = wp_remote_get($complete_url, array(
            'headers' => array(
                'referer' => get_home_url()
            )
        ));
        $body = wp_remote_retrieve_body($response);
        error_log('GetToken:回复: ' . $body);
        return $body;
    }

    public function DoPost($param, $api_name, $jwt)
    {
        $complete_url = API_URL . "/" . $api_name;
        error_log('DoPost:请求地址: ' . $complete_url);
        $response = wp_remote_post($complete_url, array(
            'body' => $param,
            'headers' => array(
                'Authorization' => 'Bearer ' . $jwt,
                'Origin' =>  get_home_url(),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
        ));

        if (is_wp_error($response)) {
            error_log($complete_url . '请求失败: ' . $response->get_error_message());
            return array(
                'errorCode' => 500,
                'errorMsg'  => $response->get_error_message(),
            );
        }
        $httpCode = wp_remote_retrieve_response_code($response);
        if ($httpCode === 401 || $httpCode === 403) {
            error_log('DoPost:授权错误, 登录不正确, 请检查是否和域名匹配' . $httpCode, 5);
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
        $bare_url = API_URL . "/" . $api_name . "/";

        if (!empty($param)) {
            $bare_url = add_query_arg($param, $bare_url);
        }

        error_log('DoGet:请求地址: ' . $bare_url);
        $response = wp_remote_get($bare_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $jwt,
                'Origin' =>  get_home_url(),
            ),
        ));

        if (is_wp_error($response)) {
            error_log($bare_url . '请求失败: ' . $response->get_error_message());
            return array(
                'errorCode' => 500,
                'errorMsg'  => $response->get_error_message(),
            );
        }
        $httpCode = wp_remote_retrieve_response_code($response);
        if ($httpCode === 401 || $httpCode === 403) {
            error_log('DoGet:授权错误, 登录不正确, 请检查是否和域名匹配' . $httpCode, 5);
            return array(
                'errorCode' => $httpCode,
                'errorMsg' => '登录不正确, 请检查是否和域名匹配'
            );
        }
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
}
