<?php

class live2D_Utils{

	/**
	 * 
	 * $keyName options中的key值
	 * $type 有3个选择：Selector（用于带有选择器的数组），Array（纯数组），List（只有文本的数组列表）
	 * $readonly 让第一组input 只读
	 */
	public static function loopMsg($keyName,$type = 'List' ,$readonly = true ,$optName = 'live_2d_advanced_option_name'){
		$optionsArray = get_option($optName);
		$txtCount = 1;// 以下判断不为true时 $txtCount =1
		// 如果options中存在则先获取options的长度
		if( isset($optionsArray[$keyName])){
			$txtCount = count($optionsArray[$keyName]);
		}
		// 为了防止$txtCount小于1做的强制判定
		$txtCount = $txtCount < 1 ? $txtCount = 1 : $txtCount;
		switch ($type){
			case 'Selector':
				for($x = 0;$x<$txtCount;$x++){
					printf(
						'<p class = "'.$keyName.'">
						<input class="regular-text selector" type="text" name="'.$optName.'['.$keyName.']['.$x.'][selector]" id="'.$keyName.'_'.$x.'_selector" value="%s" style="width: 200px">：
						<input class="regular-text text" type="text" name="'.$optName.'['.$keyName.']['.$x.'][text]" id="'.$keyName.'_'.$x.'_text" value="%s">
						<input class="button delbtn" optname = "'.$optName.'" keyname="'.$keyName.'" arrtype="'.$type.'" type="button" name="'.$keyName.'_delbtn'.$x.'" id="'.$keyName.'_delbtn'.$x.'" value="-"></p>',
						isset( $optionsArray[$keyName][$x]['selector'] ) ? esc_attr( $optionsArray[$keyName][$x]['selector']) : '',
						isset( $optionsArray[$keyName][$x]['text'] ) ? esc_attr( $optionsArray[$keyName][$x]['text']) : ''
					);
				}
				echo '<p class="addBtn"><input class="button addbtn" optname = "'.$optName.'" keyname="'.$keyName.'" arrtype="'.$type.'" type="button" value="+ ' . __('点击此处增加一条','live-2d') . '" id="'.$keyName.'_addbtn" /></p>';
			break;
			case 'Array':
				if($readonly){
					//$txtCount = $roCount; 
					for($x = 0;$x<$txtCount;$x++){
						printf(
							'<p class = "'.$keyName.'">
							<input class="regular-text" type="text" name="'.$optName.'['.$keyName.']['.$x.'][0]" id="'.$keyName.'_'.$x.'_0" value="%s" style="width: 100px" readonly="readonly">：
							<input class="regular-text" type="text" name="'.$optName.'['.$keyName.']['.$x.'][1]" id="'.$keyName.'_'.$x.'_1" value="%s">',
							isset( $optionsArray[$keyName][$x][0] ) ? esc_attr( $optionsArray[$keyName][$x][0]) : '',
							isset( $optionsArray[$keyName][$x][1] ) ? esc_attr( $optionsArray[$keyName][$x][1]) : ''
						);
					}
				} else{ //这个可能性应该是没有
					for($x = 0;$x<$txtCount;$x++){
						printf(
							'<p class = "'.$keyName.'">
							<input class="regular-text" type="text" name="'.$optName.'['.$keyName.']['.$x.'][0]" id="'.$keyName.'_'.$x.'_0" value="%s" style="width: 200px">：
							<input class="regular-text" type="text" name="'.$optName.'['.$keyName.']['.$x.'][1]" id="'.$keyName.'_'.$x.'_1" value="%s">
							<input class="button delbtn" optname = "'.$optName.'" keyname="'.$keyName.'" arrtype="'.$type.'" type="button" name="'.$keyName.'_delbtn'.$x.'" id="'.$keyName.'_delbtn'.$x.'" value="-"></p>',
							isset( $optionsArray[$keyName][$x][0] ) ? esc_attr( $optionsArray[$keyName][$x][0]) : '',
							isset( $optionsArray[$keyName][$x][1] ) ? esc_attr( $optionsArray[$keyName][$x][1]) : ''
						);
					}
					echo '<p class="addBtn"><input class="button addbtn" optname = "'.$optName.'" keyname="'.$keyName.'" arrtype="'.$type.'" type="button" value="+ ' . __('点击此处增加一条','live-2d') . '" id="'.$keyName.'_addbtn" /></p>';
				}
			break;
			case 'List':
				for($x = 0;$x<$txtCount;$x++){
					printf(
						'<p class = "'.$keyName.'">
						<input class="regular-text textArray" type="text" name="'.$optName.'['.$keyName.']['.$x.']" id="'.$keyName.'_'.$x.'" value="%s">
						<input class="button delbtn" optname = "'.$optName.'" keyname="'.$keyName.'" arrtype="'.$type.'" type="button" name="'.$keyName.'_delbtn'.$x.'" id="'.$keyName.'_delbtn'.$x.'" value="-"></p>',
						isset( $optionsArray[$keyName][$x] ) ? esc_attr( $optionsArray[$keyName][$x]) : ''
					);
				}
				echo '<p class="addBtn"><input class="button addbtn" optname = "'.$optName.'" keyname="'.$keyName.'" arrtype="'.$type.'" type="button" value="+ ' . __('点击此处增加一条','live-2d') . '" id="'.$keyName.'_addbtn" /></p>';
			break;
		}
    }


	/**
	 * 设置页 (live-2d-options) 的帮助文档。多 Tab 结构与「设置」页的导航 Tab 大致对齐：
	 *   1. V1 / V2 入门          —— 让用户先弄清自己手里的模型走哪条加载链路
	 *   2. API 方式 / 模型路径    —— modelAPI 何时以 .json 结尾、何时以 / 结尾配合 modelDir
	 *   3. 模型防盗保护 (V2)      —— protectV2 = direct / local 的取舍
	 *   4. 提示消息与特殊标记     —— 兼容旧版「高级设置帮助」中的 placeholder 表
	 *   5. 进阶文档与外部链接     —— 指向仓库内 docs/wiki 与官网
	 *
	 * 每个 Tab 内容都是 HTML，文案统一走 __()/sprintf 以接入 i18n（详见
	 * languages/translations.json，新增字符串需要重新跑 build-i18n.py）。
	 */
	public static function live_2D_help_tab(){
		$screen = get_current_screen();

		// ---- Tab 1: V1 / V2 入门 -------------------------------------------------
		$screen->add_help_tab( array(
			'id'    => 'live_2d_v1v2_help_tab',
			'title' => __('V1 / V2 模型', 'live-2d'),
			'content' => self::wrapHelpContent(
				'<p>' . esc_html__('本插件同时支持两套独立的 Live2D 加载链路。第一次配置前请先确认手里的模型属于哪一类：', 'live-2d') . '</p>' .
				'<table class="widefat striped"><thead><tr>' .
				'<th>' . esc_html__('模型类别', 'live-2d') . '</th>' .
				'<th>' . esc_html__('清单文件', 'live-2d') . '</th>' .
				'<th>' . esc_html__('主体文件', 'live-2d') . '</th>' .
				'<th>' . esc_html__('对应 API 方式', 'live-2d') . '</th>' .
				'</tr></thead><tbody>' .
				'<tr><td>' . esc_html__('V1（Cubism 2 / 3 系，如 22 娘、Pio、Tia、Shizuku 等）', 'live-2d') . '</td>' .
				'<td><code>model.json</code></td><td><code>*.moc</code></td>' .
				'<td>' . esc_html__('「本地部署旧版模型」或「自行部署旧版模型」', 'live-2d') . '</td></tr>' .
				'<tr><td>' . esc_html__('Cubism 4+（V2，官方仍在维护的最新格式）', 'live-2d') . '</td>' .
				'<td><code>*.model3.json</code></td><td><code>*.moc3</code></td>' .
				'<td>' . esc_html__('「自定义新版模型 · 直链加载」或「自定义新版模型 · 托管到本站」(均需登录并购买授权)', 'live-2d') . '</td></tr>' .
				'</tbody></table>' .
				'<p>' . esc_html__('简单判断：清单文件名以 .model3.json 结尾就是 V2，否则是 V1。', 'live-2d') . '</p>'
			),
		) );

		// ---- Tab 2: API 方式 / 模型路径 ------------------------------------------
		$screen->add_help_tab( array(
			'id'    => 'live_2d_api_help_tab',
			'title' => __('API 方式与模型路径', 'live-2d'),
			'content' => self::wrapHelpContent(
				'<p>' . esc_html__('「API 方式」是 V1 / V2 链路的总开关, 2026-05 重构后由原三选项 + protectV2 二态合并为「四选项 radio」, 每个选项作用不同:', 'live-2d') . '</p>' .
				'<ul>' .
				'<li><strong>' . esc_html__('本地部署旧版模型', 'live-2d') . '</strong>:' . esc_html__('使用插件随包内置的 V1 模型, 由本站 REST 接口 (/wp-json/live2d/v1/model/*) 提供, 不向外网发起请求。「模型 ID」「材质 ID」会自动变成下拉框。需要在「Live 2D 创意工坊」页面先把模型下载到本地。', 'live-2d') . '</li>' .
				'<li><strong>' . esc_html__('自行部署旧版模型', 'live-2d') . '</strong>:' . esc_html__('对接您自己服务器或第三方提供的 V1 模型 API (即 fghrsh 风格接口), 「模型 API」需要填写返回 model_list.json 的接口根地址。', 'live-2d') . '</li>' .
				'<li><strong>' . esc_html__('自定义新版模型 · 直链加载 (Cubism 4+)', 'live-2d') . '</strong>:' . esc_html__('用于 Cubism 4+ 模型 (V2, *.model3.json), 「模型 API」填跨域 URL, 浏览器直接拉源站。需登录账号并完成付费后才能选择, 防盗链由对象存储 / CDN 站长自己处理。', 'live-2d') . '</li>' .
				'<li><strong>' . esc_html__('自定义新版模型 · 托管到本站 (Cubism 4+, 推荐)', 'live-2d') . '</strong>:' . esc_html__('同上, 但插件会把模型下载 / 托管到本站 wp-content/plugins/live-2d/model/{slug}/, 并代为防盗链 (临时签名 URL)。需登录账号并完成付费后才能选择。', 'live-2d') . '</li>' .
				'</ul>' .
				'<h4>' . esc_html__('选了「托管到本站」后的表单变化', 'live-2d') . '</h4>' .
				'<p>' . esc_html__('选「自定义新版模型 · 托管到本站」后, 「模型 API」输入框会被隐藏, 系统自动把它写成本站 wp-content/plugins/live-2d/model/ 根地址。站长唯一需要填的是「模型目录」列表, 每行一个 slug (= 子目录名), 插件会按 {root}{slug}/{slug}.model3.json 自动展开为完整 manifest URL。', 'live-2d') . '</p>' .
				'<h4>' . esc_html__('「模型 API」何时以 .json 结尾、何时以斜杠结尾', 'live-2d') . '</h4>' .
				'<p>' . esc_html__('仅当「API 方式 = 自定义新版模型 · 直链加载」时, 「模型 API」支持以下两种写法, 由结尾字符决定走哪种:', 'live-2d') . '</p>' .
				'<table class="widefat striped"><thead><tr>' .
				'<th>' . esc_html__('写法', 'live-2d') . '</th>' .
				'<th>' . esc_html__('行为', 'live-2d') . '</th>' .
				'<th>' . esc_html__('「模型目录」是否使用', 'live-2d') . '</th>' .
				'</tr></thead><tbody>' .
				'<tr><td>' . esc_html__('以 .json 结尾 (直链单模型)', 'live-2d') . '</td>' .
				'<td>' . esc_html__('直接当作 *.model3.json 加载, 不再做任何路径拼接。例: https://cdn.example.com/live2d/haru/haru.model3.json', 'live-2d') . '</td>' .
				'<td>' . esc_html__('忽略, 下方「模型目录」「待机动画文件名」两行会自动隐藏。', 'live-2d') . '</td></tr>' .
				'<tr><td>' . esc_html__('以 / 结尾 (目录根 + 多模型)', 'live-2d') . '</td>' .
				'<td>' . esc_html__('当作根目录使用, 配合下方「模型目录」每行一个 slug, 自动按 {模型 API}{slug}/{slug}.model3.json 拼成完整 URL。', 'live-2d') . '</td>' .
				'<td>' . esc_html__('必填。每行写一个子目录名称, 名称必须与 {slug}.model3.json 的前缀完全一致。', 'live-2d') . '</td></tr>' .
				'</tbody></table>' .
				'<p>' . esc_html__('补充约束: 路径不能包含中文; HTTPS 站点的模型清单及其引用到的所有资源 (.moc3、贴图、motion3.json、physics3.json 等) 也必须是 HTTPS; 放在第三方对象存储 / CDN 时请正确开启 CORS。', 'live-2d') . '</p>' .
				'<p>' . esc_html__('「默认材质 ID」仅 V1 模型使用, V2 模型留空即可。', 'live-2d') . '</p>'
			),
		) );

		// ---- Tab 3: 模型防盗保护 -------------------------------------------------
		$screen->add_help_tab( array(
			'id'    => 'live_2d_protect_help_tab',
			'title' => __('模型防盗保护 (Cubism 4+)', 'live-2d'),
			'content' => self::wrapHelpContent(
				'<p>' . esc_html__('防盗链只对 Cubism 4+ 模型的两个「自定义新版模型」选项生效; 旧版 V1 走本站 REST 接口, 不需要这层保护。', 'live-2d') . '</p>' .
				'<ul>' .
				'<li><strong>' . esc_html__('自定义新版模型 · 直链加载', 'live-2d') . '</strong>:' . esc_html__('插件不动您填的「模型 API」, 浏览器直接拉源站。适用于您已经把模型放在阿里云 OSS、腾讯云 COS、又拍、七牛、自家 CDN 等已经具备 Referer 防盗 / URL 鉴权能力的存储上 (原 protectV2 = direct)。', 'live-2d') . '</li>' .
				'<li><strong>' . esc_html__('自定义新版模型 · 托管到本站 (推荐)', 'live-2d') . '</strong>:' . esc_html__('插件会把模型文件保存到本站 wp-content/plugins/live-2d/model/{slug}/, 并对前端只露出经过签名的临时 URL, 访客 F12 也看不到源站地址。适用于源站没有防盗能力 (GitHub Pages、jsDelivr、自建 nginx 等) (原 protectV2 = local)。', 'live-2d') . '</li>' .
				'</ul>' .
				'<p>' . esc_html__('选「托管到本站」后, 「模型 API」输入框会被隐藏, 系统自动写成本站 model/ 根地址; 站长唯一需要填的是「模型目录」列表 (每行一个 slug)。页面下方会出现「模型托管」面板, 含双标签页: 从 URL 下载 / 上传文件夹。两者完成后都会自动把 slug 追加到「模型目录」列表, 点「保存设置」后生效。', 'live-2d') . '</p>' .
				'<p>' . esc_html__('2026-05 后取消了访客首次访问时的「自动懒下载」 (原为默认行为但不可见, 需要站长主动点「下载到本站」或拖拽上传。「清理孤儿」用于删除已经从设置里移除、但还残留在本地缓存里的旧模型文件。', 'live-2d') . '</p>'
			),
		) );

		// ---- Tab 4: 提示消息与特殊标记 -------------------------------------------
		$screen->add_help_tab( array(
			'id'    => 'live_2d_tips_help_tab',
			'title' => __('提示消息与特殊标记', 'live-2d'),
			'content' => self::wrapHelpContent(
				/* translators: %s 是渲染后的「+ 点击此处增加一条」按钮 */
				'<p>' . sprintf(
					esc_html__('在「高级设置」「提示消息选项」页面点击 %s 可以在同一个事件下追加随机语句，运行时随机选一条显示。', 'live-2d'),
					'&nbsp;<input class="button" type="button" disabled value="+ ' . esc_attr__('点击此处增加一条', 'live-2d') . '">&nbsp;'
				) . '</p>' .
				'<p>' . esc_html__('以下事件支持在文本中插入特殊标记，运行时会被替换为对应内容：', 'live-2d') . '</p>' .
				'<ul>' .
				'<li>' . esc_html__('鼠标悬停时的消息提示：{text} 触发事件的内容、{highlight} 高亮样式占位符', 'live-2d') . '</li>' .
				'<li>' . esc_html__('鼠标点击时的消息提示：{text} 触发事件的内容、{highlight} 高亮样式占位符', 'live-2d') . '</li>' .
				'<li>' . esc_html__('节日事件：{year} 年份、{highlight} 高亮样式占位符', 'live-2d') . '</li>' .
				'<li>' . esc_html__('搜索引擎入站提示：{title} 网站标题、{keyword} 关键词、{website} 站点名称、{highlight} 高亮样式占位符（请以预设值为准，并非所有搜索引擎都会带齐这些信息）', 'live-2d') . '</li>' .
				'<li>' . esc_html__('访问本站点的提示：{website} 站点名称、{highlight} 高亮样式占位符', 'live-2d') . '</li>' .
				'<li>' . esc_html__('一言 API 的消息：{title}、{source}、{creator}、{date}、{dynasty}、{author}、{highlight}（请以预设值为准，并非所有句子都会带齐这些字段）', 'live-2d') . '</li>' .
				'</ul>' .
				'<p>' . sprintf(
					/* translators: %s 是高亮样式占位符的代码片段 */
					esc_html__('高亮用法：在任意 style 属性中插入 %s 即可，高亮颜色请到「提示消息选项」中设置。', 'live-2d'),
					'<code>&lt;span style="{highlight}"&gt;...&lt;/span&gt;</code>'
				) . '</p>' .
				'<p>' . esc_html__('如需恢复全部默认值：删除插件后重新安装即可（注意会同时清空已保存的设置）。', 'live-2d') . '</p>'
			),
		) );

		// ---- Tab 5: 样式 / 工具栏 / 性能 -----------------------------------------
		$screen->add_help_tab( array(
			'id'    => 'live_2d_style_help_tab',
			'title' => __('样式与工具栏', 'live-2d'),
			'content' => self::wrapHelpContent(
				'<ul>' .
				'<li>' . esc_html__('「看板娘大小」「页面边距」「拖拽样式」位于「样式设置」页，调整后立即对前端生效。', 'live-2d') . '</li>' .
				'<li>' . esc_html__('「面页小于指定宽度禁用看板娘」用于在小屏 / 移动端隐藏，留空或填 0 表示始终显示。', 'live-2d') . '</li>' .
				'<li>' . esc_html__('「模型切换方式」「材质切换方式」分别决定按下工具栏切换按钮时是顺序切换还是随机切换。', 'live-2d') . '</li>' .
				'<li>' . esc_html__('「超采样」与「抗锯齿」属于付费功能，仅对 V2 模型有效，开启后可显著提升贴图清晰度，但会增加 GPU 占用。', 'live-2d') . '</li>' .
				'<li>' . esc_html__('「Cubism Core for Web 引用地址」一般保留默认（指向插件随包分发的 6.x 本地副本即可）。除非您明确知道在做什么，不建议改成 CDN 地址，官方 CDN 目前仍停留在 5.1.0，会与插件内置 Framework 不兼容。', 'live-2d') . '</li>' .
				'</ul>'
			),
		) );

		// ---- Tab 6: 文档与外链 ---------------------------------------------------
		$screen->add_help_tab( array(
			'id'    => 'live_2d_links_help_tab',
			'title' => __('进阶文档与链接', 'live-2d'),
			'content' => self::wrapHelpContent(
				'<ul>' .
				'<li><a target="_blank" rel="noopener" href="https://jiangweifang.github.io/wp-live2d/wiki/v1-vs-v2.html">' . esc_html__('V1 / V2 模型详细对比', 'live-2d') . '</a></li>' .
				'<li><a target="_blank" rel="noopener" href="https://jiangweifang.github.io/wp-live2d/wiki/custom-model.html">' . esc_html__('使用 V2 自定义模型（Cubism 4+）', 'live-2d') . '</a></li>' .
				'<li><a target="_blank" rel="noopener" href="https://jiangweifang.github.io/wp-live2d/wiki/anti-hotlink.html">' . esc_html__('V2 模型防盗链详解', 'live-2d') . '</a></li>' .
				'<li><a target="_blank" rel="noopener" href="https://jiangweifang.github.io/wp-live2d/wiki/touch-area.html">' . esc_html__('V2 模型的触摸区域 (HitArea)', 'live-2d') . '</a></li>' .
				'<li><a target="_blank" rel="noopener" href="https://jiangweifang.github.io/wp-live2d/wiki/chatgpt.html">' . esc_html__('ChatGPT 接入与自定义 API', 'live-2d') . '</a></li>' .
				'<li><a target="_blank" rel="noopener" href="https://jiangweifang.github.io/wp-live2d/wiki/chrome-extension.html">' . esc_html__('Chrome / Edge 浏览器扩展（个人用户）', 'live-2d') . '</a></li>' .
				'</ul>' .
				'<p>' . esc_html__('源码与 issue 反馈：', 'live-2d') . ' <a target="_blank" rel="noopener" href="https://github.com/jiangweifang/wp-live2d">github.com/jiangweifang/wp-live2d</a></p>'
			),
		) );

		$screen->set_help_sidebar( self::helpSidebar() );
	}

	/**
	 * 创意工坊页 (live-2d-shop) 的帮助文档。
	 * 创意工坊只列出 V1 模型，下载入口属于免费功能（仅需登录 + 邮箱验证）。
	 */
	public static function workshop_help_tab(){
		$screen = get_current_screen();

		$screen->add_help_tab( array(
			'id'    => 'workshop_intro_help_tab',
			'title' => __('创意工坊使用说明', 'live-2d'),
			'content' => self::wrapHelpContent(
				'<p>' . esc_html__('本页面列出插件内置的 V1（Cubism 2 / 3 系）模型，例如 bilibili 22 娘 / 33 娘、Potion Maker Pio / Tia、Shizuku 等。点击「下载」后，插件会从作者维护的源站把模型 ZIP 拉到本站、解压到 wp-content/plugins/live-2d/model/ 目录中。', 'live-2d') . '</p>' .
				'<p>' . esc_html__('使用要求：', 'live-2d') . '</p>' .
				'<ul>' .
				'<li>' . esc_html__('已经在 live2dweb.com 完成账号登录，并把账号 Key 保存到「Live 2D 设置 → 登录」选项卡。', 'live-2d') . '</li>' .
				'<li>' . esc_html__('已经完成邮箱验证（V1 模型下载本身不收费，但需要可联系到的邮箱以便接收授权变更通知）。', 'live-2d') . '</li>' .
				'</ul>' .
				'<p>' . esc_html__('下载完成后，请前往「Live 2D 设置 → 基础设置」，把「API 方式」切换到「本地部署旧版模型」，然后在「默认模型 ID」下拉中即可看到刚才下载的模型并选用。', 'live-2d') . '</p>' .
				'<p>' . esc_html__('如果某个模型下载失败：先确认 Key 仍然有效（登录页会提示），并检查站点是否能访问 download.live2dweb.com；网络环境受限的站点也可以在源站手动下载 ZIP 后放进 wp-content/plugins/live-2d/model/ 目录。', 'live-2d') . '</p>' .
				'<p>' . esc_html__('Cubism 4+ 模型不会出现在本页面，需要在「Live 2D 设置 → 基础设置」选择「自定义新版模型 · 直链加载」或「自定义新版模型 · 托管到本站」后自行填写 model3.json 地址(直链)或在「模型目录」每行填一个 slug(托管到本站),并参考「设置帮助 → API 方式与模型路径」一节。', 'live-2d') . '</p>'
			),
		) );

		$screen->set_help_sidebar( self::helpSidebar() );
	}

	/**
	 * 统一的 help_tab 内容外壳，避免 add_help_tab 内联样式四处重复。
	 */
	private static function wrapHelpContent( $html ){
		return '<div class="live2d-help-tab" style="line-height:1.6">' . $html . '</div>';
	}

	/**
	 * help_sidebar：所有 help_tab 共用的右侧栏。
	 */
	private static function helpSidebar(){
		return
			'<p><strong>' . esc_html__('更多帮助', 'live-2d') . '</strong></p>' .
			'<p><a target="_blank" rel="noopener" href="https://jiangweifang.github.io/wp-live2d/">' . esc_html__('插件文档站', 'live-2d') . '</a></p>' .
			'<p><a target="_blank" rel="noopener" href="https://www.live2dweb.com/">' . esc_html__('账号 / 授权管理', 'live-2d') . '</a></p>' .
			'<p><a target="_blank" rel="noopener" href="https://github.com/jiangweifang/wp-live2d/issues">' . esc_html__('GitHub Issues 反馈', 'live-2d') . '</a></p>';
	}
}

?>