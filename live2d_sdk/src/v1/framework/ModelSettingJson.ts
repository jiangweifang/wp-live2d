import { Live2DFramework } from "./Live2DFramework"
import { PlatformManager } from '../PlatformManager'
export class ModelSettingJson {
    public constructor(json = undefined) {
        this.NAME = "name";
        this.ID = "id";
        this.MODEL = "model";
        this.TEXTURES = "textures";
        this.HIT_AREAS = "hit_areas";
        this.PHYSICS = "physics";
        this.POSE = "pose";
        this.EXPRESSIONS = "expressions";
        this.MOTION_GROUPS = "motions";
        this.SOUND = "sound";
        this.FADE_IN = "fade_in";
        this.FADE_OUT = "fade_out";
        this.LAYOUT = "layout";
        this.HIT_AREAS_CUSTOM = "hit_areas_custom";
        this.INIT_PARAM = "init_param";
        this.INIT_PARTS_VISIBLE = "init_parts_visible";
        this.VALUE = "val";
        this.FILE = "file";

        this.json = json;
    }

    public async loadModelSetting(path: string, token?: string) {
        var thisRef = this;
        if (this.json) {
            return this.json;
        } else {
            //这里是一个单例模式 不能对它实例化
            var pm = Live2DFramework.getPlatformManager() as PlatformManager;
            const buf = await pm.loadBytes(path, token);
            var str = String.fromCharCode.apply(null, new Uint8Array(buf));
            thisRef.json = JSON.parse(str);
            return thisRef.json;
        }
    }

    public getTextureFile(n) {
        if (this.json[this.TEXTURES] == null || this.json[this.TEXTURES][n] == null)
            return null;

        return this.json[this.TEXTURES][n];
    }

    public getModelFile() {
        return this.json[this.MODEL];
    }

    public getTextureNum() {
        if (this.json[this.TEXTURES] == null) return 0;

        return this.json[this.TEXTURES].length;
    }


    public getHitAreaNum() {
        // this.json 在 loadModelSetting 完成前是 undefined。tapEvent 在模型未加载完时
        // 触发会一路走到这里,沿用本类既有的 `== null` 守卫风格挡掉。
        if (this.json == null || this.json[this.HIT_AREAS] == null)
            return 0;

        return this.json[this.HIT_AREAS].length;
    }


    public getHitAreaID(n) {
        if (this.json[this.HIT_AREAS] == null ||
            this.json[this.HIT_AREAS][n] == null)
            return null;

        return this.json[this.HIT_AREAS][n][this.ID];
    }


    public getHitAreaName(n) {
        if (this.json[this.HIT_AREAS] == null ||
            this.json[this.HIT_AREAS][n] == null)
            return null;

        return this.json[this.HIT_AREAS][n][this.NAME];
    }


    public getPhysicsFile() {
        return this.json[this.PHYSICS];
    }


    public getPoseFile() {
        return this.json[this.POSE];
    }


    public getExpressionNum() {
        return (this.json[this.EXPRESSIONS] == null) ? 0 : this.json[this.EXPRESSIONS].length;
    }


    public getExpressionFile(n) {
        if (this.json[this.EXPRESSIONS] == null)
            return null;
        return this.json[this.EXPRESSIONS][n][this.FILE];
    }


    public getExpressionName(n) {
        if (this.json[this.EXPRESSIONS] == null)
            return null;
        return this.json[this.EXPRESSIONS][n][this.NAME];
    }


    public getLayout() {
        return this.json[this.LAYOUT];
    }


    public getHitAreasCustom() {
        return this.json[this.HIT_AREAS_CUSTOM];
    }


    public getInitParamNum() {
        return (this.json[this.INIT_PARAM] == null) ? 0 : this.json[this.INIT_PARAM].length;
    }


    public getMotionNum(name) {
        if (this.json[this.MOTION_GROUPS] == null ||
            this.json[this.MOTION_GROUPS][name] == null)
            return 0;

        return this.json[this.MOTION_GROUPS][name].length;
    }


    public getMotionFile(name, n) {
        if (this.json[this.MOTION_GROUPS] == null ||
            this.json[this.MOTION_GROUPS][name] == null ||
            this.json[this.MOTION_GROUPS][name][n] == null)
            return null;

        return this.json[this.MOTION_GROUPS][name][n][this.FILE];
    }


    public getMotionSound(name, n) {
        if (this.json[this.MOTION_GROUPS] == null ||
            this.json[this.MOTION_GROUPS][name] == null ||
            this.json[this.MOTION_GROUPS][name][n] == null ||
            this.json[this.MOTION_GROUPS][name][n][this.SOUND] == null)
            return null;

        return this.json[this.MOTION_GROUPS][name][n][this.SOUND];
    }


    public getMotionFadeIn(name, n) {
        if (this.json[this.MOTION_GROUPS] == null ||
            this.json[this.MOTION_GROUPS][name] == null ||
            this.json[this.MOTION_GROUPS][name][n] == null ||
            this.json[this.MOTION_GROUPS][name][n][this.FADE_IN] == null)
            return 1000;

        return this.json[this.MOTION_GROUPS][name][n][this.FADE_IN];
    }


    public getMotionFadeOut(name, n) {
        if (this.json[this.MOTION_GROUPS] == null ||
            this.json[this.MOTION_GROUPS][name] == null ||
            this.json[this.MOTION_GROUPS][name][n] == null ||
            this.json[this.MOTION_GROUPS][name][n][this.FADE_OUT] == null)
            return 1000;

        return this.json[this.MOTION_GROUPS][name][n][this.FADE_OUT];
    }


    public getInitParamID(n) {
        if (this.json[this.INIT_PARAM] == null ||
            this.json[this.INIT_PARAM][n] == null)
            return null;

        return this.json[this.INIT_PARAM][n][this.ID];
    }


    public getInitParamValue(n) {
        if (this.json[this.INIT_PARAM] == null || this.json[this.INIT_PARAM][n] == null)
            return NaN;

        return this.json[this.INIT_PARAM][n][this.VALUE];
    }


    public getInitPartsVisibleNum() {
        return (this.json[this.INIT_PARTS_VISIBLE] == null) ? 0 : this.json[this.INIT_PARTS_VISIBLE].length;
    }


    public getInitPartsVisibleID(n) {
        if (this.json[this.INIT_PARTS_VISIBLE] == null || this.json[this.INIT_PARTS_VISIBLE][n] == null)
            return null;
        return this.json[this.INIT_PARTS_VISIBLE][n][this.ID];
    }


    public getInitPartsVisibleValue(n) {
        if (this.json[this.INIT_PARTS_VISIBLE] == null || this.json[this.INIT_PARTS_VISIBLE][n] == null)
            return NaN;

        return this.json[this.INIT_PARTS_VISIBLE][n][this.VALUE];
    }


    NAME: string;
    ID: string;
    MODEL: string;
    TEXTURES: string;
    HIT_AREAS: string;
    PHYSICS: string;
    POSE: string;
    EXPRESSIONS: string;
    MOTION_GROUPS: string;
    SOUND: string;
    FADE_IN: string;
    FADE_OUT: string;
    LAYOUT: string;
    HIT_AREAS_CUSTOM: string;
    INIT_PARAM: string;
    INIT_PARTS_VISIBLE: string;
    VALUE: string;
    FILE: string;

    json: any;
}
