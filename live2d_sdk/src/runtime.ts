import { Live2dTips, getRenderer, type ModelConfiguration } from 'live2d-shared';

declare global {
    var live2d_settings: ModelConfiguration;
    interface Window { initLive2dWeb: () => Promise<void>; }
}

let initialized = false;
window.initLive2dWeb = async () => {
    if (initialized) return;
    const container = document.querySelector<HTMLElement>('.waifu');
    if (!container) return;
    initialized = true;
    const config = live2d_settings;
    const renderer = typeof config.renderer === 'string' ? config.renderer : 'legacy';
    const factory = renderer === 'legacy' ? (value: ModelConfiguration) => new Live2dTips(value) : getRenderer(renderer);
    if (!factory) {
        console.warn('[Live2D] The configured renderer is unavailable; the saved model has not been replaced.');
        const canvas = container.querySelector('canvas');
        if (canvas) canvas.hidden = true;
        const tips = new Live2dTips(config);
        tips.initTips();
        return;
    }
    try {
        const tips = factory(config);
        tips.initTips();
        const settings = config.settings;
        if (!settings || typeof settings !== 'object') throw new Error('Missing model settings.');
        const modelId = 'modelId' in settings ? String(settings.modelId ?? '') : '';
        const textureId = 'modelTexturesId' in settings ? String(settings.modelTexturesId ?? '') : '';
        await tips.initModel(modelId, textureId);
    } catch (error) {
        console.error('[Live2D] Model initialization failed.', error);
    }
};
