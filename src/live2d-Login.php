<?php
//接收一个post过来的内容 具体怎么做回头再说
class live2D_Login {
    public function __construct(array $postdata) {
        $this->valid = $this->validatePostData($postdata);
    }
    public function __invoke() {
        if ($this->valid) {
            // do whatever you need to do 
            exit();
        }
    }

    private function validatePostData(array $postdata) {
        $userToken = $postdata["token"];
        echo get_option( 'live_2d_user_token' );
        // check here the $_POST data, e.g. if the post data actually comes
        // from the api, autentication and so on
        if(!empty($userToken)){
            register_setting( "live_2d_settings_base_group","live_2d_user_token", $userToken );
        }
    } 
}
?>