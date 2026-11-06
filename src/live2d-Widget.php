<?php
class Live2D_Widget extends WP_Widget
{

    public function __construct()
    {
        // 主要内容方法
        $widget_ops = array(
            'classname'                   => 'live2D_Widget',
            'description'                 => __('您可以把看板娘放在一个指定的区域', 'live-2d'),
            'customize_selective_refresh' => true,
        );
        //$control_ops = array('width' => 400, 'height' => 300);
        parent::__construct('pages', 'Live 2D', $widget_ops);
    }

    public function widget($args, $instance)
    {
        $title = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title']);
        echo wp_kses_post($args['before_widget']);
        if ($title)
            echo wp_kses_post($args['before_title']) . esc_html($title) . wp_kses_post($args['after_title']);
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
            <div class="gptInput"><input type="text" id="live2dChatText" /><span><button class="wp-element-button" id="live2dSend"><?php esc_html_e('发送', 'live-2d'); ?></button></span></div>
        </div>
        <?php // 必须用 type="module": live2dwebsdk.min.js 是 ES module(defer 执行),
              // 经典 inline <script> 会在 module 之前同步执行, 那时 window.initLive2dWeb
              // 还未被赋值. inline module 与 src module 共用 defer 队列, 按文档顺序执行. ?>
        <script type="module">
            const initialize = () => {
                if (typeof window.initLive2dWeb === 'function') {
                    window.initLive2dWeb();
                }
            };
            if (document.readyState === 'complete') {
                initialize();
            } else {
                window.addEventListener('load', initialize, { once: true });
            }
        </script>
    <?php
        echo wp_kses_post($args['after_widget']);
    }

    public function update($new_instance, $old_instance)
    {
        return array('title' => sanitize_text_field($new_instance['title'] ?? ''));
    }

    public function form($instance)
    {
        $title = '';
        if (isset($instance['title'])) {
            $title = esc_attr($instance['title']);
        }
    ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Title:', 'live-2d'); ?>
                <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
            </label>
        </p>
<?php
    }
}
?>