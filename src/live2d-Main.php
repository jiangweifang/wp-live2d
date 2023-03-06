<?php
require_once(dirname(__FILE__)  . '/waifu-Advanced.php');
require_once(dirname(__FILE__)  . '/waifu-Settings.php');
require_once(dirname(__FILE__)  . '/waifu-Settings-Style.php');
require_once(dirname(__FILE__)  . '/waifu-Settings-Tips.php');
require_once(dirname(__FILE__)  . '/waifu-Settings-Toolbar.php');
require_once(dirname(__FILE__)  . '/waifu-Settings-Base.php');
require_once(dirname(__FILE__)  . '/live2d-Login.php');
require_once(dirname(__FILE__)  . '/live2d-Shop.php');
class live2D
{
	public function __construct()
	{
		add_action('admin_menu', array($this, 'live_2d__add_plugin_page'));
		add_action('admin_init', array($this, 'live_2d_waifu_page_init'));
		// 保存设置JSON的钩子 在执行update_option_live_2d_advanced_option_name之后进行
		add_filter("pre_update_option_live_2d_settings_option_name", array(new live2D_SDK(), 'Update_Options'), 10, 3);
		add_action('updated_option', array($this, 'live2D_Advanced_Save'), 10, 3);
	}

	public function live2D_Advanced_Save($option_name, $old_value, $value)
	{
		if ($option_name == 'live_2d_settings_option_name') {
			$live2D_sdk = new live2D_SDK();
			$live2D_sdk->Save_Options($value);
		}
	}
	public function live_2d__add_plugin_page()
	{
		$menu = __('Live 2D 设置', 'live-2d');
		$my_admin_page = add_options_page(
			$menu, // page_title
			$menu, // menu_title
			'manage_options', // capability
			'live-2d-options', // menu_slug
			array($this, 'live_2d__create_admin_page') // function
		);
		$shop_title = __('Live 2D 创意工坊', 'live-2d');
		add_menu_page(
			$shop_title, // page_title
			$shop_title, // menu_title
			'manage_options', // capability
			'live-2d-shop', // menu_slug
			array(new live2d_Shop(), 'live2d_shop_init') // function
		);
		add_action('load-' . $my_admin_page, array('live2D_Utils', 'live_2D_help_tab'));
	}

	public function live_2d__create_admin_page()
	{
?>
		<div class="wrap">
			<h2 class="nav-tab-wrapper">
				<a id="login_btn" href="#login" class="nav-tab"><?php esc_html_e('登陆', 'live-2d') ?></a>
				<a id="settings_btn" href="#settings" class="nav-tab"><?php esc_html_e('基础设置', 'live-2d') ?></a>
				<a id="toolbar_btn" href="#toolbar" class="nav-tab"><?php esc_html_e('工具栏设置', 'live-2d') ?></a>
				<a id="tips_btn" href="#tips" class="nav-tab"><?php esc_html_e('提示消息选项', 'live-2d') ?></a>
				<a id="style_btn" href="#style" class="nav-tab"><?php esc_html_e('样式设置', 'live-2d') ?></a>
				<a id="advanced_btn" href="#advanced" class="nav-tab"><?php esc_html_e('高级设置', 'live-2d') ?></a>
			</h2>
			<?php get_settings_errors('live_2d_advanced_option_saveFiles'); ?>
			<?php get_settings_errors('live_2d_sdk_error'); ?>
			<form method="post" action="options.php">
				<?php settings_fields('live_2d_settings_base_group'); ?>
				<div id="settings" class="group">
					<?php
					do_settings_sections('live-2d-settings-base');
					submit_button();
					?>
				</div>
				<div id="toolbar" class="group">
					<?php
					do_settings_sections('live-2d-settings-toolbar');
					submit_button();
					?>
				</div>
				<div id="tips" class="group">
					<?php
					do_settings_sections('live-2d-settings-tips');
					submit_button();
					?>
				</div>
				<div id="style" class="group">
					<?php
					do_settings_sections('live-2d-settings-style');
					submit_button();
					?>
				</div>
			</form>
			<div id="advanced" class="group">
				<form method="post" action="options.php">
					<?php
					settings_fields('live_2d_advanced_option_group');
					do_settings_sections('live-2d-advanced-admin');
					submit_button('', 'primary', 'submit_advanced');
					?>
				</form>
			</div>
			<div id="login" class="group">
				<form method="post" action="options.php">
					<?php
					settings_fields('live_2d_login_option_group');
					do_settings_sections('live-2d-login-admin');
					submit_button('', 'primary', 'submit_advanced');
					?>
				</form>
			</div>
		</div>
<?php }

	public function live_2d_waifu_page_init()
	{
		wp_enqueue_style('wp-color-picker');
		wp_enqueue_style('live2d_admin', plugin_dir_url(dirname(__FILE__)) . 'assets/waifu.css'); //css
		wp_register_script('wp-color-picker-alpha', plugin_dir_url(dirname(__FILE__)) . 'assets/wp-color-picker-alpha.min.js', array('wp-color-picker'));
		wp_add_inline_script(
			'wp-color-picker-alpha',
			'jQuery( function() { jQuery( ".color-picker" ).wpColorPicker(); } );'
		);
		wp_enqueue_script('wp-color-picker-alpha');
		wp_enqueue_script('admin_js', plugins_url('../assets/waifu-admin.min.js', __FILE__));
		wp_localize_script('admin_js', 'settings', array(
			'userInfo' => get_option('live_2d_settings_user_token'),
			'homeUrl' => get_home_url()
		));
		// 注册基础设置
		register_setting(
			'live_2d_settings_base_group', // option_group
			'live_2d_settings_option_name', // option_name
			array(new live2D_Settings(), 'live_2d_settings_sanitize') // sanitize_callback
		);

		//加载基础设置
		$waifu_set = new live2D_Settings_Base();
		$waifu_set->live_2d_settings_base_init();

		//加载样式设置
		$waifu_style = new live2D_Settings_Style();
		$waifu_style->live_2d_settings_style_init();

		//加载提示设置
		$waifu_tips = new live2D_Settings_Tips();
		$waifu_tips->live_2d_settings_tips_init();

		//加载工具栏设置
		$waifu_toolbar = new live2D_Settings_Toolbar();
		$waifu_toolbar->live_2d_settings_toolbar_init();

		// 加载高级设置
		$waifu_opt = new live2D_Advanced();
		$waifu_opt->live_2d_advanced_init();

		// 加载登录异常
		$waifu_login = new live2D_Login();
		$waifu_login->live_2d_login_init();
	}
}
?>