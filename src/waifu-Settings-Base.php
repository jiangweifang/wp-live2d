<?php
class live2D_Settings_Base
{

    private $live_2d__options;

    public function live_2d_settings_base_init()
    {
        $this->live_2d__options = get_option('live_2d_settings_option_name');

        add_settings_section(
            'live_2d_setting_base_section', // id
            __('基础设置', 'live-2d'), // title
            array($this, 'live_2d__section_info'), // callback
            'live-2d-settings-base' // page
        );

            add_settings_field(
                'live2dLayoutType', // id
                __('看板娘模式', 'live-2d'), // title
                array($this, 'live2dLayoutType_callback'), // callback
                'live-2d-settings-base', // page
                'live_2d_setting_base_section' // section
            );
        add_settings_field(
            'apiType', // id
            __('API 方式', 'live-2d'), // title
            array($this, 'apiType_callback'), // callback
            'live-2d-settings-base', // page
            'live_2d_setting_base_section' // section
        );
        add_settings_field(
            'modelAPI', // id
            __('模型 API', 'live-2d'), // title
            array($this, 'modelAPI_callback'), // callback
            'live-2d-settings-base', // page
            'live_2d_setting_base_section' // section
        );

        add_settings_field(
            'modelId', // id
            __('默认模型 ID', 'live-2d'), // title
            array($this, 'modelId_callback'), // callback
            'live-2d-settings-base', // page
            'live_2d_setting_base_section' // section
        );

        add_settings_field(
            'modelTexturesId', // id
            __('默认材质 ID', 'live-2d'), // title
            array($this, 'modelTexturesId_callback'), // callback
            'live-2d-settings-base', // page
            'live_2d_setting_base_section' // section
        );

    }

    public function live_2d__section_info()
    {
        printf(
            '<input type="hidden" name="live_2d_settings_option_name[homePageUrl]" id="homePageUrl" value="%s">',
            esc_url(get_home_url())
        );
    }

    public function apiType_callback()
    {
        $current = live2d_normalize_api_type($this->live_2d__options['apiType'] ?? 'remote');
        $types = live2d_Extensions::api_types();
        echo '<fieldset>';
        foreach ($types as $value => $type) {
            printf('<label><input type="radio" class="apiType" name="live_2d_settings_option_name[apiType]" value="%s" %s> %s</label><p class="description">%s</p>',
                esc_attr($value), checked($current, $value, false), esc_html($type['label']), esc_html($type['description']));
        }
        if (!isset($types[$current])) {
            echo '<p>' . esc_html__('已保存的模型类型需要尚未启用的增强插件，当前设置会保留。', 'live-2d') . '</p>';
        }
        echo '</fieldset>';
    }

    public function live2dLayoutType_callback()
    {
    ?>
        <fieldset>
            <?php
            $checked = (isset($this->live_2d__options['live2dLayoutType']) && $this->live_2d__options['live2dLayoutType'] === true);
            ?>
            <label for="live2dLayoutType-0"><input type="radio" name="live_2d_settings_option_name[live2dLayoutType]" id="live2dLayoutType-0" value="1" <?php echo $checked ? 'checked' : ''; ?>> <?php esc_html_e('页面', 'live-2d') ?></label><br>
            <label for="live2dLayoutType-1"><input type="radio" name="live_2d_settings_option_name[live2dLayoutType]" id="live2dLayoutType-1" value="0" <?php echo !$checked ? 'checked' : ''; ?>> <?php esc_html_e('小工具(beta)', 'live-2d') ?></label>
        </fieldset>
        <?php
    }

    public function modelAPI_callback()
    {
        // 总是渲染一个普通文本输入框。在 'local' 模式下后台会在 sanitize 阶段
        // 强制写回本地 REST URL(详见 src/waifu-Settings.php),前端 live2d-admin.ts
        // 会根据 apiType 动态设 readonly 与提示文案。
        printf(
            '<input class="regular-text" type="url" name="live_2d_settings_option_name[modelAPI]" id="modelAPI" value="%s" placeholder="%s">',
            isset($this->live_2d__options['modelAPI']) ? esc_attr($this->live_2d__options['modelAPI']) : '',
            esc_attr(__('https://example.com/Samples/Resources/  或  https://example.com/Samples/Resources/haru/haru.model3.json', 'live-2d'))
        );
        echo '<p class="description live2d-modelAPI-hint live2d-modelAPI-hint-default">' . esc_html__('填写当前模式对应的模型 JSON 或模型 API 地址，具体要求见上方模式说明。', 'live-2d') . '</p>';
    }

    public function modelId_callback()
    {
        // 'local' 模式下 live2d-admin.ts 会把本文本框 replaceWith 为 select(
        // 调 wp_ajax_get_model_list 拉本地列表);'remote'/'custom' 模式下保持文本框。
        printf(
            '<input class="regular-text" type="text" name="live_2d_settings_option_name[modelId]" id="modelId" value="%s">',
            isset($this->live_2d__options['modelId']) ? esc_attr($this->live_2d__options['modelId']) : ''
        );
        echo '<p class="description">' . esc_html__('选择或填写默认加载的模型 ID(具体要求取决于上面「API 方式」)。', 'live-2d') . '</p>';
    }

    public function modelTexturesId_callback()
    {
        // 同 modelId,'local' 模式下转为 select 列出该模型可用皮肤。
        printf(
            '<input class="regular-text" type="text" name="live_2d_settings_option_name[modelTexturesId]" id="modelTexturesId" value="%s">',
            isset($this->live_2d__options['modelTexturesId']) ? esc_attr($this->live_2d__options['modelTexturesId']) : ''
        );
        echo '<p class="description">' . esc_html__('选择或填写默认皮肤 ID；Cubism 4+ 模型不使用此项，可留空。', 'live-2d') . '</p>';
    }


}
?>