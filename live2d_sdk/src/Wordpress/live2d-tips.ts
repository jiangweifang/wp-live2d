import { HubConnection, HubConnectionBuilder, LogLevel, HttpError } from '@microsoft/signalr';
import moment from 'moment';
import { Version } from 'version-parser';
import { loadv1, screenshotV1 } from '../v1/main'
import interact from 'interactjs';
import { FailedToNegotiateWithServerError } from '@microsoft/signalr/dist/esm/Errors';
import { marked } from 'marked';
import { ApiUrlType, classifyModelApi, trimTrailingSlash } from '../lib/modelApiUrl';

declare global {
    interface String {
        render(context: any): string;
    }
}
import { sanitizeMessageHtml, navigateToolbar } from '../lib/security';
export class Live2dTips {
    protected _aiProvider: string;
    protected _aiChatUrl: string;
    protected _aiWpNonce: string;
    protected _chatBound = false;
    protected _chatPending = false;
    protected _aiAbort: AbortController | undefined;

    protected readonly _lwcSignalR = "https://api.live2dweb.com";
    protected readonly _localModelPath: string;
    protected readonly _live2dSettings: any;
    protected readonly _userToken: any;
    protected readonly _live2dTips: any;
    protected readonly _currentPage: any;
    protected readonly _unitType = 'px';
    /** 定义highlight标记 */
    protected hltips = 'color:';
    protected _tipsIsShow: boolean;
    protected _getActed: boolean = false;
    protected hitokotoInterval: boolean = false;
    protected _hitokotoTimer: NodeJS.Timeout | undefined;
    protected connection: HubConnection | undefined;
    //以下是live2d元素的声明
    protected waifuDiv: HTMLDivElement;
    protected tipsDiv: HTMLDivElement;
    protected live2dCanvas: HTMLCanvasElement;
    protected toolDiv: HTMLDivElement;
    protected btnHome: HTMLSpanElement;
    protected btnChat: HTMLSpanElement;
    protected btnBot: HTMLSpanElement;
    protected btnEye: HTMLSpanElement;
    protected btnUser: HTMLSpanElement;
    protected btnPhoto: HTMLSpanElement;
    protected btnInfoCircle: HTMLSpanElement;
    protected btnCross: HTMLSpanElement;
    protected inputGroup: HTMLDivElement;
    protected txtChat: HTMLInputElement;
    protected btnSend: HTMLButtonElement;
    protected btnSendClose: HTMLElement;
    protected tipsTimeout: NodeJS.Timeout;
    protected ver = new Version('1.0.0');

    protected chatServiceConn: boolean;
    protected docNowWidth: number;
constructor(settings: any) {
    this._live2dSettings = settings.settings;
    this._userToken = settings.userInfo;
    this._live2dTips = settings.waifuTips;
    this._localModelPath = settings.localPath;
    this._currentPage = settings.currentPage;
        this._aiProvider = settings.aiProvider ?? settings.settings.aiProvider ?? 'live2dweb';
        this._aiChatUrl = settings.aiChatUrl ?? '';
        this._aiWpNonce = settings.aiWpNonce ?? '';
    this._tipsIsShow = false;
    this.waifuDiv = document.querySelector<HTMLDivElement>(".waifu");
    this.tipsDiv = document.querySelector<HTMLDivElement>(".waifu-tips");
    this.live2dCanvas = document.querySelector<HTMLCanvasElement>("#live2d");
    this.toolDiv = document.querySelector<HTMLDivElement>(".waifu-tool");
    this.btnHome = document.querySelector<HTMLSpanElement>(".waifu-tool .fui-home");
    this.btnChat = document.querySelector<HTMLSpanElement>(".waifu-tool .fui-chat");
    this.btnBot = document.querySelector<HTMLSpanElement>(".waifu-tool .fui-bot");
    this.btnEye = document.querySelector<HTMLSpanElement>(".waifu-tool .fui-eye");
    this.btnUser = document.querySelector<HTMLSpanElement>(".waifu-tool .fui-user");
    this.btnPhoto = document.querySelector<HTMLSpanElement>(".waifu-tool .fui-photo");
    this.btnInfoCircle = document.querySelector<HTMLSpanElement>(".waifu-tool .fui-info-circle");
    this.btnCross = document.querySelector<HTMLSpanElement>(".waifu-tool .fui-cross");
    this.inputGroup = document.querySelector<HTMLDivElement>(".waifu .gptInput");
    this.txtChat = document.querySelector<HTMLInputElement>("#live2dChatText");
    this.btnSend = document.querySelector<HTMLButtonElement>("#live2dSend");
    this.btnSendClose = document.querySelector<HTMLElement>("#live2dSendClose");
    this.chatServiceConn = false;
    this.docNowWidth = window.innerWidth;
}


    public initTips() {
        const that = this;
        this.tipsDiv.style.width = this._live2dSettings.waifuTipsSize['width'] + this._unitType;  //宽度
        this.tipsDiv.style.minHeight = this._live2dSettings.waifuTipsSize['height'] + this._unitType;//高度
        this.tipsDiv.style.bottom = (this._live2dSettings.waifuSize.height + this._live2dSettings.waifuTipTop) + this._unitType;//上方位置可以是负数
        this.tipsDiv.style.fontSize = this._live2dSettings.waifuFontSize + this._unitType; //字号
        this.tipsDiv.style.border = "1px solid " + this._live2dSettings.waifuBorderColor;//边框颜色 固定值是1px实心线条
        this.tipsDiv.style.backgroundColor = this._live2dSettings.waifuTipsColor;//背景色
        this.tipsDiv.style.boxShadow = "0 3px 15px 2px " + this._live2dSettings.waifuShadowColor;//影子的颜色 有4个位置默认值
        this.tipsDiv.style.color = this._live2dSettings.waifuFontsColor; //字体颜色

        const toolSpan = document.querySelectorAll('.waifu-tool span');
        toolSpan.forEach((span: HTMLSpanElement) => {
            span.addEventListener("mouseover", function () {
                span.style.color = that._live2dSettings.waifuToolHover;
            });

            span.addEventListener("mouseout", function () {
                span.style.color = that._live2dSettings.waifuToolColor;
            });

            span.style.color = that._live2dSettings.waifuToolColor;
            span.style.lineHeight = that._live2dSettings.waifuToolLine + this._unitType;
        });
        //--------------只是分割线而已-------------
        this.toolDiv.style.fontSize = this._live2dSettings.waifuToolFont + this._unitType;
        this.toolDiv.style.top = this._live2dSettings.waifuToolTop + this._unitType;

        this.hltips += this._live2dSettings.waifuHighlightColor + ';';//定义highlight标记并赋值，因为是一个css所以必须;结尾

        if (this._live2dSettings.waifuEdgeSide === 'left') {
            this.waifuDiv.style.left = this._live2dSettings.waifuEdgeSize + this._unitType;
        } else if (this._live2dSettings.waifuEdgeSide === 'right') {
            //this.waifuDiv.style.left = (that.docNowWidth - (Number(that._live2dSettings.waifuSize.width) + that._live2dSettings.waifuEdgeSize)) + that._unitType;
            this.waifuDiv.style.right = this._live2dSettings.waifuEdgeSize + this._unitType;
        }
        const minWidth = Number(this._live2dSettings.waifuMinWidth);
        if (minWidth != 0) {
            if (window.innerWidth <= minWidth) {
                this.waifuDiv.style.display = "none";
                return;
            } else {
                this.waifuDiv.style.display = "block";
            }
        }
        window.addEventListener("resize", function () {
            that.waifuResize();
        });

        interact(that.waifuDiv).draggable({
            // enable inertial throwing
            inertia: true,
            // keep the element within the area of it's parent
            modifiers: [
                interact.modifiers.restrictRect({
                    restriction: 'window',
                    endOnly: true
                })
            ],
            listeners: {
                // call this function on every dragmove event
                move: dragMoveListener,
                //松开鼠标的时候
                end(event) {
                    const target = event.target;
                    if (that._live2dSettings.waifuDraggableRevert) {
                        // translate the element
                        target.style.transform = `translate(0, 0)`;
                        target.style.transition = 'all .3s ease-in-out';
                        target.setAttribute('data-x', 0);
                        target.setAttribute('data-y', 0);
                    }
                }
            }
        });

        function dragMoveListener(event: any) {
            const target = event.target;
            target.style.transition = 'none';
            // keep the dragged position in the data-x/data-y attributes
            const x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx;
            const y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy;

            switch (that._live2dSettings.waifuDraggable) {
                case 'axis-x':
                    // translate the element
                    target.style.transform = `translate(${x + that._unitType}, 0)`;

                    // update the posiion attributes
                    target.setAttribute('data-x', x);
                    break;
                case 'unlimited':
                    // translate the element
                    target.style.transform = `translate(${x + that._unitType}, ${y + that._unitType})`;

                    // update the posiion attributes
                    target.setAttribute('data-x', x);
                    target.setAttribute('data-y', y);
                    break;
                default:
                    // translate the element
                    target.style.transform = `translate(0, 0)`;

                    // update the posiion attributes
                    target.setAttribute('data-x', 0);
                    target.setAttribute('data-y', 0);
                    break;
            }
        }

        this.btnHome.addEventListener("click", () => {
            navigateToolbar(that._live2dSettings.homePageUrl);
        });

        this.btnInfoCircle.addEventListener("click", () => {
            navigateToolbar(that._live2dSettings.aboutPageUrl, true);
        });

        this.loadTipsMessage(this._live2dTips);
        this.showWelcomeMessage(this._live2dTips);

        if (!this._live2dSettings.showToolMenu) this.toolDiv.style.display = 'none';
        if (!this._live2dSettings.canCloseLive2d) this.btnCross.style.display = 'none';
        if (!this._live2dSettings.canSwitchModel) this.btnEye.style.display = 'none';
        if (!this._live2dSettings.canSwitchTextures) this.btnUser.style.display = 'none';
        if (!this._live2dSettings.canSwitchHitokoto) this.btnChat.style.display = 'none';
        if (!this._live2dSettings.canTakeScreenshot) this.btnPhoto.style.display = 'none';
        if (!this._live2dSettings.canTurnToHomePage) this.btnHome.style.display = 'none';
        if (!this._live2dSettings.canTurnToAboutPage) this.btnInfoCircle.style.display = 'none';
        if (!this._live2dSettings.isBotButton) this.btnBot.style.display = 'none';

        this.btnEye.addEventListener("click", () => { that.loadOtherModel() });
        this.btnUser.addEventListener("click", () => { that.loadRandTextures() });
        this.btnChat.addEventListener("click", () => { that.doShowHitokoto() });
        this.btnBot.addEventListener("click", () => that.chatGpt());

        document.addEventListener("mousemove", (e) => {
            this._getActed = true;
        });
        document.addEventListener("keydown", (e) => {
            this._getActed = true;
        });

        //每秒检测活跃状态
        setInterval(() => {
            /** 
             * 如果不活跃则进入待机状态
             * 默认为活跃, 会进入elseActed 
             * 进入后直接变为不活跃
             * */
            if (!this._getActed)
                this.ifActed();
            else
                this.elseActed();
        }, 1000);
    }
protected ifActed() {
    if (!this.hitokotoInterval && this._live2dSettings.hitokoto_delay && this._live2dSettings.hitokoto_delay > 0) {
        this.hitokotoInterval = true;
        this._hitokotoTimer = setInterval(async () => {
            await this.showHitokotoActed();
        }, this._live2dSettings.hitokoto_delay * 1000);
    }
}

    protected elseActed() { this._getActed = this.hitokotoInterval = false; clearInterval(this._hitokotoTimer); }


    protected async showHitokotoActed() {
        if (document.visibilityState == 'visible') {
            this.doShowHitokoto();
        }
        if (this.connection && this.connection.state == 'Connected') {
            this.chatServiceConn = false;
            await this.connection.stop();
            this.hideChatControl();
        }
    }

    protected async doShowHitokoto() {
        const that = this;
        let result: any;
        switch (this._live2dSettings.hitokotoAPI) {
            case 'fghrsh.net':
                result = await fetch('https://api.fghrsh.net/hitokoto/rand/?encode=jsc&uid=3335', { method: 'GET' }).then(rsp => rsp.json());
                if (result.source) {
                    var text = "";
                    that._live2dTips.hitokoto_api_message.forEach((val: Array<any>) => {
                        if (val[0] === 'fghrsh.net') {
                            text = val[1];
                        }
                    });
                    text = text.render({ source: result.source, date: result.date, highlight: that.hltips });
                    that.showMessage(result.hitokoto, 5000);
                    await new Promise((resolve) => setTimeout(resolve, 5000));
                    that.showMessage(text, 3000);
                }
                break;
            case 'jinrishici.com':
                result = await fetch('https://v2.jinrishici.com/one.json', { method: 'GET' }).then(rsp => rsp.json());
                if (result.data.origin.title) {
                    var text = "";
                    that._live2dTips.hitokoto_api_message.forEach((val: Array<any>) => {
                        if (val[0] === 'jinrishici.com') {
                            text = val[1];
                        }
                    });
                    text = text.render({ title: result.data.origin.title, dynasty: result.data.origin.dynasty, author: result.data.origin.author, highlight: that.hltips });
                    that.showMessage(result.data.content, 5000);
                    await new Promise((resolve) => setTimeout(resolve, 5000));
                    that.showMessage(text, 3000);
                }
                break;
            default:
                result = await fetch('https://v1.hitokoto.cn', { method: 'GET' }).then(rsp => rsp.json());
                if (result.from) {
                    var text = "";
                    that._live2dTips.hitokoto_api_message.forEach((val: Array<any>) => {
                        if (val[0] === 'hitokoto.cn') {
                            text = val[1];
                        }
                    });
                    text = text.render({ source: result.from, creator: result.creator, highlight: that.hltips });
                    that.showMessage(result.hitokoto, 5000);
                    await new Promise((resolve) => setTimeout(resolve, 5000));
                    that.showMessage(text, 3000);
                }
        }
    }

    protected async chatGpt() {
        if (this._aiProvider === 'wp-builtin') { this.showChatControl(); return; }
        if (!this._userToken?.sign) { this.showMessage('请站长先配置云端对话账号。', 5000); return; }
        if (typeof this._userToken === 'undefined') {
            this.showMessage("想和我说点什么？记得插件需要先登录哦~", 5000);
            return;
        }
        const sign = this._userToken.sign;
        this.showMessage("想和我说点什么？", 5000);
        this.connection = new HubConnectionBuilder()
            .withUrl(`${this._lwcSignalR}/chatmsg`, { accessTokenFactory: () => sign })
            .configureLogging(LogLevel.Debug)
            .build();
        if (this.connection.state != "Connected" && !this.chatServiceConn) {
            try {
                this.chatServiceConn = true;
                await this.connection.start();
                console.log("服务器已连接.");
            } catch (err) {
                this.chatServiceConn = false;
                if (err instanceof FailedToNegotiateWithServerError) {
                    console.warn("连接失败, 如果没有付费请先付费后使用", err.message);
                }
                return;
            }
            this.showChatControl();
            this.connection.on("reply", (text) => {
                this.txtChat.value = "";
                this.showMessage(text, 0);
            });
        } else {
            console.log("服务器已连接, 请勿重新连接");
        }
    }
    protected showChatControl() {
        this.inputGroup.classList.add('show');
        if (this._chatBound) return;
        this._chatBound = true;
        this.btnSend.addEventListener('click', () => { void this.sendMsg(); });
        this.btnSendClose?.addEventListener('click', () => { void this.sendClose(); });
        this.txtChat.addEventListener('keyup', event => { if (event.key === 'Enter') void this.sendMsg(); });
    }

    protected hideChatControl() { this.inputGroup.classList.remove('show'); }


    protected async sendMsg() {
        if (this._aiProvider === 'wp-builtin') { await this.sendBuiltinMessage(); return; }
        if (this.connection.state != "Connected") {
            await this.connection.start();
        }
        const msgChat = this.txtChat.value;
        this.txtChat.value = "";
        try {
            await this.connection.invoke("Conversation", msgChat, this._currentPage);
        } catch (err) {
            console.error(err);
        }
    }
    protected async sendClose() {
        if (this._aiProvider === 'wp-builtin') { this._aiAbort?.abort(); this.hideChatControl(); return; }
        if (this.connection && this.connection.state == 'Connected') {
            this.chatServiceConn = false;
            await this.connection.stop();
            this.hideChatControl();
        }
    }
    public async initModel(modelId: number | string, modelTexturesId: number | string) {
        modelId = sessionStorage.getItem('modelId') ?? modelId;
        modelTexturesId = sessionStorage.getItem('modelTexturesId') ?? modelTexturesId;
        const settings = { ...this._live2dSettings, userInfo: this._userToken ?? {} };
        this.live2dCanvas.width = settings.waifuSize.width;
        this.live2dCanvas.height = settings.waifuSize.height;
        const raw = String(settings.modelAPI ?? '');
        const kind = classifyModelApi(raw);
        let url = trimTrailingSlash(raw);
        const headers: Record<string, string> = {};
        if (kind === ApiUrlType.Live2dWeb) {
            if (!this._userToken?.sign) throw new Error('The selected external model service requires an account.');
            headers.Authorization = 'Bearer ' + this._userToken.sign;
            url += '?id=' + encodeURIComponent(modelId) + '&tid=' + encodeURIComponent(modelTexturesId);
            if (settings.apiType) url += '&apiUrl=' + encodeURIComponent(this._localModelPath);
        } else if (kind !== ApiUrlType.JsonFile) {
            url += '/get/?id=' + encodeURIComponent(modelId) + '-' + encodeURIComponent(modelTexturesId);
        }
        const response = await fetch(url, { headers });
        if (!response.ok) throw new Error('Model request failed: ' + response.status);
        const manifest = await response.json();
        const major = Number.parseInt(String(manifest.Version ?? manifest.version ?? '1'), 10);
        if (major >= 3 || manifest.FileReferences) {
            this.live2dCanvas.hidden = true;
            console.warn('[Live2D] The configured model needs a different renderer. It has not been replaced.');
            return;
        }
        this.ver = new Version('1.0.0');
        loadv1('live2d', url, settings, manifest);
    }
    protected captureScreenshot() {
        screenshotV1(this._live2dSettings.screenshotCaptureName, true);
    }


    public showMessage(text: string | string[], timeout: number = 5000) {
        const theText = this.getRandText(text);
        const that = this;
        this.tipsDiv.innerHTML = sanitizeMessageHtml(marked.parse(theText, { async: false }));
        that._tipsIsShow = this.tipsDiv.classList.contains('show');
        if (!that._tipsIsShow) {
            this.tipsDiv.classList.add("show");
        }
        that.hideMessage(timeout);
    }

    public hideMessage(timeout: number = 5000) {
        const that = this;
        if (timeout > 0) {
            clearTimeout(that.tipsTimeout);
            that.tipsTimeout = setTimeout(() => {
                that.tipsDiv.classList.remove("show");
                that._tipsIsShow = false;
            }, timeout);
        } else {
            const newDiv = document.createElement('div');
            newDiv.className = 'waifu-tips-close';
            const tipsClose = document.createElement('i');
            tipsClose.className = 'fa-solid fa-circle-xmark';
            newDiv.onclick = () => {
                that.tipsDiv.classList.remove("show");
                that._tipsIsShow = false;
            }
            newDiv.appendChild(tipsClose);
            this.tipsDiv.appendChild(newDiv);
        }
    }

    protected loadTipsMessage(result: any) {
        const that = this;
        result.mouseover_msg.forEach((val: any, index: number) => {
            const msgAnySelect = document.querySelector(val.selector) as HTMLElement;
            if (msgAnySelect) {
                msgAnySelect.addEventListener('mouseover', () => {
                    var text = that.getRandText(val.text);
                    text = text.render({ text: msgAnySelect.textContent, highlight: that.hltips });
                    if (!that.chatServiceConn) {
                        that.showMessage(text, 3000);
                    }
                })
            }
        });

        const click_selector = document.querySelector(result.click_selector) as HTMLElement;
        click_selector?.addEventListener('click', () => {
            var text = that.getRandText(result.click_msg);
            text = text.render({ text: click_selector.textContent, highlight: that.hltips });
            that.showMessage(text, 3000);
        });

        result.seasons_msg.forEach((val: any, index: number) => {
            const now = moment();
            const after = moment(val[0].split('-')[0], 'MM/DD').startOf('day');
            const before = moment(val[0].split('-')[1] || after, 'MM/DD').endOf('day');
            if (now.isBetween(after, before)) {
                var text = that.getRandText(val[1]);
                text = text.render({ year: now.format('YYYY'), highlight: that.hltips });
                that.showMessage(text, 6000);
            }
        });

        if (this._live2dSettings.showCopyMessage) {
            document.addEventListener('copy', () => {
                that.showMessage(that.getRandText(result.copy_message), 5000);
            });
        }
        this.btnPhoto.addEventListener("click", () => {
            that.showMessage(that.getRandText(result.screenshot_message), 5000);
            that.captureScreenshot();

        })

        this.btnCross.addEventListener("click", () => {
            that.showMessage(that.getRandText(result.hidden_message), 1300);
            window.setTimeout(function () {
                that.waifuDiv.style.display = 'none';
            }, 1300);
        });
    }

    protected getRandText(text: string | string[]): string {
        if (Array.isArray(text)) {
            return text[Math.floor(Math.random() * text.length + 1) - 1];
        } else {
            return text;
        }
    }

    protected waifuResize() {
        const minWidth: number = this._live2dSettings.waifuMinWidth;
        if (window.innerWidth <= minWidth) {
            this.waifuDiv.style.display = "none";
        } else {
            this.waifuDiv.style.display = "block";
        }
        this.docNowWidth = window.innerWidth;
        if (this._live2dSettings.waifuEdgeSide == 'right') {
            this.waifuDiv.style.left = (this.docNowWidth - (Number(this._live2dSettings.waifuSize.width) + this._live2dSettings.waifuEdgeSize)) + this._unitType;
        }
    }
    protected showWelcomeMessage(result: any) {
        var text: string;
        if (window.location.href == this._live2dSettings.homePageUrl) {
            var now = (new Date()).getHours();
            if (now > 23 || now <= 5) text = this.getRandText(result.hour_tips['t23-5']);
            else if (now > 5 && now <= 7) text = this.getRandText(result.hour_tips['t5-7']);
            else if (now > 7 && now <= 11) text = this.getRandText(result.hour_tips['t7-11']);
            else if (now > 11 && now <= 14) text = this.getRandText(result.hour_tips['t11-14']);
            else if (now > 14 && now <= 17) text = this.getRandText(result.hour_tips['t14-17']);
            else if (now > 17 && now <= 19) text = this.getRandText(result.hour_tips['t17-19']);
            else if (now > 19 && now <= 21) text = this.getRandText(result.hour_tips['t19-21']);
            else if (now > 21 && now <= 23) text = this.getRandText(result.hour_tips['t21-23']);
            else text = this.getRandText(result.hour_tips['default']);
        } else {
            const referrer_message: Array<any> = result.referrer_message;
            // 用来隔开网站标题中的 “-” 
            const titleStr = document.title.split(' – ')[0];
            // 在document.referrer不为空的时候执行
            if (document.referrer !== '') {
                try {
                    var referrer = document.createElement('a');
                    referrer.href = document.referrer;
                    if (window.location.hostname == referrer.hostname) {
                        //如果是本站点跳转
                        const match = referrer_message.find(([key, value]) => key === "localhost");
                        text = match[1];
                    } else {
                        //外部站点跳转至此
                        const domain = referrer.hostname.split('.')[1];
                        const match = referrer_message.find(([key, value]) => key === domain);
                        if (match) {
                            text = match[1];
                        } else {
                            text = referrer_message.find(([key, value]) => key === "default")[1];
                        }
                    }
                    text = text.render({ title: titleStr, highlight: this.hltips });
                } catch (err) {
                    console.log('It is not important Exception ' + err)
                }
            } else {
                const match = referrer_message.find(([key, value]) => key === "none");
                text = match[1];
                text = text.render({ title: titleStr, highlight: this.hltips });
            }
        }
        this.showMessage(text, 6000);
    }

    protected loadOtherModel() {
        const that = this;
        let settings = Object.assign({}, this._live2dSettings);
        const modelId = sessionStorage.getItem("modelId") ?? settings.modelId;
        const rawModelAPI: string = settings.modelAPI ?? '';
        let apiUrl = trimTrailingSlash(rawModelAPI);
        switch (classifyModelApi(rawModelAPI)) {
            case ApiUrlType.JsonFile:
                console.warn("直接指向JSON文件不支持换装");
                break;
            case ApiUrlType.Live2dWeb:
                apiUrl = `${apiUrl}/NextModel/?id=${modelId}&type=${settings.modelRandMode == 'rand' ? 0 : 1}&isWorkshop=${settings.apiType}`;
                fetch(apiUrl, {
                    method: "GET",
                    headers: {
                        "Authorization": `Bearer ${that._userToken.sign}`
                    }
                }).then(rsp => rsp.json())
                    .then(result => {
                        that.initModel(result.ModelId, result.TextureId);
                        that.showMessage(result.Message, 3000);
                        sessionStorage.setItem("modelId", `${result.ModelId}`);
                        sessionStorage.setItem("modelTexturesId", `${result.TextureId}`);
                    }).catch(error => {
                        console.log("认证失败", error);
                    });
                break;
            case ApiUrlType.Other:
                apiUrl = `${apiUrl}/${settings.modelRandMode}/?id=${modelId}`;
                fetch(apiUrl, {
                    method: "GET",
                }).then(rsp => rsp.json())
                    .then(result => {
                        that.initModel(result.model['id'], 0);
                        that.showMessage(result.model['message'], 3000);
                        sessionStorage.setItem("modelId", `${result.model['id']}`);
                        sessionStorage.setItem("modelTexturesId", `0`);
                    }).catch(error => {
                        console.log("获取失败", error);
                    });
                break;

        }
    }

    protected loadRandTextures() {
        const that = this;
        let settings = Object.assign({}, this._live2dSettings);
        const modelId = sessionStorage.getItem("modelId") ?? settings.modelId;
        const texturesId = sessionStorage.getItem("modelTexturesId") ?? settings.modelTexturesId;
        const rawModelAPI: string = settings.modelAPI ?? '';
        let apiUrl = trimTrailingSlash(rawModelAPI);
        switch (classifyModelApi(rawModelAPI)) {
            case ApiUrlType.JsonFile:
                console.log("直接指向JSON文件不支持换装");
                break;
            case ApiUrlType.Live2dWeb:
                apiUrl = `${apiUrl}/NextModel/?id=${modelId}&tid=${texturesId}&type=${settings.modelRandMode == 'rand' ? 2 : 3}&isWorkshop=${settings.apiType}`;
                fetch(apiUrl, {
                    method: "GET",
                    headers: {
                        "Authorization": `Bearer ${that._userToken.sign}`
                    }
                }).then(rsp => rsp.json())
                    .then(result => {
                        if (result.TextureId !== texturesId) {
                            that.showMessage(that._live2dTips.load_rand_textures[1]);
                        } else {
                            that.showMessage(that._live2dTips.load_rand_textures[0]);
                        }
                        that.initModel(result.ModelId, result.TextureId);
                        sessionStorage.setItem("modelId", `${modelId}`);
                        sessionStorage.setItem("modelTexturesId", `${result.TextureId}`);
                    }).catch(error => {
                        console.log("认证失败", error);
                    });
                break;
            case ApiUrlType.Other:
                apiUrl = `${apiUrl}/${settings.modelRandMode}_textures/?id=${modelId}-${texturesId}`;
                fetch(apiUrl, {
                    method: "GET",
                }).then(rsp => rsp.json())
                    .then(result => {
                        if (result.textures['id'] !== texturesId) {
                            that.showMessage(that._live2dTips.load_rand_textures[1]);
                        }
                        else {
                            that.showMessage(that._live2dTips.load_rand_textures[0]);
                        }
                        that.initModel(modelId, result.textures['id']);
                        sessionStorage.setItem("modelId", `${modelId}`);
                        sessionStorage.setItem("modelTexturesId", `${result.textures['id']}`);
                    }).catch(error => {
                        console.log("获取失败", error);
                    });
                break;
        }
    }
    protected async sendBuiltinMessage() {
        const message = this.txtChat.value.trim();
        if (!message || this._chatPending) return;
        if (!this._aiChatUrl || !this._aiWpNonce) {
            this.showMessage('AI 后端未正确配置。', 5000);
            return;
        }
        this._chatPending = true;
        this.btnSend.disabled = true;
        this.txtChat.value = '';
        let reader: ReadableStreamDefaultReader<Uint8Array> | undefined;
        const controller = new AbortController();
        this._aiAbort = controller;
        this.showMessage('正在思考…', 0);
        try {
            const response = await fetch(this._aiChatUrl, {
                method: 'POST', credentials: 'same-origin', signal: controller.signal,
                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': this._aiWpNonce },
                body: JSON.stringify({ message, page: this._currentPage })
            });
            if (!response.ok) {
                const error: unknown = await response.json();
                const detail = error && typeof error === 'object' && 'message' in error && typeof error.message === 'string'
                    ? error.message : 'AI request failed: ' + response.status;
                throw new Error(detail);
            }
            if (!response.body) throw new Error('AI response has no stream.');
            reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '', reply = '', complete = false;
            const frame = (value: string) => {
                let event = '', data: string[] = [];
                for (const line of value.split('\n')) {
                    if (line.startsWith('event:')) event = line.slice(6).trim();
                    if (line.startsWith('data:')) data.push(line.slice(5).replace(/^ /, ''));
                }
                const payload = data.join('\n');
                if (event === 'token') {
                    reply += payload.replace(/\\n/g, '\n');
                    this.showMessage(reply, 0);
                } else if (event === 'done') {
                    complete = true;
                } else if (event === 'error') {
                    let error: unknown;
                    try { error = JSON.parse(payload); }
                    catch (parseError) {
                        if (!(parseError instanceof SyntaxError)) throw parseError;
                        error = { message: payload };
                    }
                    throw new Error(error && typeof error === 'object' && 'message' in error && typeof error.message === 'string'
                        ? error.message : 'AI generation failed.');
                }
            };
            for (;;) {
                const chunk = await reader.read();
                buffer = (buffer + decoder.decode(chunk.value, { stream: !chunk.done })).replace(/\r\n/g, '\n');
                let boundary: number;
                while ((boundary = buffer.indexOf('\n\n')) >= 0) {
                    frame(buffer.slice(0, boundary));
                    buffer = buffer.slice(boundary + 2);
                }
                if (chunk.done) break;
            }
            if (buffer.trim()) frame(buffer);
            if (!complete) throw new Error('AI stream ended before completion.');
            this.showMessage(reply, 10000);
        } catch (error) {
            if (controller.signal.aborted) return;
            console.error('[Live2D] AI request failed.', error);
            this.showMessage(error instanceof Error ? error.message : 'AI request failed.', 5000);
        } finally {
            if (reader) {
                try { await reader.cancel(); }
                catch (error) { if (!controller.signal.aborted) console.error('[Live2D] AI stream cleanup failed.', error); }
            }
            this._aiAbort = undefined;
            this._chatPending = false;
            this.btnSend.disabled = false;
        }
    }

}


String.prototype.render = function (context: any): string {
    const tokenReg = /(\\)?\{([^\{\}\\]+)(\\)?\}/g;

    return this.replace(tokenReg, function (word, slash1, token, slash2) {
        if (slash1 || slash2) { return word.replace('\\', ''); }

        const variables = token.replace(/\s/g, '').split('.');
        let currentObject = context;
        let i, length, variable;

        for (i = 0, length = variables.length; i < length; ++i) {
            variable = variables[i];
            currentObject = currentObject[variable];
            if (!currentObject) return '';
        }
        return currentObject;
    });
};