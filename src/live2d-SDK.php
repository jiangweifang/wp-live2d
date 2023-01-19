<?php
require_once(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/wp-admin/includes/plugin.php');
require(dirname(__FILE__)  . '/../jwt/JWT.php');
require(dirname(__FILE__)  . '/../jwt/Key.php');
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
$dir = explode('/',plugin_dir_url(dirname(__FILE__)));
$dir_len = count($dir);
define( 'IS_PLUGIN_ACTIVE', is_plugin_active($dir[$dir_len - 2]."/wordpress-live2d.php") );//补丁启用
class live2d_SDK{
    /**
     * 获取用户登录结果
     */
    public function user_login($request){
        if(!empty($request["token"])){
            $userInfo = array();
            $userInfo["token"] = $request["token"];
            $userInfo["publicKey"] = $request["publicKey"];
            $userInfo["userName"] = $request["userName"];
            $userInfo["errorCode"] = intval($request["errorCode"]);
            $userInfo["hosts"] = plugin_dir_url(dirname(__FILE__));
            if(IS_PLUGIN_ACTIVE){
                update_option('live_2d_settings_user_token',$userInfo);
                echo "1";
            }else{
                echo "0";
            }
        }else{
            echo "-1";
        }
    }
    /**
     * 获取回滚的设置
     */
    public function rollback_set($request){
        $tokenInfo = get_option( 'live_2d_settings_user_token' );
        $settings = array();
        $publicKey = $tokenInfo['publicKey'];
        $userName = $tokenInfo['userName'];
        if(!empty($request['token'])){
            $public_key = live2d_SDK::MakePem($publicKey);
            print_r(new Key($public_key, 'RS256'));
            if($userName == $request['userName']){
                $setArr = json_decode($request['setJson'],true);
                $keyList = array_keys($setArr);
                foreach($keyList as $keyItem){
                    $item = $setArr[$keyItem];
                    if(is_array($item)){
                        foreach(array_keys($item) as $childKey){
                            $settings[$keyItem][$childKey] = $item[$childKey];
                        } 
                    }else if(strlen($item) != 0){
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
        }else{
            echo '-2';
        }
    }

    public function Save_Options($value, $old_value){
        $userInfo = get_option( 'live_2d_settings_user_token' );
        $response = $this -> DoPost('new_value',$value, "Options/UpdateOpt",$userInfo["token"]);
        $result = json_decode($response,true);
        if(isset($result)){
            if($result["errorCode"] != 200){
                add_settings_error('live_2d_sdk_error',$result["errorCode"],'Save Error:'. $result["errorMsg"].' Error Code:'.$result["errorCode"]);
                return $old_value;
            }else{
                return $value;
            }
        }else{
            add_settings_error('live_2d_sdk_error',500, '接口返回为空');
            return $old_value;
        }
    }

    public function DoPost($paramName,$paramValue,$api_name,$jwt){
        try{
            $post = [
                $paramName => json_encode($paramValue)
            ];
            $curl = curl_init();
            $url = "https://api.live2dweb.com/". $api_name;
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
                $response = array(
                    'errorCode'=> $httpCode,
                    'errorMsg' => curl_error($curl)
                );
            }else if(empty($response)){
                $response = array(
                    'errorCode'=> $httpCode,
                    'errorMsg' => '接口返回为空'
                );
            }
            curl_close($curl);
            return $response;
        }catch(Exception $e){
            return array(
                'errorCode'=> 9500,
                'errorMsg' => $e
            );
        }
	}

    private function MakePem($str){
        $str=chunk_split($str, 64, "\r\n");
        return "-----BEGIN PUBLIC KEY-----\r\n$str-----END PUBLIC KEY-----\r\n";
    }
}
?>