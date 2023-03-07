<?php
require_once(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/wp-admin/includes/plugin.php');
require(dirname(__FILE__)  . '/jwt/JWT.php');
require(dirname(__FILE__)  . '/jwt/Key.php');
$dir = explode('/', plugin_dir_url(dirname(__FILE__)));
$dir_len = count($dir);
define('IS_PLUGIN_ACTIVE', is_plugin_active($dir[$dir_len - 2] . "/wordpress-live2d.php")); //补丁启用
define('API_URL', "https://api.live2dweb.com"); //API地址
define('DOWNLOAD_DIR', plugin_dir_path(dirname(__FILE__)) . 'model/'); //服务器下载的路径
class live2d_SDK
{
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
            } else {
                echo "0";
            }
        } else {
            if ($errCode) {
                echo $errCode;
            } else {
                echo "1005";
            }
        }
    }
    /**
     * 下载一个指定ID的模型
     */
    public function DownloadModel()
    {
        $modelId = intval($_POST["modelId"]);
        $userInfo = get_option('live_2d_settings_user_token');
        if (!empty($userInfo["sign"])) {
            $param = ['id' => $modelId];
            $result = $this->DoPost($param, "Model/ModelInfo", $userInfo["sign"]);
            if (isset($result) && !empty($result["modelName"])) {
                if (!file_exists(DOWNLOAD_DIR) && !mkdir(DOWNLOAD_DIR, 0777, true)) {
                    echo json_encode(array(
                        'errorCode' => 9500,
                        'errorMsg' => '文件夹创建失败'
                    ));
                }
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $result["fileUrl"]);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_HTTPGET, true); //GET数据
                curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                    'Authorization: Bearer ' . $userInfo["sign"],
                    'Origin: '. get_home_url(),
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
                }
            } else {
                echo json_encode($result);
            }
        } else {
            echo json_encode(array(
                'errorCode' => 9500,
                'errorMsg' => '没有登录信息'
            ));
        }
        wp_die();
    }

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

    //排查错误使用
    public function Save_Options($value)
    {
        $userInfo = get_option('live_2d_settings_user_token');
        if (!empty($userInfo["sign"])) {
            $param = [
                'new_value' => json_encode($value)
            ];
            $result = $this->DoPost($param, "Options/UpdateOpt", $userInfo["sign"]);
        }
    }

    /**
     * 删除这段有可能会导致保存成功后调用不了我的API
     */
    public function Update_Options($value, $old_value)
    {
        $userInfo = get_option('live_2d_settings_user_token');
        $url = $value["modelAPI"];
        if (preg_match('/^https:\/\/api\.live2dweb\.com/i', $url) && empty($userInfo["sign"])) {
            add_settings_error('live_2d_sdk_error', 500, '保存成功，但是您必须登录才可以使用官方API。');
        }
        return $value;
    }

    public function Get_Jwt($sign)
    {
        $pub_key = new Firebase\JWT\Key($this->GetPem(), 'RS256');
        $setArr = (array)Firebase\JWT\JWT::decode($sign, $pub_key);
        return $setArr;
    }

    public function DoPost($param, $api_name, $jwt)
    {
        try {
            $url = API_URL . "/" . $api_name;
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true); //POST数据
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Authorization: Bearer ' . $jwt,
                'Origin: '. get_home_url(),
            ));
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($param));
            //curl_setopt($curl, CURLOPT_TIMEOUT,20);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);  //禁用后cURL将终止从服务端进行验证
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);  //不验证证书是否存在
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            if ($httpCode === 401 || $httpCode === 403) {
                return array(
                    'errorCode' => $httpCode,
                    'errorMsg' => '登录不正确, 请检查是否和域名匹配'
                );
            } else {
                if (!$response) {
                    return array(
                        'errorCode' => $httpCode,
                        'errorMsg' => curl_error($curl)
                    );
                } else {
                    return json_decode($response, true);
                }
            }
        } catch (Exception $e) {
            return array(
                'errorCode' => 9500,
                'errorMsg' => $e
            );
        }
    }

    private function GetPem()
    {
        $publicKeyFile = plugin_dir_path(dirname(__FILE__)) . 'assets/client.pem';
        $publicKey = openssl_pkey_get_public(
            file_get_contents($publicKeyFile)
        );
        return $publicKey;
    }
}
