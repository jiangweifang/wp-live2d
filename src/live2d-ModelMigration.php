<?php
if (!defined('ABSPATH')) {
    exit;
}

class live2d_ModelMigration
{
    const BACKUP_OPTION = 'live2d_model_migration_backup';

    public static function register()
    {
        add_action('admin_post_live2d_migrate_models', array(__CLASS__, 'handle'));
        add_filter('option_live_2d_settings_option_name', array(__CLASS__, 'normalize_url'));
    }

    public static function normalize_url($settings)
    {
        $old = live2d_ModelFiles::legacy_url();
        if (get_option('live2d_model_storage_migrated', false) && is_array($settings)
            && isset($settings['modelAPI']) && is_string($settings['modelAPI'])
            && strpos($settings['modelAPI'], $old) === 0) {
            $settings['modelAPI'] = live2d_ModelFiles::persistent_url() . substr($settings['modelAPI'], strlen($old));
        }
        return $settings;
    }

    public static function migrate()
    {
        if (!current_user_can('manage_options')) return new WP_Error('forbidden', 'Administrator permission is required.');
        if (is_multisite()) return new WP_Error('network_migration', 'Multisite legacy models require a network administrator backup and per-site migration.');
        $legacy = live2d_ModelFiles::legacy_dir();
        $target = live2d_ModelFiles::persistent_dir();
        $sourceReal = realpath($legacy);
        if ($sourceReal === false || is_link(rtrim($legacy, '/\\'))) {
            return new WP_Error('missing_legacy_models', 'The legacy model directory is missing or unsafe. Restore a verified backup before continuing.');
        }
        if (is_link(rtrim($target, '/\\')) || (!is_dir($target) && !wp_mkdir_p($target))) {
            return new WP_Error('storage_unavailable', 'Cannot create persistent model storage.');
        }
        $keys = array('live_2d_settings_option_name', 'live_2d_advanced_option_name', 'live_2d_settings_user_token');
        if (!get_option(self::BACKUP_OPTION, false)) {
            $backup = array('site' => get_home_url(), 'options' => array());
            foreach ($keys as $key) $backup['options'][$key] = get_option($key);
            if (!add_option(self::BACKUP_OPTION, $backup, '', false)) {
                return new WP_Error('backup_failed', 'Settings backup could not be saved.');
            }
        }
        $count = 0;
        try {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($legacy, FilesystemIterator::SKIP_DOTS));
            foreach ($files as $file) {
                if ($file->isLink()) return new WP_Error('unsafe_model', 'A symbolic link was found in the legacy model directory.');
                if (!$file->isFile()) continue;
                $relative = substr(wp_normalize_path($file->getPathname()), strlen(trailingslashit(wp_normalize_path($legacy))));
                $valid = live2d_ModelFiles::validate_file_content($file->getPathname(), $relative);
                if (is_wp_error($valid)) return $valid;
                $dest = live2d_ModelFiles::confined_path($target, $relative, true);
                if (is_wp_error($dest)) return $dest;
                $hash = hash_file('sha256', $file->getPathname());
                if ($hash === false) return new WP_Error('read_failed', 'A source model file could not be read.');
                if (is_file($dest)) {
                    if (hash_file('sha256', $dest) !== $hash) {
                        return new WP_Error('migration_conflict', 'A different file already exists in persistent storage; neither copy was overwritten.');
                    }
                } else {
                    $temp = $dest . '.migration-' . bin2hex(random_bytes(12));
                    try {
                        if (!copy($file->getPathname(), $temp) || hash_file('sha256', $temp) !== $hash) {
                            return new WP_Error('copy_failed', 'A model file could not be copied and verified. The original files have been retained.');
                        }
                        // Publish without overwriting an existing file, including concurrent uploads.
                        if (!function_exists('link') || !link($temp, $dest)) {
                            return new WP_Error('publish_failed', 'Storage cannot publish this file without overwriting. Original files are retained; ask the server administrator to complete the copy.');
                        }
                    } finally {
                        if (is_file($temp)) wp_delete_file($temp);
                    }
                }
                $count++;
            }
        } catch (UnexpectedValueException $error) {
            return new WP_Error('read_failed', 'The legacy model directory cannot be read.');
        }
        // Resolve old URLs on read; do not resubmit Pro settings through an older or Free-only sanitizer.
        update_option('live2d_model_storage_migrated', true, false);
        if (!get_option('live2d_model_storage_migrated', false)) return new WP_Error('settings_failed', 'The storage migration marker could not be saved.');
        return $count;
    }

    public static function handle()
    {
        if (!current_user_can('manage_options')) wp_die(esc_html__('没有权限', 'live-2d'), '', array('response' => 403));
        check_admin_referer('live2d_migrate_models');
        $result = self::migrate();
        set_transient('live2d_migration_notice_' . get_current_user_id(), array(
            'error' => is_wp_error($result),
            'message' => is_wp_error($result) ? $result->get_error_message()
                // translators: %d: number of verified model files copied.
                : sprintf(__('模型文件已校验并迁移（%d 个），原文件和设置备份均已保留。', 'live-2d'), $result),
        ), 300);
        wp_safe_redirect(admin_url('options-general.php?page=live-2d-options'));
        exit;
    }

    public static function notice()
    {
        if (!current_user_can('manage_options')) return;
        $message = get_transient('live2d_migration_notice_' . get_current_user_id());
        if (is_array($message)) {
            delete_transient('live2d_migration_notice_' . get_current_user_id());
            echo '<div class="notice ' . esc_attr($message['error'] ? 'notice-error' : 'notice-success') . ' inline"><p>' . esc_html($message['message']) . '</p></div>';
        }
        if (get_option('live2d_model_storage_migrated', false) || !is_dir(live2d_ModelFiles::legacy_dir())) return;
        echo '<div class="notice notice-warning inline"><p>' . esc_html__('旧模型仍位于插件目录。请在升级或删除旧插件前，将模型复制到持久存储；迁移会校验文件并保留原文件、设置及授权备份。', 'live-2d') . '</p>';
        if (is_multisite()) {
            echo '<p>' . esc_html__('多站点共用旧模型目录，请先由网络管理员完成全网备份及逐站迁移，不要直接替换旧插件。', 'live-2d') . '</p></div>';
            return;
        }
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('live2d_migrate_models');
        echo '<input type="hidden" name="action" value="live2d_migrate_models">';
        submit_button(__('备份设置并迁移旧模型', 'live-2d'), 'secondary', 'submit', false);
        echo '</form></div>';
    }
}
