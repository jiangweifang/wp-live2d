<?php
class Live2D_Widget extends WP_Widget{

    public function __construct() {
        // 主要内容方法
        $widget_ops = array(
			'classname'                   => 'live2D_Widget',
			'description'                 => __( '您可以把看板娘放在一个指定的区域' ),
			'customize_selective_refresh' => true,
		);
		//$control_ops = array('width' => 400, 'height' => 300);
		parent::__construct('pages','Live 2D',$widget_ops);  

	}
 
    public function widget( $args, $instance ) {
        extract( $args );
        $title = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title']);
        echo $before_widget;
        if ( $title )
            echo $before_title . $title . $after_title; 
        ?>
        <div id="live2d-widget" class="waifu">
            <div class="waifu-tips"></div>
            <canvas id="live2d" class="live2d"></canvas>
            <div class="waifu-tool">
                <span class="fui-home"></span>
                <span class="fui-chat"></span>
                <span class="fui-eye"></span>
                <span class="fui-user"></span>
                <span class="fui-photo"></span>
                <span class="fui-info-circle"></span>
                <span class="fui-cross"></span>
            </div>
        </div>
        <script type="text/javascript">
        var settings_Json = '<?php echo json_encode(get_option( 'live_2d_settings_option_name' )); ?>';
        jQuery(function(){
            initModel("<?php echo LIVE2D_ASSETS ?>waifu-tips.json",JSON.parse(settings_Json));
        });
        </script>
        <?php
        echo $after_widget;
    }

    public function update( $new_instance, $old_instance ){
        return $new_instance;
    }

    public function form( $instance ){
        $title = '';
        if(isset($instance['title'])){
            $title = esc_attr($instance['title']);
        }
        ?>
	    <p>
        <label for="<?php echo $this->get_field_id('title'); ?>"><?php esc_attr_e('Title:'); ?> 
        <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
        </label>
        </p>
	    <?php
    }
}
?>