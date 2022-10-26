//全局变量
window.live2d_settings = Array(); 
var re = /x/;
var hltips = 'color:';//定义highlight标记
console.log(re + 'WP-Live2D 1.7.9');

String.prototype.render = function(context) {
    var tokenReg = /(\\)?\{([^\{\}\\]+)(\\)?\}/g;

    return this.replace(tokenReg, function (word, slash1, token, slash2) {
        if (slash1 || slash2) { return word.replace('\\', ''); }
        
        var variables = token.replace(/\s/g, '').split('.');
        var currentObject = context;
        var i, length, variable;
        
        for (i = 0, length = variables.length; i < length; ++i) {
            variable = variables[i];
            currentObject = currentObject[variable];
            if (currentObject === undefined || currentObject === null) return '';
        }
        return currentObject;
    });
};

function empty(obj) {return typeof obj=="undefined"||obj==null||obj==""?true:false}
function getRandText(text) {return Array.isArray(text) ? text[Math.floor(Math.random() * text.length + 1)-1] : text}

function showMessage(text, timeout, flag) {
    if(flag || sessionStorage.getItem('waifu-text') === '' || sessionStorage.getItem('waifu-text') === null){
        if(Array.isArray(text)) text = text[Math.floor(Math.random() * text.length + 1)-1];
        if (live2d_settings.showF12Message) console.log('[Message]', text.replace(/<[^<>]+>/g,''));
        
        if(flag) sessionStorage.setItem('waifu-text', text);
        
        $('.waifu-tips').stop();
        $('.waifu-tips').html(text).fadeTo(200, 1);
        if (timeout === undefined) timeout = 5000;
        hideMessage(timeout);
    }
}

function hideMessage(timeout) {
    $('.waifu-tips').stop().css('opacity',1);
    if (timeout === undefined) timeout = 5000;
    window.setTimeout(function() {sessionStorage.removeItem('waifu-text')}, timeout);
    $('.waifu-tips').delay(timeout).fadeTo(200, 0);
}

function initModel(waifuPath, settingsJson) {
    live2d_settings = settingsJson;
    
    //设置一个单位为px的文字
    var unitType = 'px';

    /* 判断 JQuery */
	if($ != null){
        if (typeof($.ajax) != 'function') typeof(jQuery.ajax) == 'function' ? window.$ = jQuery : console.log('[Error] JQuery is not defined.');
    }else{
        window.$ = jQuery;
    }

    /* 加载Live2D容器样式 */
    $("#live2d").attr({
        "width":live2d_settings.waifuSize['width'],
        "height":live2d_settings.waifuSize['height']
    });
    //----------从JSON中获取颜色定义----------
    $(".waifu-tips").css({
        "width":live2d_settings.waifuTipsSize['width'] + unitType,      //宽度
        "height":live2d_settings.waifuTipsSize['height'] + unitType,    //高度
        "top":(0 - live2d_settings.waifuTipTop) + unitType,                  //上方位置可以是负数
        "font-size":live2d_settings.waifuFontSize + unitType,           //字号
        "border":"1px solid "+live2d_settings.waifuBorderColor,         //边框颜色 固定值是1px实心线条
        "background-color":live2d_settings.waifuTipsColor,              //背景色
        "box-shadow":"0 3px 15px 2px "+live2d_settings.waifuShadowColor,//影子的颜色 有4个位置默认值
        "color":live2d_settings.waifuFontsColor                         //字体颜色
    });
    $(".waifu-tool span").hover(function (){
        jQuery(this).css("color",live2d_settings.waifuToolHover);
    }).mouseout(function () {
        jQuery(this).css("color",live2d_settings.waifuToolColor);
    }).css({
        "color":live2d_settings.waifuToolColor,
        "line-height":live2d_settings.waifuToolLine + unitType
    });
    //--------------只是分割线而已-------------
    $(".waifu-tool").css({
        "font-size":live2d_settings.waifuToolFont + unitType,
        "top":live2d_settings.waifuToolTop + unitType
    });
    hltips += live2d_settings.waifuHighlightColor + ';';//定义highlight标记并赋值，因为是一个css所以必须;结尾

    if (live2d_settings.waifuEdgeSide == 'left') $(".waifu").css("left",live2d_settings.waifuEdgeSize + unitType);
    else if (live2d_settings.waifuEdgeSide == 'right') $(".waifu").css("right",live2d_settings.waifuEdgeSize + unitType);
    
    window.waifuResize = function() { $(window).width() <= live2d_settings.waifuMinWidth ? $(".waifu").hide() : $(".waifu").show(); };
    if (live2d_settings.waifuMinWidth != 0) { 
        if($(window).width() <= live2d_settings.waifuMinWidth){
            $(".waifu").hide();
            return;
        }else{
            $(".waifu").show(); 
        }
        $(window).resize(function() {waifuResize()});
    }
    
    try {
        if (live2d_settings.waifuDraggable == 'axis-x') $(".waifu").draggable({ axis: "x", revert: live2d_settings.waifuDraggableRevert });
        else if (live2d_settings.waifuDraggable == 'unlimited') $(".waifu").draggable({ revert: live2d_settings.waifuDraggableRevert });
        else $(".waifu").css("transition", 'all .3s ease-in-out');
    } catch(err) { console.info('[Error] JQuery UI is not defined.'+ err) }
    
    $('.waifu-tool .fui-home').click(function (){
        window.location = live2d_settings.homePageUrl;
    });
    
    $('.waifu-tool .fui-info-circle').click(function (){
        window.open(live2d_settings.aboutPageUrl);
    });

    $.ajax({
        cache: true,
        url: waifuPath,
        dataType: "json",
        success: function (result){ loadTipsMessage(result); }
    });
    
    if (!live2d_settings.showToolMenu) $('.waifu-tool').hide();
    if (!live2d_settings.canCloseLive2d) $('.waifu-tool .fui-cross').hide();
    if (!live2d_settings.canSwitchModel) $('.waifu-tool .fui-eye').hide();
    if (!live2d_settings.canSwitchTextures) $('.waifu-tool .fui-user').hide();
    if (!live2d_settings.canSwitchHitokoto) $('.waifu-tool .fui-chat').hide();
    if (!live2d_settings.canTakeScreenshot) $('.waifu-tool .fui-photo').hide();
    if (!live2d_settings.canTurnToHomePage) $('.waifu-tool .fui-home').hide();
    if (!live2d_settings.canTurnToAboutPage) $('.waifu-tool .fui-info-circle').hide();

    var modelId = localStorage.getItem('modelId');
    var modelTexturesId = localStorage.getItem('modelTexturesId');
    
    if (!live2d_settings.modelStorage || modelId == null) {
        var modelId = live2d_settings.modelId;
        var modelTexturesId = live2d_settings.modelTexturesId;
    } loadModel(
        modelId,
        modelTexturesId,
        live2d_settings.modelPoint.zoom, 
        live2d_settings.modelPoint.x, 
        live2d_settings.modelPoint.y, 
        live2d_settings.defineHitAreaName,
        live2d_settings.sdkUrl
    );
}

function loadModel(modelId, modelTexturesId=0,zoom = 1.0 ,x = 0, y = 0, hitAreaList = {} , sdkUrl = '') {
    if (live2d_settings.modelStorage) {
        localStorage.setItem('modelId', modelId);
        localStorage.setItem('modelTexturesId', modelTexturesId);
    } else {
        sessionStorage.setItem('modelId', modelId);
        sessionStorage.setItem('modelTexturesId', modelTexturesId);
    } 
    let modelPath;
    if(live2d_settings.modelAPI.indexOf('model3.json') > 0 ){
        modelPath = live2d_settings.modelAPI;
    }else{
        modelPath = live2d_settings.modelAPI+'get/?id='+modelId+'-'+modelTexturesId;
    }
    if(sdkUrl == undefined || sdkUrl == null || sdkUrl == ''){
        sdkUrl = 'https://cubism.live2d.com/sdk-web/cubismcore/live2dcubismcore.min.js';
    }
    loadlive2d(
        'live2d', 
        modelPath,
        0.5,
        zoom,
        x,
        y,
        hitAreaList,
        sdkUrl
    );
}

function loadTipsMessage(result) {
    window.waifu_tips = result;
    
    $.each(result.mouseover, function (index, tips){
        $(document).on("mouseover", tips.selector, function (){
            var text = getRandText(tips.text);
            text = text.render({text: $(this).text(),highlight: hltips});
            showMessage(text, 3000);
        });
    });
    $.each(result.click, function (index, tips){
        $(document).on("click", tips.selector, function (){
            var text = getRandText(tips.text);
            text = text.render({text: $(this).text(),highlight: hltips});
            showMessage(text, 3000, true);
        });
    });
    $.each(result.seasons, function (index, tips){
        var now = new Date();
        var after = tips.date.split('-')[0];
        var before = tips.date.split('-')[1] || after;
        
        if((after.split('/')[0] <= now.getMonth()+1 && now.getMonth()+1 <= before.split('/')[0]) && 
           (after.split('/')[1] <= now.getDate() && now.getDate() <= before.split('/')[1])){
            var text = getRandText(tips.text);
            text = text.render({year: now.getFullYear(),highlight: hltips});
            showMessage(text, 6000, true);
        }
    });
    
    if (live2d_settings.showF12OpenMsg) {
        re.toString = function() {
            showMessage(getRandText(result.waifu.console_open_msg), 5000, true);
            return '';
        };
    }
    
    if (live2d_settings.showCopyMessage) {
        $(document).on('copy', function() {
            showMessage(getRandText(result.waifu.copy_message), 5000, true);
        });
    }
    
    $('.waifu-tool .fui-photo').click(function(){
        showMessage(getRandText(result.waifu.screenshot_message), 5000, true);
        screenshot(live2d_settings.screenshotCaptureName,true);
    });
    
    $('.waifu-tool .fui-cross').click(function(){
        sessionStorage.setItem('waifu-dsiplay', 'none');
        showMessage(getRandText(result.waifu.hidden_message), 1300, true);
        window.setTimeout(function() {$('.waifu').hide();}, 1300);
    });
    
    window.showWelcomeMessage = function(result) {
        var text;
        if (window.location.href == live2d_settings.homePageUrl) {
            var now = (new Date()).getHours();
            if (now > 23 || now <= 5) text = getRandText(result.waifu.hour_tips['t23-5']);
            else if (now > 5 && now <= 7) text = getRandText(result.waifu.hour_tips['t5-7']);
            else if (now > 7 && now <= 11) text = getRandText(result.waifu.hour_tips['t7-11']);
            else if (now > 11 && now <= 14) text = getRandText(result.waifu.hour_tips['t11-14']);
            else if (now > 14 && now <= 17) text = getRandText(result.waifu.hour_tips['t14-17']);
            else if (now > 17 && now <= 19) text = getRandText(result.waifu.hour_tips['t17-19']);
            else if (now > 19 && now <= 21) text = getRandText(result.waifu.hour_tips['t19-21']);
            else if (now > 21 && now <= 23) text = getRandText(result.waifu.hour_tips['t21-23']);
            else text = getRandText(result.waifu.hour_tips.default);
        } else {
            var referrer_message = result.waifu.referrer_message;
			// 用来隔开网站标题中的 “-” 
			var titleStr = document.title.split(' – ')[0];
			// 在document.referrer不为空的时候执行
            if (document.referrer !== '') {
				try{
					var referrer = document.createElement('a');
					referrer.href = document.referrer;
					var domain = referrer.hostname.split('.')[1];
					if (window.location.hostname == referrer.hostname){
						//text = referrer_message.localhost[0] + document.title.split(referrer_message.localhost[2])[0] + referrer_message.localhost[1];
						text = referrer_message.localhost[0];
						text = text.render({title: titleStr,highlight: hltips});
					} else if (domain == 'baidu'){
						//text = referrer_message.baidu[0] + referrer.search.split('&wd=')[1].split('&')[0] + referrer_message.baidu[1];
						text = referrer_message.baidu[0];
						text = text.render({keyword: referrer.search.split('&wd=')[1].split('&')[0],highlight: hltips});
					} else if (domain == 'so'){
						//text = referrer_message.so[0] + referrer.search.split('&q=')[1].split('&')[0] + referrer_message.so[1];
						text = referrer_message.so[0];
						text = text.render({keyword: referrer.search.split('&q=')[1].split('&')[0],highlight: hltips});
					} else if (domain == 'google'){
						//text = referrer_message.google[0] + document.title.split(referrer_message.google[2])[0] + referrer_message.google[1];
						text = referrer_message.google[0];
						text = text.render({title: titleStr,highlight: hltips});
					} else {
						$.each(result.waifu.referrer_hostname, function(i,val) {if (i==referrer.hostname) referrer.hostname = getRandText(val)});
						//text = referrer_message.default[0] + referrer.hostname + referrer_message.default[1];
						text = referrer_message.default[0];
						text = text.render({website: referrer.hostname,highlight: hltips});
					}
				}catch(err){
					console.log('It is not important Exception '+ err)
				}
            } else{ 
				//text = referrer_message.none[0] + document.title.split(referrer_message.none[2])[0] + referrer_message.none[1];
				text = referrer_message.none[0];
				text = text.render({title: titleStr,highlight: hltips});
			}
        }
        showMessage(text, 6000);
    }; if (live2d_settings.showWelcomeMessage) showWelcomeMessage(result);
    
    var waifu_tips = result.waifu;
    
    function loadOtherModel() {
        var modelId = modelStorageGetItem('modelId');
        var modelRandMode = live2d_settings.modelRandMode;
        
        $.ajax({
            cache: modelRandMode == 'switch' ? true : false,
            url: live2d_settings.modelAPI+modelRandMode+'/?id='+modelId,
            dataType: "json",
            success: function(result) {
                loadModel(result.model['id']);
                var message = result.model['message'];
                $.each(waifu_tips.model_message, function(i,val) {if (i==result.model['id']) message = getRandText(val)});
                showMessage(message, 3000, true);
            }
        });
    }
    
    function loadRandTextures() {
        var modelId = modelStorageGetItem('modelId');
        var modelTexturesId = modelStorageGetItem('modelTexturesId');
        var modelTexturesRandMode = live2d_settings.modelTexturesRandMode;
        
        $.ajax({
            cache: modelTexturesRandMode == 'switch' ? true : false,
            url: live2d_settings.modelAPI+modelTexturesRandMode+'_textures/?id='+modelId+'-'+modelTexturesId,
            dataType: "json",
            success: function(result) {
                if (result.textures['id'] == 1 && (modelTexturesId == 1 || modelTexturesId == 0))
                    showMessage(waifu_tips.load_rand_textures[0], 3000, true);
                else showMessage(waifu_tips.load_rand_textures[1], 3000, true);
                loadModel(modelId, result.textures['id']);
            }
        });
    }
    
    function modelStorageGetItem(key) { return live2d_settings.modelStorage ? localStorage.getItem(key) : sessionStorage.getItem(key); }
    
    /* 检测用户活动状态，并在空闲时显示一言 */
    if (live2d_settings.showHitokoto) {
        window.getActed = false; window.hitokotoTimer = 0; window.hitokotoInterval = false;
        $(document).mousemove(function(e){getActed = true;}).keydown(function(){getActed = true;});
        setInterval(function(){ if (!getActed) ifActed(); else elseActed(); }, 1000);
    }
    
    function ifActed() {
        if (!hitokotoInterval) {
            hitokotoInterval = true;
            hitokotoTimer = window.setInterval(showHitokotoActed, 30000);
        }
    }
    
    function elseActed() {
        getActed = hitokotoInterval = false;
        window.clearInterval(hitokotoTimer);
    }
    
    function showHitokotoActed() {
        if ($(document)[0].visibilityState == 'visible') showHitokoto();
    }
    
    function showHitokoto() {
    	switch(live2d_settings.hitokotoAPI) {
    	    case 'lwl12.com':
    	        $.getJSON('https://api.lwl12.com/hitokoto/v1?encode=realjson',function(result){
        	        if (!empty(result.source)) {
						var txtArr = waifu_tips.hitokoto_api_message['lwl12.com'][0].split('|');
						var text = txtArr[0];
						if(txtArr.length > 1){
							if (!empty(result.author)) text += txtArr[1];
						}
                        text = text.render({source: result.source, creator: result.author,highlight: hltips});
                        window.setTimeout(function() {showMessage(text, 3000, true);}, 5000);
                    } showMessage(result.text, 5000, true);
                });break;
    	    case 'fghrsh.net':
    	        $.getJSON('https://api.fghrsh.net/hitokoto/rand/?encode=jsc&uid=3335',function(result){
            	    if (!empty(result.source)) {
                        var text = waifu_tips.hitokoto_api_message['fghrsh.net'][0];
                        text = text.render({source: result.source, date: result.date,highlight: hltips});
                        window.setTimeout(function() {showMessage(text, 3000, true);}, 5000);
                        showMessage(result.hitokoto, 5000, true);
            	    }
                });break;
            case 'jinrishici.com':
                $.ajax({
                    url: 'https://v2.jinrishici.com/one.json',
                    xhrFields: {withCredentials: true},
                    success: function (result, status) {
                        if (!empty(result.data.origin.title)) {
                            var text = waifu_tips.hitokoto_api_message['jinrishici.com'][0];
                            text = text.render({title: result.data.origin.title, dynasty: result.data.origin.dynasty, author:result.data.origin.author,highlight: hltips});
                            window.setTimeout(function() {showMessage(text, 3000, true);}, 5000);
                        } showMessage(result.data.content, 5000, true);
                    }
                });break;
    	    default:
    	        $.getJSON('https://v1.hitokoto.cn',function(result){
            	    if (!empty(result.from)) {
                        var text = waifu_tips.hitokoto_api_message['hitokoto.cn'][0];
                        text = text.render({source: result.from, creator: result.creator,highlight: hltips});
                        window.setTimeout(function() {showMessage(text, 3000, true);}, 5000);
            	    }
                    showMessage(result.hitokoto, 5000, true);
                });
    	}
    }
    function rgbToRgba(color,alp){
        var r,g,b;
        var rgbaAttr = color.match(/[\d.]+/g);
        if(rgbaAttr.length >=3){
            var r,g,b;
            r = rgbaAttr[0];
            g = rgbaAttr[1];
            b = rgbaAttr[2];
            return 'rgba('+r+','+g+','+b+','+alp+')';
        }
    }
    
    $('.waifu-tool .fui-eye').click(function (){loadOtherModel()});
    $('.waifu-tool .fui-user').click(function (){loadRandTextures()});
    $('.waifu-tool .fui-chat').click(function (){showHitokoto()});
}
