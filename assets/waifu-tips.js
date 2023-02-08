//全局变量
window.live2d_settings = Array(); 
var re = /x/;
var hltips = 'color:';//定义highlight标记
console.log('WP-Live2D 1.8.1');
var connection = null;
var gptMsg = "";
var isConn = false;
var tipsIsShow = false;

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
        //if (live2d_settings.showF12Message) console.log('[Message]', text.replace(/<[^<>]+>/g,''));
        if(flag) sessionStorage.setItem('waifu-text', text);
        if (timeout === undefined) timeout = 5000;
        $('.waifu-tips').stop();
        if(!tipsIsShow){
            $('.waifu-tips').fadeTo(200, 1);
            tipsIsShow = true;
            hideMessage(timeout);
        }
        $('.waifu-tips').html(text);
    }
}

function hideMessage(timeout) {
    $('.waifu-tips').stop().css('opacity',1);
    if (timeout === undefined) timeout = 5000;
    if(tipsIsShow){
        window.setTimeout(function() {
            sessionStorage.removeItem('waifu-text');
            $('.waifu-tips').fadeTo(200, 0);
            tipsIsShow = false;
        }, timeout);
    }
}

function initModel(waifuPath) {
    live2d_settings = Object.assign({},waifu_settings);
    
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
        "min-height":live2d_settings.waifuTipsSize['height'] + unitType,    //高度
        "top":(0 - live2d_settings.waifuTipTop) + unitType,                  //上方位置可以是负数
        "font-size":live2d_settings.waifuFontSize + unitType,           //字号
        "border":"1px solid "+live2d_settings.waifuBorderColor,         //边框颜色 固定值是1px实心线条
        "background-color":live2d_settings.waifuTipsColor,              //背景色
        "box-shadow":"0 3px 15px 2px "+live2d_settings.waifuShadowColor,//影子的颜色 有4个位置默认值
        "color":live2d_settings.waifuFontsColor                         //字体颜色
    });
    $(".waifu-tool span").on("hover",function (){
        jQuery(this).css("color",live2d_settings.waifuToolHover);
    }).on("mouseout",function () {
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
        $(window).on("resize",function() {waifuResize()});
    }
    
    try {
        if (live2d_settings.waifuDraggable == 'axis-x') $(".waifu").draggable({ axis: "x", revert: live2d_settings.waifuDraggableRevert });
        else if (live2d_settings.waifuDraggable == 'unlimited') $(".waifu").draggable({ revert: live2d_settings.waifuDraggableRevert });
        else $(".waifu").css("transition", 'all .3s ease-in-out');
    } catch(err) { console.info('[Error] JQuery UI is not defined.'+ err) }
    
    $('.waifu-tool .fui-home').on("click",function (){
        window.location = live2d_settings.homePageUrl;
    });
    
    $('.waifu-tool .fui-info-circle').on("click",function (){
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

    loadModel(
        live2d_settings.modelId,
        live2d_settings.modelTexturesId,
        live2d_settings
    );
}

function loadModel(modelId, modelTexturesId=0, settings) {
    let objSet = Object.assign({},settings);
    let regJson = new RegExp(/model3(\.json$)/i);
    let apiUrl = objSet.modelAPI;
    if(!regJson.test(apiUrl)){
        regapiJson = new RegExp("https://api.live2dweb.com/[^\s]*(\/v2$)","i");
        if(regapiJson.test(apiUrl)){
            objSet.modelAPI = `${apiUrl}?id=${modelId}&tid=${modelTexturesId}`;
        }else{
            let apiLen = apiUrl.length - 1;
            let lidx = apiUrl.lastIndexOf('/');
            if(apiLen == lidx)
                apiUrl = apiUrl.substring(0,lidx);
                objSet.modelAPI = `${apiUrl}/get/?id=${modelId}-${modelTexturesId}`;
        }
    }
    if(objSet.sdkUrl == undefined || objSet.sdkUrl == null || objSet.sdkUrl == ''){
        objSet.sdkUrl = 'https://cubism.live2d.com/sdk-web/cubismcore/live2dcubismcore.min.js';
    }
    loadlive2d('live2d', objSet);
}

function showHitokoto() {
    switch(live2d_settings.hitokotoAPI) {
        case 'fghrsh.net':
            $.getJSON('https://api.fghrsh.net/hitokoto/rand/?encode=jsc&uid=3335',function(result){
                if (!empty(result.source)) {
                    var text = waifu_tips.waifu.hitokoto_api_message['fghrsh.net'][0];
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
                        var text = waifu_tips.waifu.hitokoto_api_message['jinrishici.com'][0];
                        text = text.render({title: result.data.origin.title, dynasty: result.data.origin.dynasty, author:result.data.origin.author,highlight: hltips});
                        window.setTimeout(function() {showMessage(text, 3000, true);}, 5000);
                    } showMessage(result.data.content, 5000, true);
                }
            });break;
        default:
            $.getJSON('https://v1.hitokoto.cn',function(result){
                if (!empty(result.from)) {
                    var text = waifu_tips.waifu.hitokoto_api_message['hitokoto.cn'][0];
                    text = text.render({source: result.from, creator: result.creator,highlight: hltips});
                    window.setTimeout(function() {showMessage(text, 3000, true);}, 5000);
                }
                showMessage(result.hitokoto, 5000, true);
            });
    }
}

async function chatGpt(){
    if(typeof userToken === 'undefined'){
        showMessage("想和我说点什么？记得插件需要先登录哦~", 5000, true);
        return;
    }
    let sign = userToken.sign;
    
    showMessage("想和我说点什么？", 5000, true);
    connection = new signalR.HubConnectionBuilder()
    .withUrl("https://api.live2dweb.com/chatmsg", { accessTokenFactory: () => sign })
    .configureLogging(signalR.LogLevel.Information)
    .build();
    try {
        if (connection.state != "Connected") {
            await connection.start();
            console.log("服务器已连接.");
            showChatControl();
            isConn = true;
            connection.on("reply", (text) => {
                gptMsg += text;
                console.log(gptMsg);
                showMessage(gptMsg, 5000);
            });
        } else {
            isConn = false;
            console.log("服务器已连接, 请勿重新连接", hubConnection);
        }
    } catch (err) {
        console.log("服务异常,正在重新连接", err);
        setTimeout(
            async () => {
                await chatGpt()
            }
            , 5000);
    }
}

function showChatControl(){
    $(".gptInput").addClass("show");
    $('#live2dSend').on("click",(e)=>{
        sendMsg();
    });
    $('#live2dChatText').on('keydown',(e)=>{
        var keyCode = e.keyCode;
        if(keyCode === 13){
            sendMsg();
        }
    });
}



async function sendMsg(){
    gptMsg = "";
    let text = $('#live2dChatText').val();
    try {
        await connection.invoke("Conversation", text);
    } catch (err) {
        console.error(err);
    }
}

function hideChatControl(){
    $(".gptInput").removeClass("show");
    $("#live2dSend").off("click");
    isConn = false;
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
    
    $('.waifu-tool .fui-photo').on("click",function(){
        showMessage(getRandText(result.waifu.screenshot_message), 5000, true);
        screenshot(live2d_settings.screenshotCaptureName,true);
    });
    
    $('.waifu-tool .fui-cross').on("click",function(){
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
        $.ajax({
            cache: live2d_settings.modelRandMode == 'switch' ? true : false,
            url: live2d_settings.modelAPI+live2d_settings.modelRandMode+'/?id='+live2d_settings.modelId,
            dataType: "json",
            success: function(result) {
                loadModel(result.model['id'],0,waifu_settings);
                var message = result.model['message'];
                $.each(waifu_tips.model_message, function(i,val) {if (i==result.model['id']) message = getRandText(val)});
                showMessage(message, 3000, true);
            }
        });
    }
    
    function loadRandTextures() {
        $.ajax({
            cache: live2d_settings.modelTexturesRandMode == 'switch' ? true : false,
            url: live2d_settings.modelAPI+live2d_settings.modelTexturesRandMode+'_textures/?id='+live2d_settings.modelId+'-'+live2d_settings.modelTexturesId,
            dataType: "json",
            success: function(result) {
                if (result.textures['id'] == 1 && (live2d_settings.modelTexturesId == 1 || live2d_settings.modelTexturesId == 0))
                    showMessage(waifu_tips.load_rand_textures[0], 3000, true);
                else showMessage(waifu_tips.load_rand_textures[1], 3000, true);
                loadModel(live2d_settings.modelId, result.textures['id'],waifu_settings);
            }
        });
    }
    
    /* 检测用户活动状态，并在空闲时显示一言 */
    if (live2d_settings.showHitokoto) {
        window.getActed = false; window.hitokotoTimer = 0; window.hitokotoInterval = false;
        $(document).on("mousemove",function(e){getActed = true;}).on("keydown",function(){getActed = true;});
        setInterval(function(){ if (!getActed) ifActed(); else elseActed(); }, 1000);
    }
    
    function ifActed() {
        if (!hitokotoInterval && !isConn) {
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

    $('.waifu-tool .fui-eye').on("click",function (){loadOtherModel()});
    $('.waifu-tool .fui-user').on("click",function (){loadRandTextures()});
    $('.waifu-tool .fui-chat').on("click",function (){showHitokoto()});
    $('.waifu-tool .fui-bot').on("click",(e)=>chatGpt(e));
}
