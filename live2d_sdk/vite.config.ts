import { defineConfig } from 'vite';
import { resolve } from 'node:path';

export default defineConfig({
    publicDir: false,
    plugins: [{
        name: 'verify-free-module-boundary',
        generateBundle(_options, bundle) {
            for (const chunk of Object.values(bundle)) {
                if (chunk.type !== 'chunk') continue;
                for (const id of Object.keys(chunk.modules)) {
                    if (/[\\/]src[\\/]v2[\\/]|[\\/]src[\\/]Chromium[\\/]|[\\/]wp-live2d-pro[\\/]/.test(id)) {
                        this.error(`Pro implementation entered the Free module graph: ${id}`);
                    }
                }
                if (chunk.code.includes('Live2DCubismCore')) this.error('Cubism Core entered the Free output.');
            }
        }
    }],
    build: {
        outDir: resolve(import.meta.dirname, '../assets'),
        emptyOutDir: false,
        target: 'es2017',
        minify: 'esbuild',
        sourcemap: false,
        rollupOptions: {
            preserveEntrySignatures: 'strict',
            external: ['moment', 'jquery', 'live2d-shared', 'live2d-admin-shared'],
            input: {
                'live2d-shared': resolve(import.meta.dirname, 'src/shared.ts'),
                'live2d-admin-shared': resolve(import.meta.dirname, 'src/admin-shared.ts'),
                live2dv1: resolve(import.meta.dirname, 'src/legacy-entry.ts'),
                live2dwebsdk: resolve(import.meta.dirname, 'src/runtime.ts'),
                'waifu-admin': resolve(import.meta.dirname, 'src/admin-entry.ts'),
                waifu: resolve(import.meta.dirname, 'src/Wordpress/waifu.scss')
            },
            output: {
                format: 'es',
                entryFileNames: '[name].min.js',
                chunkFileNames: 'free-chunks/[name]-[hash].min.js',
                assetFileNames: '[name][extname]'
            }
        }
    }
});
