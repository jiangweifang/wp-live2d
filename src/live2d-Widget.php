<?php
class Live2D_Widget extends WP_Widget
{

    public function __construct()
    {
        // 主要内容方法
        $widget_ops = array(
            'classname'                   => 'live2D_Widget',
            'description'                 => __('您可以把看板娘放在一个指定的区域'),
            'customize_selective_refresh' => true,
        );
        //$control_ops = array('width' => 400, 'height' => 300);
        parent::__construct('pages', 'Live 2D', $widget_ops);
    }

    public function widget($args, $instance)
    {
        extract($args);
        $title = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title']);
        echo $before_widget;
        if ($title)
            echo $before_title . $title . $after_title;
?>
        <div id="live2d-widget" class="waifu">
            <div class="waifu-tips"></div>
            <canvas id="live2d" class="live2d"></canvas>
            <div class="waifu-tool">
                <span class="fui-home"><i class="fa-solid fa-house"></i></span>
                <span class="fui-chat"><i class="fa-solid fa-message"></i></span>
                <span class="fui-bot"><i class="fa-solid fa-robot"></i></span>
                <span class="fui-eye"><i class="fa-solid fa-eye"></i></span>
                <span class="fui-user"><i class="fa-solid fa-user"></i></span>
                <span class="fui-photo"><i class="fa-solid fa-image"></i></span>
                <span class="fui-info-circle"><i class="fa-solid fa-circle-info"></i></span>
                <span class="fui-cross"><i class="fa-solid fa-circle-xmark"></i></span>
            </div>
            <div class="gptInput"><input type="text" id="live2dChatText" /><span><button class="wp-element-button" id="live2dSend">发送</button></span></div>
        </div>
        <script type="text/javascript">
            window.onload = initLive2dWeb();
        </script>
    <?php
        echo $after_widget;
    }

    public function update($new_instance, $old_instance)
    {
        return $new_instance;
    }

    public function form($instance)
    {
        $title = '';
        if (isset($instance['title'])) {
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