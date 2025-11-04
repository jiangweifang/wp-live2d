<?php
class live2D_Settings_Tips {
    private $live_2d__options;
    public function live_2d_settings_tips_init() {
        $this->live_2d__options = get_option( 'live_2d_settings_option_name' );
        

        add_settings_section(
            'live_2d_setting_tips_section', // id
            __('提示框设置','live-2d'), // title
            array( $this, 'live_2d_tips_section_info' ), // callback
            'live-2d-settings-tips' // page
        );

        add_settings_field(
            'hitokoto_delay', // id
            __('启用一言','live-2d'), // title
            array( $this, 'hitokoto_delay_callback' ), // callback
            'live-2d-settings-tips', // page
            'live_2d_setting_tips_section' // section
        );

        add_settings_field(
            'hitokotoAPI', // id
            __('一言 API','live-2d'), // title
            array( $this, 'hitokotoAPI_callback' ), // callback
            'live-2d-settings-tips', // page
            'live_2d_setting_tips_section' // section
        );

        add_settings_field(
			'idle_motion_delay', // id
			__('待机动画延迟时间（毫秒）','live-2d'), // title
			array( $this, 'idle_motion_delay_callback' ), // callback
			'live-2d-settings-tips', // page
			'live_2d_setting_tips_section' // section
		);

        add_settings_field(
			'idle_motion', // id
			__('待机动画文件名','live-2d'), // title
			array( $this, 'idle_motion_callback' ), // callback
			'live-2d-settings-tips', // page
			'live_2d_setting_tips_section' // section
		);

        add_settings_field(
            'showCopyMessage', // id
            __('显示“复制内容”提示','live-2d'), // title
            array( $this, 'showCopyMessage_callback' ), // callback
            'live-2d-settings-tips', // page
            'live_2d_setting_tips_section' // section
        );

        add_settings_field(
            'showWelcomeMessage', // id
            __('显示进入面页欢迎词','live-2d'), // title
            array( $this, 'showWelcomeMessage_callback' ), // callback
            'live-2d-settings-tips', // page
            'live_2d_setting_tips_section' // section
        );

        add_settings_field(
            'waifuTipsSize', // id
            __('提示框大小','live-2d'), // title
            array( $this, 'waifuTipsSize_callback' ), // callback
            'live-2d-settings-tips', // page
            'live_2d_setting_tips_section' // section
        );

        add_settings_field(
            'waifuFontSize', // id
            __('提示框字号(px)','live-2d'), // title
            array( $this, 'waifuFontSize_callback' ), // callback
            'live-2d-settings-tips', // page
            'live_2d_setting_tips_section' // section
        );
        add_settings_field(
            'waifuTipTop', // id
            __('提示框位置(px)','live-2d'), // title
            array( $this, 'waifuTipTop_callback' ), // callback
            'live-2d-settings-tips', // page
            'live_2d_setting_tips_section' // section
        );

        add_settings_field(
            'waifuTipsColor', // id
            __('提示框背景色','live-2d'), // title
            array( $this, 'waifuTipsColor_callback' ), // callback
            'live-2d-settings-tips', // page
            'live_2d_setting_tips_section' // section
        );

        add_settings_field(
            'waifuBorderColor', // id
            __('边框颜色','live-2d'), // title
            array( $this, 'waifuBorderColor_callback' ), // callback
            'live-2d-settings-tips', // page
            'live_2d_setting_tips_section' // section
        );

        add_settings_field(
            'waifuShadowColor', // id
            __('阴影颜色','live-2d'), // title
            array( $this, 'waifuShadowColor_callback' ), // callback
            'live-2d-settings-tips', // page
            'live_2d_setting_tips_section' // section
        );

        add_settings_field(
            'waifuFontsColor', // id
            __('提示框文字颜色','live-2d'), // title
            array( $this, 'waifuFontsColor_callback' ), // callback
            'live-2d-settings-tips', // page
            'live_2d_setting_tips_section' // section
        );

        add_settings_field(
            'waifuHighlightColor', // id
            __('高亮文字颜色','live-2d'), // title
            array( $this, 'waifuHighlightColor_callback' ), // callback
            'live-2d-settings-tips', // page
            'live_2d_setting_tips_section' // section
        );
    }

    public function live_2d_tips_section_info(){
        
    }

    public function hitokoto_delay_callback() {
        printf(
            '<input type="number" name="live_2d_settings_option_name[hitokoto_delay]" id="hitokoto_delay" value="%s" min = "0" max="3600" > '.esc_html__('秒','live-2d').'
            <p>'.esc_html__('待机动画延迟时间，单位为秒，默认值为30秒。如果设置为0，则禁用待机动画。','live-2d').'</p>',
            isset( $this->live_2d__options['hitokoto_delay'] ) ? esc_attr( $this->live_2d__options['hitokoto_delay']) : 30
        );
    }

    public function hitokotoAPI_callback() {
        ?> <select name="live_2d_settings_option_name[hitokotoAPI]" id="hitokotoAPI">
            <?php $selected = (isset( $this->live_2d__options['hitokotoAPI'] ) && $this->live_2d__options['hitokotoAPI'] === 'hitokoto.cn') ? 'selected' : '' ; ?>
            <option <?php echo $selected; ?>>hitokoto.cn</option>
            <?php $selected = (isset( $this->live_2d__options['hitokotoAPI'] ) && $this->live_2d__options['hitokotoAPI'] === 'jinrishici.com') ? 'selected' : '' ; ?>
            <option <?php echo $selected; ?>>jinrishici.com</option>
            <?php $selected = (isset( $this->live_2d__options['hitokotoAPI'] ) && $this->live_2d__options['hitokotoAPI'] === 'fghrsh.net') ? 'selected' : '' ; ?>
            <option <?php echo $selected; ?>>fghrsh.net</option>
        </select> <?php
    }

    //待机动画延迟时间
    public function idle_motion_delay_callback() {
        printf(
            '<input type="number" name="live_2d_settings_option_name[idle_motion_delay]" id="idle_motion_delay" value="%s" min = "0" max="3600" > '.esc_html__('秒','live-2d').'
            <p>'.esc_html__('待机动画延迟时间，单位为秒，默认值为30秒。如果设置为0，则禁用待机动画。','live-2d').'</p>',
            isset( $this->live_2d__options['idle_motion_delay'] ) ? esc_attr( $this->live_2d__options['idle_motion_delay']) : 30
        );
    }

    //待机动画文件名
	public function idle_motion_callback() {
		live2D_Utils::loopMsg('idle_motion','List',true,'live_2d_settings_option_name');
		echo '<p>'.esc_html__('待机动画文件名, 文件是*.motion3.json','live-2d').'</p>';
	}

    public function showCopyMessage_callback() {
        ?> <fieldset><?php $checked = ( isset( $this->live_2d__options['showCopyMessage'] ) && $this->live_2d__options['showCopyMessage'] === true ) ? 'checked' : '' ; ?>
        <label for="showCopyMessage-0"><input type="radio" name="live_2d_settings_option_name[showCopyMessage]" id="showCopyMessage-0" value="1" <?php echo $checked; ?>> <?php esc_html_e('显示','live-2d') ?></label><br>
        <?php $checked = ( isset( $this->live_2d__options['showCopyMessage'] ) && $this->live_2d__options['showCopyMessage'] === false ) ? 'checked' : '' ; ?>
        <label for="showCopyMessage-1"><input type="radio" name="live_2d_settings_option_name[showCopyMessage]" id="showCopyMessage-1" value="0" <?php echo $checked; ?>> <?php esc_html_e('隐藏','live-2d') ?></label></fieldset> <?php
    }

    public function showWelcomeMessage_callback() {
        ?> <fieldset><?php $checked = ( isset( $this->live_2d__options['showWelcomeMessage'] ) && $this->live_2d__options['showWelcomeMessage'] === true ) ? 'checked' : '' ; ?>
        <label for="showWelcomeMessage-0"><input type="radio" name="live_2d_settings_option_name[showWelcomeMessage]" id="showWelcomeMessage-0" value="1" <?php echo $checked; ?>> <?php esc_html_e('显示','live-2d') ?></label><br>
        <?php $checked = ( isset( $this->live_2d__options['showWelcomeMessage'] ) && $this->live_2d__options['showWelcomeMessage'] === false ) ? 'checked' : '' ; ?>
        <label for="showWelcomeMessage-1"><input type="radio" name="live_2d_settings_option_name[showWelcomeMessage]" id="showWelcomeMessage-1" value="0" <?php echo $checked; ?>> <?php esc_html_e('隐藏','live-2d') ?></label></fieldset> <?php
    }
    
    public function waifuTipsSize_callback() {
        printf(
            '<input type="number" name="live_2d_settings_option_name[waifuTipsSize][width]" id="waifuTipsSize_width" value="%s" min = "0" max="1024" > x
            <input type="number" name="live_2d_settings_option_name[waifuTipsSize][height]" id="waifuTipsSize_height" value="%s" min = "0" max="1024" >
            <p>'.esc_html__('由于提示大小不同，请自行设置：宽度 x 高度','live-2d').'</p>',
            isset( $this->live_2d__options['waifuTipsSize']['width'] ) ? esc_attr( $this->live_2d__options['waifuTipsSize']['width']) : 250,
            isset( $this->live_2d__options['waifuTipsSize']['height'] ) ? esc_attr( $this->live_2d__options['waifuTipsSize']['height']) : 70
        );
    }

    public function waifuFontSize_callback() {
        printf(
            '<input type="number" name="live_2d_settings_option_name[waifuFontSize]" id="waifuFontSize" value="%s" min = "0" max="50" >',
            isset( $this->live_2d__options['waifuFontSize'] ) ? esc_attr( $this->live_2d__options['waifuFontSize']) : 12
        );
    }

    public function waifuTipTop_callback() {
        printf(
            '<input type="number" name="live_2d_settings_option_name[waifuTipTop]" id="waifuTipTop" value="%s"  min = "-1000" max="1000" >
            <p>'.esc_html__('数字越大越靠上','live-2d').'</p>',
            isset( $this->live_2d__options['waifuTipTop'] ) ? esc_attr( $this->live_2d__options['waifuTipTop']) : 0
        );
    }

    public function waifuTipsColor_callback(){
        printf(
            '<input type="text" class="color-picker" data-alpha-enabled="true" name="live_2d_settings_option_name[waifuTipsColor]" id="waifuTipsColor" value="%s" />',
            isset( $this->live_2d__options['waifuTipsColor'] ) ? esc_attr( $this->live_2d__options['waifuTipsColor']) : ''
        );
    }

    public function waifuBorderColor_callback(){
        printf(
            '<input type="text" class="color-picker" data-alpha-enabled="true" name="live_2d_settings_option_name[waifuBorderColor]" id="waifuBorderColor" value="%s" />',
            isset( $this->live_2d__options['waifuBorderColor'] ) ? esc_attr( $this->live_2d__options['waifuBorderColor']) : ''
        );
    }

    public function waifuShadowColor_callback(){
        printf(
            '<input type="text" class="color-picker" data-alpha-enabled="true" name="live_2d_settings_option_name[waifuShadowColor]" id="waifuShadowColor" value="%s" />',
            isset( $this->live_2d__options['waifuShadowColor'] ) ? esc_attr( $this->live_2d__options['waifuShadowColor']) : ''
        );
    }

    public function waifuFontsColor_callback(){
        printf(
            '<input type="text" class="color-picker" name="live_2d_settings_option_name[waifuFontsColor]" id="waifuFontsColor" value="%s"  />',
            isset( $this->live_2d__options['waifuFontsColor'] ) ? esc_attr( $this->live_2d__options['waifuFontsColor']) : ''
        );
    }

    public function waifuHighlightColor_callback(){
        printf(
            '<input type="text" class="color-picker" name="live_2d_settings_option_name[waifuHighlightColor]" id="waifuHighlightColor" value="%s"  />',
            isset( $this->live_2d__options['waifuHighlightColor'] ) ? esc_attr( $this->live_2d__options['waifuHighlightColor']) : ''
        );
    }
}
?>