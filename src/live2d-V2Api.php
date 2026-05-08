<?php
/**
 * V2 模型(Cubism 4/5 model3.json)防盗链 REST API。
 * ----------------------------------------------------------
 * 与 wp-live2d-api(C# 后端)的 /Model/Session 行为对齐,前端 fetcher 一份代码。
 *
 * 接口前缀:/wp-json/live2d/v2
 *   POST /v2/session                          -> 拿 token + manifestUrl
 *   GET  /v2/m/{token}/manifest.json          -> alias 化的 model3.json
 *   GET  /v2/m/{token}/{alias}?e=&s=          -> readfile() 流出真实资源
 *
 * 算法(必须与 ModelProtectionBLL.cs 一致):
 *   masterKey      = sha256(wp_salt('auth'))
 *   sessionSecret  = HMAC_SHA256(masterKey, token)
 *   sig            = base64url(HMAC_SHA256(sessionSecret, token + "|" + alias + "|" + exp))
 *   token          = base64url(random 16B)  -> 22 char
 *   alias          = base64url(random 8B)   -> 11 char
 *   ttl            = 600 秒(10 分钟,与 set_transient 同步)
 *
 * 失败约定(防探测,与 nizima 一致):
 *   manifest endpoint 失败 -> 200 + "{}"
 *   asset    endpoint 失败 -> 200 + 1×1 透明 PNG
 *   绝不返 4xx/5xx
 *
 * 部署形态:
 *   - 同源:WP 站点本地的 wp-content/plugins/live-2d/model/.../*.model3.json,
 *     真实文件由 PHP readfile() 流出,Cache-Control: private,严禁被 CDN 缓存。
 *   - 跨域:暂未实现 OSS/COS 302,本轮只支持同源(同 ChromeExtension 端的 manifest 模式)。
 *
 * 前端入口:Wordpress/live2d-tips.ts 的 ApiUrlType.JsonFile 分支
 *   优先尝试 POST /v2/session 拿 manifestUrl 替换 modelAPI;失败回落到裸链。
 */

if (!defined('ABSPATH')) {
    exit;
}

class live2d_V2Api
{
    const REST_NS    = 'live2d/v2';
    const TTL        = 600;          // 10 分钟,与 ModelProtectionBLL 默认值一致
    const ALIAS_BYTES = 8;           // 11 char base64url
    const TOKEN_BYTES = 16;          // 22 char base64url

    /** REST 路由注册:挂在 rest_api_init 钩子 */
    public static function register_routes()
    {
        $self = new self();

        register_rest_route(self::REST_NS, '/session', array(
            'methods'             => 'POST',
            'permission_callback' => '__return_true',
            'callback'            => array($self, 'create_session'),
            'args'                => array(
                'modelApi'     => array('required' => true,  'type' => 'string'),
                'manifestJson' => array('required' => false, 'type' => 'string'),
            ),
        ));

        // 注意:WP REST 路由的占位符不能跨 `/`;manifest.json 故意写成字面量
        // 是为了 lapplive2dmanager 的 /\.json$/ 直链分支能命中 manifestUrl。
        register_rest_route(self::REST_NS, '/m/(?P<token>[A-Za-z0-9_-]{20,32})/manifest\.json', array(
            'methods'             => 'GET',
            'permission_callback' => '__return_true',
            'callback'            => array($self, 'get_manifest'),
        ));

        // /hashes.json — alias -> sha256(hex) 映射,**仅本地文件 alias 才有 hash**。
        // 与 manifest.json 同 token 命名空间,前端把它当"内容指纹清单"用,从而:
        //   - IDB 主键改用 sha:{hash}(取代抖动的 token+alias URL),跨会话稳定命中;
        //   - 命中本地缓存就不用走 stream_local readfile,把 PHP 干净跳过。
        // 远端代理的 alias 在 mapping 里是 http(s) URL,**不收录到 hash 表**;
        // 前端那条 alias 自然走老路径(URL key + LRU)。
        // 失败约定与 manifest 一致 → 200 + "{}"(防探测)。
        register_rest_route(self::REST_NS, '/m/(?P<token>[A-Za-z0-9_-]{20,32})/hashes\.json', array(
            'methods'             => 'GET',
            'permission_callback' => '__return_true',
            'callback'            => array($self, 'get_hashes'),
        ));

        register_rest_route(self::REST_NS, '/m/(?P<token>[A-Za-z0-9_-]{20,32})/(?P<alias>[A-Za-z0-9_-]{8,16})', array(
            'methods'             => 'GET',
            'permission_callback' => '__return_true',
            'callback'            => array($self, 'get_asset'),
            'args'                => array(
                'e' => array('required' => true, 'type' => 'integer'),
                's' => array('required' => true, 'type' => 'string'),
            ),
        ));
    }

    /**
     * POST /session
     * body: { modelApi: string, manifestJson?: string }
     */
    public function create_session(WP_REST_Request $request)
    {
        $modelApi = trim((string) $request->get_param('modelApi'));
        if ($modelApi === '') {
            return self::ok_empty();
        }
        // 所有 http(s) 绝对 URL 都接受 — 同站 / GitHub raw / jsdelivr / OSS / COS 均可。
        // 同站 → stream_local readfile;跨域 → stream_remote 服务端代理。
        // wp_safe_remote_get 默认封碎内网 IP / file:// / 0.0.0.0,SSRF 防设已在。
        if (!preg_match('#^https?://#i', $modelApi)) {
            return self::ok_with_error('invalid_url', 'modelApi 必须是 http(s) URL');
        }

        // 1) 拿 manifest 文本:优先用客户端预拉的,空就 PHP 拉
        // 用 wp_remote_get(非 _safe_):避免 wp_http_validate_url 把
        // raw.githubusercontent.com / OSS 等公网 host 拒掉。modelApi 来自 WP option,
        // 已是后台 sanitize 过的字符串,这里放宽 SSRF 限制不引入新威胁。
        $manifestText = (string) $request->get_param('manifestJson');
        if ($manifestText === '') {
            $resp = wp_remote_get($modelApi, array('timeout' => 8, 'sslverify' => true));
            if (is_wp_error($resp) || (int) wp_remote_retrieve_response_code($resp) !== 200) {
                return self::ok_with_error('fetch_failed', '服务端无法拉取 modelApi');
            }
            $manifestText = (string) wp_remote_retrieve_body($resp);
        }

        // 2) 解析 + alias 化
        $manifest = json_decode($manifestText, true);
        if (!is_array($manifest) || !isset($manifest['FileReferences'])) {
            return self::ok_with_error('invalid_manifest', 'modelApi 不是合法的 model3.json');
        }

        $token         = self::random_base64url(self::TOKEN_BYTES);
        $masterKey     = self::master_key();
        $sessionSecret = hash_hmac('sha256', $token, $masterKey, true);
        $exp           = time() + self::TTL;

        $baseDir  = self::base_dir($modelApi);
        $slug     = self::derive_slug($modelApi);
        $localDir = $slug !== '' ? self::model_local_dir($slug) : '';
        $mapping = array();

        // 自动懒下载:protectV2='local' 模式下,若本地无 manifest → 后台静默入队 + 立即触发 cron。
        // 本次响应仍走远程签名 URL 兜底(完全不阻塞当前 session 响应)。
        // 多访客并发命中同一 slug 由 enqueue_job 的幂等保护去重(同 jobId 只入一条)。
        if ($slug !== '' && $localDir !== '') {
            $manifestBase = basename(wp_parse_url($modelApi, PHP_URL_PATH));
            $manifestLocal = $localDir . '/' . $manifestBase;
            if (!is_file($manifestLocal)) {
                self::enqueue_job($modelApi, 'auto');
            }
        }

        // 闭包优先映射到本地副本 (model/{slug}/{relPath}),本地不在才存原始 URL。
        // 这样已下载模型的子资源走 readfile,未下载 / 不可识别路径的部分走远端代理。
        $protect = function ($raw) use (&$mapping, $baseDir, $localDir, $sessionSecret, $token, $exp) {
            if (!is_string($raw) || $raw === '') {
                return $raw;
            }
            $realUrl = self::resolve_url($baseDir, $raw);
            // 仅对「原始相对路径」尝试本地 — 完整 URL / data: 不可能是本地文件。
            $local = '';
            if ($localDir !== '' && !preg_match('#^(https?:|data:)#i', $raw)) {
                $candidate = wp_normalize_path($localDir . '/' . ltrim($raw, '/'));
                if (is_file($candidate) && is_readable($candidate)) {
                    $local = $candidate;
                }
            }
            $alias   = self::random_base64url(self::ALIAS_BYTES);
            $mapping[$alias] = $local !== '' ? $local : $realUrl;
            $sig = self::sign($sessionSecret, $token, $alias, $exp);
            // 输出相对路径 —— 不带 baseUrl/token 前缀。
            // Cubism Web Framework 的 LAppModel.loadAssets / setupTextures 是
            // 字符串拼接 ${modelHomeDir}${fileName},客户端 modelHomeDir 已经是
            // ".../v2/m/{token}/",所以这里返回 "{alias}?e=&s=" 拼出来正好是完整 URL。
            return $alias . '?e=' . $exp . '&s=' . $sig;
        };

        $fr = &$manifest['FileReferences'];
        if (isset($fr['Moc']))          $fr['Moc']         = $protect($fr['Moc']);
        if (isset($fr['Physics']))      $fr['Physics']     = $protect($fr['Physics']);
        if (isset($fr['Pose']))         $fr['Pose']        = $protect($fr['Pose']);
        if (isset($fr['DisplayInfo']))  $fr['DisplayInfo'] = $protect($fr['DisplayInfo']);
        if (isset($fr['Textures']) && is_array($fr['Textures'])) {
            foreach ($fr['Textures'] as $i => $t) {
                $fr['Textures'][$i] = $protect($t);
            }
        }
        if (isset($fr['Expressions']) && is_array($fr['Expressions'])) {
            foreach ($fr['Expressions'] as $i => $exp_item) {
                if (isset($exp_item['File'])) {
                    $fr['Expressions'][$i]['File'] = $protect($exp_item['File']);
                }
            }
        }
        if (isset($fr['Motions']) && is_array($fr['Motions'])) {
            foreach ($fr['Motions'] as $group => $items) {
                if (!is_array($items)) continue;
                foreach ($items as $i => $item) {
                    if (isset($item['File'])) {
                        $fr['Motions'][$group][$i]['File'] = $protect($item['File']);
                    }
                }
            }
        }
        unset($fr); // 释放引用,避免后续 json_encode 的副作用

        // 对 mapping 中所有"本地文件"的 alias 算 SHA-256(hex),作为内容寻址键。
        // 远端代理 URL(http(s):// 开头)不收录 — 服务端代理本就不一定每会话都是同一字节流
        // (上游 ETag 不变也只是大概率,无法 SHA 兜底)。前端拿不到 hash 的 alias 自然走老的
        // URL-keyed 缓存路径,不影响功能,只是没有跨会话命中收益。
        // 这一步在首次 session 创建时同步算,首次代价 = 模型总字节 / 磁盘 I/O,
        // 一般 5~30 MB 在 SSD 上 < 100ms,与 set_transient 后立即返响应不冲突。
        $hashes = array();
        foreach ($mapping as $alias => $real) {
            // mapping 里既可能是本地绝对路径(stream_local_path 走的那种),
            // 也可能是 wp-content 下的真实 URL(stream_local 解出后变绝对路径),
            // 还可能是远端 http(s) URL(stream_remote 代理)。这里只对前两类算 hash。
            if (!is_string($real) || $real === '') continue;
            if (preg_match('#^https?://#i', $real)) continue;     // 远端不收录
            // hash_file 比 file_get_contents+hash 省一份内存(流式分块读)。
            // 失败(权限 / 不存在)直接跳过 — get_asset 仍会走 readfile,前端走 URL key 路径。
            $h = @hash_file('sha256', $real);
            if (is_string($h) && $h !== '') {
                $hashes[$alias] = $h;
            }
        }

        // 3) 写 transient(WP 端等价于 Mongo TTL index)
        $payload = array(
            'mapping'   => $mapping,
            'manifest'  => wp_json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'hashes'    => $hashes,
            'audience'  => self::request_audience($request),
            'expire_at' => $exp,
        );
        set_transient(self::transient_key($token), $payload, self::TTL);

        $manifestUrl = rest_url(self::REST_NS . '/m/' . $token . '/manifest.json');
        return new WP_REST_Response(array(
            'token'       => $token,
            'manifestUrl' => $manifestUrl,
            'expires'     => $exp,
        ), 200);
    }

    /**
     * GET /m/{token}/manifest.json
     */
    public function get_manifest(WP_REST_Request $request)
    {
        $token   = (string) $request->get_param('token');
        $payload = self::load_session($token);
        if ($payload === null) {
            return self::ok_empty_manifest();
        }
        // audience 软校验:不一致只记日志,不拦截。
        // 与 nizima.LIVE 实践一致 —— LAppModel.fetch 跑在客户站点 main world,
        // Origin 是客户站,不会等于 session 创建时记录的来源。
        $audience = self::request_audience($request);
        if ($audience !== '' && $audience !== ($payload['audience'] ?? '')) {
            error_log('live2d_V2Api: manifest audience 不一致(允许通过) token=' . $token
                . ' session=' . ($payload['audience'] ?? '') . ' req=' . $audience);
        }
        return self::respond_json_raw($payload['manifest']);
    }

    /**
     * GET /m/{token}/hashes.json
     * 返回当前 session 中所有"本地文件 alias"的 SHA-256(hex)。形如:
     *   { "abc12345xyz": "9f86d081884c7d65...", ... }
     * 远端代理 alias 不在表里;前端拿不到 hash 的 alias 走老 URL-keyed 缓存。
     * 失败(token 不存在 / 过期)按"防探测"约定回 200 + "{}"。
     */
    public function get_hashes(WP_REST_Request $request)
    {
        $token   = (string) $request->get_param('token');
        $payload = self::load_session($token);
        if ($payload === null) {
            return self::respond_json_raw('{}');
        }
        $hashes = isset($payload['hashes']) && is_array($payload['hashes']) ? $payload['hashes'] : array();
        // 不需要 nocache_headers — 这是稳定的 alias→hash 映射,与 manifest 同会话内不变;
        // 但仍给 Cache-Control: no-store 以防被中间代理误缓存到下个会话(token 不同会泄漏)。
        nocache_headers();
        header('Content-Type: application/json');
        header('Cache-Control: no-store, private');
        echo wp_json_encode($hashes, JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * GET /m/{token}/{alias}?e=&s=
     * 校验签名 → readfile() 流出真实文件(同源场景)。
     */
    public function get_asset(WP_REST_Request $request)
    {
        $token = (string) $request->get_param('token');
        $alias = (string) $request->get_param('alias');
        $exp   = (int)    $request->get_param('e');
        $sig   = (string) $request->get_param('s');

        $payload = self::load_session($token);
        if ($payload === null || $exp < time()) {
            return self::ok_one_pixel_png();
        }
        if (!isset($payload['mapping'][$alias])) {
            return self::ok_one_pixel_png();
        }

        $masterKey     = self::master_key();
        $sessionSecret = hash_hmac('sha256', $token, $masterKey, true);
        $expected      = self::sign($sessionSecret, $token, $alias, $exp);
        if (!hash_equals($expected, $sig)) {
            error_log('live2d_V2Api: 签名不匹配 token=' . $token . ' alias=' . $alias);
            return self::ok_one_pixel_png();
        }

        $realUrl = $payload['mapping'][$alias];
        return self::stream_asset($realUrl);
    }

    // ----------------------------------------------------------
    //  Helpers
    // ----------------------------------------------------------

    /**
     * master key:wp_salt('auth') 派生。
     * 与 C# 端 Data Protection 的目的等价 —— 站点级私钥,不入 git,跨实例稳定。
     */
    private static function master_key()
    {
        return hash('sha256', wp_salt('auth'), true);
    }

    private static function sign($sessionSecret, $token, $alias, $exp)
    {
        $canonical = $token . '|' . $alias . '|' . $exp;
        return self::base64url(hash_hmac('sha256', $canonical, $sessionSecret, true));
    }

    private static function base64url($bytes)
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    private static function random_base64url($byteCount)
    {
        return self::base64url(random_bytes($byteCount));
    }

    private static function transient_key($token)
    {
        // WP transient key 上限 172 字符;token 22 char,完全够。
        return 'live2d_v2sess_' . $token;
    }

    /** transient 取出后做完整性 + 过期校验,失败返 null。 */
    private static function load_session($token)
    {
        if (!preg_match('/^[A-Za-z0-9_-]{20,32}$/', $token)) {
            return null;
        }
        $payload = get_transient(self::transient_key($token));
        if (!is_array($payload) || empty($payload['mapping']) || empty($payload['manifest'])) {
            return null;
        }
        if (!empty($payload['expire_at']) && (int) $payload['expire_at'] < time()) {
            return null;
        }
        return $payload;
    }

    /** 取 modelApi 所在目录(末尾一定带 /),做相对路径基址 */
    private static function base_dir($modelApi)
    {
        $idx = strrpos($modelApi, '/');
        return $idx === false ? $modelApi . '/' : substr($modelApi, 0, $idx + 1);
    }

    /** 把 model3.json 字段里的相对路径解析成绝对 URL */
    private static function resolve_url($baseDir, $raw)
    {
        if (preg_match('#^(https?:|data:)#i', $raw)) {
            return $raw;
        }
        // 简化的 URL 拼接:不处理 .. / ./;实际 model3.json 的相对路径
        // 都是 "haru/haru.moc3" 这种正常子路径。
        return $baseDir . ltrim($raw, '/');
    }

    /** modelApi 是否与本站同源 */
    private static function is_same_origin($url)
    {
        $homeHost = wp_parse_url(get_home_url(), PHP_URL_HOST);
        $reqHost  = wp_parse_url($url, PHP_URL_HOST);
        if (!$homeHost || !$reqHost) return false;
        return strcasecmp($homeHost, $reqHost) === 0;
    }

    /** 取请求方的 origin(scheme://host[:port]) */
    private static function request_audience(WP_REST_Request $request)
    {
        $origin = $request->get_header('origin');
        if ($origin && wp_parse_url($origin, PHP_URL_HOST)) {
            $u = wp_parse_url($origin);
            return ($u['scheme'] ?? 'https') . '://' . $u['host']
                . (isset($u['port']) ? ':' . $u['port'] : '');
        }
        $referer = $request->get_header('referer');
        if ($referer && wp_parse_url($referer, PHP_URL_HOST)) {
            $u = wp_parse_url($referer);
            return ($u['scheme'] ?? 'https') . '://' . $u['host']
                . (isset($u['port']) ? ':' . $u['port'] : '');
        }
        return '';
    }

    /**
     * 资源流出入口 — mapping 值是 http(s) URL 走跨域 cURL 代理;其它一律当本地 absPath 走 readfile。
     * 同站 wp-content URL 不会出现在新版 mapping 里(create_session 会提前转为 absPath)。
     */
    private static function stream_asset($realUrl)
    {
        if (preg_match('#^https?://#i', $realUrl)) {
            return self::stream_remote($realUrl);
        }
        return self::stream_local_path($realUrl);
    }

    /**
     * readfile 本地 absPath。路径必须位于 model/ 目录下(防 path traversal)。
     */
    private static function stream_local_path($absPath)
    {
        $modelRootReal = realpath(self::model_root_dir());
        $fileReal      = $absPath ? realpath($absPath) : false;
        if ($modelRootReal === false || $fileReal === false) {
            return self::ok_one_pixel_png();
        }
        $modelRootReal = wp_normalize_path($modelRootReal);
        $fileReal      = wp_normalize_path($fileReal);
        if (strpos($fileReal, $modelRootReal) !== 0) {
            return self::ok_one_pixel_png();
        }
        if (!is_file($fileReal) || !is_readable($fileReal)) {
            return self::ok_one_pixel_png();
        }
        $mime = self::guess_mime($fileReal);
        nocache_headers();
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($fileReal));
        header('Cache-Control: no-store, private');
        header('X-Content-Type-Options: nosniff');
        readfile($fileReal);
        exit;
    }

    /**
     * 服务端代理跨域资源 — 用原生 cURL 流式透传(边收边发,大文件友好)。
     *
     * 为什么不用 wp_remote_get:`wp_remote_get` 必须 buffer 全量 body 再返,
     * 大文件(>1MB 的 .moc3 / 4096 PNG)很容易让连接缓慢的服务器命中 timeout
     * 直接 fail(实测 GitHub raw 879KB 的 moc3 在某些机房 15s 都拉不完)。
     * 流式透传则把 PHP 直接当反向代理 —— cURL 收一块就 echo 一块,客户端
     * 浏览器有自己的总超时(默认 30s),整体可用性高得多。
     *
     * 注意:这里**故意**不走 `wp_safe_remote_get`/`wp_http_validate_url`,
     * 入口 URL 来自 transient mapping,而 mapping 来自 session 创建时
     * `create_session` 反序列化的 model3.json,后者来源于后台 sanitize 过的
     * `live_2d_settings_option_name`,不是 user-controlled query。
     *
     * Referer 处理:默认不发。如需走 OSS/COS 的 Referer 防盗链,在 wp-config.php
     * 加 `define('LIVE2D_V2API_PROXY_REFERER', 'https://your-site.example/');`。
     */
    private static function stream_remote($realUrl)
    {
        if (!function_exists('curl_init')) {
            error_log('live2d_V2Api stream_remote: cURL 扩展未启用');
            return self::ok_one_pixel_png();
        }

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $realUrl,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => 10,
            // 总超时 60s — 给慢网更多空间;真正失败由 cURL 自己抛 28
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            // 用户 User-Agent 透传一些 OSS / CDN 不喜欢空 UA
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; Live2D-WP-Plugin/' . (defined('LIVE2D_VERSION') ? LIVE2D_VERSION : 'dev') . ')',
        ));

        // Referer(可选)
        $headers = array();
        if (defined('LIVE2D_V2API_PROXY_REFERER') && is_string(LIVE2D_V2API_PROXY_REFERER) && LIVE2D_V2API_PROXY_REFERER !== '') {
            $headers[] = 'Referer: ' . LIVE2D_V2API_PROXY_REFERER;
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        // ---------- 流式透传 ----------
        $headersSent = false;
        $upstreamStatus = 0;
        $upstreamMime = '';
        $upstreamLength = '';

        // 先收 header,看到 200 后再下发自己的 header,避免上游 4xx/5xx 时
        // 仍然把 200 + 一段 garbage 写给客户端。
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($_ch, $headerLine) use (&$upstreamStatus, &$upstreamMime, &$upstreamLength) {
            $line = trim($headerLine);
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $m)) {
                $upstreamStatus = (int) $m[1];
            } elseif (stripos($line, 'Content-Type:') === 0) {
                $upstreamMime = trim(substr($line, 13));
            } elseif (stripos($line, 'Content-Length:') === 0) {
                $upstreamLength = trim(substr($line, 15));
            }
            return strlen($headerLine);
        });

        // 流式 write:首块到达时下发我们自己的 200 头,后续直接 echo 出去。
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($_ch, $chunk) use (&$headersSent, &$upstreamStatus, &$upstreamMime, &$upstreamLength, $realUrl) {
            if (!$headersSent) {
                if ($upstreamStatus !== 200 && $upstreamStatus !== 0) {
                    // 上游不是 200(可能 302 已被 follow,真上游是 4xx/5xx),按失败处理
                    return 0; // 返回 0 让 cURL 中止,后面统一走 ok_one_pixel_png
                }
                $mime = $upstreamMime !== '' ? $upstreamMime : self::guess_mime_from_url($realUrl);
                nocache_headers();
                header('Content-Type: ' . $mime);
                if ($upstreamLength !== '') {
                    header('Content-Length: ' . $upstreamLength);
                }
                header('Cache-Control: no-store, private');
                header('X-Content-Type-Options: nosniff');
                $headersSent = true;
            }
            echo $chunk;
            // 调用 flush 让 PHP-FPM / Apache 立即把这块发给浏览器
            // (output_buffering 开了的环境下 ob_flush 也得跑)
            if (function_exists('ob_get_level') && ob_get_level() > 0) {
                @ob_flush();
            }
            @flush();
            return strlen($chunk);
        });

        $ok = curl_exec($ch);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($headersSent) {
            // 已经下发过 200 + 部分 body — 即使 cURL 后期 fail,客户端可能拿到
            // 不完整文件。Cubism Framework 会自己抛"Inconsistent MOC3"等,日志记下。
            if ($ok === false) {
                error_log('live2d_V2Api stream_remote partial fail errno=' . $errno . ' err=' . $err . ' url=' . $realUrl);
            }
            exit;
        }

        // 一字节也没透传,完整失败 — 写 1px PNG 与原失败行为一致。
        error_log('live2d_V2Api stream_remote fail status=' . $upstreamStatus . ' errno=' . $errno . ' err=' . $err . ' url=' . $realUrl);
        return self::ok_one_pixel_png();
    }

    /**
     * 把同源真实 URL 映射回插件目录下的物理文件并 readfile。
     * 拒绝任何越出 wp-content 的路径(防 path traversal)。
     */
    private static function stream_local($realUrl)
    {
        $contentDirUrl = trailingslashit(content_url());
        if (strpos($realUrl, $contentDirUrl) !== 0) {
            // 不是 wp-content 下的资源 —— 按跨域代理走。
            return self::stream_remote($realUrl);
        }
        $relPath = substr($realUrl, strlen($contentDirUrl));
        $absPath = WP_CONTENT_DIR . '/' . $relPath;
        $absPath = wp_normalize_path($absPath);

        // 路径必须仍在 WP_CONTENT_DIR 下(防 ../../etc/passwd)
        $contentReal = wp_normalize_path(realpath(WP_CONTENT_DIR));
        $fileReal    = realpath($absPath);
        if ($fileReal === false) {
            return self::ok_one_pixel_png();
        }
        $fileReal = wp_normalize_path($fileReal);
        if (strpos($fileReal, $contentReal) !== 0) {
            return self::ok_one_pixel_png();
        }
        if (!is_file($fileReal) || !is_readable($fileReal)) {
            return self::ok_one_pixel_png();
        }

        // readfile + Cache-Control: private,严禁被 CDN 边缘缓存
        // (WordPress REST 路由默认会经 init/headers 钩子,这里直接 exit)
        $mime = self::guess_mime($fileReal);
        nocache_headers();
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($fileReal));
        header('Cache-Control: no-store, private');
        header('X-Content-Type-Options: nosniff');
        readfile($fileReal);
        exit;
    }

    private static function guess_mime_from_url($url)
    {
        $path = wp_parse_url($url, PHP_URL_PATH);
        if (!$path) return 'application/octet-stream';
        return self::guess_mime($path);
    }

    private static function guess_mime($path)
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        switch ($ext) {
            case 'png':  return 'image/png';
            case 'jpg':
            case 'jpeg': return 'image/jpeg';
            case 'webp': return 'image/webp';
            case 'json':
            case 'moc3':
                // moc3 / motion3.json / physics3.json 等 Cubism 二进制/JSON 都按 octet-stream
                // 走,Framework 内部用 ArrayBuffer 接收,与 mime 无关。
                return $ext === 'json' ? 'application/json' : 'application/octet-stream';
            default:     return 'application/octet-stream';
        }
    }

    // ---- 标准化失败响应 ----

    private static function ok_empty()
    {
        return new WP_REST_Response(new stdClass(), 200);
    }

    private static function ok_with_error($code, $message)
    {
        return new WP_REST_Response(array(
            'errorCode' => $code,
            'message'   => $message,
        ), 200);
    }

    /** manifest 失败:200 + "{}"(防探测) */
    private static function ok_empty_manifest()
    {
        return self::respond_json_raw('{}');
    }

    /** 直接吐已经是 JSON 字符串的 manifest,跳过 wp_json_encode 二次序列化 */
    private static function respond_json_raw($jsonText)
    {
        nocache_headers();
        header('Content-Type: application/json');
        header('Cache-Control: no-store, private');
        echo $jsonText;
        exit;
    }

    /** asset 失败:200 + 1×1 透明 PNG(89B) */
    private static function ok_one_pixel_png()
    {
        $bytes = pack('C*',
            0x89,0x50,0x4E,0x47,0x0D,0x0A,0x1A,0x0A,
            0x00,0x00,0x00,0x0D,0x49,0x48,0x44,0x52,
            0x00,0x00,0x00,0x01,0x00,0x00,0x00,0x01,
            0x08,0x06,0x00,0x00,0x00,0x1F,0x15,0xC4,
            0x89,0x00,0x00,0x00,0x0D,0x49,0x44,0x41,
            0x54,0x78,0x9C,0x63,0xFA,0xCF,0x00,0x00,
            0x00,0x02,0x00,0x01,0xE5,0x27,0xDE,0xFC,
            0x00,0x00,0x00,0x00,0x49,0x45,0x4E,0x44,
            0xAE,0x42,0x60,0x82
        );
        nocache_headers();
        header('Content-Type: image/png');
        header('Cache-Control: no-store, private');
        header('Content-Length: ' . strlen($bytes));
        echo $bytes;
        exit;
    }

    // ============================================================
    //  V3 模型本地缓存(protectV2='local')
    //  - 与 V1 共用 model/ 目录;DOWNLOAD_DIR 常量在 src/live2d-SDK.php 定义。
    //  - 单模型按 slug 子目录存放,保留 model3.json 引用的相对路径结构。
    //  - 下载失败原子化清理(rename .tmp-{slug} → {slug}),不留半成品。
    //  - 公开方法供 src/live2d-Shop.php 注册的 wp_ajax_v2_* 调用。
    // ============================================================

    /** model/ 根目录 absPath(必带尾斜杠 /) */
    public static function model_root_dir()
    {
        if (defined('DOWNLOAD_DIR')) {
            return DOWNLOAD_DIR; // 与 V1 完全同址
        }
        return wp_normalize_path(plugin_dir_path(dirname(__FILE__)) . 'model/');
    }

    /** model/{slug}/ */
    public static function model_local_dir($slug)
    {
        return self::model_root_dir() . $slug;
    }

    /**
     * 由 modelApi URL 派生 slug:
     *   https://.../Mao/Mao.model3.json -> 'Mao'
     *   https://.../foo.model.json      -> 'foo'
     *   https://.../bar.json            -> 'bar'
     * 经 sanitize_file_name 清洗,空 / 非法 → ''
     */
    public static function derive_slug($modelApi)
    {
        $path = wp_parse_url($modelApi, PHP_URL_PATH);
        if (!is_string($path) || $path === '') return '';
        $base = basename($path);
        if ($base === '') return '';
        // 剥两层扩展:".model3.json" / ".model.json" / ".json"
        $stem = preg_replace('#\.(model3|model)\.json$#i', '', $base);
        if ($stem === $base) {
            $stem = preg_replace('#\.[^.]+$#', '', $base);
        }
        $san = sanitize_file_name($stem);
        return $san === '' ? '' : $san;
    }

    /**
     * 查询某 modelApi 在本地的下载状态。供前端展示用。
     * 返回:
     *   array(
     *     'slug'     => string,
     *     'exists'   => bool,                     // model3.json 是否落地
     *     'fileCount'=> int,                      // {slug}/ 下文件数(不含子目录)
     *     'totalBytes'=> int,                     // 总字节
     *   )
     */
    public static function get_local_status($modelApi)
    {
        $slug = self::derive_slug($modelApi);
        $out = array('slug' => $slug, 'exists' => false, 'fileCount' => 0, 'totalBytes' => 0);
        if ($slug === '') return $out;
        $dir = self::model_local_dir($slug);
        if (!is_dir($dir)) return $out;
        // model3.json 是否在本地
        $base = basename(wp_parse_url($modelApi, PHP_URL_PATH));
        $manifestLocal = $dir . '/' . $base;
        $out['exists'] = is_file($manifestLocal);
        // 全量统计(递归)
        try {
            $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
            foreach ($rii as $f) {
                if ($f->isFile()) {
                    $out['fileCount']++;
                    $out['totalBytes'] += (int) $f->getSize();
                }
            }
        } catch (Exception $e) {
            // 目录读不全也认账,只是计数 0
            error_log('live2d_V2Api get_local_status iterate fail: ' . $e->getMessage() . ' slug=' . $slug);
        }
        return $out;
    }

    /**
     * 把 modelApi 指向的整个 V3 模型下载到 model/{slug}/。
     * 全部成功才落地(原子 rename .tmp-{slug} → {slug});任一失败清理 + 回错。
     *
     * 返回数组(供 wp_send_json):
     *   成功:array('errorCode'=>200, 'slug'=>..., 'fileCount'=>N, 'totalBytes'=>N)
     *   失败:array('errorCode'=>非 200, 'errorMsg'=>..., 'failedUrl'=>...)
     *
     * @param string        $modelApi         用户后台填的 model3.json 直链
     * @param callable|null $progressCallback 可选,签名 fn(int $current, int $total, string $relPath):void
     *                                        - 仅供 Cron 任务调用,写 transient 进度;
     *                                        - 单条下载完成后才回调一次;
     *                                        - $current=0,$relPath='' 表示「manifest 已解析,共 $total 个子资源,即将开始」。
     */
    public static function download_model_to_local($modelApi, $progressCallback = null)
    {
        $modelApi = trim((string) $modelApi);
        if ($modelApi === '' || !preg_match('#^https?://#i', $modelApi)) {
            return array('errorCode' => 400, 'errorMsg' => 'modelApi 必须是 http(s) URL');
        }
        $slug = self::derive_slug($modelApi);
        if ($slug === '') {
            return array('errorCode' => 400, 'errorMsg' => '无法从 modelApi 派生有效的目录名');
        }

        $rootDir = self::model_root_dir();
        if (!file_exists($rootDir) && !wp_mkdir_p($rootDir)) {
            return array('errorCode' => 500, 'errorMsg' => 'model/ 目录创建失败');
        }
        $finalDir = $rootDir . $slug;
        $tmpDir   = $rootDir . '.tmp-' . $slug;

        // 已下载过:清掉旧 final,确保本次是全新下载(避免新模型与旧模型残留混存)
        if (is_dir($finalDir)) {
            self::rmdir_recursive($finalDir);
        }
        if (is_dir($tmpDir)) {
            self::rmdir_recursive($tmpDir);
        }
        if (!wp_mkdir_p($tmpDir)) {
            return array('errorCode' => 500, 'errorMsg' => '临时目录创建失败:' . $tmpDir);
        }

        // 1) 拉 model3.json
        $manifestBase = basename(wp_parse_url($modelApi, PHP_URL_PATH));
        $manifestDest = $tmpDir . '/' . $manifestBase;
        $r = self::download_one_file($modelApi, $manifestDest);
        if (!$r['ok']) {
            self::rmdir_recursive($tmpDir);
            return array('errorCode' => $r['code'], 'errorMsg' => $r['msg'], 'failedUrl' => $modelApi);
        }
        $manifestText = (string) @file_get_contents($manifestDest);
        $manifest = json_decode($manifestText, true);
        if (!is_array($manifest) || !isset($manifest['FileReferences'])) {
            self::rmdir_recursive($tmpDir);
            return array('errorCode' => 422, 'errorMsg' => 'model3.json 不是合法 manifest');
        }

        // 2) 枚举所有子资源并下载
        $baseDir = self::base_dir($modelApi);
        $relList = self::collect_relative_files($manifest['FileReferences']);
        // 过滤掉绝对 URL/data: — 它们运行时走 stream_remote,不下载
        $relListFiltered = array();
        foreach ($relList as $rel) {
            if (!is_string($rel) || $rel === '') continue;
            if (preg_match('#^(https?:|data:)#i', $rel)) continue;
            $relListFiltered[] = $rel;
        }
        $totalCount = count($relListFiltered);
        if (is_callable($progressCallback)) {
            // 通知:manifest 已就绪,共 N 个子资源,即将开始
            call_user_func($progressCallback, 0, $totalCount, '');
        }
        $doneCount = 0;
        foreach ($relListFiltered as $rel) {
            $url  = self::resolve_url($baseDir, $rel);
            $dest = $tmpDir . '/' . ltrim($rel, '/');
            // 防 zip-slip:dest 必须仍在 tmpDir 内
            $tmpReal = wp_normalize_path(realpath($tmpDir));
            $destNorm = wp_normalize_path($dest);
            if ($tmpReal === false || strpos($destNorm, $tmpReal) !== 0) {
                self::rmdir_recursive($tmpDir);
                return array('errorCode' => 422, 'errorMsg' => '非法相对路径:' . $rel);
            }
            // 父目录按需创建
            $parent = dirname($dest);
            if (!is_dir($parent) && !wp_mkdir_p($parent)) {
                self::rmdir_recursive($tmpDir);
                return array('errorCode' => 500, 'errorMsg' => '子目录创建失败:' . $parent);
            }
            $r = self::download_one_file($url, $dest);
            if (!$r['ok']) {
                self::rmdir_recursive($tmpDir);
                return array('errorCode' => $r['code'], 'errorMsg' => $r['msg'], 'failedUrl' => $url);
            }
            $doneCount++;
            if (is_callable($progressCallback)) {
                call_user_func($progressCallback, $doneCount, $totalCount, $rel);
            }
        }

        // 3) atomic rename .tmp → final
        if (!@rename($tmpDir, $finalDir)) {
            // 跨文件系统时 rename 会失败 → 退回到逐文件 copy
            if (!self::move_dir_recursive($tmpDir, $finalDir)) {
                self::rmdir_recursive($tmpDir);
                return array('errorCode' => 500, 'errorMsg' => '保存失败:' . $finalDir);
            }
        }

        $status = self::get_local_status($modelApi);
        return array(
            'errorCode'  => 200,
            'slug'       => $status['slug'],
            'fileCount'  => $status['fileCount'],
            'totalBytes' => $status['totalBytes'],
        );
    }

    /**
     * 删除某 modelApi 对应的本地副本(model/{slug}/)。
     * 返回:array('errorCode'=>200|404, 'slug'=>..., 'deleted'=>bool)
     */
    public static function delete_model_local($modelApi)
    {
        $slug = self::derive_slug($modelApi);
        if ($slug === '') {
            return array('errorCode' => 400, 'errorMsg' => '无法从 modelApi 派生 slug');
        }
        $dir = self::model_local_dir($slug);
        if (!is_dir($dir)) {
            return array('errorCode' => 200, 'slug' => $slug, 'deleted' => false);
        }
        $ok = self::rmdir_recursive($dir);
        return array('errorCode' => $ok ? 200 : 500, 'slug' => $slug, 'deleted' => (bool) $ok);
    }

    /**
     * 单文件下载:wp_remote_get(stream),失败返 array('ok'=>false,'code','msg')。
     * 单文件 50MB 上限可由 LIVE2D_V2API_MODEL_MAX_FILE_BYTES 覆盖。
     */
    private static function download_one_file($url, $destPath)
    {
        $maxBytes = defined('LIVE2D_V2API_MODEL_MAX_FILE_BYTES') ? (int) LIVE2D_V2API_MODEL_MAX_FILE_BYTES : (50 * 1024 * 1024);
        $args = array(
            'timeout'  => 60,
            'redirection' => 5,
            'sslverify' => true,
            'stream'   => true,
            'filename' => $destPath,
            'headers'  => array(),
        );
        if (defined('LIVE2D_V2API_PROXY_REFERER') && is_string(LIVE2D_V2API_PROXY_REFERER) && LIVE2D_V2API_PROXY_REFERER !== '') {
            $args['headers']['Referer'] = LIVE2D_V2API_PROXY_REFERER;
        }
        $resp = wp_remote_get($url, $args);
        if (is_wp_error($resp)) {
            @unlink($destPath);
            return array('ok' => false, 'code' => 500, 'msg' => 'cURL 错误: ' . $resp->get_error_message());
        }
        $code = (int) wp_remote_retrieve_response_code($resp);
        if ($code !== 200) {
            @unlink($destPath);
            return array('ok' => false, 'code' => $code, 'msg' => '下载失败 HTTP ' . $code);
        }
        $size = is_file($destPath) ? (int) filesize($destPath) : 0;
        if ($size <= 0) {
            @unlink($destPath);
            return array('ok' => false, 'code' => 502, 'msg' => '响应为空');
        }
        if ($size > $maxBytes) {
            @unlink($destPath);
            return array('ok' => false, 'code' => 413, 'msg' => '单文件超过限额 ' . size_format($maxBytes));
        }
        @chmod($destPath, 0644);
        return array('ok' => true, 'size' => $size);
    }

    /** 列出 FileReferences 里所有相对文件路径(扁平字符串数组)。 */
    private static function collect_relative_files($fr)
    {
        $out = array();
        if (!is_array($fr)) return $out;
        foreach (array('Moc', 'Physics', 'Pose', 'DisplayInfo', 'UserData', 'CdiFile') as $k) {
            if (!empty($fr[$k]) && is_string($fr[$k])) $out[] = $fr[$k];
        }
        if (!empty($fr['Textures']) && is_array($fr['Textures'])) {
            foreach ($fr['Textures'] as $t) if (is_string($t)) $out[] = $t;
        }
        if (!empty($fr['Expressions']) && is_array($fr['Expressions'])) {
            foreach ($fr['Expressions'] as $e) {
                if (!empty($e['File']) && is_string($e['File'])) $out[] = $e['File'];
            }
        }
        if (!empty($fr['Motions']) && is_array($fr['Motions'])) {
            foreach ($fr['Motions'] as $items) {
                if (!is_array($items)) continue;
                foreach ($items as $m) {
                    if (!empty($m['File']) && is_string($m['File'])) $out[] = $m['File'];
                    if (!empty($m['Sound']) && is_string($m['Sound'])) $out[] = $m['Sound']; // motion 可能挂语音
                }
            }
        }
        return array_values(array_unique($out));
    }

    /** 递归删除目录(同 V1 OpenZip 失败清理用法)。 */
    private static function rmdir_recursive($dir)
    {
        if (!is_dir($dir)) return true;
        try {
            $rii = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($rii as $item) {
                if ($item->isDir()) {
                    @rmdir($item->getPathname());
                } else {
                    @unlink($item->getPathname());
                }
            }
        } catch (Exception $e) {
            error_log('live2d_V2Api rmdir_recursive iterate fail: ' . $e->getMessage() . ' dir=' . $dir);
        }
        return @rmdir($dir);
    }

    /** 跨文件系统时 rename 失败 → 逐文件 copy 兜底。 */
    private static function move_dir_recursive($src, $dst)
    {
        if (!wp_mkdir_p($dst)) return false;
        try {
            $rii = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($rii as $item) {
                $rel = substr($item->getPathname(), strlen($src) + 1);
                $target = $dst . '/' . $rel;
                if ($item->isDir()) {
                    if (!is_dir($target) && !wp_mkdir_p($target)) return false;
                } else {
                    if (!@copy($item->getPathname(), $target)) return false;
                    @chmod($target, 0644);
                }
            }
        } catch (Exception $e) {
            error_log('live2d_V2Api move_dir_recursive fail: ' . $e->getMessage());
            return false;
        }
        self::rmdir_recursive($src);
        return true;
    }

    // ============================================================
    //  V3 模型本地缓存 — 异步任务队列(transient + WP Cron)
    //  ----------------------------------------------------------
    //  设计:每个 job 一个独立 transient key,key 派生自
    //    jobId = sha1(modelApi|slug)
    //  好处:
    //    - 多访客并发 enqueue 同一 slug → 写同一 key,内容相同,无 read-modify-write 竞态
    //    - 多 slug → key 隔离
    //  无需自加互斥锁;WP 自带 _get_cron_lock() 保证同一时刻只跑一个 cron 进程。
    //
    //  下载入口统一为 enqueue_job + spawn_cron;前端 + 自动懒触发都走它。
    // ============================================================

    const CACHE_JOB_PREFIX = 'live2d_v2_cache_job_';
    const CACHE_JOB_TTL    = DAY_IN_SECONDS;       // 24h,完成态保留同样时长(供 UI 显示"已缓存 12.4MB")
    const CACHE_CRON_HOOK  = 'live2d_v2_run_cache_jobs';

    /** 在 plugins_loaded 后挂 cron hook(由 wordpress-live2d.php 主入口调用) */
    public static function register_cron()
    {
        add_action(self::CACHE_CRON_HOOK, array(__CLASS__, 'run_cache_jobs'));
    }

    /** 由 modelApi 派生稳定 jobId(slug 走 derive_slug) */
    public static function job_id($modelApi, $slug = null)
    {
        if ($slug === null) $slug = self::derive_slug($modelApi);
        return sha1((string) $modelApi . '|' . (string) $slug);
    }

    /** 单条 job transient key */
    private static function job_key($jobId)
    {
        return self::CACHE_JOB_PREFIX . $jobId;
    }

    /** 取一条 job,返 null 表示不存在/过期 */
    public static function get_job($jobId)
    {
        if (!preg_match('/^[a-f0-9]{40}$/', $jobId)) return null;
        $j = get_transient(self::job_key($jobId));
        return is_array($j) ? $j : null;
    }

    /** 写一条 job(刷 TTL) */
    private static function save_job($job)
    {
        if (!is_array($job) || empty($job['jobId'])) return false;
        return set_transient(self::job_key($job['jobId']), $job, self::CACHE_JOB_TTL);
    }

    /** 删一条 job */
    public static function delete_job($jobId)
    {
        if (!preg_match('/^[a-f0-9]{40}$/', $jobId)) return false;
        return delete_transient(self::job_key($jobId));
    }

    /**
     * 列举当前所有 job(扫 wp_options LIKE)。
     * 规模 < 几十条,性能可忽略。仅供 UI 展示;Cron 内部循环也用它。
     * 返回数组按 queuedAt 升序。
     */
    public static function list_all_jobs()
    {
        global $wpdb;
        $like = '_transient_' . self::CACHE_JOB_PREFIX . '%';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
            $like
        ));
        if (!$rows) return array();
        $out = array();
        $prefixLen = strlen('_transient_' . self::CACHE_JOB_PREFIX);
        foreach ($rows as $r) {
            $jobId = substr($r->option_name, $prefixLen);
            $j = self::get_job($jobId);
            if ($j) $out[] = $j;
        }
        usort($out, function ($a, $b) {
            return ((int) ($a['queuedAt'] ?? 0)) - ((int) ($b['queuedAt'] ?? 0));
        });
        return $out;
    }

    /**
     * 入队一个下载任务。幂等:
     *   - 同 jobId 已 pending/downloading → 直接返回原 job(不覆盖)
     *   - 同 jobId 最近 1h 内 done → 返回原 job(不重复跑)
     *   - 其余(无记录 / failed / 1h 前 done)→ 新建 pending
     * 末尾自动 spawn_cron() 立即触发。
     *
     * @param string $modelApi
     * @param string $trigger 'manual' | 'auto'
     * @return array|WP_Error
     */
    public static function enqueue_job($modelApi, $trigger = 'manual')
    {
        $modelApi = trim((string) $modelApi);
        if ($modelApi === '' || !preg_match('#^https?://#i', $modelApi)) {
            return new WP_Error('invalid_url', 'modelApi 必须是 http(s) URL');
        }
        $slug = self::derive_slug($modelApi);
        if ($slug === '') {
            return new WP_Error('invalid_slug', '无法从 modelApi 派生有效目录名');
        }
        $jobId = self::job_id($modelApi, $slug);
        $existing = self::get_job($jobId);
        if ($existing) {
            $st = $existing['status'] ?? '';
            if ($st === 'pending' || $st === 'downloading') {
                return $existing; // 正在排队/下载,不重复
            }
            if ($st === 'done') {
                $age = time() - (int) ($existing['finishedAt'] ?? 0);
                if ($age >= 0 && $age < HOUR_IN_SECONDS) {
                    return $existing; // 1h 内刚完成,不重复
                }
            }
            // failed / 1h 前 done → 重置为 pending,继续走下面的写入
        }
        $job = array(
            'jobId'      => $jobId,
            'modelApi'   => $modelApi,
            'slug'       => $slug,
            'status'     => 'pending',
            'progress'   => array('current' => 0, 'total' => 0, 'currentFile' => ''),
            'queuedAt'   => time(),
            'startedAt'  => null,
            'finishedAt' => null,
            'error'      => null,
            'trigger'    => in_array($trigger, array('manual', 'auto'), true) ? $trigger : 'manual',
            'totalBytes' => null,
            'fileCount'  => null,
        );
        self::save_job($job);
        // 立即触发 cron(非阻塞)
        if (!wp_next_scheduled(self::CACHE_CRON_HOOK)) {
            wp_schedule_single_event(time(), self::CACHE_CRON_HOOK);
        }
        spawn_cron();
        return $job;
    }

    /** 进度回调内部用 — 写当前文件 + 下载序号 */
    private static function update_job_progress($jobId, $current, $total, $currentFile)
    {
        $job = self::get_job($jobId);
        if (!$job) return;
        $job['progress'] = array(
            'current'     => (int) $current,
            'total'       => (int) $total,
            'currentFile' => (string) $currentFile,
        );
        self::save_job($job);
    }

    /**
     * Cron hook 入口 — 顺序处理 pending 队列(每次 hook 跑 1 个,完成后再排一次自身接力)。
     * WP 自带 _get_cron_lock() 保证同一时刻只有一个 cron 进程跑此 hook,无需自加锁。
     */
    public static function run_cache_jobs()
    {
        // 共享主机有的 30s,这里尽量解锁;失败时 wp_schedule_single_event 会接力。
        @set_time_limit(0);
        @ignore_user_abort(true);

        $jobs = self::list_all_jobs();
        $pending = null;
        foreach ($jobs as $j) {
            if (($j['status'] ?? '') === 'pending') { $pending = $j; break; }
        }
        if (!$pending) return; // 无待办,正常结束

        $jobId = $pending['jobId'];
        $pending['status']    = 'downloading';
        $pending['startedAt'] = time();
        $pending['error']     = null;
        self::save_job($pending);

        $cb = function ($current, $total, $relPath) use ($jobId) {
            self::update_job_progress($jobId, $current, $total, $relPath);
        };

        $result = self::download_model_to_local($pending['modelApi'], $cb);

        // 重新读一遍(下载期间可能被 cleanup 删了) — 没了就放弃写终态,不抛错
        $job = self::get_job($jobId);
        if (!$job) {
            // 任务在执行中被外部删除,跳过
        } else {
            $job['finishedAt'] = time();
            if (is_array($result) && (int) ($result['errorCode'] ?? 0) === 200) {
                $job['status']     = 'done';
                $job['error']      = null;
                $job['fileCount']  = (int) ($result['fileCount'] ?? 0);
                $job['totalBytes'] = (int) ($result['totalBytes'] ?? 0);
                $job['progress']['current'] = $job['progress']['total'] ?: $job['fileCount'];
                $job['progress']['currentFile'] = '';
            } else {
                $job['status'] = 'failed';
                $msg = is_array($result) ? ($result['errorMsg'] ?? '未知错误') : '未知错误';
                $url = is_array($result) ? ($result['failedUrl'] ?? '') : '';
                $job['error'] = $url !== '' ? ($msg . ' (' . $url . ')') : $msg;
            }
            self::save_job($job);
        }

        // 还有 pending,1 秒后接力下一个,避免单进程被 max_execution_time 截断
        $next = self::list_all_jobs();
        foreach ($next as $j) {
            if (($j['status'] ?? '') === 'pending') {
                wp_schedule_single_event(time() + 1, self::CACHE_CRON_HOOK);
                break;
            }
        }
    }

    /**
     * 「全部清理」— 删除 model/ 目录下所有 V2 模型(用 manifest 后缀区分,V1 的 model.json 不动)。
     * 同时清理所有 done/failed 的 job 记录。
     * 返回:array('errorCode'=>200,'deleted'=>['slug1','slug2',...])
     */
    public static function cleanup_all_v2()
    {
        $rootDir = self::model_root_dir();
        $deleted = array();
        if (!is_dir($rootDir)) {
            return array('errorCode' => 200, 'deleted' => $deleted);
        }
        $entries = @scandir($rootDir);
        if (!is_array($entries)) {
            return array('errorCode' => 500, 'errorMsg' => 'model 目录读取失败');
        }
        foreach ($entries as $name) {
            if ($name === '.' || $name === '..') continue;
            $sub = $rootDir . $name;
            if (!is_dir($sub)) continue;
            // V2 标识:目录里有 *.model3.json
            if (self::dir_has_v2_manifest($sub)) {
                if (self::rmdir_recursive($sub)) {
                    $deleted[] = $name;
                }
            }
        }
        // 同步清理已完成/失败的 job 记录(pending/downloading 不动,避免影响正在跑的任务)
        foreach (self::list_all_jobs() as $j) {
            $st = $j['status'] ?? '';
            if ($st === 'done' || $st === 'failed') {
                self::delete_job($j['jobId']);
            }
        }
        return array('errorCode' => 200, 'deleted' => $deleted);
    }

    /**
     * 「清理孤儿」— 与传入的 modelApi 列表对比,删磁盘上多余的 V2 目录。
     * 入参 $keepModelApis 是当前 modelDir 展开 + JsonFile 模式下的 modelAPI(全集)。
     * 返回:array('errorCode'=>200,'deleted'=>['slug1',...],'kept'=>['slug2',...])
     */
    public static function cleanup_orphans_v2($keepModelApis)
    {
        $rootDir = self::model_root_dir();
        $deleted = array();
        $kept    = array();
        if (!is_dir($rootDir)) {
            return array('errorCode' => 200, 'deleted' => $deleted, 'kept' => $kept);
        }
        $keepSlugs = array();
        if (is_array($keepModelApis)) {
            foreach ($keepModelApis as $url) {
                $s = self::derive_slug((string) $url);
                if ($s !== '') $keepSlugs[$s] = true;
            }
        }
        $entries = @scandir($rootDir);
        if (!is_array($entries)) {
            return array('errorCode' => 500, 'errorMsg' => 'model 目录读取失败');
        }
        foreach ($entries as $name) {
            if ($name === '.' || $name === '..') continue;
            $sub = $rootDir . $name;
            if (!is_dir($sub)) continue;
            if (!self::dir_has_v2_manifest($sub)) continue; // 仅处理 V2,V1 不动
            if (isset($keepSlugs[$name])) {
                $kept[] = $name;
                continue;
            }
            if (self::rmdir_recursive($sub)) {
                $deleted[] = $name;
                // 顺手删该 slug 关联的 job 记录(无 modelApi 反推不出 jobId,保守不动)
            }
        }
        return array('errorCode' => 200, 'deleted' => $deleted, 'kept' => $kept);
    }

    /** 目录里是否存在 *.model3.json(用于 V2 vs V1 判定)。仅扫一层。 */
    private static function dir_has_v2_manifest($dir)
    {
        $files = @scandir($dir);
        if (!is_array($files)) return false;
        foreach ($files as $f) {
            if (substr($f, -strlen('.model3.json')) === '.model3.json') return true;
        }
        return false;
    }

    /**
     * 从当前保存的 live_2d_settings_option_name 还原应该被缓存的全部 modelApi 列表。
     * 与前端 protectV2Settings / protectV2ModelDirSettings 的 URL 推导规则一致:
     *   - apiType='custom' && modelAPI 以 .json 结尾 → JsonFile 模式,单个 URL
     *   - apiType='custom' && modelDir 非空           → ModelDir 模式,展开为 [${root}${dir}/${dir}.model3.json, ...]
     *   - apiType='custom' && 其余                    → 空(没有合法 V2 模型可缓存)
     *   - 其它 apiType                                → 空(本地/远程 V1 模式不走 V2 防盗链)
     * 用于:批量入队、清理孤儿、UI 展示行列表。
     *
     * @return string[] 去重后的 URL 列表
     */
    public static function collect_configured_targets()
    {
        $opt = get_option('live_2d_settings_option_name');
        if (!is_array($opt)) return array();
        $apiType = isset($opt['apiType']) ? (string) $opt['apiType'] : '';
        if ($apiType !== 'custom') return array();
        $modelApi = isset($opt['modelAPI']) ? trim((string) $opt['modelAPI']) : '';
        if ($modelApi === '' || !preg_match('#^https?://#i', $modelApi)) return array();

        // JsonFile 模式
        if (preg_match('#\.json$#i', $modelApi)) {
            return array($modelApi);
        }
        // ModelDir 模式
        $dirs = isset($opt['modelDir']) && is_array($opt['modelDir']) ? $opt['modelDir'] : array();
        $root = $modelApi;
        if (substr($root, -1) !== '/') $root .= '/';
        $out = array();
        foreach ($dirs as $dir) {
            $dir = trim((string) $dir);
            if ($dir === '') continue;
            $out[] = $root . $dir . '/' . $dir . '.model3.json';
        }
        return array_values(array_unique($out));
    }
}
