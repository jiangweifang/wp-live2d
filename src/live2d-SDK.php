<?php
require_once(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/wp-admin/includes/plugin.php');
class live2d_SDK{
    function user_login($request){
        $dir = explode('/',plugin_dir_url(dirname(__FILE__)));
        $dirLenght = count($dir);
        if(!empty($request["token"])){
            $userInfo = array();
            $userInfo["token"] = $request["token"];
            $userInfo["userName"] = $request["userName"];
            $userInfo["errorCode"] = intval($request["errorCode"]);
            $userInfo["hosts"] = plugin_dir_url(dirname(__FILE__));
            if(is_plugin_active($dir[$dirLenght - 2]."/wordpress-live2d.php")){
                update_option('live_2d_settings_user_token',$userInfo);
                echo "1";
            }else{
                echo "0";
            }
        }else{
            echo "-1";
        }
    }

    public function Save_Options($value, $old_value){
        $response = $this -> DoPost($value, "Options/UpdateOpt");
        $result = array();
        if($response["errorCode"] != 200){
            add_settings_error('live_2d_sdk_error',$response["errorCode"],'Save Error:'. $response["errorMsg"].' Error Code:'.$response["errorCode"]);
            return $old_value;
        }else{
            $result = json_decode($response,true);
        }
        return $value;
    }

    public function DoPost($new_value,$api_name){
        try{
            $userInfo = get_option( 'live_2d_settings_user_token' );
            $post = [
                'new_value' => json_encode($new_value)
            ];
            $curl = curl_init();
            $url = "https://localhost:7017/". $api_name;
            curl_setopt($curl, CURLOPT_URL,$url );
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);//POST数据
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Authorization: Bearer '.$userInfo["token"]
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
}
?>