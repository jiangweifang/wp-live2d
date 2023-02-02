jQuery(function($){
    var userInfo = settings.userInfo;
    if(userInfo.errorCode == 200 && userInfo.userName){
        $("#btnLogin").hide();
        $(".lgoined").show();
        $("#labLogined").html(`${userInfo.userName} 已登录`);
    }else{
        $("#btnLogin").show();
        $(".lgoined").hide();
    }
    $("#btnLogin").on("click",function(){
        let ref = encodeURIComponent(`${settings.homeUrl}`);
        let width = 500;
        let height  = 680;
        let loginWin = window.open(`https://www.live2dweb.com/SingleLogin?referer=${ref}`,"Login",`toolbar=no,location=no,resizable=no, height=${height}, width=${width}`);
        let x = window.screen.availWidth - width;
        let y = window.screen.availHeight - height;
        try{
            loginWin.moveTo(x/2,y/2);
        }catch{ }
        let intervalId = setInterval(
            ()=>{
                if(loginWin.closed){
                    window.location.reload();
                    clearInterval(intervalId);
                }
            },
            500
        );
        
        console.log("已打开登陆窗口");
    });
})