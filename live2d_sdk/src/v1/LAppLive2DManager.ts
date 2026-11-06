import { Live2DFramework } from "./framework/Live2DFramework"
import { PlatformManager } from "./PlatformManager"
import { LAppModel } from "./LAppModel"
import { LAppDefine } from "./LAppDefine"

export default class LAppLive2DManager {
  private models: LAppModel[];
  private count;
  private reloadFlg;
  public constructor() {
    this.models = [];
    this.count = -1;
    this.reloadFlg = false;

    (<any>window).Live2D.init();
    Live2DFramework.setPlatformManager(new PlatformManager);
  }

  public createModel() {
    var model = new LAppModel();
    this.models.push(model);
    return model;
  }


  public changeModel(gl: any, modelurl: string, settings?: any, manifest?: any) {
    // console.log("--> LAppLive2DManager.update(gl)");
    if (this.reloadFlg) {
      this.reloadFlg = false;
      this.releaseModel(0, gl);
      this.createModel();
      this.models[0].load(modelurl, () => {
        if (LAppDefine.DEBUG_LOG) console.log("changeModel完成");
      }, settings.userToken?.sign || settings.userInfo.sign, manifest);
    }
  }


  public getModel(no) {
    // console.log("--> LAppLive2DManager.getModel(" + no + ")");

    if (no >= this.models.length) return null;

    return this.models[no];
  }



  public releaseModel(no, gl) {
    // console.log("--> LAppLive2DManager.releaseModel(" + no + ")");

    if (this.models.length <= no) return;

    this.models[no].release(gl);

    delete this.models[no];
    this.models.splice(no, 1);
  }



  public numModels() {
    return this.models.length;
  }



  public setDrag(x, y) {
    for (var i = 0; i < this.models.length; i++) {
      this.models[i].setDrag(x, y);
    }
  }



  public maxScaleEvent() {
    if (LAppDefine.DEBUG_LOG)
      console.log("Max scale event.");
    for (var i = 0; i < this.models.length; i++) {
      this.models[i].startRandomMotion(LAppDefine.MOTION_GROUP_PINCH_IN,
        LAppDefine.PRIORITY_NORMAL);
    }
  }



  public minScaleEvent() {
    if (LAppDefine.DEBUG_LOG)
      console.log("Min scale event.");
    for (var i = 0; i < this.models.length; i++) {
      this.models[i].startRandomMotion(LAppDefine.MOTION_GROUP_PINCH_OUT,
        LAppDefine.PRIORITY_NORMAL);
    }
  }



  public tapEvent(x, y) {
    if (LAppDefine.DEBUG_LOG)
      console.log("tapEvent view x:" + x + " y:" + y);

    for (var i = 0; i < this.models.length; i++) {

      if (this.models[i].hitTest(LAppDefine.HIT_AREA_HEAD.toLowerCase(), x, y)) {

        if (LAppDefine.DEBUG_LOG)
          console.log("Tap face.");

        this.models[i].setRandomExpression();
      } else if (this.models[i].hitTest(LAppDefine.HIT_AREA_BODY.toLowerCase(), x, y)) {

        if (LAppDefine.DEBUG_LOG)
          console.log("Tap body." + " models[" + i + "]");

        this.models[i].startRandomMotion(LAppDefine.MOTION_GROUP_TAP_BODY,
          LAppDefine.PRIORITY_NORMAL);
      } else if (this.models[i].hitTestCustom('head', x, y)) {

        if (LAppDefine.DEBUG_LOG)
          console.log("Tap face.");

        this.models[i].startRandomMotion(LAppDefine.MOTION_GROUP_FLICK_HEAD,
          LAppDefine.PRIORITY_NORMAL);
      } else if (this.models[i].hitTestCustom('body', x, y)) {

        if (LAppDefine.DEBUG_LOG)
          console.log("Tap body." + " models[" + i + "]");

        this.models[i].startRandomMotion(LAppDefine.MOTION_GROUP_TAP_BODY,
          LAppDefine.PRIORITY_NORMAL);
      }
    }

    return true;
  }

}