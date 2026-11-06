import { Live2DFramework, L2DBaseModel, L2DMotionManager } from "./framework/Live2DFramework"
import { ModelSettingJson } from "./framework/ModelSettingJson"
import { MatrixStack } from "./framework/MatrixStack"
import { LAppDefine } from "./LAppDefine"

export class LAppModel extends L2DBaseModel {
    private modelHomeDir;
    private modelSetting: ModelSettingJson;
    private tmpMatrix: any[];
    mainMotionManager: any;
    expressionManager: any;
    constructor() {
        super();
        //L2DBaseModel.apply(this, arguments);
        L2DBaseModel.prototype.constructor.call(this);

        this.modelHomeDir = "";
        this.modelSetting = null;
        this.tmpMatrix = [];
    }

    public async load(modelSettingPath: string, callback: () => void, token?: string, manifest?: any) {
        this.setUpdating(true);
        this.setInitialized(false);

        this.modelHomeDir = "";

        this.modelSetting = new ModelSettingJson(manifest);

        var thisRef = this;

        await this.modelSetting.loadModelSetting(modelSettingPath, token);
        //处理相对路径和绝对路径
        //已知thisRef.modelSetting.getModelFile() 是获取当前JSON中的模型文件:moc文件
        var mocfile = thisRef.modelSetting.getModelFile();
        if (/^https?:\/\/|^\/\//i.test(mocfile)) {
            thisRef.modelHomeDir = "";
        } else {
            thisRef.modelHomeDir = modelSettingPath.substring(0, modelSettingPath.lastIndexOf("/") + 1);
        }
        var path = thisRef.modelHomeDir + thisRef.modelSetting.getModelFile();
        thisRef.loadModelData(path, function (model) {
            for (var i = 0; i < thisRef.modelSetting.getTextureNum(); i++) {
                let texPaths = thisRef.modelHomeDir + thisRef.modelSetting.getTextureFile(i);
                thisRef.loadTexture(i, texPaths, function () {
                    if (thisRef.isTexLoaded) {
                        if (thisRef.modelSetting.getExpressionNum() > 0) {
                            thisRef.expressions = {};
                            for (var j = 0; j < thisRef.modelSetting.getExpressionNum(); j++) {
                                var expName = thisRef.modelSetting.getExpressionName(j);
                                var expFilePath = thisRef.modelHomeDir +
                                    thisRef.modelSetting.getExpressionFile(j);
                                thisRef.loadExpression(expName, expFilePath, null);
                            }
                        }
                        else {
                            thisRef.expressionManager = null;
                            thisRef.expressions = {};
                        }
                        if (thisRef.eyeBlink == null) {
                            //thisRef.eyeBlink = new L2DEyeBlink();
                        }
                        if (thisRef.modelSetting.getPhysicsFile() != null) {
                            thisRef.loadPhysics(thisRef.modelHomeDir +
                                thisRef.modelSetting.getPhysicsFile());
                        }
                        else {
                            thisRef.physics = null;
                        }
                        if (thisRef.modelSetting.getPoseFile() != null) {
                            thisRef.loadPose(
                                thisRef.modelHomeDir +
                                thisRef.modelSetting.getPoseFile(),
                                function () {
                                    thisRef.pose.updateParam(thisRef.live2DModel);
                                }
                            );
                        }
                        else {
                            thisRef.pose = null;
                        }
                        if (thisRef.modelSetting.getLayout() != null) {
                            var layout = thisRef.modelSetting.getLayout();
                            if (layout["width"] != null)
                                thisRef.modelMatrix.setWidth(layout["width"]);
                            if (layout["height"] != null)
                                thisRef.modelMatrix.setHeight(layout["height"]);

                            if (layout["x"] != null)
                                thisRef.modelMatrix.setX(layout["x"]);
                            if (layout["y"] != null)
                                thisRef.modelMatrix.setY(layout["y"]);
                            if (layout["center_x"] != null)
                                thisRef.modelMatrix.centerX(layout["center_x"]);
                            if (layout["center_y"] != null)
                                thisRef.modelMatrix.centerY(layout["center_y"]);
                            if (layout["top"] != null)
                                thisRef.modelMatrix.top(layout["top"]);
                            if (layout["bottom"] != null)
                                thisRef.modelMatrix.bottom(layout["bottom"]);
                            if (layout["left"] != null)
                                thisRef.modelMatrix.left(layout["left"]);
                            if (layout["right"] != null)
                                thisRef.modelMatrix.right(layout["right"]);
                        }

                        if (thisRef.modelSetting.getHitAreasCustom() != null) {
                            var hit_areas_custom = thisRef.modelSetting.getHitAreasCustom();
                            if (hit_areas_custom["head_x"] != null)
                                LAppDefine.HitAreasCustomHead_x = hit_areas_custom["head_x"];
                            if (hit_areas_custom["head_y"] != null)
                                LAppDefine.HitAreasCustomHead_y = hit_areas_custom["head_y"];
                            if (hit_areas_custom["body_x"] != null)
                                LAppDefine.HitAreasCustomBody_x = hit_areas_custom["body_x"];
                            if (hit_areas_custom["body_y"] != null)
                                LAppDefine.HitAreasCustomBody_y = hit_areas_custom["body_y"];
                        }

                        for (var j = 0; j < thisRef.modelSetting.getInitParamNum(); j++) {

                            thisRef.live2DModel.setParamFloat(
                                thisRef.modelSetting.getInitParamID(j),
                                thisRef.modelSetting.getInitParamValue(j)
                            );
                        }

                        for (var j = 0; j < thisRef.modelSetting.getInitPartsVisibleNum(); j++) {

                            thisRef.live2DModel.setPartsOpacity(
                                thisRef.modelSetting.getInitPartsVisibleID(j),
                                thisRef.modelSetting.getInitPartsVisibleValue(j)
                            );
                        }
                        thisRef.live2DModel.saveParam();
                        // thisRef.live2DModel.setGL(gl);
                        thisRef.preloadMotionGroup(LAppDefine.MOTION_GROUP_IDLE.toLowerCase());
                        thisRef.preloadMotionGroup(LAppDefine.PRIORITY_SLEEPY);
                        thisRef.mainMotionManager.stopAllMotions();

                        thisRef.setUpdating(false);
                        thisRef.setInitialized(true);

                        callback();
                    }
                });
            }
        });
    }

    public release(gl) {
        // this.live2DModel.deleteTextures();
        let pm = Live2DFramework.getPlatformManager();

        gl.deleteTexture(pm.texture);
    }

    public preloadMotionGroup(name) {
        var thisRef = this;

        for (var i = 0; i < this.modelSetting.getMotionNum(name); i++) {
            var file = this.modelSetting.getMotionFile(name, i);
            this.loadMotion(file, this.modelHomeDir + file, function (motion) {
                motion.setFadeIn(thisRef.modelSetting.getMotionFadeIn(name, i));
                motion.setFadeOut(thisRef.modelSetting.getMotionFadeOut(name, i));
            });

        }
    }

    public update() {
        // console.log("--> LAppModel.update()");

        if (this.live2DModel == null) {
            if (LAppDefine.DEBUG_LOG) console.error("Failed to update.");

            return;
        }

        var timeMSec = (<any>window).UtSystem.getUserTimeMSec() - this.startTimeMSec;
        var timeSec = timeMSec / 1000.0;
        var t = timeSec * 2 * Math.PI;


        if (this.mainMotionManager.isFinished()) {
            var Sleepy = sessionStorage.getItem('Sleepy');
            if (Sleepy === '1') {
                this.startRandomMotion(LAppDefine.MOTION_GROUP_SLEEPY, LAppDefine.PRIORITY_SLEEPY);
            } else {
                this.startRandomMotion(LAppDefine.MOTION_GROUP_IDLE.toLowerCase(), LAppDefine.PRIORITY_IDLE);
            }
        }

        //-----------------------------------------------------------------		


        this.live2DModel.loadParam();



        var update = this.mainMotionManager.updateParam(this.live2DModel);
        if (!update) {

            if (this.eyeBlink != null) {
                this.eyeBlink.updateParam(this.live2DModel);
            }
        }


        this.live2DModel.saveParam();

        //-----------------------------------------------------------------		


        if (this.expressionManager != null &&
            this.expressions != null &&
            !this.expressionManager.isFinished()) {
            this.expressionManager.updateParam(this.live2DModel);
        }



        this.live2DModel.addToParamFloat("PARAM_ANGLE_X", this.dragX * 30, 1);
        this.live2DModel.addToParamFloat("PARAM_ANGLE_Y", this.dragY * 30, 1);
        this.live2DModel.addToParamFloat("PARAM_ANGLE_Z", (this.dragX * this.dragY) * -30, 1);



        this.live2DModel.addToParamFloat("PARAM_BODY_ANGLE_X", this.dragX * 10, 1);



        this.live2DModel.addToParamFloat("PARAM_EYE_BALL_X", this.dragX, 1);
        this.live2DModel.addToParamFloat("PARAM_EYE_BALL_Y", this.dragY, 1);



        this.live2DModel.addToParamFloat("PARAM_ANGLE_X",
            Number((15 * Math.sin(t / 6.5345))), 0.5);
        this.live2DModel.addToParamFloat("PARAM_ANGLE_Y",
            Number((8 * Math.sin(t / 3.5345))), 0.5);
        this.live2DModel.addToParamFloat("PARAM_ANGLE_Z",
            Number((10 * Math.sin(t / 5.5345))), 0.5);
        this.live2DModel.addToParamFloat("PARAM_BODY_ANGLE_X",
            Number((4 * Math.sin(t / 15.5345))), 0.5);
        this.live2DModel.setParamFloat("PARAM_BREATH",
            Number((0.5 + 0.5 * Math.sin(t / 3.2345))), 1);


        if (this.physics != null) {
            this.physics.updateParam(this.live2DModel);
        }


        if (this.lipSync == null) {
            this.live2DModel.setParamFloat("PARAM_MOUTH_OPEN_Y",
                this.lipSyncValue);
        }


        if (this.pose != null) {
            this.pose.updateParam(this.live2DModel);
        }

        this.live2DModel.update();
    }



    public setRandomExpression() {
        var tmp = [];
        for (var name in this.expressions) {
            tmp.push(name);
        }

        var no = parseInt((Math.random() * tmp.length).toString());

        this.setExpression(tmp[no]);
    }



    public startRandomMotion(name, priority) {
        var max = this.modelSetting.getMotionNum(name);
        var no = parseInt((Math.random() * max).toString());
        this.startMotion(name, no, priority);
    }



    public startMotion(name, no, priority) {
        // console.log("startMotion : " + name + " " + no + " " + priority);

        var motionName = this.modelSetting.getMotionFile(name, no);

        if (motionName == null || motionName == "") {
            if (LAppDefine.DEBUG_LOG)
                console.error("Failed to motion.");
            return;
        }

        if (priority == LAppDefine.PRIORITY_FORCE) {
            this.mainMotionManager.setReservePriority(priority);
        }
        else if (!this.mainMotionManager.reserveMotion(priority)) {
            if (LAppDefine.DEBUG_LOG)
                console.log("Motion is running.")
            return;
        }

        var thisRef = this;
        var motion;

        if (this.motions[name] == null) {
            this.loadMotion(null, this.modelHomeDir + motionName, function (mtn) {
                motion = mtn;


                thisRef.setFadeInFadeOut(name, no, priority, motion);

            });
        }
        else {
            motion = this.motions[name];


            thisRef.setFadeInFadeOut(name, no, priority, motion);
        }
    }


    public setFadeInFadeOut(name, no, priority, motion) {
        var motionName = this.modelSetting.getMotionFile(name, no);

        motion.setFadeIn(this.modelSetting.getMotionFadeIn(name, no));
        motion.setFadeOut(this.modelSetting.getMotionFadeOut(name, no));


        if (LAppDefine.DEBUG_LOG)
            console.log("Start motion : " + motionName);

        if (this.modelSetting.getMotionSound(name, no) == null) {
            this.mainMotionManager.startMotionPrio(motion, priority);
        }
        else {
            var soundName = this.modelSetting.getMotionSound(name, no);
            // var player = new Sound(this.modelHomeDir + soundName);

            var snd = document.createElement("audio");
            snd.src = this.modelHomeDir + soundName;

            if (LAppDefine.DEBUG_LOG)
                console.log("Start sound : " + soundName);

            snd.play();
            this.mainMotionManager.startMotionPrio(motion, priority);
        }
    }



    public setExpression(name) {
        var motion = this.expressions[name];

        if (LAppDefine.DEBUG_LOG)
            console.log("Expression : " + name);

        this.expressionManager.startMotion(motion, false);
    }


    /**
     * 模仿v3版本的framework写的
     * 同等于lappmodel.ts中797行代码
     * @param {实例化后的MatrixStack} matrix 
     */
    public draw(matrix: MatrixStack) {
        matrix.multMatrix(this.modelMatrix.getArray());

        this.tmpMatrix = matrix.getMatrix()

        this.live2DModel.setMatrix(this.tmpMatrix);
        this.live2DModel.draw();
    }



    public hitTest(id, testX, testY) {
        var len = this.modelSetting.getHitAreaNum();
        for (var i = 0; i < len; i++) {
            if (id == this.modelSetting.getHitAreaName(i)) {
                var drawID = this.modelSetting.getHitAreaID(i);

                return this.hitTestSimple(drawID, testX, testY);
            }
        }

        return false;
    }


    public hitTestCustom(type, testX, testY) {
        if (type == 'head') {
            return this.hitTestSimpleCustom(LAppDefine.HitAreasCustomHead_x, LAppDefine.HitAreasCustomHead_y, testX, testY);
        } else if (type == 'body') {
            return this.hitTestSimpleCustom(LAppDefine.HitAreasCustomBody_x, LAppDefine.HitAreasCustomBody_y, testX, testY);
        } else {
            return false;
        }
    }


}