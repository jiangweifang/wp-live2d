<?php
class live2D_Settings_Toolbar {
    private $live_2d__options;
    private $userInfo;
    public function live_2d_settings_toolbar_init() {
        $this->live_2d__options = get_option( 'live_2d_settings_option_name' );
        $this->userInfo = get_option('live_2d_settings_user_token');

        add_settings_section(
            'live_2d_setting_toolbar_section', // id
            __('工具栏设置','live-2d'), // title
            array( $this, 'live_2d_toolbar_section_info' ), // callback
            'live-2d-settings-toolbar' // page
        );

        add_settings_field(
            'showToolMenu', // id
            __('工具栏','live-2d'), // title
            array( $this, 'showToolMenu_callback' ), // callback
            'live-2d-settings-toolbar', // page
            'live_2d_setting_toolbar_section' // section
        );

        add_settings_field(
            'isBotButton', // id
            __('ChatGPT按钮','live-2d'), // title
            array( $this, 'isBotButton_callback' ), // callback
            'live-2d-settings-toolbar', // page
            'live_2d_setting_toolbar_section' // section
        );

        // AI 后端整组 4 个字段(aiProvider + aiProviderId + aiSystemPrompt + aiModel)
        // 与 waifu-Settings-Base.php 里 custom-* 模型方式同付费门槛: userLevel>0 才 register。
        // 未付费用户直接看不到这一整组,sanitize 阶段额外兜底(防止绕过前端 POST)。
        if (!empty($this->userInfo["userLevel"]) && intval($this->userInfo["userLevel"]) > 0) {
            add_settings_field(
                'aiProvider', // id
                __('AI 后端','live-2d'), // title
                array( $this, 'aiProvider_callback' ), // callback
                'live-2d-settings-toolbar', // page
                'live_2d_setting_toolbar_section' // section
            );

            add_settings_field(
                'aiProviderId', // id
                __('AI Provider(WP 内置)','live-2d'), // title
                array( $this, 'aiProviderId_callback' ), // callback
                'live-2d-settings-toolbar', // page
                'live_2d_setting_toolbar_section', // section
                // 第 6 个参数 $args 里的 class 会被附加到 settings_field 渲染的 <tr> 上,
                // 供 aiProvider_callback 末尾的 inline JS 选中并按 aiProvider 切换显隐。
                array('class' => 'live2d-ai-builtin-row')
            );

            add_settings_field(
                'aiSystemPrompt', // id
                __('AI 人设(System Prompt)','live-2d'), // title
                array( $this, 'aiSystemPrompt_callback' ), // callback
                'live-2d-settings-toolbar', // page
                'live_2d_setting_toolbar_section', // section
                array('class' => 'live2d-ai-builtin-row')
            );

            add_settings_field(
                'aiModel', // id
                __('AI 模型(可选)','live-2d'), // title
                array( $this, 'aiModel_callback' ), // callback
                'live-2d-settings-toolbar', // page
                'live_2d_setting_toolbar_section', // section
                array('class' => 'live2d-ai-builtin-row')
            );
        }

        add_settings_field(
            'canCloseLive2d', // id
            __('关闭看板娘按钮','live-2d'), // title
            array( $this, 'canCloseLive2d_callback' ), // callback
            'live-2d-settings-toolbar', // page
            'live_2d_setting_toolbar_section' // section
        );

        add_settings_field(
            'canSwitchModel', // id
            __('模型切换按钮','live-2d'), // title
            array( $this, 'canSwitchModel_callback' ), // callback
            'live-2d-settings-toolbar', // page
            'live_2d_setting_toolbar_section' // section
        );

        add_settings_field(
            'canSwitchTextures', // id
            __('材质切换按钮','live-2d'), // title
            array( $this, 'canSwitchTextures_callback' ), // callback
            'live-2d-settings-toolbar', // page
            'live_2d_setting_toolbar_section' // section
        );

        add_settings_field(
            'canSwitchHitokoto', // id
            __('一言切换按钮','live-2d'), // title
            array( $this, 'canSwitchHitokoto_callback' ), // callback
            'live-2d-settings-toolbar', // page
            'live_2d_setting_toolbar_section' // section
        );

        add_settings_field(
            'canTakeScreenshot', // id
            __('看板娘截图按钮','live-2d'), // title
            array( $this, 'canTakeScreenshot_callback' ), // callback
            'live-2d-settings-toolbar', // page
            'live_2d_setting_toolbar_section' // section
        );

        add_settings_field(
            'screenshotCaptureName', // id
            __('看板娘截图文件名','live-2d'), // title
            array( $this, 'screenshotCaptureName_callback' ), // callback
            'live-2d-settings-toolbar', // page
            'live_2d_setting_toolbar_section' // section
        );

        add_settings_field(
            'canTurnToHomePage', // id
            __('返回首页按钮','live-2d'), // title
            array( $this, 'canTurnToHomePage_callback' ), // callback
            'live-2d-settings-toolbar', // page
            'live_2d_setting_toolbar_section' // section
        );

        add_settings_field(
            'canTurnToAboutPage', // id
            __('跳转关于页按钮','live-2d'), // title
            array( $this, 'canTurnToAboutPage_callback' ), // callback
            'live-2d-settings-toolbar', // page
            'live_2d_setting_toolbar_section' // section
        );

        add_settings_field(
            'aboutPageUrl', // id
            __('关于页地址','live-2d'), // title
            array( $this, 'aboutPageUrl_callback' ), // callback
            'live-2d-settings-toolbar', // page
            'live_2d_setting_toolbar_section' // section
        );

        add_settings_field(
            'waifuToolFont', // id
            __('工具栏图标大小(px)','live-2d'), // title
            array( $this, 'waifuToolFont_callback' ), // callback
            'live-2d-settings-toolbar', // page
            'live_2d_setting_toolbar_section' // section
        );

        add_settings_field(
            'waifuToolLine', // id
            __('工具栏行高(px)','live-2d'), // title
            array( $this, 'waifuToolLine_callback' ), // callback
            'live-2d-settings-toolbar', // page
            'live_2d_setting_toolbar_section' // section
        );

        add_settings_field(
            'waifuToolTop', // id
            __('工具栏顶部边距(px)','live-2d'), // title
            array( $this, 'waifuToolTop_callback' ), // callback
            'live-2d-settings-toolbar', // page
            'live_2d_setting_toolbar_section' // section
        );

        add_settings_field(
            'waifuToolColor', // id
            __('工具栏图标颜色','live-2d'), // title
            array( $this, 'waifuToolColor_callback' ), // callback
            'live-2d-settings-toolbar', // page
            'live_2d_setting_toolbar_section' // section
        );

        add_settings_field(
            'waifuToolHover', // id
            __('鼠标触碰时图标颜色','live-2d'), // title
            array( $this, 'waifuToolHover_callback' ), // callback
            'live-2d-settings-toolbar', // page
            'live_2d_setting_toolbar_section' // section
        );
    }

    public function live_2d_toolbar_section_info(){
        
    }

    public function showToolMenu_callback() {
        ?> <fieldset><?php $checked = ( isset( $this->live_2d__options['showToolMenu'] ) && $this->live_2d__options['showToolMenu'] === true ) ? 'checked' : '' ; ?>
        <label for="showToolMenu-0"><input type="radio" name="live_2d_settings_option_name[showToolMenu]" id="showToolMenu-0" value="1" <?php echo $checked; ?>> <?php esc_html_e('显示','live-2d') ?></label><br>
        <?php $checked = ( isset( $this->live_2d__options['showToolMenu'] ) && $this->live_2d__options['showToolMenu'] === false ) ? 'checked' : '' ; ?>
        <label for="showToolMenu-1"><input type="radio" name="live_2d_settings_option_name[showToolMenu]" id="showToolMenu-1" value="0" <?php echo $checked; ?>> <?php esc_html_e('隐藏','live-2d') ?></label></fieldset> <?php
    }

    public function isBotButton_callback(){
        ?> <fieldset><?php $checked = ( isset( $this->live_2d__options['isBotButton'] ) && $this->live_2d__options['isBotButton'] === true ) ? 'checked' : '' ; ?>
        <label for="isBotButton-0"><input type="radio" name="live_2d_settings_option_name[isBotButton]" id="isBotButton-0" value="1" <?php echo $checked; ?>> <?php esc_html_e('显示','live-2d') ?></label><br>
        <?php $checked = ( isset( $this->live_2d__options['isBotButton'] ) && $this->live_2d__options['isBotButton'] === false ) ? 'checked' : '' ; ?>
        <label for="isBotButton-1"><input type="radio" name="live_2d_settings_option_name[isBotButton]" id="isBotButton-1" value="0" <?php echo $checked; ?>> <?php esc_html_e('隐藏','live-2d') ?></label></fieldset> <?php
    }

    /**
     * AI 后端选择: live2dweb (默认, 原 SignalR 走 qwen/hunyuan) 或
     * wp-builtin (本站 PHP 调 WordPress AI Client SDK, 由 AI Building Blocks
     * 体系下任意一个 provider 插件提供 — 例如官方「AI」、「AI Provider for OpenAI」、
     * 「AI Services」, 或直接 composer 引入 wordpress/php-ai-client + 任意 provider 包).
     *
     * 启用阈值: 只看 PHP AI Client SDK 类是否存在, 不绑定 WP 版本号 —
     * ai-provider-for-openai 1.0.3 起 Requires at least: 6.9, 所以 WP 6.9+
     * 装上该 provider 插件就能用; WP 7.0+ 核心内置 SDK 则无需任何 provider 插件
     * (但 provider 包仍需提供 API Key)。
     */
    public function aiProvider_callback() {
        $cur = isset($this->live_2d__options['aiProvider']) ? $this->live_2d__options['aiProvider'] : 'live2dweb';
        $env = class_exists('live2d_AiChat') ? live2d_AiChat::detect_environment() : array(
            'available'  => false,
            'sdk'        => false,
            'providers'  => array(),
            'configured' => array(),
        );
        // SDK 未加载 → wp-builtin 不可选,强制把 cur 拉回 live2dweb 以免渲染出已选但 disabled。
        $can_use_builtin = $env['sdk'];
        if (!$can_use_builtin && $cur === 'wp-builtin') {
            $cur = 'live2dweb';
        }
        ?>
        <fieldset style="line-height:1.7;">
            <label for="aiProvider-0">
                <input type="radio" name="live_2d_settings_option_name[aiProvider]" id="aiProvider-0" class="live2d-ai-provider" value="live2dweb" <?php checked($cur, 'live2dweb'); ?>>
                <?php esc_html_e('Live2dWeb 云端(默认, qwen / hunyuan)','live-2d') ?>
            </label>
            <p class="description" style="margin:2px 0 12px 24px;">
                <?php
                echo wp_kses(
                    __('通过 <a href="https://www.live2dweb.com/Sites/" target="_blank" rel="noopener">Live2dWeb</a> 走插件作者后端的 qwen / hunyuan,需要登录付费账号。', 'live-2d'),
                    array('a' => array('href' => array(), 'target' => array(), 'rel' => array()))
                );
                ?>
            </p>

            <label for="aiProvider-1" <?php if (!$can_use_builtin) echo 'style="opacity:.55;"'; ?>>
                <input type="radio" name="live_2d_settings_option_name[aiProvider]" id="aiProvider-1" class="live2d-ai-provider" value="wp-builtin"
                    <?php checked($cur, 'wp-builtin'); ?>
                    <?php disabled(!$can_use_builtin); ?>
                >
                <?php esc_html_e('WordPress 内置(AI Building Blocks / PHP AI Client SDK)','live-2d') ?>
            </label>
            <div class="description" style="margin:2px 0 0 24px;">
                <?php
                echo wp_kses(
                    __('依赖 <a href="https://make.wordpress.org/ai/2025/07/17/ai-building-blocks/" target="_blank" rel="noopener">AI Building Blocks</a> 体系,任选其一即可:', 'live-2d'),
                    array('a' => array('href' => array(), 'target' => array(), 'rel' => array()))
                );
                ?>
                <ul class="description" style="margin-left:18px;list-style:disc;">
                    <li>
                        <?php
                        // WP 7.0+ 核心把 PHP AI Client SDK 内置进去, 凭证管理页固定挂在
                        // options-connectors.php (Settings → Connectors)。WP 6.x 上该地址
                        // 不存在, 点开会 404; 但本行整段文案就是说 "7.0+ 核心" 的, 6.x
                        // 用户应该看下面 3 项 provider 插件方案, 所以可以放心给绝对 admin URL。
                        echo wp_kses(
                            sprintf(
                                /* translators: %s: admin URL of Settings → Connectors */
                                __('WordPress 7.0+ 核心内置 SDK,在 <a href="%s">设置 → Connectors</a> 配置(无需额外插件)', 'live-2d'),
                                esc_url(admin_url('options-connectors.php'))
                            ),
                            array('a' => array('href' => array()))
                        );
                        ?>
                    </li>
                    <li>
                        <?php
                        echo wp_kses(
                            __('<a href="https://wordpress.org/plugins/ai/" target="_blank" rel="noopener">官方「AI」插件</a>(WP 7.0+)', 'live-2d'),
                            array('a' => array('href' => array(), 'target' => array(), 'rel' => array()))
                        );
                        ?>
                    </li>
                    <li>
                        <?php
                        echo wp_kses(
                            __('<a href="https://wordpress.org/plugins/ai-provider-for-openai/" target="_blank" rel="noopener">「AI Provider for OpenAI」</a>(WP 6.9+ 即可)', 'live-2d'),
                            array('a' => array('href' => array(), 'target' => array(), 'rel' => array()))
                        );
                        ?>
                    </li>
                    <li>
                        <?php
                        echo wp_kses(
                            __('<a href="https://github.com/felixarntz/ai-services" target="_blank" rel="noopener">「AI Services」</a>(同时支持 OpenAI / Google / Anthropic 等)', 'live-2d'),
                            array('a' => array('href' => array(), 'target' => array(), 'rel' => array()))
                        );
                        ?>
                    </li>
                </ul>
                <strong><?php esc_html_e('当前检测结果:','live-2d'); ?></strong>
                <?php if (!$env['sdk']): ?>
                    <span style="color:#b32d2e;"><?php esc_html_e('未检测到 PHP AI Client SDK — 请先安装上述任一插件并启用,本选项才能使用。', 'live-2d'); ?></span>
                <?php elseif (empty($env['providers'])): ?>
                    <span style="color:#b32d2e;"><?php esc_html_e('SDK 已加载,但未注册任何 provider', 'live-2d'); ?></span>
                <?php else: ?>
                    <?php esc_html_e('已注册 provider:', 'live-2d'); ?>
                    <code><?php echo esc_html(implode(', ', $env['providers'])); ?></code>;
                    <?php esc_html_e('已配置(可用):', 'live-2d'); ?>
                    <code style="color:<?php echo empty($env['configured']) ? '#b32d2e' : '#1a7f37'; ?>;">
                        <?php echo esc_html(empty($env['configured']) ? __('无', 'live-2d') : implode(', ', $env['configured'])); ?>
                    </code>
                <?php endif; ?>
            </div>
        </fieldset>
        <script>
            // 联动: 只有选中「WordPress 内置」时, 才显示 aiProviderId / aiSystemPrompt / aiModel 三行。
            // 模仿 live2d-admin.ts 里 apiType 行的做法: 完全由 JS 设 inline display,
            // 不挂 <style> 规则 (内联 display='' 无法覆盖 <style> 里的 display:none, 会出现
            // "点了但不显示" 的 bug)。settings field 注册时 args.class 写了
            // 'live2d-ai-builtin-row', WP 会把它附加到 <tr> 上。
            (function () {
                var sync = function () {
                    var sel = document.querySelector('input.live2d-ai-provider:checked');
                    var show = sel && sel.value === 'wp-builtin';
                    var rows = document.querySelectorAll('tr.live2d-ai-builtin-row');
                    for (var i = 0; i < rows.length; i++) {
                        rows[i].style.display = show ? '' : 'none';
                    }
                };
                var radios = document.querySelectorAll('input.live2d-ai-provider');
                for (var i = 0; i < radios.length; i++) {
                    radios[i].addEventListener('change', sync);
                }
                // 立即跑一次, 同时挂 DOMContentLoaded 兜底 — 这段 <script> 是在
                // aiProvider 行的 callback 里输出, 它后面的 aiProviderId / aiSystemPrompt /
                // aiModel 三行的 <tr> 此刻可能还没被浏览器解析出来。两次 sync 都跑确保命中。
                sync();
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', sync);
                }
            })();
        </script>
        <?php
    }

    /**
     * 可选: 在多个 configured provider 之间显式指定。
     * 留空 = 让 AiClient 从 defaultRegistry 里自动挑首个 isConfigured 的。
     * qwen / hunyuan 出官方 provider 包后, 站长在这里选一下就能切, 无需改代码。
     */
    public function aiProviderId_callback() {
        $cur = isset($this->live_2d__options['aiProviderId']) ? (string) $this->live_2d__options['aiProviderId'] : '';
        $env = class_exists('live2d_AiChat') ? live2d_AiChat::detect_environment() : array('configured' => array());
        ?>
        <select name="live_2d_settings_option_name[aiProviderId]" id="aiProviderId">
            <option value=""><?php esc_html_e('自动(让 SDK 挑首个可用)', 'live-2d'); ?></option>
            <?php foreach ($env['configured'] as $pid): ?>
                <option value="<?php echo esc_attr($pid); ?>" <?php selected($cur, $pid); ?>><?php echo esc_html($pid); ?></option>
            <?php endforeach; ?>
            <?php if ($cur !== '' && !in_array($cur, $env['configured'], true)): ?>
                <option value="<?php echo esc_attr($cur); ?>" selected><?php echo esc_html($cur . ' (' . __('未配置', 'live-2d') . ')'); ?></option>
            <?php endif; ?>
        </select>
        <p class="description"><?php esc_html_e('仅「WordPress 内置」模式生效。留空 = 自动。', 'live-2d'); ?></p>
        <?php
    }

    public function aiSystemPrompt_callback() {
        $val = isset($this->live_2d__options['aiSystemPrompt'])
            ? $this->live_2d__options['aiSystemPrompt']
            : __('你是可爱的 Live2D 看板娘,回答简洁、温柔, 要有二次元的风格。','live-2d');
        ?>
        <textarea name="live_2d_settings_option_name[aiSystemPrompt]" id="aiSystemPrompt" rows="3" class="large-text"><?php echo esc_textarea($val); ?></textarea>
        <p class="description"><?php esc_html_e('仅「WordPress 内置」模式生效。','live-2d'); ?></p>
        <?php
    }

    public function aiModel_callback() {
        $val = isset($this->live_2d__options['aiModel']) ? $this->live_2d__options['aiModel'] : '';
        printf(
            '<input class="regular-text" type="text" name="live_2d_settings_option_name[aiModel]" id="aiModel" value="%s" placeholder="gpt-4o-mini">',
            esc_attr($val)
        );
        ?>
        <p class="description"><?php esc_html_e('留空让 SDK 自动挑选可用模型;填写如 gpt-4o-mini / gemini-2.5-flash / qwen-turbo 等具体模型 ID。仅「WordPress 内置」模式生效。','live-2d'); ?></p>
        <?php
    }

    public function canCloseLive2d_callback() {
        ?> <fieldset><?php $checked = ( isset( $this->live_2d__options['canCloseLive2d'] ) && $this->live_2d__options['canCloseLive2d'] === true ) ? 'checked' : '' ; ?>
        <label for="canCloseLive2d-0"><input type="radio" name="live_2d_settings_option_name[canCloseLive2d]" id="canCloseLive2d-0" value="1" <?php echo $checked; ?>> <?php esc_html_e('开启','live-2d') ?></label><br>
        <?php $checked = ( isset( $this->live_2d__options['canCloseLive2d'] ) && $this->live_2d__options['canCloseLive2d'] === false ) ? 'checked' : '' ; ?>
        <label for="canCloseLive2d-1"><input type="radio" name="live_2d_settings_option_name[canCloseLive2d]" id="canCloseLive2d-1" value="0" <?php echo $checked; ?>> <?php esc_html_e('关闭','live-2d') ?></label></fieldset> <?php
    }

    public function canSwitchModel_callback() {
        ?> <fieldset><?php $checked = ( isset( $this->live_2d__options['canSwitchModel'] ) && $this->live_2d__options['canSwitchModel'] === true ) ? 'checked' : '' ; ?>
        <label for="canSwitchModel-0"><input type="radio" name="live_2d_settings_option_name[canSwitchModel]" id="canSwitchModel-0" value="1" <?php echo $checked; ?>> <?php esc_html_e('显示','live-2d') ?></label><br>
        <?php $checked = ( isset( $this->live_2d__options['canSwitchModel'] ) && $this->live_2d__options['canSwitchModel'] === false ) ? 'checked' : '' ; ?>
        <label for="canSwitchModel-1"><input type="radio" name="live_2d_settings_option_name[canSwitchModel]" id="canSwitchModel-1" value="0" <?php echo $checked; ?>> <?php esc_html_e('隐藏','live-2d') ?></label></fieldset> <?php
    }

    public function canSwitchTextures_callback() {
        ?> <fieldset><?php $checked = ( isset( $this->live_2d__options['canSwitchTextures'] ) && $this->live_2d__options['canSwitchTextures'] === true ) ? 'checked' : '' ; ?>
        <label for="canSwitchTextures-0"><input type="radio" name="live_2d_settings_option_name[canSwitchTextures]" id="canSwitchTextures-0" value="1" <?php echo $checked; ?>> <?php esc_html_e('显示','live-2d') ?></label><br>
        <?php $checked = ( isset( $this->live_2d__options['canSwitchTextures'] ) && $this->live_2d__options['canSwitchTextures'] === false ) ? 'checked' : '' ; ?>
        <label for="canSwitchTextures-1"><input type="radio" name="live_2d_settings_option_name[canSwitchTextures]" id="canSwitchTextures-1" value="0" <?php echo $checked; ?>> <?php esc_html_e('隐藏','live-2d') ?></label></fieldset> <?php
    } 

    public function canSwitchHitokoto_callback() {
        ?> <fieldset><?php $checked = ( isset( $this->live_2d__options['canSwitchHitokoto'] ) && $this->live_2d__options['canSwitchHitokoto'] === true ) ? 'checked' : '' ; ?>
        <label for="canSwitchHitokoto-0"><input type="radio" name="live_2d_settings_option_name[canSwitchHitokoto]" id="canSwitchHitokoto-0" value="1" <?php echo $checked; ?>> <?php esc_html_e('显示','live-2d') ?></label><br>
        <?php $checked = ( isset( $this->live_2d__options['canSwitchHitokoto'] ) && $this->live_2d__options['canSwitchHitokoto'] === false ) ? 'checked' : '' ; ?>
        <label for="canSwitchHitokoto-1"><input type="radio" name="live_2d_settings_option_name[canSwitchHitokoto]" id="canSwitchHitokoto-1" value="0" <?php echo $checked; ?>> <?php esc_html_e('隐藏','live-2d') ?></label></fieldset> <?php
    }

    public function canTakeScreenshot_callback() {
        ?> <fieldset><?php $checked = ( isset( $this->live_2d__options['canTakeScreenshot'] ) && $this->live_2d__options['canTakeScreenshot'] === true ) ? 'checked' : '' ; ?>
        <label for="canTakeScreenshot-0"><input type="radio" name="live_2d_settings_option_name[canTakeScreenshot]" id="canTakeScreenshot-0" value="1" <?php echo $checked; ?>> <?php esc_html_e('显示','live-2d') ?></label><br>
        <?php $checked = ( isset( $this->live_2d__options['canTakeScreenshot'] ) && $this->live_2d__options['canTakeScreenshot'] === false ) ? 'checked' : '' ; ?>
        <label for="canTakeScreenshot-1"><input type="radio" name="live_2d_settings_option_name[canTakeScreenshot]" id="canTakeScreenshot-1" value="0" <?php echo $checked; ?>> <?php esc_html_e('隐藏','live-2d') ?></label></fieldset> <?php
    }

    public function canTurnToHomePage_callback() {
        ?> <fieldset><?php $checked = ( isset( $this->live_2d__options['canTurnToHomePage'] ) && $this->live_2d__options['canTurnToHomePage'] === true ) ? 'checked' : '' ; ?>
        <label for="canTurnToHomePage-0"><input type="radio" name="live_2d_settings_option_name[canTurnToHomePage]" id="canTurnToHomePage-0" value="1" <?php echo $checked; ?>> <?php esc_html_e('显示','live-2d') ?></label><br>
        <?php $checked = ( isset( $this->live_2d__options['canTurnToHomePage'] ) && $this->live_2d__options['canTurnToHomePage'] === false ) ? 'checked' : '' ; ?>
        <label for="canTurnToHomePage-1"><input type="radio" name="live_2d_settings_option_name[canTurnToHomePage]" id="canTurnToHomePage-1" value="0" <?php echo $checked; ?>> <?php esc_html_e('隐藏','live-2d') ?></label></fieldset> <?php
    }

    public function canTurnToAboutPage_callback() {
        ?> <fieldset><?php $checked = ( isset( $this->live_2d__options['canTurnToAboutPage'] ) && $this->live_2d__options['canTurnToAboutPage'] === true ) ? 'checked' : '' ; ?>
        <label for="canTurnToAboutPage-0"><input type="radio" name="live_2d_settings_option_name[canTurnToAboutPage]" id="canTurnToAboutPage-0" value="1" <?php echo $checked; ?>> <?php esc_html_e('显示','live-2d') ?></label><br>
        <?php $checked = ( isset( $this->live_2d__options['canTurnToAboutPage'] ) && $this->live_2d__options['canTurnToAboutPage'] === false ) ? 'checked' : '' ; ?>
        <label for="canTurnToAboutPage-1"><input type="radio" name="live_2d_settings_option_name[canTurnToAboutPage]" id="canTurnToAboutPage-1" value="0" <?php echo $checked; ?>> <?php esc_html_e('隐藏','live-2d') ?></label></fieldset> <?php
    }

    public function waifuToolFont_callback() {
        printf(
            '<input type="number" name="live_2d_settings_option_name[waifuToolFont]" id="waifuToolFont" value="%s" min = "0" max="50" >',
            isset( $this->live_2d__options['waifuToolFont'] ) ? esc_attr( $this->live_2d__options['waifuToolFont']) : 14
        );
    }

    public function waifuToolLine_callback() {
        printf(
            '<input type="number" name="live_2d_settings_option_name[waifuToolLine]" id="waifuToolLine" value="%s" min = "0" max="50" >',
            isset( $this->live_2d__options['waifuToolLine'] ) ? esc_attr( $this->live_2d__options['waifuToolLine']) : 20
        );
    }

    public function waifuToolTop_callback() {
        printf(
            '<input type="number" name="live_2d_settings_option_name[waifuToolTop]" id="waifuToolTop" value="%s" min = "-1000" max="1000" >
            <p>'.esc_html__('数字越大越靠下','live-2d').'</p>',
            isset( $this->live_2d__options['waifuToolTop'] ) ? esc_attr( $this->live_2d__options['waifuToolTop']) : 0
        );
    }

    public function waifuToolColor_callback(){
        printf(
            '<input type="text" class="color-picker" data-alpha-enabled="true" name="live_2d_settings_option_name[waifuToolColor]" id="waifuToolColor" value="%s"  />',
            isset( $this->live_2d__options['waifuToolColor'] ) ? esc_attr( $this->live_2d__options['waifuToolColor']) : '#5b6c7d'
        );
    }

    public function waifuToolHover_callback(){
        printf(
            '<input type="text" class="color-picker" data-alpha-enabled="true" name="live_2d_settings_option_name[waifuToolHover]" id="waifuToolHover" value="%s"  />',
            isset( $this->live_2d__options['waifuToolHover'] ) ? esc_attr( $this->live_2d__options['waifuToolHover']) : '#34495e'
        );
    }

    public function aboutPageUrl_callback() {
        printf(
            '<input class="regular-text" type="text" name="live_2d_settings_option_name[aboutPageUrl]" id="aboutPageUrl" value="%s">',
            isset( $this->live_2d__options['aboutPageUrl'] ) ? esc_attr( $this->live_2d__options['aboutPageUrl']) : ''
        );
    }

    public function screenshotCaptureName_callback() {
        printf(
            '<input class="regular-text" type="text" name="live_2d_settings_option_name[screenshotCaptureName]" id="screenshotCaptureName" value="%s">',
            isset( $this->live_2d__options['screenshotCaptureName'] ) ? esc_attr( $this->live_2d__options['screenshotCaptureName']) : ''
        );
    }
}
?>