import { Live2dAdmin } from 'live2d-admin-shared';

declare global {
    var settings: Record<string, unknown>;
    var ajaxurl: string;
    interface Window { live2dSettings: () => void; }
}

let initialized = false;
window.live2dSettings = () => {
    if (initialized) return;
    initialized = true;
    const admin = new Live2dAdmin(settings, ajaxurl);
    admin.initControl();
};
