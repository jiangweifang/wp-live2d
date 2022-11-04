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
    
    public function update_Waifu_JsonFile($jsonArray){
		try{
			$filePath = plugin_dir_path(__FILE__)  . '../assets/waifu-tips.json';
			$isSave = file_put_contents($filePath,json_encode($jsonArray));
			if(!$isSave) add_settings_error('live_2d_advanced_option_saveFiles','500','文件保存失败,当前文件路径为：'.$filePath);
		}catch (Exception $e){
			add_settings_error('live_2d_advanced_option_saveFiles','500','Save Error:'.$e);
		}
    }

    public function advanced_json($value) {
		$result = array();
	
		$result["waifu"] = $this->waifuMsg_Json($value);
		
		$result["mouseover"] = $this->mouseOverMsg_Json($value['mouseover_msg']);
		
		$result["click"] = $this->clickMsg_Json($value['click_selector'],$value['click_msg']);
		
		$result["seasons"] = $this->seasonsMsg_Json($value['seasons_msg']);
		
		return $result;
	}
	
	// waifu节点组装，此节点主要是提示消息
	public function waifuMsg_Json($value){
		$msg = array();
		$msg["console_open_msg"] = $value['console_open_msg'];
		$msg["copy_message"] = $value['copy_message'];
		$msg["screenshot_message"] = $value['screenshot_message'];
		$msg["hidden_message"] = $value['hidden_message'];
		$msg["load_rand_textures"] = $value['load_rand_textures'];
		$msg["hour_tips"] = $this->tips_Array($value['hour_tips']);
		$msg["referrer_message"] = $this->tips_Array($value['referrer_message']);
		$msg["referrer_hostname"] = $this->tips_Array($value['referrer_hostname']);
		$msg["hitokoto_api_message"] = $this->tips_Array($value['hitokoto_api_message']);
		return $msg;
	}
	
	// 把提示重组
	public function tips_Array($arr){
		$arrayTips = array();
		foreach($arr as $value){
			$arrayTips[$value[0]]=array($value[1]);
		}
		return $arrayTips;
	}
	
	// 鼠标浮动事件（可自定义）
	public function mouseOverMsg_Json($arrOpt){
		$mouseOverMsg = array();
		foreach($arrOpt as $key=> $value){
			$mouseOverMsg[$key]["selector"]= $value["selector"];
			$mouseOverMsg[$key]["text"]= $value["text"];
		}
		return $mouseOverMsg;
	}
	// 鼠标点击事件（可自定义）
	public function clickMsg_Json($selector,$textArr){
		$clickMsg = array();
		$clickMsg[0]["selector"]=$selector;
		$clickMsg[0]["text"] = $textArr;
		return $clickMsg;
	}
	
	// 节日事件（可自定义）
	public function seasonsMsg_Json($arr){
		$seasonsMsg = array();
		foreach($arr as $key=> $value){
			$seasonsMsg[$key]["date"]= $value[0];
			$seasonsMsg[$key]["text"]= $value[1];
		}
		return $seasonsMsg;
	}

	public static function live_2D_help_tab(){
		$screen = get_current_screen();
		// 设置帮助文档
		$screen->add_help_tab( array(
			'id'	=> 'live_2d_base_help_tab',
			'title'	=> __('基础设置帮助','live-2d'),
			'content'	=> ''.
			'<p>模型ID：您可以参考 <a target="_blank" href="https://cdn.jsdelivr.net/gh/fghrsh/live2d_api@1.0.1/model_list.json">model_list.json</a> 了解ID序号，如果您自己搭建模型API请酌情填写。 </p>'.
			'<p>模型缩放倍数：您可以适当调节模型在容器中的缩放比例。</p>'.
			'<p>moc3模型自定义动作：SDK3.3版本的model3.json中没有明确指定“动作”，需要用户自定义，目前已知的触碰位置有touch_body、touch_head、touch_special，请在使用时为触碰位置明确动作文件。
			例如："touch_head.motion3.json"请在输入框中输入touch_head</p>'
		) );

		$screen->add_help_tab( array(
			'id'	=> 'live_2d_advanced_help_tab',
			'title'	=> __('高级设置帮助','live-2d'),
			'content'	=> '<p>高级设置中点击&nbsp;<input class="button" type="button" value="+ ' . __('点击此处增加一条','live-2d') . '" id="show_btn">&nbsp;就会在同一个事件中增加随机语言。</p>
			<p>关于特殊标记，目前只有以下功能可以使用特殊标记：</p>
			<ul>
			<li>鼠标悬停时的消息提示：{text}触发事件的内容、{highlight}高亮样式</li>
			<li>鼠标点击时的消息提示：{text}触发事件的内容、{highlight}高亮样式</li>
			<li>节日事件：{year}年份、{highlight}高亮样式</li>
			<li>搜索引擎入站提示：{title}网站标题、{keyword}关键词、{website}站点名称、{highlight}高亮样式。注意：不是所有搜索引擎都可用这些标记，具体请看预设值。</li>
			<li>访问本站点的提示：{website}站点名称、{highlight}高亮样式</li>
			<li>一言API的消息：{title}、{source}、{creator}、{date}、{dynasty}、{author}、{highlight} 。注意：不是所有消息都可用这些标记，具体请看预设值。</li>
			</ul>
			<p>设置高亮的规则：'.esc_attr('<span style="{highlight}"></span>').'，您可以在任意一个style中增加{highlight}标记，高亮颜色请前往【提示消息选项】中查看</p>
			<p>如果您想恢复初始设置，可以删除插件后重新安装，所有内容会恢复初始化。</p>'
		) );
	}
}

?>