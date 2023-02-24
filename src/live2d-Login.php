<?php
include_once(dirname(__FILE__)  . '/live2d-SDK.php');
class live2D_Login {
	
	private $live_2d_user_token;
	
	public function live_2d_login_init() {
		$this->live_2d_user_token = get_option( 'live_2d_settings_user_token' );
		
		register_setting(
			'live_2d_login_option_group', // option_group
			'live_2d_settings_user_token', // option_name
			array( $this, 'live_2d_login_sanitize' ) // sanitize_callback
		);

		add_settings_section(
			'live_2d_login_setting_section', // id
			__('登录','live-2d'), // title
			array( $this, 'live_2d_login_section_info' ), // callback
			'live-2d-login-admin' // page
		);

        add_settings_field(
            'live2dLogin', // id
            __('登录','live-2d'), // title
            array( $this, 'live2dLogin_callback' ), // callback
            'live-2d-login-admin', // page
            'live_2d_login_setting_section' // section
        );

		add_settings_field(
			'sign', // id
			__('请输入license','live-2d'), // title
			array( $this, 'sign_callback' ), // callback
			'live-2d-login-admin', // page
			'live_2d_login_setting_section' // section
		);
	}

	public function live_2d_login_sanitize($input) {
		$sanitary_values = array();
        $tokenArrCount = count($this -> live_2d_user_token);
        if($tokenArrCount > 0 && $this-> live_2d_user_token['sign'] == $input['sign']){
            return $this-> live_2d_user_token;
        }
		if ( isset($input['sign']) && $input["sign"] != '' ) {
            try{
                $live2dSDK = new live2d_SDK();
                $signInfo = $live2dSDK -> Get_Jwt($input["sign"]);
                $sanitary_values['sign'] = $input['sign'] ;
                $sanitary_values["userName"] = $signInfo["email"];
                $sanitary_values["role"] = intval($signInfo["role"]);
                $sanitary_values["certserialnumber"] = intval($signInfo["certserialnumber"]);
                $sanitary_values["userLevel"] = intval($signInfo["http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata"]);
                $sanitary_values["errorCode"] = 200;
                $sanitary_values["hosts"] = $signInfo["aud"];
            } catch(Exception $e){
                add_settings_error('live_2d_sdk_error',500, '保存失败, 登录信息无法解析');
            }
		}else{
            add_settings_error('live_2d_sdk_error',500, '保存失败, 登录信息为空');
        }
		return $sanitary_values;
	}

    public function sign_callback(){
        printf(
            ' <p>如果您无法解决链接问题，请在<a href="https://www.live2dweb.com/Sites" target="_blank">官方网站登录</a>，查看Token后将其复制到下方，保存后就可以登录成功了！</p><br />
            <textarea class="regular-text" style="width: 700px;" rows="10" name="live_2d_settings_user_token[sign]">%s</textarea>',
            isset( $this-> live_2d_user_token['sign'] ) ? esc_attr( $this-> live_2d_user_token['sign']) : ''
        );
    }

	public function live2dLogin_callback() {
        $userInfo = $this->live_2d_user_token;
        $homeUrl = get_home_url();
        ?>
        <buttom id="btnLogin" class="button button-primary">登录</buttom> 
        <p id="labLogined" class="lgoined" style="display:none"></p>
        <br /> 
        <a id="signOut" class="lgoined">如要退出登陆请停用再启用插件</a>
        <?php
        if(!empty($userInfo)){
            if($userInfo["hosts"] != $homeUrl){
                ?>
                <p>此站点未绑定, 请<a href="https://www.live2dweb.com/Sites" target="_blank">点击此处</a>绑定站点, 绑定时请注意与WordPress中的 站点地址（URL） 相同</p>
                <?php
            }
            if(intval($userInfo["role"]) != 2){
                ?>
                <p>您的邮箱未激活, 请<a href="https://www.live2dweb.com/Email" target="_blank">点击此处</a>激活邮箱</p>
                <?php
            }
            if(intval($userInfo["userLevel"]) < 1){
                ?>
                <p>您是未付费用户, 请<a href="https://www.live2dweb.com/Order/Pay" target="_blank">点击此处</a>付费后使用更多功能</p>
                <?php
            }
        }
    }

	public function live_2d_login_section_info() {
        ?>
            <p>如果您发现登录没有反应，或者出现了错误，请先检查 <a href="<?php echo get_home_url() ?>/?rest_route=/live2d/v1/"><?php echo get_home_url() ?>/?rest_route=/live2d/v1/</a> 是否可以被访问</p>
        <?php
	}
}
?>
