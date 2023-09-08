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
define('DOWNLOAD_DIR', plugin_dir_path(dirname(__FILE__)) . 'model/'); //服务器下载的路径
class live2d_SDK
{
    private $userInfo;
    public function __construct()
    {
        $this->userInfo = get_option('live_2d_settings_user_token');
    }
    /**
     * 获取用户登录结果
     */
    public function user_login($request)
    {
        $homeUrl = get_home_url();
        $errCode = intval($request["errorCode"]);
        if (!empty($request["sign"]) && $errCode === 200) {
            $signInfo = $this->Get_Jwt($request["sign"]);
            $userInfo = array();
            $userInfo["sign"] = $request["sign"];
            $userInfo["userName"] = $signInfo["email"];
            $userInfo["role"] = intval($signInfo["role"]);
            $userInfo["certserialnumber"] = intval($signInfo["certserialnumber"]);
            $userInfo["userLevel"] = intval($signInfo["http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata"]);
            $userInfo["errorCode"] = intval($request["errorCode"]);
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
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $result["fileUrl"]);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_HTTPGET, true); //GET数据
                curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                    'Authorization: Bearer ' . $this->userInfo["sign"],
                    'Origin: ' . get_home_url(),
                ));
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);  //禁用后cURL将终止从服务端进行验证
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);  //不验证证书是否存在
                $content = curl_exec($curl);
                $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);
                if ($httpCode === 200) {
                    $modelName = str_replace(array('/', '.'), '_', $result["modelName"]);
                    $fileName = urlencode($modelName) . ".zip";
                    $localFile = @fopen(DOWNLOAD_DIR . $fileName, 'a');
                    fwrite($localFile, $content);
                    fclose($localFile);
                    chmod(DOWNLOAD_DIR . $fileName, 0777);
                    unset($content);
                    //验证文件MD5是否正确
                    $downloaded_file = DOWNLOAD_DIR . $fileName;
                    $downloaded_md5 = md5_file($downloaded_file);
                    if ($result["fileMd5"] === $downloaded_md5) {
                    } else {
                    }
                    echo json_encode(array(
                        'errorCode' => 200,
                        'fileName' => $fileName
                    ));
                } else {
                    echo json_encode(array(
                        'errorCode' => $httpCode,
                        'errorMsg' => '服务器未授权此访问'
                    ));
                    error_log('DownloadModel:[' . $httpCode . ']服务器未授权此访问');
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
        $result = $this->DoGet([], "Model/List", $this->userInfo["sign"]);
        foreach ($result as &$value) {
            $fileName = str_replace(array('/', '.'), '_', $value["name"]);
            $filePath = DOWNLOAD_DIR . $fileName;
            $value["downloaded"] = file_exists($filePath);
        }
        echo json_encode($result);
        wp_die();
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
        $zipFile = DOWNLOAD_DIR . $_POST["fileName"];
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
        $filePath = DOWNLOAD_DIR . $_POST["fileName"];
        if (file_exists($filePath)) {
            unlink($filePath);
            echo 1;
        } else {
            echo 0;
        }
        wp_die();
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
        $modelAPI = $this->HttpRequest($value['modelAPI'], null, "GET", null);
        if (!empty($modelAPI)) {
            if (!empty($modelAPI['Version'])) {
                $value['modelVersion'] = $modelAPI['Version'];
                $value['modelMotions'] = $modelAPI['FileReferences']["Motions"];
            } else {
                error_log("没有正确的调用模型文件清单model3.json");
            }
        } else {
            error_log("没有正确的调用模型文件清单model3.json");
        }
    }

    /**
     * 删除这段有可能会导致保存成功后调用不了我的API
     */
    public function Update_Options($value, $old_value)
    {
        $url = $value["modelAPI"];
        if (preg_match('/^https:\/\/api\.live2dweb\.com/i', $url) && empty($this->userInfo["sign"])) {
            add_settings_error('live_2d_sdk_error', 500, '保存成功，但是您必须登录才可以使用官方API。');
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

    public function DoPost($param, $api_name, $jwt)
    {
        $url = API_URL . "/" . $api_name;
        return $this->HttpRequest($url, $param, "POST", $jwt);
    }

    public function DoGet($param, $api_name, $jwt)
    {
        $url = API_URL . "/" . $api_name . "/";
        return $this->HttpRequest($url, $param, "GET", $jwt);
    }

    private function HttpRequest(string $url, $param, $method = "POST" | "GET", $jwt = null)
    {
        try {
            $curl = curl_init();
            if ($method === "POST") {
                curl_setopt($curl, CURLOPT_POST, true); //POST数据
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($param));
            } else {
                curl_setopt($curl, CURLOPT_HTTPGET, true); //GET数据
                if (!empty($param)) {
                    $url = $url . '?' . http_build_query($param);
                }
            }
            if (!empty($url)) {
                curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                    'Authorization: Bearer ' . $jwt,
                    'Origin: ' . get_home_url(),
                ));
            }
            error_log('Http[' . $method . ']请求URL：' . $url);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);
            if ($httpCode === 401 || $httpCode === 403) {
                error_log('HttpRequest:授权错误, 登录不正确, 请检查是否和域名匹配' . $httpCode, 5);
                return array(
                    'errorCode' => $httpCode,
                    'errorMsg' => '登录不正确, 请检查是否和域名匹配'
                );
            } else {
                if (!$response) {
                    error_log('HttpRequest:[' . $httpCode . ']' . $curlError, 5);
                    return array(
                        'errorCode' => $httpCode,
                        'errorMsg' => $curlError
                    );
                } else {
                    return json_decode($response, true);
                }
            }
        } catch (Exception $e) {
            error_log('HttpRequest:[9500]异常' . $e, 5);
            return array(
                'errorCode' => 9500,
                'errorMsg' => $e
            );
        }
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
