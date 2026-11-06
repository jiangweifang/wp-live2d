<?php
if (!defined('ABSPATH')) {
    exit;
}

class live2d_Extensions
{
    const API_VERSION = 1;
    const PRO_URL = 'https://www.live2dweb.com/Downloads/WordPressPro';

    public static function status()
    {
        $status = array('available' => false, 'reason' => 'inactive', 'version' => '');
        return apply_filters('live2d_extension_status', $status);
    }

    public static function api_types()
    {
        return apply_filters('live2d_api_types', array(
            'local' => array(
                'label' => __('本地部署旧版模型', 'live-2d'),
                'description' => __('使用本站保存的旧版 Live2D 模型。', 'live-2d'),
            ),
            'remote' => array(
                'label' => __('自行部署旧版模型', 'live-2d'),
                'description' => __('使用旧版模型 JSON 或旧版模型 API。', 'live-2d'),
            ),
        ));
    }

    public static function notice()
    {
        if (!current_user_can('manage_options')) return;
        $status = self::status();
        $labels = array(
            'inactive' => __('Pro 未安装或尚未启用', 'live-2d'),
            'incompatible' => __('Pro 与基础插件版本不兼容', 'live-2d'),
            'license' => __('Pro 已安装，请使用有对应权益的官网账号完成授权；升级账号后请在登录页重新保存 Key 刷新权益。', 'live-2d'),
            'ready' => __('Pro 已启用', 'live-2d'),
        );
        $label = $labels[$status['reason']] ?? __('增强插件状态不可用', 'live-2d');
        ?>
        <div class="notice notice-info inline live2d-edition-notice">
            <h2><?php esc_html_e('免费基础版与 Pro 增强版', 'live-2d'); ?></h2>
            <p><?php esc_html_e('为区分旧版 Live2D 与依赖 Cubism Core 的新版模型，并分别管理许可和分发，插件现拆分为免费基础版与 Pro 增强版。旧版模型和共同功能保留在免费版；新版模型由 Pro 提供。', 'live-2d'); ?></p>
            <p><?php esc_html_e('此次拆分不会主动删除已有模型、设置或授权数据。已购用户请使用原账号前往官网确认对应权益并获取 Pro，不需要为同一权益重复付款。', 'live-2d'); ?></p>
            <p><strong><?php echo esc_html($label); ?></strong>
                <a class="button button-secondary" href="<?php echo esc_url(self::PRO_URL); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('前往官网获取 Pro', 'live-2d'); ?></a>
                <?php if (current_user_can('install_plugins')) : ?>
                    <a class="button button-primary" href="<?php echo esc_url(is_multisite() ? network_admin_url('plugin-install.php?tab=upload') : admin_url('plugin-install.php?tab=upload')); ?>"><?php esc_html_e('上传 Pro 增强补丁', 'live-2d'); ?></a>
                <?php endif; ?>
            </p>
            <p><?php esc_html_e('下载 Pro ZIP 后，点击上传按钮进入 WordPress 官方安装页，上传并启用补丁。版本与账号权益验证通过后，本设置页会显示 Pro 功能；无需替换 Free。请勿将付费文件直接覆盖到 Free 目录。', 'live-2d'); ?></p>
            <?php
            $options = get_option('live_2d_settings_option_name');
            $type = is_array($options) ? live2d_normalize_api_type($options['apiType'] ?? 'remote') : 'remote';
            if (!isset(self::api_types()[$type])) {
                echo '<p class="description">' . esc_html__('已保存的模型类型当前不可用。原配置和模型保持不变；请启用兼容且已授权的 Pro 后恢复，不会自动替换为其他角色。', 'live-2d') . '</p>';
            }
            ?>
        </div>
        <?php
        if (class_exists('live2d_ModelMigration')) live2d_ModelMigration::notice();
    }
}
