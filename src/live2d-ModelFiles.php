<?php
if (!defined('ABSPATH')) {
    exit;
}

class live2d_ModelFiles
{
    public static function root_dir()
    {
        if (!get_option('live2d_model_storage_migrated', false) && is_dir(self::legacy_dir())) return self::legacy_dir();
        return self::persistent_dir();
    }

    public static function persistent_dir()
    {
        $uploads = wp_get_upload_dir();
        return trailingslashit(wp_normalize_path($uploads['basedir'])) . 'live2d-models/';
    }

    public static function root_url()
    {
        if (!get_option('live2d_model_storage_migrated', false) && is_dir(self::legacy_dir())) return self::legacy_url();
        return self::persistent_url();
    }

    public static function persistent_url()
    {
        $uploads = wp_get_upload_dir();
        return trailingslashit($uploads['baseurl']) . 'live2d-models/';
    }

    public static function legacy_dir()
    {
        return plugin_dir_path(self::legacy_plugin_file()) . 'model/';
    }

    public static function legacy_url()
    {
        return plugin_dir_url(self::legacy_plugin_file()) . 'model/';
    }

    private static function legacy_plugin_file()
    {
        if (defined('LIVE2D_PLUGIN_FILE')) return LIVE2D_PLUGIN_FILE;
        if (class_exists('live2d_SDK')) {
            return dirname((new ReflectionClass('live2d_SDK'))->getFileName(), 2) . '/wordpress-live2d.php';
        }
        return WP_PLUGIN_DIR . '/live-2d/wordpress-live2d.php';
    }

    public static function validate_relative_file($path)
    {
        if (!is_string($path) || $path === '' || strlen($path) > 512) {
            return new WP_Error('invalid_path', '模型文件路径为空或超长');
        }
        foreach (explode('/', $path) as $segment) {
            if (!preg_match('/^[A-Za-z0-9_-][A-Za-z0-9._-]*$/D', $segment)
                || substr($segment, -1) === '.'
                || preg_match('/^(con|prn|aux|nul|com[0-9]|lpt[0-9])(?:\.|$)/i', $segment)
                || preg_match('/\.(php[0-9]*|phtml|pht|phar|cgi|pl|py|asp|aspx|shtml|htaccess)(?:\.|$)/i', $segment)) {
                return new WP_Error('invalid_path', '不安全的模型文件路径');
            }
        }
        if (in_array(strtoupper(basename($path)), array('LICENSE', 'README', 'COPYING'), true)) return true;
        $allowed = array('json', 'moc3', 'moc', 'mtn', 'png', 'jpg', 'jpeg', 'webp', 'wav', 'mp3', 'ogg', 'm4a', 'txt', 'cache');
        if (!in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), $allowed, true)) {
            return new WP_Error('invalid_type', '不支持的模型文件类型');
        }
        return true;
    }

    protected static function path_is_inside($path, $root)
    {
        return strpos(wp_normalize_path($path), rtrim(wp_normalize_path($root), '/') . '/') === 0;
    }

    public static function confined_path($root, $relative, $create = false)
    {
        $valid = self::validate_relative_file($relative);
        if (is_wp_error($valid)) return $valid;
        $rootReal = realpath($root);
        if ($rootReal === false || !is_dir($rootReal) || is_link(rtrim($root, '/\\'))) {
            return new WP_Error('invalid_directory', '模型目录不存在或为符号链接');
        }
        $parts = explode('/', $relative);
        $filename = array_pop($parts);
        $parent = $rootReal;
        foreach ($parts as $part) {
            $parent .= '/' . $part;
            if (is_link($parent)) return new WP_Error('invalid_path', '模型路径不允许符号链接');
            if (!is_dir($parent) && $create && !wp_mkdir_p($parent)) {
                return new WP_Error('mkdir_failed', '模型子目录创建失败');
            }
            $real = realpath($parent);
            if ($real === false || !is_dir($real) || !self::path_is_inside($real, $rootReal)) {
                return new WP_Error('invalid_path', '模型路径越出托管区或不存在');
            }
            $parent = $real;
        }
        $dest = $parent . '/' . $filename;
        if (is_link($dest) || (file_exists($dest) && (!is_file($dest) || !self::path_is_inside(realpath($dest), $rootReal)))) {
            return new WP_Error('invalid_path', '模型目标文件路径无效');
        }
        return $dest;
    }

    public static function validate_file_content($path, $filename)
    {
        $valid = self::validate_relative_file($filename);
        if (is_wp_error($valid)) return $valid;
        $head = file_get_contents($path, false, null, 0, 16);
        if ($head === false || $head === '') {
            return new WP_Error('invalid_content', '模型文件为空或无法读取');
        }
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array(strtoupper(basename($filename)), array('LICENSE', 'README', 'COPYING'), true)) $ext = 'txt';
        $ok = false;
        switch ($ext) {
            case 'json':
                $ok = is_array(json_decode(file_get_contents($path), true));
                break;
            case 'png':
            case 'jpg':
            case 'jpeg':
            case 'webp':
                $ok = wp_get_image_mime($path) === self::guess_mime($filename);
                break;
            case 'moc3': $ok = substr($head, 0, 4) === 'MOC3'; break;
            case 'moc': $ok = substr($head, 0, 3) === 'moc'; break;
            case 'wav': $ok = substr($head, 0, 4) === 'RIFF' && substr($head, 8, 4) === 'WAVE'; break;
            case 'ogg': $ok = substr($head, 0, 4) === 'OggS'; break;
            case 'm4a': $ok = substr($head, 4, 4) === 'ftyp'; break;
            // Legacy texture-cache metadata has no fixed header and is never executed.
            case 'cache': $ok = true; break;
            case 'mp3':
                $ok = substr($head, 0, 3) === 'ID3' || (strlen($head) >= 2 && ord($head[0]) === 0xff && (ord($head[1]) & 0xe0) === 0xe0);
                break;
            case 'mtn':
            case 'txt':
                $text = file_get_contents($path);
                $ok = $text !== false && strpos($text, "\0") === false && preg_match('//u', $text) === 1;
                break;
        }
        return $ok ? true : new WP_Error('invalid_content', '模型文件内容与类型不符: ' . $filename);
    }

    protected static function guess_mime($path)
    {
        switch (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            case 'png': return 'image/png';
            case 'jpg':
            case 'jpeg': return 'image/jpeg';
            case 'webp': return 'image/webp';
            case 'json': return 'application/json';
            default: return 'application/octet-stream';
        }
    }
}
