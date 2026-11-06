import $ from 'jquery';
import { applyAdminExtensions } from '../lib/registry';
export class Live2dAdmin {

    protected ajaxurl: string;
    protected homeUrl: any;
    protected settings: any;
    constructor(settings: any, ajaxurl: string) {
        this.homeUrl = settings.homeUrl;
        this.settings = settings.settings;
        this.ajaxurl = ajaxurl;
    }

    public initControl() {
        const that = this;
        // 默认值
        $('.group').hide();
        $('.nav-tab-wrapper a').removeClass('nav-tab-active');
        //表单默认值
        var activetab = '';
        if (typeof (localStorage) !== 'undefined') {
            activetab = localStorage.getItem("activetab");
        }
        if (activetab !== '' && $(activetab).length) {
            $(activetab).fadeIn();
        } else {
            $('.group:first').fadeIn();
        }
        // 选项卡默认值
        if (activetab !== '' && $(activetab + '_btn').length) {
            $(activetab + '_btn').addClass('nav-tab-active');
        }
        else {
            $('#settings_btn').addClass('nav-tab-active');
        }

        // 切换按钮事件
        $('.nav-tab-wrapper a').on("click", function (evt) {
            $('.nav-tab-wrapper a').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            var clicked_group = $(this).attr('href');

            $('.group').hide();
            $(clicked_group).fadeIn();
            evt.preventDefault();

            if (typeof (localStorage) !== 'undefined') {
                localStorage.setItem("activetab", clicked_group);
            }
        });

        //----------------“添加/删除”按钮事件--------------

        $('input.addbtn').on("click", function () {
            var optName = $(this).attr('optname');
            var keyName = $(this).attr('keyname');
            var arrType = $(this).attr('arrtype');
            that.addMsgInput(optName, keyName, this, arrType);
        });


        $('input.delbtn').on("click", function () {
            var optName = $(this).attr('optname');
            var keyName = $(this).attr('keyname');
            var arrType = $(this).attr('arrtype');
            that.delMsgInput(optName, keyName, this, arrType);
        });

        $('input[type=range]').on('input propertychange', function () {
            let val = $(this).val();
            $(this).next('.result').html(`${val}`);
        });


        const update = () => {
            const selected = document.querySelector<HTMLInputElement>('input.apiType:checked');
            const raw = selected?.value ?? that.settings.apiType;
            const kind = raw === true || raw === 'true' || raw === '1' ? 'local' : raw === false ? 'remote' : String(raw ?? 'remote');
            if (applyAdminExtensions(kind)) return;
            const legacy = kind === 'local' || kind === 'remote';
            $('#modelAPI').closest('tr').toggle(kind === 'remote');
            $('#modelId, #modelTexturesId').closest('tr').toggle(legacy);
            if (kind === 'local') that.inputToSelectModel(that.settings.modelId);
            if (kind === 'remote') {
                for (const key of ['modelId', 'modelTexturesId']) {
                    if (document.getElementById(key)?.tagName.toLowerCase() === 'select')
                        that.selectToInput(key, 'live_2d_settings_option_name[' + key + ']', that.settings[key]);
                }
            }
        };
        $('input.apiType').on('change', update);
        update();
    }


    protected addMsgInput(optName, clsName, obj, typeName) {
        var that = this;
        var txtList = $('p.' + clsName);
        var indexNum = txtList.length
        var txtClone = txtList.last().clone();
        switch (typeName) {
            case 'Selector':
                txtClone.children('input.selector').attr('name', optName + '[' + clsName + '][' + indexNum + '][selector]')
                    .attr('id', clsName + '_' + indexNum + '_selector').val('');
                txtClone.children('input.text').attr('name', optName + '[' + clsName + '][' + indexNum + '][text]')
                    .attr('id', clsName + '_' + indexNum + '_text').val('');
                break;
            case 'Array':
                txtClone.children('input:eq(0)').attr('name', optName + '[' + clsName + '][' + indexNum + '][0]')
                    .attr('id', clsName + '_' + indexNum + '_0').val('');
                txtClone.children('input:eq(1)').attr('name', optName + '[' + clsName + '][' + indexNum + '][1]')
                    .attr('id', clsName + '_' + indexNum + '_1').val('');
                break;
            case 'List':
                txtClone.children('input.textArray').attr('name', optName + '[' + clsName + '][' + indexNum + ']')
                    .attr('id', clsName + '_' + indexNum).val('');
                break;
        }
        //删除按钮
        txtClone.children('input.delbtn').attr('name', clsName + '_delbtn' + indexNum)
            .attr('id', clsName + '_delbtn' + indexNum)
            .on('click', function () {
                that.delMsgInput(optName, clsName, this, typeName);
            });
        txtList.last().after(txtClone);
    }

    // 删除一个动态选项isSelector是如果是有选择器的动态选项，有选择器则传true
    // isArray是没有选择器的动态选项，例如日期选择（暂时不用）
    // 如果都不传递则为，支持多句随机类型的添加器。
    protected delMsgInput(optName, clsName, obj, typeName) {
        //如果没有其他组件就不能删除了
        var otherTxt = $(obj).parent().siblings('.' + clsName);
        if (otherTxt.length == 0) return;
        // 在删除前隐藏并重组组件
        $(obj).parent().fadeOut("fast", function () {
            var allTxt = $(this).siblings('.' + clsName);
            allTxt.each(function (i, e) {
                switch (typeName) {
                    case 'Selector':
                        $(e).children('.selector').attr('name', optName + '[' + clsName + '][' + i + '][selector]')
                            .attr('id', clsName + '_' + i + '_selector');
                        $(e).children('.text').attr('name', optName + '[' + clsName + '][' + i + '][text]')
                            .attr('id', clsName + '_' + i + '_text');
                        break;
                    case 'Array':
                        $(e).children('input:eq(0)').attr('name', optName + '[' + clsName + '][' + i + '][0]')
                            .attr('id', clsName + '_' + i + '_0');
                        $(e).children('input:eq(1)').attr('name', optName + '[' + clsName + '][' + i + '][1]')
                            .attr('id', clsName + '_' + i + '_1');
                        break;
                    case 'List':
                        $(e).children('input.textArray').attr('name', optName + '[' + clsName + '][' + i + ']')
                            .attr('id', clsName + '_' + i);
                        break;
                }
                $(e).children('input.delbtn').attr('id', clsName + '_delbtn' + i)
                    .attr('name', clsName + '_delbtn' + i);
            });
            // 执行删除
            this.remove();
        });
    }

    public selectToInput(elId: string, elName: string, val: number | string) {
        var input = $('<input>').attr('type', 'text').attr('id', elId).attr('name', elName);
        input.addClass("regular-text").val(val);
        $(`#${elId}`).replaceWith(input);
        $(`#${elId}`).off("change");
    }

    public inputToSelectModel(modelId: number | string) {
        const that = this;
        const select = $('<select>').attr('name', 'live_2d_settings_option_name[modelId]').attr('id', 'modelId');
        // 始终注入「请选择」占位项,即便已存值非合法 catalog 条目,也能回退到该项
        $('<option>').text('请选择').val('').appendTo(select);
        const nonce = (typeof (window as any).settings !== 'undefined' && (window as any).settings.nonce) ? (window as any).settings.nonce : '';
        $.post(this.ajaxurl, {
            'action': 'get_model_list',
            '_wpnonce': nonce,
        }, (list) => {
            list.forEach(val => {
                $('<option>').text(val.name).val(val.id).appendTo(select);
            });
            // 已保存值若不在 catalog 中,select.val() 会变 null,这里 fallback 回占位项
            const target = (modelId === undefined || modelId === null) ? '' : String(modelId);
            $(`#modelId`).val(target);
            if ($(`#modelId`).val() === null) {
                $(`#modelId`).val('');
            }
        });
        $(`#modelId`).replaceWith(select);
        $(`#modelId`).on('change', (e: any) => {
            that.changeTextures(e.target.value);
        });
    }

    public inputToSelectTexture(modelId: number | string, textureId: number | string) {
        const select = $('<select>').attr('name', 'live_2d_settings_option_name[modelTexturesId]').attr('id', 'modelTexturesId');
        $(`#modelTexturesId`).replaceWith(select);
        this.changeTextures(modelId, () => {
            const target = (textureId === undefined || textureId === null) ? '' : String(textureId);
            $(`#modelTexturesId`).val(target);
            if ($(`#modelTexturesId`).val() === null) {
                $(`#modelTexturesId`).val('');
            }
        });
    }

    public changeTextures(modelId: number | string, callback?: () => void) {
        const select = $("select#modelTexturesId");
        if (!select.length) return;
        select.empty();
        // 皮肤下拉框首项也是占位「请选择」,与模型下拉框保持一致
        select.append($('<option>').text('请选择').val(''));
        // modelId 为空(用户选了「请选择」)时,皮肤列表保持只有占位项,不再发请求
        const id = (modelId === undefined || modelId === null) ? '' : String(modelId);
        if (id === '') {
            if (typeof callback == "function") callback();
            return;
        }
        const nonce = (typeof (window as any).settings !== 'undefined' && (window as any).settings.nonce) ? (window as any).settings.nonce : '';
        $.post(this.ajaxurl, {
            'action': 'get_texture_list',
            'modelId': id,
            '_wpnonce': nonce,
        }, (list) => {
            list.forEach(val => {
                select.append($('<option>').text(val.name).val(val.id));
            });
            if (typeof callback == "function") callback();
        });
    }

    public getMotions(group: string) {

    }
}
