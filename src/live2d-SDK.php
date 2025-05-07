<?php
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
class live2d_SDK
{
    private $userInfo;
    public function __construct()
    {
        $this->userInfo = get_option('live_2d_settings_user_token');
        if (!is_array($this->userInfo)) {
            $this->userInfo = array(); // 确保 $this->userInfo 是一个数组
        }
    }
    /**
     * 获取用户登录结果
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

    public function rollback_set($request){
        $homeUrl = get_home_url();
        $errCode = intval($request["errorCode"]);
        $sign = $request["sign"];
        $key = $request["key"];
    }

    /**
     * 下载一个指定ID的模型
     */
    public function DownloadModel()
    {
        $modelId = intval($_POST["modelId"]);
        if (!empty($this->userInfo["sign"])) {
            $param = ['id' => $modelId];
            $result = $this->DoPost($param, "Model/ModelInfo", $this->userInfo["sign"]);
            if (isset($result) && !empty($result["modelName"])) {
                if (!file_exists(DOWNLOAD_DIR) && !mkdir(DOWNLOAD_DIR, 0777, true)) {
                    echo json_encode(array(
                        'errorCode' => 9500,
                        'errorMsg' => '文件夹创建失败'
                    ));
                    error_log('DownloadModel:[9500]文件夹创建失败');
                }
                $fileUrl = $result["fileUrl"];
                $tmpfname = wp_tempnam($fileUrl);
                error_log('DownloadModel:[开始下载 ' . $fileUrl . ']');
                error_log('DownloadModel:[Origin ' . get_home_url() . ']');
                error_log('DownloadModel:[临时文件 ' . $tmpfname . ']');

                $response = wp_safe_remote_get($fileUrl, array(
                    'headers' => array(
                        'Authorization' => 'Bearer ' . $this->userInfo["sign"],
                        'Origin' =>  get_home_url(),
                        'Host' => 'download.live2dweb.com',
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36',
                        'Accept' => '*/*',
                        'Accept-Encoding' => 'gzip, deflate, br',
                        'Connection' => 'keep-alive',
                    ),
                    'timeout' => 300,
                    'stream' => true,
                    'filename' => $tmpfname
                ));

                if (is_wp_error($response)) {
                    echo json_encode(array(
                        'errorCode' => 500,
                        'errorMsg' => $response->get_error_message()
                    ));
                    error_log('DownloadModel:[请求失败: ' . $response->get_error_message() . ']');
                } else {
                    $httpCode = wp_remote_retrieve_response_code($response);
                    if ($httpCode === 401 || $httpCode === 403) {
                        echo json_encode(array(
                            'errorCode' => $httpCode,
                            'errorMsg' => '服务器未授权此访问'
                        ));
                        error_log('DownloadModel:[请求失败: ' . $httpCode . ']服务器未授权此访问');
                    } elseif ($httpCode === 200) {
                        // 文件已自动保存到$tmpfname，无需再手动写入
                        $modelName = str_replace(array('/', '.'), '_', $result["modelName"]);
                        $fileName = urlencode($modelName) . ".zip";
                        $sanfilename = sanitize_file_name($fileName);

                        // 将临时文件移动到目标位置
                        rename($tmpfname, DOWNLOAD_DIR . $sanfilename);
                        error_log('DownloadModel:[文件保存到 ' . DOWNLOAD_DIR . $sanfilename . ']');
                        // 设置文件权限
                        chmod(DOWNLOAD_DIR . $sanfilename, 0777);

                        // 验证文件MD5是否正确
                        $downloaded_md5 = md5_file(DOWNLOAD_DIR . $sanfilename);
                        if ($result["fileMd5"] === $downloaded_md5) {
                            error_log('DownloadModel:[文件MD5校验成功]');
                        } else {
                            error_log('DownloadModel:[文件MD5校验失败]');
                        }

                        echo json_encode(array(
                            'errorCode' => 200,
                            'fileName' => $sanfilename
                        ));
                    }
                }
            } else {
                echo json_encode($result);
            }
        } else {
            echo json_encode(array(
                'errorCode' => 9500,
                'errorMsg' => '没有登录信息'
            ));
            error_log('DownloadModel:[9500]没有登录信息');
        }
        wp_die();
    }
    
    /**
     * 去服务器获取列表, 被ts中 getModelList 方法调用
     * 这个方法可以通过PHP过滤已下载的路径, 避免前端重复下载
     */
    public function GetModelList()
    {
        if (!empty($this->userInfo["sign"])) {
            $result = $this->DoGet([], "Model/List", $this->userInfo["sign"]);
            foreach ($result as &$value) {
                $fileName = str_replace(array('/', '.'), '_', $value["name"]);
                $sanfilename = sanitize_file_name($fileName);
                $filePath = DOWNLOAD_DIR . $sanfilename;
                $value["downloaded"] = file_exists($filePath);
            }
            return $result;
        } else {
            return [];
        }
    }

    public function GetTextureList()
    {
        $param = ['modelId' => $_POST["modelId"]];
        $result = $this->DoGet($param, "Model/Textures", $this->userInfo["sign"]);
        echo json_encode($result);
        wp_die();
    }

    /**
     * 对ZIP进行解压缩
     */
    public function OpenZip()
    {
        $zip = new ZipArchive;
        $sanfilename = sanitize_file_name($_POST["fileName"]);
        $zipFile = DOWNLOAD_DIR . $sanfilename;
        $zipFileName = pathinfo($zipFile, PATHINFO_FILENAME);
        $res = $zip->open($zipFile);
        if ($res === TRUE) {
            $zip->extractTo(DOWNLOAD_DIR . '/' . $zipFileName);
            $zip->close();
            unlink($zipFile);
            echo 1;
        } else {
            echo 0;
        }
        wp_die();
    }

    public function Downloaded()
    {
        $modelId = intval($_POST["modelId"]);
        $param = ['id' => $modelId];
        $result = $this->DoPost($param, "Model/Downloaded", $this->userInfo["sign"]);
        echo json_encode($result);
        wp_die();
    }

    /**
     * 清理文件: 用户如果没有下载成功, 会下载一个XML是.ZIP格式的, 需要给它清除
     * 执行此方法可以清除
     */
    public function ClearFiles()
    {
        if (!current_user_can("delete_plugins")) {
            echo 0;
            wp_die();
        }
        $sanfilename = sanitize_file_name($_POST["fileName"]);
        $filePath = DOWNLOAD_DIR . $sanfilename;
        if (file_exists($filePath)) {
            unlink($filePath);
            echo 1;
        } else {
            echo 0;
        }
        wp_die();
    }

    public function GetModelMotions() {}

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

    public function Get_Jwt($sign)
    {
        $pub_key = new Key($this->GetPem(), 'RS256');
        try {
            $setArr = (array)JWT::decode($sign, $pub_key);
            return $setArr;
        } catch (Exception $e) {
            error_log('Get_Jwt:签名错误' . $e, 5);
            return false;
        }
    }

    public function JwtDecode($jwt, $key)
    {
        $pub_key = new Key($key, 'HS256');
        try {
            $setArr = (array)JWT::decode($jwt, $pub_key);
            return $setArr;
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
        error_log('JWT DEBUG: ' . json_encode($key) . '$payload: ' . json_encode($payload) . ' $jwt: ' . $jwt);
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

    private function GetPem()
    {
        $publicKeyFile = plugin_dir_path(__DIR__) . 'assets/client.pem';
        $publicKey = openssl_pkey_get_public(
            file_get_contents($publicKeyFile)
        );
        return $publicKey;
    }
}
