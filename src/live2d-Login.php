<?php
require_once(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/wp-load.php');
require_once(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/wp-admin/includes/plugin.php');
$dir = explode('/',plugin_dir_url(dirname(__FILE__)));
$dirLenght = count($dir);
if(!empty($_POST["token"])){
    $userInfo = array();
    $userInfo["token"] = $_POST["token"];
    $userInfo["userName"] = $_POST["userName"];
    $userInfo["errorCode"] = intval($_POST["errorCode"]);
    $userInfo["hosts"] = plugin_dir_url(dirname(__FILE__));
    if(is_plugin_active($dir[$dirLenght - 2]."/wordpress-live2d.php")){
        delete_option( 'live_2d_settings_user_token' );
        add_option('live_2d_settings_user_token',$userInfo);
        echo "1";
    }else{
        echo "0";
    }
}else{
    echo "-1";
}

?>