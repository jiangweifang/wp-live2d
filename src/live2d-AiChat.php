<?php
/**
 * WordPress 内置 AI 通道 (PHP AI Client SDK)
 * ----------------------------------------------------------
 * 与「Live2dWeb 云端」(api.live2dweb.com/chatmsg, qwen/hunyuan) 并列的本地后端。
 * 站长在「工具栏设置 → AI 后端」选 `wp-builtin` 时启用,前端 live2d-tips.ts
 * 走 fetch + ReadableStream 解析 SSE,替代原 SignalR 通道。
 *
 * 依赖关系(任选其一即可,本插件不打包 SDK):
 *   - WordPress 7.0+ : 核心内置 PHP AI Client SDK + Abilities API
 *   - 官方「AI」插件 (wordpress.org/plugins/ai/) - WP 6.x 上需配合 provider 插件
 *   - 「AI Provider for OpenAI」(wordpress.org/plugins/ai-provider-for-openai/) - 自带 SDK
 *   - 「AI Services」(felixarntz/ai-services) - 自带 SDK, 同时提供 OpenAI/Google/Anthropic 等
 *   - composer 包 `wordpress/php-ai-client` + 任意 provider 包
 *
 * provider 不硬编码: 调 ->generateText() 时省略 usingProvider(), SDK 会从
 * AiClient::defaultRegistry() 里挑出首个 isConfigured() 的 provider 自动用。
 * 站长在后台选了具体 provider id 才优先用那个; qwen / hunyuan 出官方 provider
 * 包后无需改一行代码就能切过去。
 *
 * 路由:
 *   POST /wp-json/live2d/v1/ai-chat
 *     - Header X-WP-Nonce: 必填 (wp_create_nonce('wp_rest'))
 *     - Body  { "message": string, "page"?: { ... } }
 *     - Resp  Content-Type: text/event-stream (SSE)
 *       事件帧:
 *         event: token        (data: 文本片段)
 *         event: done         (data: {"reason":"finish"})
 *         event: error        (data: {"message":"..."})
 *
 * 鉴权:
 *   - 必须登录用户 (避免匿名爆刷 OpenAI/Google 等付费配额)
 *   - WP REST nonce 双重校验
 *   - 速率限制: 每用户每分钟 20 次 (transient)
 *
 * 流式说明:
 *   php-ai-client 1.3.x 还没有公开的真流式 generator API,
 *   这里调 ->generateText() 一次性拿全文,然后 PHP 端按 UTF-8 字符
 *   切片用 SSE 推出去,前端体验仍是「打字机」效果。后续 SDK 出真流式
 *   API 再升级。
 */

if (!defined('ABSPATH')) {
    exit;
}

class live2d_AiChat
{
    const REST_NS         = 'live2d/v1';
    const RATE_LIMIT      = 20;     // 每分钟最多请求次数
    const RATE_WINDOW     = 60;
    const TYPEWRITER_MS   = 25;     // 每片 sleep(ms), 越大越像打字机

    /**
     * 检测当前环境是否具备 AI Building Blocks 能力:
     *   1) PHP AI Client SDK 类是否存在 (WP 7.0+ 内置 / provider 插件随包带入)
     *   2) 是否有至少 1 个已配置 (有 API Key) 的 provider
     *
     * 仅供后台设置页 + handle_chat() 复用; 后台需要据此决定是否打开/隐藏开关。
     *
     * @return array{available:bool, sdk:bool, providers:list<string>, configured:list<string>}
     */
    public static function detect_environment()
    {
        $out = array(
            'available'  => false,
            'sdk'        => false,
            'providers'  => array(),
            'configured' => array(),
        );
        if (!class_exists('WordPress\\AiClient\\AiClient')) {
            return $out;
        }
        $out['sdk'] = true;
        try {
            $registry = \WordPress\AiClient\AiClient::defaultRegistry();
            $ids = $registry->getRegisteredProviderIds();
        } catch (\Throwable $e) {
            return $out;
        }
        $out['providers'] = is_array($ids) ? array_values($ids) : array();
        foreach ($out['providers'] as $pid) {
            try {
                if (\WordPress\AiClient\AiClient::isConfigured($pid)) {
                    $out['configured'][] = $pid;
                }
            } catch (\Throwable $e) {
                // ignore single-provider failure, keep checking others
            }
        }
        $out['available'] = !empty($out['configured']);
        return $out;
    }

    /** REST 路由注册:挂在 rest_api_init 钩子 */
    public static function register_routes()
    {
        $self = new self();
        register_rest_route(self::REST_NS, '/ai-chat', array(
            'methods'             => 'POST',
            'permission_callback' => array($self, 'check_permission'),
            'callback'            => array($self, 'handle_chat'),
            'args'                => array(
                'message' => array('required' => true, 'type' => 'string'),
            ),
        ));
    }

    /**
     * 双重校验:
     *   1) WP REST nonce (X-WP-Nonce 头, WP 内部已经走过 cookie 校验)
     *   2) 必须登录用户
     */
    public function check_permission(WP_REST_Request $req)
    {
        if (!is_user_logged_in()) {
            return new WP_Error('not_logged_in', __('请登录后再使用 AI 对话功能。', 'live-2d'), array('status' => 401));
        }
        // 速率限制
        $uid = get_current_user_id();
        $key = 'l2d_ai_rl_' . $uid;
        $count = (int) get_transient($key);
        if ($count >= self::RATE_LIMIT) {
            return new WP_Error('rate_limited', __('请求过于频繁,请稍后再试。', 'live-2d'), array('status' => 429));
        }
        set_transient($key, $count + 1, self::RATE_WINDOW);
        return true;
    }

    /**
     * 主处理:输出 SSE。
     * 注意 REST 控制器拿到 callback 返回值才会发头/写响应体,
     * 这里要在写入第一帧前就把 header 直接 send,然后流式 echo + flush,
     * 最后 exit 阻止 REST 框架再追加 JSON 包装。
     */
    public function handle_chat(WP_REST_Request $req)
    {
        $env = self::detect_environment();
        if (!$env['sdk']) {
            return new WP_Error(
                'sdk_missing',
                __('未检测到 PHP AI Client SDK。请安装下列任一插件并启用:WordPress 官方「AI」(WP 7.0+)、「AI Provider for OpenAI」、「AI Services」。', 'live-2d'),
                array('status' => 500)
            );
        }
        if (empty($env['configured'])) {
            return new WP_Error(
                'provider_unconfigured',
                __('PHP AI Client SDK 已就绪,但没有可用 provider。请在 wp-config.php 配置 API Key(如 OPENAI_API_KEY / GOOGLE_API_KEY),或在 provider 插件后台填入凭证。', 'live-2d'),
                array('status' => 500)
            );
        }

        $message = trim((string) $req->get_param('message'));
        if ($message === '') {
            return new WP_Error('empty_message', __('消息为空。', 'live-2d'), array('status' => 400));
        }

        $opts          = get_option('live_2d_settings_option_name');
        $system_prompt = (is_array($opts) && !empty($opts['aiSystemPrompt']))
            ? (string) $opts['aiSystemPrompt']
            : '你是一只可爱的 Live2D 看板娘,回答简洁、温柔,带一点俏皮。';
        $model_pref    = (is_array($opts) && !empty($opts['aiModel']))
            ? (string) $opts['aiModel']
            : '';
        // 站长在后台显式选了 provider id (例如 'openai' / 'google') 时优先用那个;
        // 留空表示让 SDK 自动从 $env['configured'] 里挑首个可用的, 适配未来出现的
        // qwen / hunyuan provider 包 (无需改一行代码就能切过去)。
        $provider_pref = (is_array($opts) && !empty($opts['aiProviderId']))
            ? (string) $opts['aiProviderId']
            : '';

        $this->send_sse_headers();

        try {
            $builder = \WordPress\AiClient\AiClient::prompt($message)
                ->usingSystemInstruction($system_prompt)
                ->usingTemperature(0.8)
                ->usingMaxTokens(1024);

            if ($provider_pref !== '' && in_array($provider_pref, $env['configured'], true)) {
                $builder = $builder->usingProvider($provider_pref);
            }
            if ($model_pref !== '') {
                $builder = $builder->usingModelPreference($model_pref);
            }

            $reply = (string) $builder->generateText();
        } catch (\Throwable $e) {
            $this->sse_emit('error', array('message' => $e->getMessage()));
            $this->sse_done();
            exit;
        }

        if ($reply === '') {
            $this->sse_emit('error', array('message' => __('AI 未返回任何内容。', 'live-2d')));
            $this->sse_done();
            exit;
        }

        // 按 UTF-8 字符切片(中文每次 1 字, 英文每次 2~3 字符)。
        $this->stream_typewriter($reply);
        $this->sse_done();
        exit;
    }

    /**
     * SSE 响应头 + 关掉所有可能的缓冲(WP/PHP/Nginx)。
     */
    private function send_sse_headers()
    {
        // WP REST 默认会发 application/json + 200, 这里强制覆盖。
        @ini_set('zlib.output_compression', '0');
        @ini_set('output_buffering', 'off');
        @ini_set('implicit_flush', '1');
        while (ob_get_level() > 0) {
            @ob_end_flush();
        }
        nocache_headers();
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Connection: keep-alive');
        // Nginx fastcgi/proxy 默认会 buffer SSE,这一行让它直通。
        header('X-Accel-Buffering: no');
    }

    /** 发一个 SSE 事件 */
    private function sse_emit($event, $data)
    {
        $payload = is_string($data) ? $data : wp_json_encode($data);
        echo "event: " . $event . "\n";
        // 多行 data 必须每行前缀 "data:",这里数据短不会有换行,统一一行处理。
        echo "data: " . str_replace(array("\r\n", "\n"), '\\n', $payload) . "\n\n";
        @flush();
    }

    private function sse_done()
    {
        $this->sse_emit('done', array('reason' => 'finish'));
    }

    /**
     * UTF-8 安全切字 + 节流输出。每片 2~4 个 mb_char,
     * 中英文混排都能拿到「逐字浮现」的体验。
     */
    private function stream_typewriter($text)
    {
        $chars  = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($chars) || count($chars) === 0) {
            return;
        }
        $chunk_size = 3;
        $buf = '';
        $i = 0;
        foreach ($chars as $c) {
            $buf .= $c;
            $i++;
            if ($i % $chunk_size === 0) {
                $this->sse_emit('token', $buf);
                $buf = '';
                usleep(self::TYPEWRITER_MS * 1000);
                if (connection_aborted()) {
                    return;
                }
            }
        }
        if ($buf !== '') {
            $this->sse_emit('token', $buf);
        }
    }
}
