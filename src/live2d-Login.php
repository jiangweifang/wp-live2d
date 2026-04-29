<?php
include_once(dirname(__FILE__)  . '/live2d-SDK.php');
class live2D_Login {
	
	private $live_2d_user_token;
	
	public function live_2d_login_init() {
		$this->live_2d_user_token = get_option( 'live_2d_settings_user_token' );

		// 退出登录: 清掉登录 token option 并跳回登录设置 tab.
		// 用 admin-post.php (而不是 AJAX) 是因为完成后要重定向回设置页, 流程最简单.
		add_action( 'admin_post_live2d_signout', array( $this, 'live_2d_handle_signout' ) );

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
            'key', // id
            __('请输入key','live-2d'), // title
            array( $this, 'live2dKey_callback' ), // callback
            'live-2d-login-admin', // page
            'live_2d_login_setting_section' // section
        );
		
	}

	public function live_2d_login_sanitize($input) {
		$sanitary_values = array();
        $live2dSDK = new live2d_SDK();
        if ( isset($input['key']) && $input["key"] != '' ) {
            try{
                $sanitary_values['key'] = $input['key'];
                $sign = $live2dSDK -> GetToken($input['key']);
                $signInfo = $live2dSDK -> JwtDecode($sign, $input['key']);
                $sanitary_values['sign'] = $sign;
                $sanitary_values["userName"] = $signInfo["email"];
                $sanitary_values["role"] = intval($signInfo["role"]);
                $sanitary_values["certserialnumber"] = intval($signInfo["certserialnumber"]);
                $sanitary_values["userLevel"] = intval($signInfo["http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata"]);
                $sanitary_values["errorCode"] = 200;
                $sanitary_values["hosts"] = $signInfo["aud"];
            } catch(Exception $e){
                add_settings_error('live_2d_sdk_error',500, __('保存失败, 登录信息无法解析', 'live-2d'));
            }
        }else{
            add_settings_error('live_2d_sdk_error',500, __('保存失败, 登录信息为空', 'live-2d'));
        }
        return $sanitary_values;
	}

    public function live2dKey_callback() {
        printf(
            ' <p>' . wp_kses(
                __('如果您无法解决链接问题，请在<a href="https://www.live2dweb.com/Sites" target="_blank">官方网站登录</a>，查看Key后将其复制到下方，保存后就可以登录成功了！', 'live-2d'),
                array('a' => array('href' => array(), 'target' => array()))
            ) . '</p><br />
            <input class="regular-text" style="width: 700px;" name="live_2d_settings_user_token[key]" value="%s" />',
            isset( $this-> live_2d_user_token['key'] ) ? esc_attr( $this-> live_2d_user_token['key']) : ''
        );
    }

	public function live2dLogin_callback() {
        $userInfo = $this->live_2d_user_token;
        $homeUrl = get_home_url();
        if(empty($userInfo['userName'])){
            ?>
            <buttom id="btnLogin" class="button button-primary"><?php esc_html_e('登录', 'live-2d'); ?></buttom> 
            <?php
        }else{
            $signout_url = wp_nonce_url(
                admin_url( 'admin-post.php?action=live2d_signout' ),
                'live2d_signout'
            );
            ?>
            <p id="labLogined" class="lgoined"><?php
                /* translators: %s: user name */
                printf( esc_html__( '已登录, 用户名: %s', 'live-2d' ), esc_html( $userInfo['userName'] ) );
            ?></p>
            <p>
                <a id="signOut" href="<?php echo esc_url( $signout_url ); ?>" class="button"><?php esc_html_e('退出登录', 'live-2d'); ?></a>
            </p>
            <?php
        }
        if(!empty($userInfo)){
            if($userInfo["hosts"] != $homeUrl){
                ?>
                <p><?php echo wp_kses(
                    __('此站点未绑定, 请<a href="https://www.live2dweb.com/Sites" target="_blank">点击此处</a>绑定站点, 绑定时请注意与WordPress中的 站点地址（URL） 相同', 'live-2d'),
                    array('a' => array('href' => array(), 'target' => array()))
                ); ?></p>
                <?php
            }
            if(intval($userInfo["role"]) != 2){
                ?>
                <p><?php echo wp_kses(
                    __('您的邮箱未激活, 请<a href="https://www.live2dweb.com/Email" target="_blank">点击此处</a>激活邮箱', 'live-2d'),
                    array('a' => array('href' => array(), 'target' => array()))
                ); ?></p>
                <?php
            }
            if(intval($userInfo["userLevel"]) < 1){
                ?>
                <p><?php echo wp_kses(
                    __('您是未付费用户, 请<a href="https://www.live2dweb.com/Order/Pay" target="_blank">点击此处</a>付费后使用更多功能', 'live-2d'),
                    array('a' => array('href' => array(), 'target' => array()))
                ); ?></p>
                <?php
            }
        }
    }

	public function live_2d_login_section_info() {
        ?>
            <p><?php
                $check_url = esc_url( get_home_url() . '/?rest_route=/live2d/v1/' );
                /* translators: %s: REST endpoint URL */
                printf(
                    wp_kses(
                        __( '如果您发现登录没有反应，或者出现了错误，请先检查 <a href="%1$s">%1$s</a> 是否可以被访问', 'live-2d' ),
                        array( 'a' => array( 'href' => array() ) )
                    ),
                    $check_url
                );
            ?></p>
        <?php
	}

	/**
	 * 处理退出登录: 校验 nonce + 权限, 清掉本插件登录 token, 跳回设置页登录 tab.
	 */
	public function live_2d_handle_signout() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( '没有权限', 'live-2d' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'live2d_signout' );
		delete_option( 'live_2d_settings_user_token' );
		wp_safe_redirect( admin_url( 'options-general.php?page=live-2d-options#login' ) );
		exit;
	}
}
?>
