export const LAppDefine = {


    DEBUG_LOG: false,
    DEBUG_MOUSE_LOG: false,
    DEBUG_DRAW_HIT_AREA: false,
    DEBUG_DRAW_ALPHA_MODEL: false,




    VIEW_MAX_SCALE: 2,
    VIEW_MIN_SCALE: 0.8,

    VIEW_LOGICAL_LEFT: -1,
    VIEW_LOGICAL_RIGHT: 1,

    VIEW_LOGICAL_MAX_LEFT: -2,
    VIEW_LOGICAL_MAX_RIGHT: 2,
    VIEW_LOGICAL_MAX_BOTTOM: -2,
    VIEW_LOGICAL_MAX_TOP: 2,


    PRIORITY_NONE: 0,
    PRIORITY_IDLE: 1,
    PRIORITY_SLEEPY: 2,
    PRIORITY_NORMAL: 3,
    PRIORITY_FORCE: 4,

    MOTION_GROUP_IDLE: "idle",
    MOTION_GROUP_SLEEPY: "sleepy",
    MOTION_GROUP_TAP_BODY: "tap_body",
    MOTION_GROUP_FLICK_HEAD: "flick_head",
    MOTION_GROUP_PINCH_IN: "pinch_in",
    MOTION_GROUP_PINCH_OUT: "pinch_out",
    MOTION_GROUP_SHAKE: "shake",


    HIT_AREA_HEAD: "head",
    HIT_AREA_BODY: "body",

    HitAreasCustomHead_x: '',
    HitAreasCustomHead_y: '',
    HitAreasCustomBody_x: '',
    HitAreasCustomBody_y: ''
};
var context = undefined;

export function setContext(webglContext) {
    context = webglContext;
}

export function getContext() {
    return context;
}

const v2dir = new RegExp('\model\/([^\/]*)\/([^\/]*)', 'gi');
const v2File = new RegExp('([^\/]+)\.(mtn|moc)$', 'gi');
/**
 * 返回V2模型路径中的名字
 */
export function getV2Path(path: string): string {
    var url = /[^/]*$/;
    var test = path.match(v2dir);
    if (test) {
        return test[0];
    } else {
        return path.match(url) ? path.match(url)[0] : null;
    }
}
/**
 * 返回V2模型文件的名字
 */
export function getV2File(path: string): string {
    var url = /[^/]*$/;
    var test = path.match(v2File);
    if (test) {
        return test[0];
    } else {
        return path.match(url) ? path.match(url)[0] : null;
    }
}