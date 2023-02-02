<?php
require(dirname(__FILE__)  . '/live2d-Utils.php');
class live2D_Advanced {
	
	private $live_2d_advanced_options;
	
	public function live_2d_advanced_init() {
		$this->live_2d_advanced_options = get_option( 'live_2d_advanced_option_name' );
		
		register_setting(
			'live_2d_advanced_option_group', // option_group
			'live_2d_advanced_option_name', // option_name
			array( $this, 'live_2d_advanced_sanitize' ) // sanitize_callback
		);

		add_settings_section(
			'live_2d_advanced_setting_section', // id
			__('高级设置','live-2d'), // title
			array( $this, 'live_2d_advanced_section_info' ), // callback
			'live-2d-advanced-admin' // page
		);

		add_settings_field(
			'console_open_msg', // id
			__('打开控制台提示','live-2d'), // title
			array( $this, 'console_open_msg_callback' ), // callback
			'live-2d-advanced-admin', // page
			'live_2d_advanced_setting_section' // section
		);

		add_settings_field(
			'copy_message', // id
			__('复制信息时的提示','live-2d'), // title
			array( $this, 'copy_message_callback' ), // callback
			'live-2d-advanced-admin', // page
			'live_2d_advanced_setting_section' // section
		);

		add_settings_field(
			'screenshot_message', // id
			__('截图时的提示','live-2d'), // title
			array( $this, 'screenshot_message_callback' ), // callback
			'live-2d-advanced-admin', // page
			'live_2d_advanced_setting_section' // section
		);

		add_settings_field(
			'hidden_message', // id
			__('隐藏看板娘的提示','live-2d'), // title
			array( $this, 'hidden_message_callback' ), // callback
			'live-2d-advanced-admin', // page
			'live_2d_advanced_setting_section' // section
		);

		add_settings_field(
			'load_rand_textures', // id
			__('更换服装时的提示','live-2d'), // title
			array( $this, 'load_rand_textures_callback'), // callback
			'live-2d-advanced-admin', // page
			'live_2d_advanced_setting_section' // section
		);
		
		add_settings_field(
			'hour_tips', // id
			__('每小时提示','live-2d'), // title
			array( $this, 'hour_tips_callback' ), // callback
			'live-2d-advanced-admin', // page
			'live_2d_advanced_setting_section' // section
		);

		add_settings_field(
			'referrer_message', // id
			__('搜索引擎入站提示','live-2d'), // title
			array( $this, 'referrer_message_callback' ), // callback
			'live-2d-advanced-admin', // page
			'live_2d_advanced_setting_section' // section
		);

		add_settings_field(
			'referrer_hostname', // id
			__('访问本站点的提示','live-2d'), // title
			array( $this, 'referrer_hostname_callback' ), // callback
			'live-2d-advanced-admin', // page
			'live_2d_advanced_setting_section' // section
		);

		add_settings_field(
			'hitokoto_api_message', // id
			__('一言API的消息','live-2d'), // title
			array( $this, 'hitokoto_api_message_callback' ), // callback
			'live-2d-advanced-admin', // page
			'live_2d_advanced_setting_section' // section
		);

		add_settings_field(
			'mouseover_msg', // id
			__('鼠标悬停时的消息提示','live-2d'), // title
			array( $this, 'mouseover_msg_callback' ), // callback
			'live-2d-advanced-admin', // page
			'live_2d_advanced_setting_section' // section
		);
		
		add_settings_field(
			'click_selector', // id
			__('鼠标点击选择器','live-2d'), // title
			array( $this, 'click_selector_callback' ), // callback
			'live-2d-advanced-admin', // page
			'live_2d_advanced_setting_section' // section
		);

		add_settings_field(
			'click_msg', // id
			__('鼠标点击时的消息提示','live-2d'), // title
			array( $this, 'click_msg_callback' ), // callback
			'live-2d-advanced-admin', // page
			'live_2d_advanced_setting_section' // section
		);

		add_settings_field(
			'seasons_msg', // id
			__('节日事件','live-2d'), // title
			array( $this, 'seasons_msg_callback' ), // callback
			'live-2d-advanced-admin', // page
			'live_2d_advanced_setting_section' // section
		);
	}

	public function live_2d_advanced_sanitize($input) {
		$sanitary_values = array();
		if ( isset( $input['console_open_msg'] ) ) {
			$sanitary_values['console_open_msg'] = $input['console_open_msg'] ;
		}

		if ( isset( $input['copy_message'] ) ) {
			$sanitary_values['copy_message'] = $input['copy_message'] ;
		}

		if ( isset( $input['screenshot_message'] ) ) {
			$sanitary_values['screenshot_message'] = $input['screenshot_message'] ;
		}

		if ( isset( $input['hidden_message'] ) ) {
			$sanitary_values['hidden_message'] =  $input['hidden_message'];
		}

		if ( isset( $input['load_rand_textures'] ) ) {
			$sanitary_values['load_rand_textures'] = $input['load_rand_textures'];
		}

		if ( isset( $input['hour_tips'] ) ) {
			$sanitary_values['hour_tips'] = $input['hour_tips'];
		}

		if ( isset( $input['hour_tips_hidden'] ) ) {
			$sanitary_values['hour_tips_hidden'] = $input['hour_tips_hidden'];
		}
		
		if ( isset( $input['referrer_message'] ) ) {
			$sanitary_values['referrer_message'] = $input['referrer_message'];
		}

		if ( isset( $input['referrer_hostname'] ) ) {
			$sanitary_values['referrer_hostname'] =  $input['referrer_hostname'] ;
		}

		if ( isset( $input['hitokoto_api_message'] ) ) {
			$sanitary_values['hitokoto_api_message'] = $input['hitokoto_api_message'];
		}

		if ( isset( $input['mouseover_msg'] ) ) {
			$sanitary_values['mouseover_msg'] = $input['mouseover_msg'];
		}
		
		if ( isset( $input['click_selector'] ) ) {
			$sanitary_values['click_selector'] = sanitize_text_field($input['click_selector']);
		}
		
		if ( isset( $input['click_msg'] ) ) {
			$sanitary_values['click_msg'] = $input['click_msg'];
		}

		if ( isset( $input['seasons_msg'] ) ) {
			$sanitary_values['seasons_msg'] = $input['seasons_msg'];
		}

		return $sanitary_values;
	}
	//控制台被打开提醒（支持多句随机）
	public function console_open_msg_callback() {
		live2D_Utils::loopMsg('console_open_msg','List');
	}
	//内容被复制触发提醒（支持多句随机）
	public function copy_message_callback() {
		live2D_Utils::loopMsg('copy_message','List');
	}
	//看板娘截图提示语（支持多句随机）
	public function screenshot_message_callback() {
		live2D_Utils::loopMsg('screenshot_message','List');
	}
	//看板娘隐藏提示语（支持多句随机）
	public function hidden_message_callback() {
		live2D_Utils::loopMsg('hidden_message','List');
	}
	//随机材质提示语（暂不支持多句）
	public function load_rand_textures_callback() {
		printf(
			'<input class="regular-text" style="width: 280px"  type="text" name="live_2d_advanced_option_name[load_rand_textures][0]" id="load_rand_textures_0" value="%s" placeholder = "没有服装时的提示">
			 <input class="regular-text" style="width: 280px" type="text" name="live_2d_advanced_option_name[load_rand_textures][1]" id="load_rand_textures_1" value="%s" placeholder = "切换时的提示"><br />
			 <p>'.esc_html__('请在第一个输入框输入没有服装时的默认提示，第二个输入框输入每次切换时的提示消息','live-2d').'</p>
			',
			isset( $this->live_2d_advanced_options['load_rand_textures'][0] ) ? esc_attr( $this->live_2d_advanced_options['load_rand_textures'][0]) : '',
			isset( $this->live_2d_advanced_options['load_rand_textures'][1] ) ? esc_attr( $this->live_2d_advanced_options['load_rand_textures'][1]) : ''
		);
	}
	//时间段欢迎语（支持多句随机）
	public function hour_tips_callback() {
		live2D_Utils::loopMsg('hour_tips','Array');
		echo '<p>'.esc_html__('时间按照t{开始小时}-{结束小时}的方式填写，例如：t5-7或t7-11（避免改错，目前此项无法更改）','live-2d').'</p>';
	}


	// 请求来源欢迎语（不支持多句）
	public function referrer_message_callback() {
		live2D_Utils::loopMsg('referrer_message','Array');
		echo '<p>'.esc_html__('请务必不要修改{}中的内容，{title}网站标题、{keyword}关键词、{website}站点名称','live-2d').'</p>';
	}
	//请求来源自定义名称（根据 host，支持多句随机）
	public function referrer_hostname_callback() {
		live2D_Utils::loopMsg('referrer_hostname','Array' , false);
	}
	
	//一言 API 输出模板（不支持多句随机）
	public function hitokoto_api_message_callback() {
		live2D_Utils::loopMsg('hitokoto_api_message','Array');
		echo '<p>'.esc_html__('请务必不要修改{}中的内容，lwl12.com接口会有没有作者的情况语句中需要用“|”进行分割','live-2d').'</p>';//lwl12.com会有没有作者的情况
	}
	//鼠标触发提示（根据 CSS 选择器，支持多句随机）
	public function mouseover_msg_callback() {	
		live2D_Utils::loopMsg('mouseover_msg','Selector');
		echo '<p>'.__('鼠标悬停位置的<a href="https://www.w3school.com.cn/jquery/jquery_ref_selectors.asp" target="_blank">jQuery选择器</a>','live-2d').'</p>';
	}
	
	public function click_selector_callback(){
		printf(
			'<input class="regular-text" type="text" name="live_2d_advanced_option_name[click_selector]" id="click_selector" value="%s">',
			isset( $this->live_2d_advanced_options['click_selector'] ) ? esc_attr( $this->live_2d_advanced_options['click_selector']) : ''
		);
	}
	
	// 鼠标点击触发提示（根据 CSS 选择器，支持多句随机）
	public function click_msg_callback() {
		live2D_Utils::loopMsg('click_msg','List');
		echo '<p>'.esc_html__('点击看板娘会循环以上的每一行点击事件','live-2d').'</p>';
	}
	
	
	//节日提示（日期段，支持多句随机）
	public function seasons_msg_callback() {
		live2D_Utils::loopMsg('seasons_msg','Array',false);
		echo '<p>'.esc_html__('在指定的日期说提示语，日期的规则为MM/dd，例如2月14日为 02/14，可填写一个时间区间，格式为11/05-11/12。','live-2d').'</p>';
	}

	public function live_2d_advanced_section_info() {

	}
}
?>
