<?php
if (!defined('ABSPATH')) {
    exit; // 阻止直接访问
}
require_once(ABSPATH . 'wp-admin/includes/plugin.php');
require_once(__DIR__  . '/jwt/BeforeValidException.php');
require_once(__DIR__  . '/jwt/CachedKeySet.php');
require_once(__DIR__  . '/jwt/ExpiredException.php');
require_once(__DIR__  . '/jwt/JWK.php');
require_once(__DIR__  . '/jwt/JWT.php');
require_once(__DIR__  . '/jwt/Key.php');
require_once(__DIR__  . '/jwt/SignatureInvalidException.php');

use Firebase\JWT\BeforeValidException;
use Firebase\JWT\CachedKeySet;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;

$dir = explode('/', plugin_dir_url(dirname(__FILE__)));
$dir_len = count($dir);
define('IS_PLUGIN_ACTIVE', is_plugin_active($dir[$dir_len - 2] . "/wordpress-live2d.php")); //补丁启用
define('API_URL', "https://api.live2dweb.com"); //API地址
define('DOMAIN', "https://www.live2dweb.com"); //API地址
define('DOWNLOAD_DIR', plugin_dir_path(dirname(__FILE__)) . 'model/'); //服务器下载的路径
// V1 模型 zip 直链。对齐 Chromium 扩展 v1ModelCache.ts 的 ZIP_BASE:
//   GET http://download.live2dweb.com/model/{modelName}.zip,需要 Bearer {sign}
// 注意:下载域名是 http(不是 https),路径 model 全小写;与 api.live2dweb.com
// 不是同一个服务,不要随手改回 https 或 /Model。
define('LIVE2D_V1_ZIP_BASE', 'http://download.live2dweb.com/model');
class live2d_SDK
{
    private $userInfo;
    private $apiKey;
    public function __construct()
    {
        $this->userInfo = get_option('live_2d_settings_user_token');
        if (isset($this->userInfo["key"])) {
            $this->apiKey = $this->userInfo["key"];
        }
    }
    /**
     * 获取用户登录结果
     * 用这个方法可以通过key获取到token
     */
    public function user_login($request)
    {
        $homeUrl = get_home_url();
        $errCode = intval($request["errorCode"]);
        $sign = $request["sign"];
        $key = $request["key"];

        if (!empty($sign) && !empty($key) && $errCode === 200) {
            $signInfo = $this->JwtDecode($sign, $key);
            $userInfo = array();
            $userInfo["key"] = $key;
            $userInfo["sign"] = $sign;
            $userInfo["userName"] = $signInfo["email"];
            $userInfo["role"] = intval($signInfo["role"]);
            $userInfo["certserialnumber"] = intval($signInfo["certserialnumber"]);
            $userInfo["userLevel"] = intval($signInfo["http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata"]);
            $userInfo["errorCode"] = $errCode;
            $userInfo["hosts"] = $signInfo["aud"];
            if ($homeUrl == $signInfo["aud"] && IS_PLUGIN_ACTIVE) {
                update_option('live_2d_settings_user_token', $userInfo);
                echo "1";
                error_log('用户登录结果:true');
            } else {
                echo "0";
                error_log('用户登录结果:false');
            }
        } else {
            if ($errCode) {
                echo $errCode;
                error_log('用户登录结果:false ' . $errCode);
            } else {
                echo "1005";
                error_log('用户登录结果:false [1005]');
            }
        }
    }

    public function rollback_set($request)
    {
        // 预留接口, 当前未实现; 调用者不应依赖返回值.
        return new WP_Error('not_implemented', 'rollback_set is not implemented', array('status' => 501));
    }
    /**
     * 刷新token
     * 通过或这个方法更新token, 目前是2小时过期
     */
    public function refresh_token($request)
    {
        $sign = $request["sign"];
        $signInfo = $this->JwtDecode($sign, $this->apiKey);
        if ($signInfo === 0) {
            $newToken = $this->get_refresh_token($sign);
            if (!$newToken) {
                status_header(403);
            } else {
                status_header(200);
                echo $newToken;
            }
            exit;
        } else {
            status_header(200);
            echo $sign;
        }
    }
    /**
     * JS验证token
     * 这个方法是给前端使用的, 通过这个方法可以验证token是否过期
     */
    public function verify_token($request)
    {
        $auth_header = $request->get_header('Authorization');
        if (empty($auth_header)) {
            status_header(403);
            exit;
        }
        // 通常 Authorization 的格式为 "Bearer token"
        if (preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
            $token = $matches[1];
            // 使用你的密钥解码 JWT
            $decoded = $this->JwtDecode($token, $this->apiKey);
            if (!$decoded) {
                status_header(403);
                exit;
            }

            if ($decoded === 0) {
                error_log('JS验证成功但是已经过期');
                $newToken = $this->get_refresh_token($token);
                if (!$newToken) {
                    error_log('JS验证失败Token获取时出错。');
                    status_header(403);
                } else {
                    error_log('JS验证成功已获取到新Token: ' . $newToken);
                    status_header(200);
                    echo $newToken;
                }
                exit;
            }

            if (is_array($decoded)) {
                error_log('JS验证成功 Token: ' . $token);
                status_header(200);
                echo $token;
            } else {
                error_log('JS验证失败 Token: ' . $token);
                status_header(403);
            }
        } else {
            error_log('JS验证收到不正确的请求。');
            status_header(400);
        }
    }

    private function get_refresh_token($sign)
    {
        $result = $this->DoGet(['key' => $this->apiKey], "Verify/RefreshToken", $sign);
        if ($result["errorCode"] == 200) {
            $token = $result["errorMsg"];
            $this->userInfo["sign"] = $token;
            update_option('live_2d_settings_user_token', $this->userInfo);
            error_log('verify_token:token: ' . $token);
            return $token;
        } else {
            return false;
        }
    }

    /**
     * V1 模型清单(对齐 Chromium 扩展 v1Catalog.ts 中的 V1_CATALOG)。
     *  - name:与 download.live2dweb.com/model/{name}.zip 路由一致,
     *         也是 *.textures.json 的文件前缀,严禁改写。
     *  - label:UI 显示名。
     *  - texturesJson:扩展自带 assets/v1/{name}.textures.json 时为 true。
     *  - thumbnail:相对插件 assets/ 的预览图路径。
     *  - skins:「1 ID 多皮肤」型(子目录区分独立模型)的可选项,与 texturesJson 互斥。
     */
    public static function GetV1Catalog()
    {
        return array(
            array('name' => 'bilibili-live_22',       'label' => 'Bilibili 22 娘',                'texturesJson' => true,  'thumbnail' => 'v1/imgs/bilibili-live_22.png'),
            array('name' => 'bilibili-live_33',       'label' => 'Bilibili 33 娘',                'texturesJson' => true,  'thumbnail' => 'v1/imgs/bilibili-live_33.png'),
            array('name' => 'Potion-Maker_Pio',       'label' => 'Potion Maker · Pio',            'texturesJson' => true,  'thumbnail' => 'v1/imgs/Potion-Maker_Pio.png'),
            array('name' => 'Potion-Maker_Tia',       'label' => 'Potion Maker · Tia',            'texturesJson' => true,  'thumbnail' => 'v1/imgs/Potion-Maker_Tia.png'),
            array(
                'name' => 'HyperdimensionNeptunia',
                'label' => '超次元海莉娜 (Hyperdimension Neptunia)',
                'texturesJson' => false,
                'thumbnail' => 'v1/imgs/HyperdimensionNeptunia.png',
                'skins' => array(
                    'blanc_classic', 'blanc_normal', 'blanc_swimwear',
                    'general', 'histoire', 'histoirenohover',
                    'nepgear', 'nepgearswim', 'nepgear_extra',
                    'nepmaid', 'nepnep', 'nepswim',
                    'neptune_classic', 'neptune_santa',
                    'noir', 'noireswim', 'noir_classic', 'noir_santa',
                    'vert_classic', 'vert_normal', 'vert_swimwear',
                ),
            ),
            array(
                'name' => 'KantaiCollection',
                'label' => '舰これ (Kantai Collection)',
                'texturesJson' => false,
                'thumbnail' => 'v1/imgs/KantaiCollection.png',
                'skins' => array('murakumo'),
            ),
            array(
                'name' => 'ShizukuTalk',
                'label' => 'Shizuku Talk',
                'texturesJson' => false,
                'thumbnail' => 'v1/imgs/ShizukuTalk.png',
                'skins' => array('shizuku-48', 'shizuku-pajama'),
            ),
        );
    }

    /**
     * V1 模型下载(对齐 Chromium 扩展 v1ModelCache.ts 的 downloadAndCacheV1Model 行为):
     *   1. 由前端传入 catalog 中的 modelName,而不是 modelId,免去 Model/ModelInfo 一次往返。
     *   2. 直接 GET LIVE2D_V1_ZIP_BASE/{name}.zip,Bearer 用本地保存的 sign。
     *   3. zip 落到 DOWNLOAD_DIR/{name}.zip,等前端再发 zip_model 调 OpenZip 解压。
     * 解压依旧由 PHP ZipArchive 处理,前端不做任何解压。
     */
    public function DownloadV1Model()
    {
        // ====诊断日志·一旦报错 看这几行==== //
        $postedNonce = isset($_POST['_wpnonce']) ? wp_unslash($_POST['_wpnonce']) : '';
        $freshNonce  = wp_create_nonce('live2d_shop_action');
        error_log('DownloadV1Model:[ENTER] modelName=' . (isset($_POST['modelName']) ? wp_unslash($_POST['modelName']) : '<missing>')
            . ' user_id=' . get_current_user_id()
            . ' logged_in=' . (is_user_logged_in() ? '1' : '0')
            . ' can_manage=' . (current_user_can('manage_options') ? '1' : '0')
            . ' posted_nonce=' . $postedNonce
            . ' fresh_nonce=' . $freshNonce
            . ' nonce_match=' . ($postedNonce === $freshNonce ? 'EXACT' : 'DIFF')
            . ' verify_now=' . var_export(wp_verify_nonce($postedNonce, 'live2d_shop_action'), true)
            . ' session_token=' . substr((string) wp_get_session_token(), 0, 8) . '...');
        $this->verify_admin_ajax('live2d_shop_action');
        $rawName = isset($_POST['modelName']) ? wp_unslash($_POST['modelName']) : '';
        $modelName = $this->ResolveV1ModelName($rawName);
        if ($modelName === '') {
            wp_send_json(array('errorCode' => 9400, 'errorMsg' => '未知的 V1 模型名'));
            return;
        }
        if (empty($this->userInfo['sign'])) {
            wp_send_json(array('errorCode' => 9500, 'errorMsg' => '没有登录信息'));
            error_log('DownloadV1Model:[9500]没有登录信息');
            return;
        }
        if (!file_exists(DOWNLOAD_DIR) && !mkdir(DOWNLOAD_DIR, 0777, true)) {
            wp_send_json(array('errorCode' => 9500, 'errorMsg' => '文件夹创建失败'));
            error_log('DownloadV1Model:[9500]文件夹创建失败');
            return;
        }

        $fileUrl = LIVE2D_V1_ZIP_BASE . '/' . rawurlencode($modelName) . '.zip';
        $tmpfname = wp_tempnam($fileUrl);
        error_log('DownloadV1Model:[开始下载 ' . $fileUrl . ']');
        error_log('DownloadV1Model:[临时文件 ' . $tmpfname . ']');

        // 鉴权要求(对照 wp-live2d-api Live2dJwtBearerEvents.TokenValidated):
        //   1) Authorization: Bearer {sign} —— sign 是 ApiKey 自身做密钥的 HS256 JWT。
        //   2) Origin 或 Referer 的 host 必须等于 sign.aud 的 host。
        //   3) COS (download.live2dweb.com) 走回源鉴权/防盗链,会根据 Referer 决定是否放行。
        //      老版本 DownloadModel 能下成功,headers 与之保持一致(Authorization + Origin + Referer + UA + Accept)。
        $homeUrl = trailingslashit(get_home_url());
        $headers = array(
            'Authorization' => 'Bearer ' . $this->userInfo['sign'],
            'Origin'        => get_home_url(),
            'Referer'       => $homeUrl,
            'User-Agent'    => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36',
            'Accept'        => '*/*',
        );
        error_log('DownloadV1Model:[请求头 ' . wp_json_encode(array_keys($headers)) . ']');

        $response = wp_safe_remote_get($fileUrl, array(
            'headers'  => $headers,
            'timeout'  => 300,
            'stream'   => true,
            'filename' => $tmpfname,
        ));

        if (is_wp_error($response)) {
            @unlink($tmpfname);
            wp_send_json(array('errorCode' => 500, 'errorMsg' => $response->get_error_message()));
            error_log('DownloadV1Model:[请求失败: ' . $response->get_error_message() . ']');
            return;
        }
        $httpCode = wp_remote_retrieve_response_code($response);
        $respHeaders = wp_remote_retrieve_headers($response);
        error_log('DownloadV1Model:[HTTP ' . $httpCode . '] response headers: ' . wp_json_encode((array) $respHeaders));
        if ($httpCode === 401 || $httpCode === 403) {
            // stream=true 时 body 不在 response,只有响应头;手工读临时文件首段查看错误说明(如有)
            $bodyPeek = '';
            if (file_exists($tmpfname)) {
                $bodyPeek = (string) @file_get_contents($tmpfname, false, null, 0, 2048);
            }
            @unlink($tmpfname);
            error_log('DownloadV1Model:[请求失败: ' . $httpCode . '] body 前 2KB: ' . $bodyPeek);
            wp_send_json(array(
                'errorCode' => $httpCode,
                'errorMsg'  => '服务器未授权此访问 (HTTP ' . $httpCode . '),详情见 WP error_log',
            ));
            return;
        }
        if ($httpCode !== 200) {
            @unlink($tmpfname);
            wp_send_json(array('errorCode' => $httpCode, 'errorMsg' => '下载失败 HTTP ' . $httpCode));
            error_log('DownloadV1Model:[请求失败: HTTP ' . $httpCode . ']');
            return;
        }

        // 写到 model/{name}.zip,与 OpenZip 的入参约定保持一致(zip 同名 + 解压目录同名)。
        $sanfilename = sanitize_file_name($modelName . '.zip');
        $targetPath  = DOWNLOAD_DIR . $sanfilename;
        // PHP rename() 跨文件系统/挂载点会失败(临时文件常在 /tmp,目标在 plugins/),
        // 失败时退回到 copy + unlink,作为最后兜底。
        $renamed = @rename($tmpfname, $targetPath);
        if (!$renamed) {
            $copyOk = @copy($tmpfname, $targetPath);
            @unlink($tmpfname);
            if (!$copyOk) {
                $perm = is_writable(DOWNLOAD_DIR) ? 'writable' : 'NOT writable';
                error_log('DownloadV1Model:[9501]保存文件失败 target=' . $targetPath
                    . ' tmp=' . $tmpfname
                    . ' tmp_exists=' . (file_exists($tmpfname) ? '1' : '0')
                    . ' dir=' . DOWNLOAD_DIR . '(' . $perm . ')');
                wp_send_json(array('errorCode' => 9501, 'errorMsg' => '保存文件失败,请确认 ' . DOWNLOAD_DIR . ' 可写'));
                return;
            }
        }
        @chmod($targetPath, 0644);
        error_log('DownloadV1Model:[文件保存到 ' . $targetPath . ' 大小=' . (file_exists($targetPath) ? filesize($targetPath) : -1) . ']');

        wp_send_json(array(
            'errorCode' => 200,
            'fileName'  => $sanfilename,
            'modelName' => $modelName,
        ));
    }

    /**
     * 把前端传入的 modelName 校验为 V1 catalog 中的合法条目,防止任意 URL 拼接。
     * 通过则返回 catalog 中的精确 name(大小写敏感),否则返回空串。
     */
    private function ResolveV1ModelName($candidate)
    {
        $candidate = is_string($candidate) ? trim($candidate) : '';
        if ($candidate === '') {
            return '';
        }
        foreach (self::GetV1Catalog() as $item) {
            if ($item['name'] === $candidate) {
                return $item['name'];
            }
        }
        return '';
    }

    /**
     * 设置页 modelId 下拉框数据源。
     * 原本走 Model/List 以远程 API，现在对齐 Chromium 扩展直接返回本地 V1 catalog:
     *   id   = catalog.name (与下载路由 / 本地目录 / textures.json 前缀一致)
     *   name = catalog.label (UI 显示名)
     */
    public function GetModelMotions()
    {
        $this->verify_admin_ajax('live2d_shop_action');
        $list = array();
        foreach (self::GetV1Catalog() as $item) {
            $list[] = array(
                'id'   => $item['name'],
                'name' => $item['label'],
            );
        }
        wp_send_json($list);
    }

    /**
     * 设置页 modelTexturesId 下拉框数据源。读本地 assets/v1/{name}.textures.json 或 catalog.skins:
     *  - skins 优先(多皮肤型)
     *  - texturesJson=true 时读三方 JSON，每项是单个贴图或一组贴图文件名的数组
     *  - 都没有时只返 #0
     */
    public function GetTextureList()
    {
        $this->verify_admin_ajax('live2d_shop_action');
        $candidate = isset($_POST['modelId']) ? wp_unslash($_POST['modelId']) : '';
        $modelName = $this->ResolveV1ModelName($candidate);
        if ($modelName === '') {
            wp_send_json(array());
            return;
        }
        $item = null;
        foreach (self::GetV1Catalog() as $c) {
            if ($c['name'] === $modelName) { $item = $c; break; }
        }
        $list = array();
        if (!empty($item['skins'])) {
            foreach ($item['skins'] as $i => $skin) {
                $list[] = array('id' => $i, 'name' => '#' . $i . ' · ' . $skin);
            }
        } elseif (!empty($item['texturesJson'])) {
            // 用 dirname(__DIR__) 直接定位插件根,绕开 plugin_dir_path 在某些
            // 环境(符号链接 / mu-plugins / Windows 路径)里拼不到插件根的边界情况
            $jsonPath = dirname(__DIR__) . '/assets/v1/' . $modelName . '.textures.json';
            $exists   = file_exists($jsonPath);
            error_log('GetTextureList:[读取 textures.json] modelName=' . $modelName
                . ' path=' . $jsonPath
                . ' exists=' . ($exists ? '1' : '0'));
            if ($exists) {
                $raw  = file_get_contents($jsonPath);
                // 去掉 UTF-8 BOM (EF BB BF) 与首尾空白,
                // 否则 PHP json_decode 会以 "Syntax error" 失败
                if (strncmp($raw, "\xEF\xBB\xBF", 3) === 0) {
                    $raw = substr($raw, 3);
                }
                $raw  = trim($raw);
                $data = json_decode($raw, true);
                if (is_array($data)) {
                    foreach ($data as $i => $entry) {
                        $list[] = array('id' => $i, 'name' => self::DescribeTextureEntry($entry, $i));
                    }
                } else {
                    error_log('GetTextureList:[json_decode 失败] modelName=' . $modelName
                        . ' json_last_error=' . json_last_error_msg());
                }
            }
        }
        if (empty($list)) {
            $list[] = array('id' => 0, 'name' => '#0');
        }
        wp_send_json($list);
    }

    /**
     * 对齐 Chromium 扩展 v1Catalog.ts 中的 describeTextureEntry:
     * 数组 -> 取每项 basename 用 + 连起来(超长截断);字符串 -> 取 basename。
     */
    private static function DescribeTextureEntry($entry, $index)
    {
        $baseOf = function ($s) {
            $name = preg_replace('#\.[^./\\\\]+$#', '', basename((string)$s));
            return preg_replace('#-costume$#i', '', $name);
        };
        if (is_array($entry)) {
            if (count($entry) === 0) return '#' . $index;
            $joined = implode(' + ', array_map($baseOf, $entry));
            if (mb_strlen($joined) > 60) {
                $joined = mb_substr($joined, 0, 57) . '…';
            }
            return '#' . $index . ' · ' . $joined;
        }
        return '#' . $index . ' · ' . $baseOf($entry);
    }

    /**
     * 对ZIP进行解压缩
     *
     * 优先用 WordPress 内置的 unzip_file()(wp-admin/includes/file.php),它会:
     *   - 若环境有 ext-zip 则走 ZipArchive,否则回落到随 WP 自带的纯 PHP 库 PclZip;
     *   - 走 WP_Filesystem 写盘,失败时返回 WP_Error(有 code/message/data),信息更完整;
     *   - 自带 zip slip 防护、自动跳过 __MACOSX/.DS_Store、按需建中间目录。
     * 如果 WP_Filesystem 拿不到 direct 模式(罕见,例如纯 FTP 站点),回落到原本的
     * ZipArchive 直接调用,保证旧行为不退化。
     *
     * 失败时除了 echo 0(协议保持不变),会把所有可定位"为什么失败"的诊断信息写进 error_log:
     *   - zip 路径/大小/权限位/属主、解压目标目录是否可写
     *   - PHP 进程的 euid/egid + 用户名(POSIX 可用时);常见现象:
     *       * 文件由 ubuntu 用户手动放进去,umask 027/077 导致 www 读不到
     *       * 目标目录属主非 www 且无 group/other 写权限
     *   - WP_Error code/message,或 ZipArchive::open() 的真实返回码(int)
     * 注意:权限/不存在类错误故意 *不* 删除 zip,保留现场让运维 ls -l 查证;
     *      仅在确认是 ZIP 内容损坏(ER_NOZIP/ER_INCONS/ER_CRC 等)或越权条目时才删。
     */
    public function OpenZip()
    {
        $this->verify_admin_ajax('live2d_shop_action');
        $sanfilename = sanitize_file_name($_POST["fileName"]);
        $zipFile = DOWNLOAD_DIR . $sanfilename;
        $zipFileName = pathinfo($zipFile, PATHINFO_FILENAME);
        $extractTarget = DOWNLOAD_DIR . $zipFileName;

        // 1) 首选:WP 官方 unzip_file()
        $wpResult = $this->TryUnzipWithWpFilesystem($zipFile, $extractTarget);
        if ($wpResult === 'ok') {
            @unlink($zipFile);
            // 必须 wp_die,不然 admin-ajax.php 会追加 "0",前端拿到 "10" 会被判失败
            wp_die('1', '', array('response' => null));
        }
        if ($wpResult === 'fail') {
            // unzip_file 已识别这是错误(不是"环境不可用"),日志已写;不要再用 ZipArchive 试一遍
            wp_die('0', '', array('response' => null));
        }
        // $wpResult === 'unavailable' —— WP_Filesystem direct 模式拿不到,走兜底

        // 2) 兜底:ZipArchive 直接调用
        $zip = new ZipArchive;
        $res = $zip->open($zipFile);
        if ($res === TRUE) {
            // 防 zip slip: 逐条校验条目, 任一跳出目录则拒绝解压
            for ($i = 0, $n = $zip->numFiles; $i < $n; $i++) {
                $entryName = $zip->getNameIndex($i);
                if ($entryName === false) {
                    $zip->close();
                    @unlink($zipFile);
                    error_log('OpenZip:[读取 zip 条目失败] file=' . $zipFile);
                    wp_die('0', '', array('response' => null));
                }
                if (strpos($entryName, '..') !== false || strpos($entryName, '/..') !== false || preg_match('#^([a-zA-Z]:)?[\\/]#', $entryName)) {
                    $zip->close();
                    @unlink($zipFile);
                    error_log('OpenZip:[检测到非法条目路径: ' . $entryName . '] file=' . $zipFile);
                    wp_die('0', '', array('response' => null));
                }
            }
            $extractRes = $zip->extractTo($extractTarget);
            $zip->close();
            if ($extractRes !== true) {
                error_log('OpenZip:[ZipArchive::extractTo 失败] ' . self::DescribeFsContext($zipFile, $extractTarget));
                @unlink($zipFile);
                wp_die('0', '', array('response' => null));
            }
            @unlink($zipFile);
            wp_die('1', '', array('response' => null));
        } else {
            $errCode = is_int($res) ? $res : -1;
            $errLabel = self::ZipOpenErrorLabel($errCode);
            error_log('OpenZip:[ZipArchive::open 失败] code=' . $errCode . '(' . $errLabel . ') ' . self::DescribeFsContext($zipFile, $extractTarget));
            if (in_array($errCode, array(ZipArchive::ER_NOZIP, ZipArchive::ER_INCONS, ZipArchive::ER_CRC), true)) {
                @unlink($zipFile);
            }
            wp_die('0', '', array('response' => null));
        }
    }

    /**
     * 用 WP 官方 unzip_file() 解压。
     * 返回三态字符串:
     *   'ok'          —— 解压成功
     *   'fail'        —— unzip_file 明确失败,日志已写,zip 已按错误类型决定是否删除;
     *                    调用方应直接 echo 0,不要再回落,因为同一个文件用 ZipArchive 大概率
     *                    遇到完全相同的失败原因(权限/损坏),再来一遍只会多一份日志。
     *   'unavailable' —— WP 核心函数缺失或 WP_Filesystem direct 模式拿不到;调用方回落到
     *                    ZipArchive 直接调用。
     */
    private function TryUnzipWithWpFilesystem($zipFile, $extractTarget)
    {
        if (!function_exists('unzip_file') || !function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        if (!function_exists('unzip_file') || !function_exists('WP_Filesystem')) {
            return 'unavailable';
        }
        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            // 第三个参数 true 强制 direct,避免在某些环境弹出 FTP 凭据表单
            if (!WP_Filesystem(false, false, true)) {
                error_log('OpenZip:[WP_Filesystem 初始化失败,回落到 ZipArchive] ' . self::DescribeFsContext($zipFile, $extractTarget));
                return 'unavailable';
            }
        }
        // unzip_file 内部已做 zip slip 防护、跳过 __MACOSX、按需创建目标目录;
        // 在 ext-zip 不可用时会自动用纯 PHP 的 PclZip(随 WP 自带)
        $result = unzip_file($zipFile, $extractTarget);
        if ($result === true) {
            return 'ok';
        }
        if (is_wp_error($result)) {
            $code = $result->get_error_code();
            $msg  = $result->get_error_message();
            $data = $result->get_error_data();
            error_log('OpenZip:[unzip_file 失败] code=' . $code
                . ' msg=' . $msg
                . ' data=' . (is_scalar($data) ? (string)$data : wp_json_encode($data))
                . ' ' . self::DescribeFsContext($zipFile, $extractTarget));
            // 仅在确认是 zip 损坏/空包时才删,权限/磁盘类保留现场
            if (in_array($code, array('incompatible_archive', 'empty_archive_pclzip', 'empty_archive_ziparchive', 'corrupt_zip'), true)) {
                @unlink($zipFile);
            }
        } else {
            error_log('OpenZip:[unzip_file 返回非预期类型] ' . var_export($result, true) . ' ' . self::DescribeFsContext($zipFile, $extractTarget));
        }
        return 'fail';
    }


    /**
     * 把 ZipArchive::open() 的 int 返回码翻成可读名字。
     * 常量值见 https://www.php.net/manual/en/class.ziparchive.php
     */
    private static function ZipOpenErrorLabel($code)
    {
        static $map = null;
        if ($map === null) {
            $map = array(
                ZipArchive::ER_OK      => 'ER_OK',
                ZipArchive::ER_MULTIDISK => 'ER_MULTIDISK',
                ZipArchive::ER_RENAME  => 'ER_RENAME',
                ZipArchive::ER_CLOSE   => 'ER_CLOSE',
                ZipArchive::ER_SEEK    => 'ER_SEEK',
                ZipArchive::ER_READ    => 'ER_READ/读失败(常见:权限不足)',
                ZipArchive::ER_WRITE   => 'ER_WRITE',
                ZipArchive::ER_CRC     => 'ER_CRC/校验失败(zip 损坏)',
                ZipArchive::ER_ZIPCLOSED => 'ER_ZIPCLOSED',
                ZipArchive::ER_NOENT   => 'ER_NOENT/文件不存在',
                ZipArchive::ER_EXISTS  => 'ER_EXISTS',
                ZipArchive::ER_OPEN    => 'ER_OPEN/打开失败(常见:权限不足或被占用)',
                ZipArchive::ER_TMPOPEN => 'ER_TMPOPEN',
                ZipArchive::ER_ZLIB    => 'ER_ZLIB',
                ZipArchive::ER_MEMORY  => 'ER_MEMORY',
                ZipArchive::ER_CHANGED => 'ER_CHANGED',
                ZipArchive::ER_COMPNOTSUPP => 'ER_COMPNOTSUPP',
                ZipArchive::ER_EOF     => 'ER_EOF/文件被截断(下载未完成?)',
                ZipArchive::ER_INVAL   => 'ER_INVAL',
                ZipArchive::ER_NOZIP   => 'ER_NOZIP/不是 zip 格式(很可能是 HTML/XML 错误页)',
                ZipArchive::ER_INTERNAL => 'ER_INTERNAL',
                ZipArchive::ER_INCONS  => 'ER_INCONS/zip 不一致(损坏)',
                ZipArchive::ER_REMOVE  => 'ER_REMOVE',
                ZipArchive::ER_DELETED => 'ER_DELETED',
            );
        }
        return isset($map[$code]) ? $map[$code] : ('UNKNOWN(' . $code . ')');
    }

    /**
     * 把"PHP 这一刻看到的文件系统状态"打成一行字,集中诊断 own/perm/size 类问题。
     */
    private static function DescribeFsContext($zipFile, $extractTarget)
    {
        $exists = file_exists($zipFile);
        $size = $exists ? @filesize($zipFile) : -1;
        $readable = $exists && is_readable($zipFile);
        $perms = $exists ? substr(sprintf('%o', @fileperms($zipFile)), -4) : '----';
        $owner = '?';
        $group = '?';
        if ($exists && function_exists('fileowner')) {
            $uid = @fileowner($zipFile);
            $gid = @filegroup($zipFile);
            if (function_exists('posix_getpwuid')) {
                $pw = $uid !== false ? @posix_getpwuid($uid) : false;
                $gr = $gid !== false ? @posix_getgrgid($gid) : false;
                $owner = ($pw && isset($pw['name'])) ? $pw['name'] . '(' . $uid . ')' : (string)$uid;
                $group = ($gr && isset($gr['name'])) ? $gr['name'] . '(' . $gid . ')' : (string)$gid;
            } else {
                $owner = (string)$uid;
                $group = (string)$gid;
            }
        }
        $proc = '?';
        if (function_exists('posix_geteuid')) {
            $euid = @posix_geteuid();
            $egid = function_exists('posix_getegid') ? @posix_getegid() : -1;
            if (function_exists('posix_getpwuid')) {
                $pw = @posix_getpwuid($euid);
                $proc = ($pw && isset($pw['name'])) ? $pw['name'] . '(' . $euid . '/' . $egid . ')' : ($euid . '/' . $egid);
            } else {
                $proc = $euid . '/' . $egid;
            }
        } elseif (function_exists('get_current_user')) {
            $proc = (string) get_current_user();
        }
        $dir = dirname($zipFile);
        $dirWritable = is_dir($dir) && is_writable($dir);
        $dirPerms = is_dir($dir) ? substr(sprintf('%o', @fileperms($dir)), -4) : '----';
        $extDirExists = is_dir($extractTarget);
        $extDirWritable = $extDirExists ? is_writable($extractTarget) : is_writable($dir);
        return 'file=' . $zipFile
            . ' exists=' . ($exists ? '1' : '0')
            . ' size=' . $size
            . ' readable=' . ($readable ? '1' : '0')
            . ' perms=' . $perms
            . ' owner=' . $owner
            . ' group=' . $group
            . ' proc_user=' . $proc
            . ' dir=' . $dir
            . ' dir_perms=' . $dirPerms
            . ' dir_writable=' . ($dirWritable ? '1' : '0')
            . ' extract_target=' . $extractTarget
            . ' extract_target_exists=' . ($extDirExists ? '1' : '0')
            . ' extract_target_writable=' . ($extDirWritable ? '1' : '0');
    }

    /**
     * 清理文件: 用户如果没有下载成功, 会下载一个XML是.ZIP格式的, 需要给它清除
     * 执行此方法可以清除
     */
    public function ClearFiles()
    {
        $this->verify_admin_ajax('live2d_shop_action');
        if (!current_user_can('manage_options')) {
            wp_die('0', '', array('response' => null));
        }
        $sanfilename = sanitize_file_name($_POST["fileName"]);
        $filePath = DOWNLOAD_DIR . $sanfilename;
        // 确保路径仍位于下载目录之内
        $realDir = realpath(DOWNLOAD_DIR);
        $realFile = realpath($filePath);
        if ($realDir === false || $realFile === false || strpos($realFile, $realDir) !== 0) {
            wp_die('0', '', array('response' => null));
        }
        if (file_exists($realFile)) {
            unlink($realFile);
            wp_die('1', '', array('response' => null));
        }
        // 文件本来就不在,从前端视角等同于"已清理",仍返回 1 也合理;
        // 但保留旧语义返回 0,以免破坏调用方对"是否真的删过"的依赖
        wp_die('0', '', array('response' => null));
    }

    /**
     * 统一的后台 AJAX 鉴权: 要求登录 + 管理员权限 + nonce 有效
     */
    private function verify_admin_ajax($action)
    {
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            error_log('verify_admin_ajax:[FAIL/cap] action=' . $action
                . ' user_id=' . get_current_user_id()
                . ' logged_in=' . (is_user_logged_in() ? '1' : '0')
                . ' can_manage=' . (current_user_can('manage_options') ? '1' : '0'));
            wp_send_json_error(array('errorCode' => 403, 'errorMsg' => 'forbidden'), 403);
        }
        // check_ajax_referer 在失败时会自动调 wp_die
        $valid = isset($_POST['_wpnonce']) ? wp_verify_nonce($_POST['_wpnonce'], $action) : false;
        if (!$valid) {
            error_log('verify_admin_ajax:[FAIL/nonce] action=' . $action
                . ' has_nonce=' . (isset($_POST['_wpnonce']) ? 'yes' : 'no')
                . ' nonce_value=' . (isset($_POST['_wpnonce']) ? substr($_POST['_wpnonce'], 0, 4) . '...' : '')
                . ' verify_result=' . var_export($valid, true));
        }
        check_ajax_referer($action, '_wpnonce');
    }

    //排查错误使用
    public function Save_Options($value)
    {
        if (!empty($this->userInfo["sign"])) {
            $param = [
                'new_value' => json_encode($value)
            ];
            $result = $this->DoPost($param, "Options/UpdateOpt", $this->userInfo["sign"]);
            error_log('Save_Options:设置保存完成' . print_r($result));
        } else {
            error_log('Save_Options:设置保存完成, 但是用户没有登陆。');
        }
    }

    /**
     * 删除这段有可能会导致保存成功后调用不了我的API
     */
    public function Update_Options($value, $old_value)
    {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'live_2d_settings_base_group-options')) {
            add_settings_error('live_2d_sdk_error', 500, '保存失败，非法操作。');
            return $old_value; // 验证失败，返回旧值
        }
        $url = $value["modelAPI"];
        if (preg_match('/^https:\/\/api\.live2dweb\.com/i', $url) && empty($this->userInfo["sign"])) {
            add_settings_error('live_2d_sdk_error', 500, '保存成功，但是您必须登录才可以使用官方API。');
        }
        return $value;
    }

    public function Update_Advanced_Options($value, $old_value)
    {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'live_2d_advanced_option_group-options')) {
            add_settings_error('live_2d_sdk_error', 500, '保存失败，非法操作。');
            return $old_value; // 验证失败，返回旧值
        }
        return $value;
    }

    public function Update_Login_Options($value, $old_value)
    {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'live_2d_login_option_group-options')) {
            add_settings_error('live_2d_sdk_error', 500, '保存失败，非法操作。');
            return $old_value; // 验证失败，返回旧值
        }
        return $value;
    }

    public function JwtDecode($jwt, $key)
    {
        try {
            $setArr = (array)JWT::decode($jwt, new Key($key, 'HS256'));
            return $setArr;
        } catch (ExpiredException $e) {
            error_log("Token 已过期: " . $e->getMessage());
            return 0;
        } catch (Exception $e) {
            error_log('JwtDecode:签名错误' . $e, 5);
            return false;
        }
    }

    public function JwtEncode()
    {
        $key = $this->userInfo['key'];
        $issuedAt = time(); // 当前时间作为 iat
        $notBefore = $issuedAt; // JWT 立即生效
        $expire = $issuedAt + 7200; // 1 小时后过期
        $payload = [
            'iss' => DOMAIN,
            'aud' => get_home_url(),
            'iat' => $issuedAt,
            'nbf' => $notBefore,
            'exp' => $expire,
            'email' => $this->userInfo["userName"],
            'role' => intval($this->userInfo["role"]),
            'certserialnumber' => intval($this->userInfo["certserialnumber"]),
            'http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata' => intval($this->userInfo["userLevel"]),
        ];
        $jwt = JWT::encode($payload, $key, 'HS256');
        return $jwt;
    }

    public function GetToken($key)
    {
        if (empty($key)) {
            $key = $this->userInfo["key"];
        }
        $bare_url = API_URL . "/Verify/GetToken";
        $param = ['key' => $key];
        $complete_url = add_query_arg($param, $bare_url);
        error_log('GetToken:请求地址: ' . $complete_url);
        $response = wp_remote_get($complete_url, array(
            'headers' => array(
                'referer' => get_home_url()
            )
        ));
        $body = wp_remote_retrieve_body($response);
        error_log('GetToken:回复: ' . $body);
        return $body;
    }

    public function DoPost($param, $api_name, $jwt)
    {
        $complete_url = API_URL . "/" . $api_name;
        error_log('DoPost:请求地址: ' . $complete_url);
        $response = wp_remote_post($complete_url, array(
            'body' => $param,
            'headers' => array(
                'Authorization' => 'Bearer ' . $jwt,
                'Origin' =>  get_home_url(),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
        ));

        if (is_wp_error($response)) {
            error_log($complete_url . '请求失败: ' . $response->get_error_message());
            return array(
                'errorCode' => 500,
                'errorMsg'  => $response->get_error_message(),
            );
        }
        $httpCode = wp_remote_retrieve_response_code($response);
        if ($httpCode === 401 || $httpCode === 403) {
            error_log('DoPost:授权错误, 登录不正确, 请检查是否和域名匹配' . $httpCode, 5);
            return array(
                'errorCode' => $httpCode,
                'errorMsg' => '登录不正确, 请检查是否和域名匹配'
            );
        }
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    public function DoGet($param, $api_name, $jwt)
    {
        $bare_url = API_URL . "/" . $api_name . "/";

        if (!empty($param)) {
            $bare_url = add_query_arg($param, $bare_url);
        }

        error_log('DoGet:请求地址: ' . $bare_url);
        $response = wp_remote_get($bare_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $jwt,
                'Origin' =>  get_home_url(),
            ),
        ));

        if (is_wp_error($response)) {
            error_log($bare_url . '请求失败: ' . $response->get_error_message());
            return array(
                'errorCode' => 500,
                'errorMsg'  => $response->get_error_message(),
            );
        }
        $httpCode = wp_remote_retrieve_response_code($response);
        if ($httpCode === 401 || $httpCode === 403) {
            error_log('DoGet:授权错误, 登录不正确, 请检查是否和域名匹配' . $httpCode, 5);
            return array(
                'errorCode' => $httpCode,
                'errorMsg' => '登录不正确, 请检查是否和域名匹配'
            );
        }
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
}
