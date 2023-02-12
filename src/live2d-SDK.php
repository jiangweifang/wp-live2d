<?php
require_once(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/wp-admin/includes/plugin.php');
require(dirname(__FILE__)  . '/jwt/JWT.php');
require(dirname(__FILE__)  . '/jwt/Key.php');
$dir = explode('/',plugin_dir_url(dirname(__FILE__)));
$dir_len = count($dir);
define( 'IS_PLUGIN_ACTIVE', is_plugin_active($dir[$dir_len - 2]."/wordpress-live2d.php") );//补丁启用
define( 'API_URL', "https://api.live2dweb.com");//补丁启用
class live2d_SDK{
    /**
     * 获取用户登录结果
     */
    public function user_login($request){
        $homeUrl = get_home_url();
        $errCode = intval($request["errorCode"]);
        if(!empty($request["sign"]) && $errCode === 200){
            $signInfo = $this -> Get_Jwt($request["sign"]);
            $userInfo = array();
            $userInfo["sign"] = $request["sign"];
            $userInfo["userName"] = $signInfo["email"];
            $userInfo["role"] = intval($signInfo["role"]);
            $userInfo["certserialnumber"] = intval($signInfo["certserialnumber"]);
            $userInfo["userLevel"] = intval($signInfo["http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata"]);
            $userInfo["errorCode"] = intval($request["errorCode"]);
            $userInfo["hosts"] = $signInfo["aud"];
            if($homeUrl == $signInfo["aud"] && IS_PLUGIN_ACTIVE){
                update_option('live_2d_settings_user_token',$userInfo);
                echo "1";
            }else {
                echo "0";
            }
        }else{
            if($errCode){
                echo $errCode;
            }else{
                echo "1005";
            }
        }
    }
    /**
     * 获取回滚的设置
     */
    public function rollback_set($request){
        $userInfo = get_option( 'live_2d_settings_user_token' );
        $settings = get_option( 'live_2d_settings_option_name' );
        $setArr = $this -> Get_Jwt($request["token"]);
        if(!empty($request['sign']) && $userInfo['userName'] == $request['userName']){
                $keyList = array_keys($setArr);
                foreach($keyList as $keyItem){
                    $item = $setArr[$keyItem];
                    if(is_object($item)){
                        $item = (array)$item;
                        foreach(array_keys($item) as $childKey){
                            $settings[$keyItem][$childKey] = $item[$childKey];
                        } 
                    }else if(isset($item)){
                        $settings[$keyItem] = $item;
                    }
                }
                if(IS_PLUGIN_ACTIVE){
                    update_option('live_2d_settings_option_name',$settings);
                    echo "1";
                }else{
                    echo "0";
                }
          
        }else{
            echo '-1';
        }
    }

    public function Save_Options($value){
        $userInfo = get_option( 'live_2d_settings_user_token' );
        if(!empty($userInfo["sign"])){
            $result = $this -> DoPost('new_value',$value, "Options/UpdateOpt",$userInfo["sign"]);
            if(isset($result) && $result["errorCode"] != 200){
                add_settings_error('live_2d_sdk_error',$result["errorCode"],'保存成功！但插件暂时无法同步，这不影响您的使用:'. $result["errorMsg"].' | 错误代码:'.$result["errorCode"]);
            }
        }
    }

    /**
     * 删除这段有可能会导致保存成功后调用不了我的API
     */
    public function Update_Options($value, $old_value){
        $userInfo = get_option( 'live_2d_settings_user_token' );
        $url = $value["modelAPI"];
        if (preg_match('/^https:\/\/api\.live2dweb\.com/i', $url) && !empty($userInfo["sign"])){
            return $value;
        }else{
            add_settings_error('live_2d_sdk_error',500,'保存失败，您必须登录才可以使用官方API。');
            return $old_value;
        }
    }

    private function Get_Jwt($sign) {
        $pub_key = new Firebase\JWT\Key( $this -> GetPem() ,'RS256');
        $setArr = (array)Firebase\JWT\JWT::decode( $sign , $pub_key);
        return $setArr;
    }

    public function DoPost($paramName,$paramValue,$api_name,$jwt){
        try{
            $post = [
                $paramName => json_encode($paramValue)
            ];
            $curl = curl_init();
            $url = API_URL . "/" . $api_name;
            curl_setopt($curl, CURLOPT_URL,$url );
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);//POST数据
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Authorization: Bearer '. $jwt
            ));
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
            //curl_setopt($curl, CURLOPT_TIMEOUT,20);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,false);  //禁用后cURL将终止从服务端进行验证
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,false);  //不验证证书是否存在
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl,CURLINFO_HTTP_CODE);
            if($response === false){
                $response = json_encode(array(
                    'errorCode'=> $httpCode,
                    'errorMsg' => curl_error($curl)
                ));
            }else if(empty($response)){
                if($httpCode === 401){
                    $response = json_encode(array(
                        'errorCode'=> $httpCode,
                        'errorMsg' => '请登录成功后保存设置'
                    ));
                }else{
                    $response = json_encode(array(
                        'errorCode'=> $httpCode,
                        'errorMsg' => '接口返回为空'
                    ));
                }
            }
            curl_close($curl);
            return json_decode($response,true);
        }catch(Exception $e){
            return array(
                'errorCode'=> 9500,
                'errorMsg' => $e
            );
        }
	}

    private function GetPem(){
        $publicKeyFile = plugin_dir_url(dirname(__FILE__)) . 'assets/client.pem';
        $publicKey = openssl_pkey_get_public(
            file_get_contents($publicKeyFile)
        );
        return $publicKey;
    }
}
?>