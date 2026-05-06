<?php
class live2D_Settings_Base
{

    private $live_2d__options;
    private $userInfo;

    public function live_2d_settings_base_init()
    {
        $this->live_2d__options = get_option('live_2d_settings_option_name');
        $this->userInfo = get_option('live_2d_settings_user_token');

        add_settings_section(
            'live_2d_setting_base_section', // id
            __('基础设置', 'live-2d'), // title
            array($this, 'live_2d__section_info'), // callback
            'live-2d-settings-base' // page
        );

        if (!empty($this->userInfo["userLevel"]) && intval($this->userInfo["userLevel"]) > 0) {
            add_settings_field(
                'live2dLayoutType', // id
                __('看板娘模式', 'live-2d'), // title
                array($this, 'live2dLayoutType_callback'), // callback
                'live-2d-settings-base', // page
                'live_2d_setting_base_section' // section
            );
        }

        // apiType 三选项中 'local' 不需要登录/付费即可使用,因此把整个 radio 字段
        // 移出 userLevel>0 的付费门槛,让未登录用户也能切到 'local' 直接用本地模型。
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

        if (!empty($this->userInfo["userLevel"]) && intval($this->userInfo["userLevel"]) > 0) {

            // modelDir 始终 register;实际可见性交给 live2d-admin.ts
            // 根据 apiType 三态(local/remote/custom + .json 结尾判断)动态切换。
            add_settings_field(
                'modelDir', // id
                __('模型目录', 'live-2d'), // title
                array($this, 'modelDir_callback'), // callback
                'live-2d-settings-base', // page
                'live_2d_setting_base_section' // section
            );

            // 待机动画文件名 (idle_motion) 原本在 "提示消息选项" 页. 与 modelDir
            // 共用 "Custom new-format model path" 的联动: 仅 apiType=='custom'
            // 且 modelAPI 不以 .json 结尾时显示 (live2d-admin.ts refreshModelDirRow
            // 已扩展为同时 toggle .modelDir / .idle_motion 两行). 付费门槛与
            // modelDir 同一 block, userLevel<1 不 register, sanitize 兜底见
            // src/waifu-Settings.php (空串过滤 + 数组校验, 防止 404).
            add_settings_field(
                'idle_motion', // id
                __('待机动画文件名', 'live-2d'), // title
                array($this, 'idle_motion_callback'), // callback
                'live-2d-settings-base', // page
                'live_2d_setting_base_section' // section
            );

            // V2(Cubism 4/5)模型防盗链开关 — 仅对 apiType='custom' (.model3.json) 链路生效.
            // 关闭(默认): wordpress-live2d.php 注入 v2SessionUrl='', 前端 signOneModel 早退到裸链,
            //            行为与未引入开关前完全一致 (老站升级零感知).
            // 开启:      注入完整 rest_url, 前端拉 alias 化 manifest, 子资源全部走
            //            …/v2/m/{token}/{alias}?e=&s=, 访客 F12 看不到真实 modelAPI / *.moc3 / *.png.
            // 'local'/'remote' (旧 V1 PHP) 不受影响, 模型本来就托管在站内不需要 alias.
            add_settings_field(
                'protectV2',
                __('防盗链(Cubism 4+ 模型)', 'live-2d'),
                array($this, 'protectV2_callback'),
                'live-2d-settings-base',
                'live_2d_setting_base_section'
            );

            // 模型缩放倍数 / 看板娘位置 已迁移到 "样式" 设置页 (waifu-Settings-Style.php),
            // 仍读写同一个 modelPoint 字段, JS 端 (lappdelegate.ts initialize) 无须改动.

            add_settings_field(
                'sdkUrl', // id
                __('Cubism Core for Web <br/> 引用地址', 'live-2d'), // title
                array($this, 'sdkUrl_callback'), // callback
                'live-2d-settings-base', // page
                'live_2d_setting_base_section' // section
            );

            // shaderDir 不再暴露给用户配置: 路径完全由 wordpress-live2d.php 的
            // live2D_style() 在运行时按 plugin_dir_url(__FILE__) 自动拼接,
            // 确保跟随插件实际安装位置 / 站点 URL,免受 DB 旧值污染。
        }
    }

    public function live_2d__section_info()
    {
        printf(
            '<input type="hidden" name="live_2d_settings_option_name[homePageUrl]" id="homePageUrl" value="%s">',
            get_home_url()
        );
    }

    public function apiType_callback()
    {
        // apiType 三态字符串,详见 src/live2d-V1Api.php live2d_normalize_api_type()。
        // 旧 DB 里 bool true→'local'、false→'remote';新 'custom' 用于 .model3.json / Cubism 4+ 目录。
        $current = function_exists('live2d_normalize_api_type')
            ? live2d_normalize_api_type(isset($this->live_2d__options['apiType']) ? $this->live_2d__options['apiType'] : null)
            : 'remote';
        // 'local' 走本地 model/ 目录,无需付费/登录;'custom' (Cubism 4+) 走自定义 URL,
        // 与原 V2 自定 API 行为一致,沿用付费门槛。
        $hasPaid = !empty($this->userInfo["userLevel"]) && intval($this->userInfo["userLevel"]) > 0;
        $shopUrl = esc_url(admin_url('admin.php?page=live-2d-shop'));
?>
        <fieldset>
            <label for="apiType-local">
                <input type="radio" name="live_2d_settings_option_name[apiType]" id="apiType-local" class="apiType" value="local" <?php echo $current === 'local' ? 'checked' : ''; ?>>
                <?php esc_html_e('本地部署旧版模型', 'live-2d'); ?>
            </label>
            <span style="margin-left: 8px;">
                <a href="<?php echo $shopUrl; ?>"><?php esc_html_e('点击进入下载页面', 'live-2d'); ?></a>
            </span><br>
            <span class="description"><?php esc_html_e('模型文件由本插件托管,模型 API 自动指向本站,不会向外网发起请求。', 'live-2d'); ?></span><br>

            <label for="apiType-remote">
                <input type="radio" name="live_2d_settings_option_name[apiType]" id="apiType-remote" class="apiType" value="remote" <?php echo $current === 'remote' ? 'checked' : ''; ?>>
                <?php esc_html_e('自行部署旧版模型', 'live-2d'); ?>
            </label><br>
            <span class="description"><?php esc_html_e('对接你自己部署的旧版 V1/V2 模型 API(例如 fghrsh-style 的 /get/?id= 路由)。', 'live-2d'); ?></span><br>

            <?php if ($hasPaid): ?>
                <label for="apiType-custom">
                    <input type="radio" name="live_2d_settings_option_name[apiType]" id="apiType-custom" class="apiType" value="custom" <?php echo $current === 'custom' ? 'checked' : ''; ?>>
                    <?php esc_html_e('自定义新版模型路径', 'live-2d'); ?>
                </label><br>
                <span class="description"><?php esc_html_e('Cubism 4+ 模型(*.model3.json),可填模型直链或目录根并在下方"模型目录"中列出多个模型。', 'live-2d'); ?></span>
            <?php else: ?>
                <?php // 未登录 / 未付费: radio 与 label 都置灰, 并配合 cursor:not-allowed 提示用户不可点击.
                      // disabled 属性已经阻止勾选, 这里加视觉反馈; 同时把 title 提示加到 label 上,
                      // hover 时会显示完整原因, 避免用户以为是 bug. ?>
                <label for="apiType-custom" style="opacity: 0.5; cursor: not-allowed;" title="<?php esc_attr_e('完成登录并付费后可用', 'live-2d'); ?>">
                    <input type="radio" disabled style="cursor: not-allowed;"> <?php esc_html_e('自定义新版模型路径', 'live-2d'); ?>
                </label><br>
                <span class="description" style="opacity: 0.7;"><?php esc_html_e('Cubism 4+ 模型: 完成登录并付费后可用。', 'live-2d'); ?></span>
            <?php endif; ?>
        </fieldset>
    <?php
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
            '<input class="regular-text" type="url" name="live_2d_settings_option_name[modelAPI]" id="modelAPI" value="%s">',
            isset($this->live_2d__options['modelAPI']) ? esc_attr($this->live_2d__options['modelAPI']) : ''
        );
        // 静态帮助文案仅作底层说明;与 apiType 联动的选择性提示由 JS 插入 / 隐藏。
        echo '<p class="description live2d-modelAPI-hint live2d-modelAPI-hint-default">'
            . esc_html__('上面「API 方式」选不同选项时, 此处填写要求不同。', 'live-2d')
            . '</p>';
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
        echo '<p class="description">' . esc_html__('选择或填写默认皮肤 ID;新版模型不使用此项, 可留空。', 'live-2d') . '</p>';
    }

    public function modelDir_callback()
    {
        live2D_Utils::loopMsg('modelDir','List',true,'live_2d_settings_option_name');
        echo '<p>' . esc_html__('可切换的模型名称，程序会通过Model API来按顺序获取模型的信息，请保证模型目录的名称和 model3.json 一致','live-2d').'</p>';
    }

    // 待机动画文件名 — loopMsg 渲染的 <p class="idle_motion"> 会被
    // live2d-admin.ts 的 refreshModelDirRow 一并 toggle (与 modelDir 同步).
    public function idle_motion_callback()
    {
        live2D_Utils::loopMsg('idle_motion','List',true,'live_2d_settings_option_name');
        echo '<p>' . esc_html__('待机动画文件名, 文件是*.motion3.json','live-2d') . '</p>';
    }

    /**
     * Cubism 4+ 模型保护策略 — 二态 radio,默认 'direct'。
     *   - 'direct' 不缓存(默认):不启用防盗链;如果你使用了第三方对象存储工具或者不需要防盗链即可选这个。
     *   - 'local'  缓存到本地:插件会 alias 化所有模型资源,访客 F12 只看到临时签名 URL。
     *              需要在下面点「下载到本地」把模型文件拉进插件 model/ 目录。
     *
     * 运行时由 wordpress-live2d.php live2D_style() 决定是否注入 v2SessionUrl(仅 'local' 注入)。
     * 下载区域 (#protectV2-models-section) 的可见性由 live2d-admin.ts 根据 radio 状态 toggle。
     */
    public function protectV2_callback()
    {
        $current = isset($this->live_2d__options['protectV2']) ? $this->live_2d__options['protectV2'] : 'direct';
        // 老版本存的是 bool 或 'oss':bool true → 'local',其余非 'local' 一律归为 'direct'
        if (is_bool($current)) {
            $current = $current ? 'local' : 'direct';
        } elseif (!in_array($current, array('local', 'direct'), true)) {
            $current = 'direct';
        }
        ?>
        <fieldset class="protectV2-fieldset">
            <label for="protectV2-direct">
                <input type="radio" name="live_2d_settings_option_name[protectV2]" id="protectV2-direct" class="protectV2" value="direct" <?php checked($current, 'direct'); ?>>
                <?php esc_html_e('不缓存 (默认)', 'live-2d'); ?>
            </label>
            <span class="description" style="margin-left:8px;"><?php esc_html_e('不启用防盗链;如果你使用了第三方对象存储工具或者不需要防盗链即可选择这个。', 'live-2d'); ?></span><br>

            <label for="protectV2-local">
                <input type="radio" name="live_2d_settings_option_name[protectV2]" id="protectV2-local" class="protectV2" value="local" <?php checked($current, 'local'); ?>>
                <?php esc_html_e('缓存到本地 (推荐)', 'live-2d'); ?>
            </label>
            <span class="description" style="margin-left:8px;"><?php esc_html_e('访客只看到临时签名 URL,真实 *.model3.json / *.moc3 / *.png 不会暴露;需要下载模型到本地 model/ 目录。', 'live-2d'); ?></span>
        </fieldset>

        <?php // 下载区域 —— 默认隐藏, live2d-admin.ts 会在 protectV2='local' 时显示。
              // 后端 AJAX 在 src/live2d-V2Api.php 里实现,接入点在 src/live2d-Shop.php。 ?>
        <div id="protectV2-models-section" style="display:none;margin-top:12px;padding:12px;border:1px solid #ddd;background:#fafafa;border-radius:4px;">
            <strong><?php esc_html_e('模型本地缓存', 'live-2d'); ?></strong>
            <p class="description" style="margin:6px 0;">
                <?php esc_html_e('点击下面按钮,插件会把当前「模型 API」指向的 model3.json 及其全部子资源下载到本插件的 model/ 目录(与 V1 模型同位置)。之后访客加载该模型不再依赖源站。', 'live-2d'); ?>
            </p>
            <div id="protectV2-models-list">
                <?php // 由 admin TS 动态填充列表条目:当前 slug + 状态(未下载 / 已下载 X 个文件 N KB) + [下载/删除] 按钮 ?>
                <span class="description"><?php esc_html_e('正在读取状态…', 'live-2d'); ?></span>
            </div>
            <p id="protectV2-models-msg" style="margin:6px 0;color:#1d2327;"></p>
        </div>
        <?php
    }

    // modelZoomNumberV2_callback / modelXYaxis_callback 已迁移到
    // src/waifu-Settings-Style.php, 仍写入 live_2d_settings_option_name[modelPoint] 同一字段.

    public function sdkUrl_callback()
    {
        // 默认 placeholder 改用插件本地 Cubism Core 6.x(由 wordpress-live2d.php 中
        // 的 LIVE2D_ASSETS . 'cubism-core/live2dcubismcore.min.js' 提供),原因见该文件
        // live2D_style() 注释。仍允许用户改回官方 CDN 或镜像地址。
        $defaultSdkUrl = plugin_dir_url(dirname(__FILE__)) . 'assets/cubism-core/live2dcubismcore.min.js';
        printf(
            '<input class="regular-text" type="text" name="live_2d_settings_option_name[sdkUrl]" id="sdkUrl" value="%s">',
            isset($this->live_2d__options['sdkUrl']) ? esc_attr($this->live_2d__options['sdkUrl']) : esc_attr($defaultSdkUrl)
        );
        echo '<p>' . esc_html__('如未授权请勿修改此地址，擅自修改此地址引发的法律问题与插件作者无关。', 'live-2d') . '</p>
        <p>' . esc_html__('软件许可协议：', 'live-2d')
            . '<a href = "https://www.live2d.com/eula/live2d-proprietary-software-license-agreement_en.html" target="_blank">Live2D Proprietary Software License Agreement</a> 
        | <a href = "https://www.live2d.com/eula/live2d-open-software-license-agreement_en.html" target="_blank">Live2D Open Software License Agreement</a> </p>';
    }

    // shaderDir_callback 已移除: shaderDir 不再作为可配置项, 详见 live_2d_settings_base_init
    // 顶部说明; 实际值在 wordpress-live2d.php live2D_style() 运行时统一注入。
}
?>