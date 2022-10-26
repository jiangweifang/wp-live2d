<?php
class live2D_Settings_Base {

    private $live_2d__options;
    public function live_2d_settings_base_init() {

        $this->live_2d__options = get_option( 'live_2d_settings_option_name' );

        add_settings_section(
            'live_2d_setting_base_section', // id
            __('基础设置','live-2d'), // title
            array( $this, 'live_2d__section_info' ), // callback
            'live-2d-settings-base' // page
        );

        add_settings_field(
            'live2dLayoutType', // id
            __('看板娘模式','live-2d'), // title
            array( $this, 'live2dLayoutType_callback' ), // callback
            'live-2d-settings-base', // page
            'live_2d_setting_base_section' // section
        );

        add_settings_field(
            'modelAPI', // id
            __('模型 API','live-2d'), // title
            array( $this, 'modelAPI_callback' ), // callback
            'live-2d-settings-base', // page
            'live_2d_setting_base_section' // section
        );

        add_settings_field(
            'modelId', // id
            __('默认模型 ID','live-2d'), // title
            array( $this, 'modelId_callback' ), // callback
            'live-2d-settings-base', // page
            'live_2d_setting_base_section' // section
        );

        add_settings_field(
            'modelTexturesId', // id
            __('默认材质 ID','live-2d'), // title
            array( $this, 'modelTexturesId_callback' ), // callback
            'live-2d-settings-base', // page
            'live_2d_setting_base_section' // section
        );

        add_settings_field(
            'modelZoomNumberV2', // id
            __('模型缩放倍数','live-2d'), // title
            array( $this, 'modelZoomNumberV2_callback' ), // callback
            'live-2d-settings-base', // page
            'live_2d_setting_base_section' // section
        );

        add_settings_field(
            'modelXYaxis', // id
            __('看板娘位置','live-2d'), // title
            array( $this, 'modelXYaxis_callback' ), // callback
            'live-2d-settings-base', // page
            'live_2d_setting_base_section' // section
        );

        add_settings_field(
			'sdkUrl', // id
			__('Cubism Core for Web <br/> 引用地址','live-2d'), // title
			array( $this, 'sdkUrl_callback' ), // callback
			'live-2d-settings-base', // page
			'live_2d_setting_base_section' // section
		);

        add_settings_field(
			'defineHitAreaName', // id
			__('moc3模型自定义动作','live-2d'), // title
			array( $this, 'defineHitAreaName_callback' ), // callback
			'live-2d-settings-base', // page
			'live_2d_setting_base_section' // section
		);
    }
    
    public function live_2d__section_info() {
        printf(
            '<input type="hidden" name="live_2d_settings_option_name[homePageUrl]" id="homePageUrl" value="%s">',
            get_home_url()
        );
    }

    public function live2dLayoutType_callback() {
        ?> <fieldset><?php $checked = ( isset( $this->live_2d__options['live2dLayoutType'] ) && $this->live_2d__options['live2dLayoutType'] === true ) ? 'checked' : '' ; ?>
        <label for="live2dLayoutType-0"><input type="radio" name="live_2d_settings_option_name[live2dLayoutType]" id="live2dLayoutType-0" value="1" <?php echo $checked; ?>> <?php esc_html_e('页面','live-2d') ?></label><br>
        <?php $checked = ( isset( $this->live_2d__options['live2dLayoutType'] ) && $this->live_2d__options['live2dLayoutType'] === false ) ? 'checked' : '' ; ?>
        <label for="live2dLayoutType-1"><input type="radio" name="live_2d_settings_option_name[live2dLayoutType]" id="live2dLayoutType-1" value="0" <?php echo $checked; ?>> <?php esc_html_e('小工具(beta)','live-2d') ?></label></fieldset> <?php
    }

    public function modelAPI_callback() {
        printf(
            '<input class="regular-text" type="text" name="live_2d_settings_option_name[modelAPI]" id="modelAPI" value="%s">',
            isset( $this->live_2d__options['modelAPI'] ) ? esc_attr( $this->live_2d__options['modelAPI']) : ''
        );
    }

    public function modelId_callback() {
        printf(
            '<input class="regular-text" type="text" name="live_2d_settings_option_name[modelId]" id="modelId" value="%s">',
            isset( $this->live_2d__options['modelId'] ) ? esc_attr( $this->live_2d__options['modelId']) : ''
        );
        echo '<p>'.esc_html__('您可以在此处直接填写模型ID','live-2d').'</p>';
    }

    public function modelTexturesId_callback() {
        printf(
            '<input class="regular-text" type="text" name="live_2d_settings_option_name[modelTexturesId]" id="modelTexturesId" value="%s">',
            isset( $this->live_2d__options['modelTexturesId'] ) ? esc_attr( $this->live_2d__options['modelTexturesId']) : ''
        );
        echo '<p>'.esc_html__('您可以在此处直接填写皮肤ID','live-2d').'</p>';
    }

    public function modelZoomNumberV2_callback(){
        printf(
            '<input type="number" name="live_2d_settings_option_name[modelPoint][zoom]" id="modelZoomNumberV2" value="%s" step="0.1" min="1.0" max="10.0" />
            <p>'.esc_html__('设置看板娘在画框中的缩放比例，最小1倍，最大10倍，可以有小数点','live-2d').'</p>',
            isset( $this->live_2d__options['modelPoint']['zoom'] ) ? esc_attr( $this->live_2d__options['modelPoint']['zoom']) : '1.0'
        );
    }

    public function modelXYaxis_callback(){
        printf(
            'x: <input type="number" name="live_2d_settings_option_name[modelPoint][x]" id="modelPoint_x" value="%s" min="-100" max="100" /> 
            y: <input type="number" name="live_2d_settings_option_name[modelPoint][y]" id="modelPoint_y" value="%s" min="-100" max="100" />
            <p>'.esc_html__('设置看板娘的位置，可以是负数','live-2d').'</p>',
            isset( $this->live_2d__options['modelPoint']['x'] ) ? esc_attr( $this->live_2d__options['modelPoint']['x']) : '0',
            isset( $this->live_2d__options['modelPoint']['y'] ) ? esc_attr( $this->live_2d__options['modelPoint']['y']) : '0'
        );
    }

    public function sdkUrl_callback (){
        printf(
            '<input class="regular-text" type="text" name="live_2d_settings_option_name[sdkUrl]" id="sdkUrl" value="%s">',
            isset( $this->live_2d__options['sdkUrl'] ) ? esc_attr( $this->live_2d__options['sdkUrl']) : 'https://cubism.live2d.com/sdk-web/cubismcore/live2dcubismcore.min.js'
        );
        echo '<p>'.esc_html__('如未授权请勿修改此地址，擅自修改此地址引发的法律问题与插件作者无关。','live-2d').'</p>
        <p>'. esc_html__('软件许可协议：', 'live-2d') 
        .'<a href = "https://www.live2d.com/eula/live2d-proprietary-software-license-agreement_en.html" target="_blank">Live2D Proprietary Software License Agreement</a> 
        | <a href = "https://www.live2d.com/eula/live2d-open-software-license-agreement_en.html" target="_blank">Live2D Open Software License Agreement</a> </p>';
    }

    public function defineHitAreaName_callback(){
        live2D_Utils::loopMsg('defineHitAreaName','List',true,'live_2d_settings_option_name');
        echo '<p>'.esc_html__('请输入文件名（不包含扩展名），例如："touch_head.motion3.json"请在输入框中输入touch_head','live-2d').'</p>';
    }
}
?>