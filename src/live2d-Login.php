<?php
require_once(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/wp-admin/includes/plugin.php');
class live2d_Login{
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

    function user_signout($request){
        echo $request["userName"];
    }
}
?>