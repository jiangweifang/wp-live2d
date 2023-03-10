<?php
class live2D_Settings{
	
    public function live_2d_settings_sanitize($input) {
            
		$sanitary_values = array();
		if ( isset( $input['live2dLayoutType'] ) ) {
            $sanitary_values['live2dLayoutType'] = (Boolean)$input['live2dLayoutType'];
        }else{
			$sanitary_values['live2dLayoutType'] = true;
		}

		if ( isset( $input['apiType'] ) ) {
            $sanitary_values['apiType'] = (Boolean)$input['apiType'];
        }else{
			$sanitary_values['apiType'] = false;
		}

        if ( isset( $input['modelAPI'] ) ) {
            $sanitary_values['modelAPI'] = sanitize_text_field( $input['modelAPI'] );
        }else{
			$sanitary_values['modelAPI'] = "https://live2d.fghrsh.net/api/";
		}

        if ( isset( $input['tipsMessage'] ) ) {
            $sanitary_values['tipsMessage'] = sanitize_text_field( $input['tipsMessage'] );
        }

        if ( isset( $input['hitokotoAPI'] ) ) {
            $sanitary_values['hitokotoAPI'] = $input['hitokotoAPI'];
        }

        if ( isset( $input['modelId'] ) ) {
            $sanitary_values['modelId'] = $input['modelId'];
        }

        if ( isset( $input['modelTexturesId'] ) ) {
            $sanitary_values['modelTexturesId'] = sanitize_text_field( $input['modelTexturesId'] );
		}
		
		if ( isset( $input['modelPoint'] ) ) {
            $sanitary_values['modelPoint'] = $input['modelPoint'];
		}else{
			$sanitary_values['modelPoint']['zoom']="1.0";
			$sanitary_values['modelPoint']['x']= 0;
			$sanitary_values['modelPoint']['y']= 0;
		}

		if ( isset( $input['sdkUrl'] ) ) {
            $sanitary_values['sdkUrl'] = sanitize_text_field( $input['sdkUrl'] );
        }else{
			$sanitary_values['sdkUrl'] = 'https://cubism.live2d.com/sdk-web/cubismcore/live2dcubismcore.min.js';
		}

        if ( isset( $input['showToolMenu'] ) ) {
            $sanitary_values['showToolMenu'] = (Boolean)$input['showToolMenu'];
        }

        if ( isset( $input['canCloseLive2d'] ) ) {
            $sanitary_values['canCloseLive2d'] = (Boolean)$input['canCloseLive2d'];
        }

        if ( isset( $input['canSwitchModel'] ) ) {
            $sanitary_values['canSwitchModel'] = (Boolean)$input['canSwitchModel'];
        }

        if ( isset( $input['canSwitchTextures'] ) ) {
            $sanitary_values['canSwitchTextures'] = (Boolean)$input['canSwitchTextures'];
        }

        if ( isset( $input['canSwitchHitokoto'] ) ) {
            $sanitary_values['canSwitchHitokoto'] = (Boolean)$input['canSwitchHitokoto'];
        }

        if ( isset( $input['canTakeScreenshot'] ) ) {
            $sanitary_values['canTakeScreenshot'] = (Boolean)$input['canTakeScreenshot'];
        }

        if ( isset( $input['canTurnToHomePage'] ) ) {
            $sanitary_values['canTurnToHomePage'] = (Boolean)$input['canTurnToHomePage'];
        }

        if ( isset( $input['canTurnToAboutPage'] ) ) {
            $sanitary_values['canTurnToAboutPage'] = (Boolean)$input['canTurnToAboutPage'];
        }

        if ( isset( $input['modelStorage'] ) ) {
            $sanitary_values['modelStorage'] = (Boolean)$input['modelStorage'];
        }

        if ( isset( $input['modelRandMode'] ) ) {
            $sanitary_values['modelRandMode'] = $input['modelRandMode'];
        }

        if ( isset( $input['modelTexturesRandMode'] ) ) {
            $sanitary_values['modelTexturesRandMode'] = $input['modelTexturesRandMode'];
        }

        if ( isset( $input['showHitokoto'] ) ) {
            $sanitary_values['showHitokoto'] = (Boolean)$input['showHitokoto'];
        }

        if ( isset( $input['showF12Status'] ) ) {
            $sanitary_values['showF12Status'] = (Boolean)$input['showF12Status'];
        }

        if ( isset( $input['showF12Message'] ) ) {
            $sanitary_values['showF12Message'] = (Boolean)$input['showF12Message'];
        }

        if ( isset( $input['showF12OpenMsg'] ) ) {
            $sanitary_values['showF12OpenMsg'] = (Boolean)$input['showF12OpenMsg'];
        }

        if ( isset( $input['showCopyMessage'] ) ) {
            $sanitary_values['showCopyMessage'] = (Boolean)$input['showCopyMessage'];
        }

        if ( isset( $input['showWelcomeMessage'] ) ) {
            $sanitary_values['showWelcomeMessage'] = (Boolean)$input['showWelcomeMessage'];
        }

        if ( isset( $input['waifuSize'] ) ) {
            $sanitary_values['waifuSize'] = $input['waifuSize'];
        }

        if ( isset( $input['waifuTipsSize'] ) ) {
            $sanitary_values['waifuTipsSize'] = $input['waifuTipsSize'];
        }

        if ( isset( $input['waifuFontSize'] ) ) {
            $sanitary_values['waifuFontSize'] = (int) $input['waifuFontSize'] ;
        }

        if ( isset( $input['waifuToolFont'] ) ) {
            $sanitary_values['waifuToolFont'] = (int)$input['waifuToolFont'];
        }

        if ( isset( $input['waifuToolLine'] ) ) {
            $sanitary_values['waifuToolLine'] = (int)$input['waifuToolLine'];
        }

        if ( isset( $input['waifuToolTop'] ) ) {
            $sanitary_values['waifuToolTop'] = (int) $input['waifuToolTop'] ;
        }

        if ( isset( $input['waifuMinWidth'] ) ) {
            $sanitary_values['waifuMinWidth'] = (int) $input['waifuMinWidth'] ;
		}

		if ( isset( $input['waifuMobileDisable'] ) ) {
            $sanitary_values['waifuMobileDisable'] = (Boolean) $input['waifuMobileDisable'] ;
		}
		
		if ( isset( $input['waifuEdgeSide'] ) ) {
            $sanitary_values['waifuEdgeSide'] = sanitize_text_field( $input['waifuEdgeSide'] );
        }

        if ( isset( $input['waifuEdgeSize'] ) ) {
            $sanitary_values['waifuEdgeSize'] = (int) $input['waifuEdgeSize'] ;
        }

        if ( isset( $input['waifuDraggable'] ) ) {
            $sanitary_values['waifuDraggable'] = $input['waifuDraggable'];
        }

        if ( isset( $input['waifuDraggableRevert'] ) ) {
            $sanitary_values['waifuDraggableRevert'] = (Boolean)$input['waifuDraggableRevert'];
        }

        if ( isset( $input['homePageUrl'] ) ) {
            $sanitary_values['homePageUrl'] = sanitize_text_field( $input['homePageUrl'] );
        }

        if ( isset( $input['aboutPageUrl'] ) ) {
            $sanitary_values['aboutPageUrl'] = sanitize_text_field( $input['aboutPageUrl'] );
        }

        if ( isset( $input['screenshotCaptureName'] ) ) {
            $sanitary_values['screenshotCaptureName'] = sanitize_text_field( $input['screenshotCaptureName'] );
		}
		//新增的颜色和透明度
		if ( isset( $input['waifuTipsColor'] ) ) {
            $sanitary_values['waifuTipsColor'] = sanitize_text_field( $input['waifuTipsColor'] );
		}

		if ( isset( $input['waifuBorderColor'] ) ) {
            $sanitary_values['waifuBorderColor'] = sanitize_text_field( $input['waifuBorderColor'] );
		}

		if ( isset( $input['waifuShadowColor'] ) ) {
            $sanitary_values['waifuShadowColor'] = sanitize_text_field( $input['waifuShadowColor'] );
		}

		if ( isset( $input['waifuFontsColor'] ) ) {
            $sanitary_values['waifuFontsColor'] = sanitize_text_field( $input['waifuFontsColor'] );
		}

		if ( isset( $input['waifuHighlightColor'] ) ) {
            $sanitary_values['waifuHighlightColor'] = sanitize_text_field( $input['waifuHighlightColor'] );
		}
		//新增了工具栏的颜色 工具栏是字体组成的 
		if ( isset( $input['waifuToolColor'] ) ) {
            $sanitary_values['waifuToolColor'] = sanitize_text_field( $input['waifuToolColor'] );
		}

		if ( isset( $input['waifuToolHover'] ) ) {
            $sanitary_values['waifuToolHover'] = sanitize_text_field( $input['waifuToolHover'] );
		}
		// 新增tips位置
		if ( isset( $input['waifuTipTop'] ) ) {
            $sanitary_values['waifuTipTop'] = (int) $input['waifuTipTop'] ;
		}
        return $sanitary_values;
    }

    public function install_Default_Settings(){
		$live_2d_settings = get_option( 'live_2d_settings_option_name' );
		$defValue = array();
		if(FALSE === $live_2d_settings){
			$defValue['live2dLayoutType']=true;
			$defValue['modelAPI']= "https://live2d.fghrsh.net/api/";
			$defValue['hitokotoAPI']='lwl12.com';
			$defValue['modelId']='1';
			$defValue['modelTexturesId']='53';
			$defValue['modelPoint']['zoom']='1.0';
			$defValue['modelPoint']['x']=0;
			$defValue['modelPoint']['y']=0;
			$defValue['sdkUrl']='https://cubism.live2d.com/sdk-web/cubismcore/live2dcubismcore.min.js';
			$defValue['showToolMenu']=true;
			$defValue['canCloseLive2d']=true;
			$defValue['canSwitchModel']=true;
			$defValue['canSwitchTextures']=true;
			$defValue['canSwitchHitokoto']=true;
			$defValue['canTakeScreenshot']=true;
			$defValue['canTurnToHomePage']=true;
			$defValue['canTurnToAboutPage']=true;
			$defValue['modelStorage']=true;
			$defValue['modelRandMode']='rand';
			$defValue['modelTexturesRandMode']='switch';
			$defValue['showHitokoto']=true;
			$defValue['showF12Status']=true;
			$defValue['showF12Message']=true;
			$defValue['showF12OpenMsg']=true;
			$defValue['showCopyMessage']=true;
			$defValue['showWelcomeMessage']=true;
			$defValue['waifuSize']['width']=280;
			$defValue['waifuSize']['height']=250;
			$defValue['waifuTipsSize']['width']=250;
			$defValue['waifuTipsSize']['height']=70;
			$defValue['waifuFontSize']=12;
			$defValue['waifuToolFont']=14;
			$defValue['waifuToolLine']=20;
			$defValue['waifuToolTop']=20;
			$defValue['waifuMinWidth']=768;
			//$defValue['waifuMobileDisable']=false;
			$defValue['waifuEdgeSide']='left';
			$defValue['waifuEdgeSize']=0;
			$defValue['waifuDraggable']='axis-x';
			$defValue['waifuDraggableRevert']=true;
			$defValue['homePageUrl']=get_home_url();
			$defValue['aboutPageUrl']='#';
			$defValue['screenshotCaptureName']='live2d.png';
			$defValue['waifuTipsColor']='rgba(236, 217, 188, 0.5)';
			$defValue['waifuBorderColor']='rgba(224, 186, 140, 0.62)';
			$defValue['waifuShadowColor']='rgba(191, 158, 118, 0.2)';
			$defValue['waifuFontsColor']='#32373c';
			$defValue['waifuHighlightColor']='#0099cc';
			$defValue['waifuToolColor']='#5b6c7d';
			$defValue['waifuToolHover']='#34495e';
			add_option('live_2d_settings_option_name',$defValue);
		}
    }
    
    public function install_Default_Advanced(){
		$live_2d_advanced = get_option( 'live_2d_advanced_option_name' );
		if(FALSE === $live_2d_advanced){
			$defKey = array();
			//控制台被打开提醒（支持多句随机）
			$defKey['console_open_msg'][0] = '哈哈，你打开了控制台，是想要看看我的秘密吗？';
			//内容被复制触发提醒（支持多句随机）
			$defKey['copy_message'][0] = '你都复制了些什么呀，转载要记得加上出处哦！';
			//看板娘截图提示语（支持多句随机）
			$defKey['screenshot_message'][0] = '照好了嘛，是不是很可爱呢？';
			//看板娘隐藏提示语（支持多句随机）
			$defKey['hidden_message'][0] = '我们还能再见面的吧…？';
			//随机材质提示语（暂不支持多句）
			$defKey['load_rand_textures'][0]='我还没有其他衣服呢';
			$defKey['load_rand_textures'][1]='我的新衣服好看嘛?';
			//时间提示
			$defKey['hour_tips'][0][0] = 't5-7';
			$defKey['hour_tips'][0][1] = '早上好！一日之计在于晨，美好的一天就要开始了';
			$defKey['hour_tips'][1][0] = 't7-11';
			$defKey['hour_tips'][1][1] = '上午好！工作顺利嘛，不要久坐，多起来走动走动哦！';
			$defKey['hour_tips'][2][0] = 't11-14';
			$defKey['hour_tips'][2][1] = '中午了，工作了一个上午，现在是午餐时间！';
			$defKey['hour_tips'][3][0] = 't14-17';
			$defKey['hour_tips'][3][1] = '午后很容易犯困呢，今天的运动目标完成了吗？';
			$defKey['hour_tips'][4][0] = 't17-19';
			$defKey['hour_tips'][4][1] = '傍晚了！窗外夕阳的景色很美丽呢，最美不过夕阳红~';
			$defKey['hour_tips'][5][0] = 't19-21';
			$defKey['hour_tips'][5][1] = '晚上好，今天过得怎么样？';
			$defKey['hour_tips'][6][0] = 't21-23';
			$defKey['hour_tips'][6][1] = '已经这么晚了呀，早点休息吧，晚安~';
			$defKey['hour_tips'][7][0] = 't23-5';
			$defKey['hour_tips'][7][1] = '你是夜猫子呀？这么晚还不睡觉，明天起的来嘛';
			$defKey['hour_tips'][8][0] = 'default';
			$defKey['hour_tips'][8][1] = '嗨~ 快来逗我玩吧！';
			//请求来源欢迎语（不支持多句）
			$defKey['referrer_message'][0][0] = 'localhost';
			$defKey['referrer_message'][0][1] = '欢迎阅读<span style="{highlight}">『{title}』</span>';
			$defKey['referrer_message'][1][0] = 'baidu';
			$defKey['referrer_message'][1][1] = 'Hello! 来自 百度搜索 的朋友<br>你是搜索 <span style="{highlight}>{keyword}</span> 找到的我吗？';
			$defKey['referrer_message'][2][0] = 'so';
			$defKey['referrer_message'][2][1] = 'Hello! 来自 360搜索 的朋友<br>你是搜索 <span style="{highlight}">{keyword}</span> 找到的我吗？';
			$defKey['referrer_message'][3][0] = 'google';
			$defKey['referrer_message'][3][1] = 'Hello! 来自 谷歌搜索 的朋友<br>欢迎阅读<span style="{highlight}">『{title}』</span>';
			$defKey['referrer_message'][4][0] = 'default';
			$defKey['referrer_message'][4][1] = 'Hello! 来自 <span style="{highlight}">{website}</span> 的朋友';
			$defKey['referrer_message'][5][0] = 'none';
			$defKey['referrer_message'][5][1] = '欢迎阅读<span style="{highlight}">『{title}』</span>';
			//请求来源自定义名称（根据 host，支持多句随机）
			$defKey['referrer_hostname'][0][0] = 'example.com';
			$defKey['referrer_hostname'][0][1] = '示例网站';
			$defKey['referrer_hostname'][1][0] = 'www.fghrsh.net';
			$defKey['referrer_hostname'][1][1] = 'FGHRSH 的博客';
			//一言 API 输出模板（不支持多句随机）
			$defKey['hitokoto_api_message'][0][0] = 'lwl12.com';
			$defKey['hitokoto_api_message'][0][1] = '这句一言来自 <span style="{highlight}">『{source}』</span>|，是 <span style="{highlight}">{creator}</span> 投稿的。';
			$defKey['hitokoto_api_message'][1][0] = 'fghrsh.net';
			$defKey['hitokoto_api_message'][1][1] = '这句一言出处是 <span style="{highlight}">『{source}』</span>，是 <span style="{highlight}">FGHRSH</span> 在 {date} 收藏的！';
			$defKey['hitokoto_api_message'][2][0] = 'jinrishici.com';
			$defKey['hitokoto_api_message'][2][1] = '这句诗词出自 <span style="{highlight}">《{title}》</span>，是 {dynasty}诗人 {author} 创作的！';
			$defKey['hitokoto_api_message'][3][0] = 'hitokoto.cn';
			$defKey['hitokoto_api_message'][3][1] = '这句一言来自 <span style="{highlight}">『{source}』</span>，是 <span style="{highlight}">{creator}</span> 在 hitokoto.cn 投稿的。';
			//鼠标触发提示（根据 CSS 选择器，支持多句随机）
			$defKey['mouseover_msg'][0]['selector'] = ".container a[href^='http']";
			$defKey['mouseover_msg'][0]['text'] = '要看看 <span style="{highlight}">{text}</span> 么？';
			$defKey['mouseover_msg'][1]['selector'] = '.fui-home';
			$defKey['mouseover_msg'][1]['text'] = '点击前往首页，想回到上一页可以使用浏览器的后退功能哦';
			$defKey['mouseover_msg'][2]['selector'] = '.fui-chat';
			$defKey['mouseover_msg'][2]['text'] = '一言一语，一颦一笑。一字一句，一颗赛艇。';
			$defKey['mouseover_msg'][3]['selector'] = '.fui-eye';
			$defKey['mouseover_msg'][3]['text'] = '嗯··· 要切换 看板娘 吗？';
			$defKey['mouseover_msg'][4]['selector'] = '.fui-user';
			$defKey['mouseover_msg'][4]['text'] = '喜欢换装 Play 吗？';
			$defKey['mouseover_msg'][5]['selector'] = '.fui-photo';
			$defKey['mouseover_msg'][5]['text'] = '要拍张纪念照片吗？';
			$defKey['mouseover_msg'][6]['selector'] = '.fui-info-circle';
			$defKey['mouseover_msg'][6]['text'] = '这里有关于我的信息呢';
			$defKey['mouseover_msg'][7]['selector'] = '.fui-cross';
			$defKey['mouseover_msg'][7]['text'] = '你不喜欢我了吗...';
			$defKey['mouseover_msg'][8]['selector'] = '#tor_show';
			$defKey['mouseover_msg'][8]['text'] = '翻页比较麻烦吗，点击可以显示这篇文章的目录呢';
			$defKey['mouseover_msg'][9]['selector'] = '#comment_go';
			$defKey['mouseover_msg'][9]['text'] = '想要去评论些什么吗？';
			$defKey['mouseover_msg'][10]['selector'] = '#night_mode';
			$defKey['mouseover_msg'][10]['text'] = '深夜时要爱护眼睛呀';
			$defKey['mouseover_msg'][11]['selector'] = '#qrcode';
			$defKey['mouseover_msg'][11]['text'] = '手机扫一下就能继续看，很方便呢';
			$defKey['mouseover_msg'][12]['selector'] = '.comment_reply';
			$defKey['mouseover_msg'][12]['text'] = '要吐槽些什么呢';
			$defKey['mouseover_msg'][13]['selector'] = '#back-to-top';
			$defKey['mouseover_msg'][13]['text'] = '回到开始的地方吧';
			$defKey['mouseover_msg'][14]['selector'] = '#author';
			$defKey['mouseover_msg'][14]['text'] = '该怎么称呼你呢';
			$defKey['mouseover_msg'][15]['selector'] = '#mail';
			$defKey['mouseover_msg'][15]['text'] = '留下你的邮箱，不然就是无头像人士了';
			$defKey['mouseover_msg'][16]['selector'] = '#url';
			$defKey['mouseover_msg'][16]['text'] = '你的家在哪里呢，好让我去参观参观';
			$defKey['mouseover_msg'][17]['selector'] = '#textarea';
			$defKey['mouseover_msg'][17]['text'] = '认真填写哦，垃圾评论是禁止事项';
			$defKey['mouseover_msg'][18]['selector'] = '.OwO-logo';
			$defKey['mouseover_msg'][18]['text'] = '要插入一个表情吗';
			$defKey['mouseover_msg'][19]['selector'] = '#csubmit';
			$defKey['mouseover_msg'][19]['text'] = '要[提交]^(Commit)了吗，首次评论需要审核，请耐心等待~';
			$defKey['mouseover_msg'][20]['selector'] = '.ImageBox';
			$defKey['mouseover_msg'][20]['text'] = '点击图片可以放大呢';
			$defKey['mouseover_msg'][21]['selector'] = 'input[name=s]';
			$defKey['mouseover_msg'][21]['text'] = '找不到想看的内容？搜索看看吧';
			$defKey['mouseover_msg'][22]['selector'] = '.previous';
			$defKey['mouseover_msg'][22]['text'] = '去上一页看看吧';
			$defKey['mouseover_msg'][23]['selector'] = '.next';
			$defKey['mouseover_msg'][23]['text'] = '去下一页看看吧';
			$defKey['mouseover_msg'][24]['selector'] = '.dropdown-toggle';
			$defKey['mouseover_msg'][24]['text'] = '这里是菜单';
			// 鼠标点击触发提示（根据 CSS 选择器，支持多句随机）
			$defKey['click_selector']='.waifu #live2d';
			$defKey['click_msg'][0] = '是…是不小心碰到了吧';
			$defKey['click_msg'][1] = '萝莉控是什么呀';
			$defKey['click_msg'][2] = '你看到我的小熊了吗';
			$defKey['click_msg'][3] = '再摸的话我可要报警了！⌇●﹏●⌇';
			$defKey['click_msg'][4] = '110吗，这里有个变态一直在摸我(ó﹏ò｡)';
			//节日提示（日期段，支持多句随机）
			$defKey['seasons_msg'][0][0] = '01/01';
			$defKey['seasons_msg'][0][1] ='<span style="{highlight}">元旦</span>了呢，新的一年又开始了，今年是{year}年~';
			$defKey['seasons_msg'][1][0] = '02/14';
			$defKey['seasons_msg'][1][1] ='又是一年<span style="{highlight}">情人节</span>，{year}年找到对象了嘛~';
			$defKey['seasons_msg'][2][0] = '03/08';
			$defKey['seasons_msg'][2][1] ='今天是<span style="{highlight}">妇女节</span>！';
			$defKey['seasons_msg'][3][0] = '03/12';
			$defKey['seasons_msg'][3][1] ='今天是<span style="{highlight}">植树节</span>，要保护环境呀';
			$defKey['seasons_msg'][4][0] = '04/01';
			$defKey['seasons_msg'][4][1] ='悄悄告诉你一个秘密~<span style="background-color:#34495e;">今天是愚人节，不要被骗了哦~</span>';
			$defKey['seasons_msg'][5][0] = '05/01';
			$defKey['seasons_msg'][5][1] ='今天是<span style="{highlight}">五一劳动节</span>，计划好假期去哪里了吗~';
			$defKey['seasons_msg'][6][0] = '06/01';
			$defKey['seasons_msg'][6][1] ='<span style="{highlight}">儿童节</span>了呢，快活的时光总是短暂，要是永远长不大该多好啊…';
			$defKey['seasons_msg'][7][0] = '09/03';
			$defKey['seasons_msg'][7][1] ='<span style="{highlight}">中国人民抗日战争胜利纪念日</span>，铭记历史、缅怀先烈、珍爱和平、开创未来。';
			$defKey['seasons_msg'][8][0] = '09/10';
			$defKey['seasons_msg'][8][1] ='<span style="{highlight}">教师节</span>，在学校要给老师问声好呀~';
			$defKey['seasons_msg'][9][0] = '10/01';
			$defKey['seasons_msg'][9][1] ='<span style="{highlight}">国庆节</span>，新中国已经成立69年了呢';
			$defKey['seasons_msg'][10][0] = '11/05-11/12';
			$defKey['seasons_msg'][10][1] ='今年的<span style="{highlight}">双十一</span>是和谁一起过的呢~';
			$defKey['seasons_msg'][11][0] = '12/20-12/31';
			$defKey['seasons_msg'][11][1] ='这几天是<span style="{highlight}">圣诞节</span>，主人肯定又去剁手买买买了~';
			add_option('live_2d_advanced_option_name',$defKey);
		}
	}
}

?>