import type { Live2dTips } from '../Wordpress/live2d-tips';

export type ModelConfiguration = Record<string, unknown>;
export type TipsFactory = (configuration: ModelConfiguration) => Live2dTips;
const renderers = new Map<string, TipsFactory>();

export function registerRenderer(name: string, factory: TipsFactory): void {
    if (renderers.has(name)) throw new Error(`Renderer already registered: ${name}`);
    renderers.set(name, factory);
}

export function getRenderer(name: string): TipsFactory | undefined {
    return renderers.get(name);
}

export type AdminExtension = (apiType: string) => boolean;
const adminExtensions: AdminExtension[] = [];

export function registerAdminExtension(extension: AdminExtension): void {
    adminExtensions.push(extension);
}

export function applyAdminExtensions(apiType: string): boolean {
    return adminExtensions.some(extension => extension(apiType));
}
