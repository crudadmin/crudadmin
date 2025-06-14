import { defineConfig, splitVendorChunkPlugin } from 'vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { viteExternalsPlugin } from 'vite-plugin-externals';
import AutoImport from 'unplugin-auto-import/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.js', 'resources/css/app.css'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: '',
                    includeAbsolute: true,
                },
            },
        }),
        AutoImport({
            imports: ['vue', 'vue-router', 'pinia'],
            resolvers: [],
            dirs: ['./resources/js/**'],
            vueTemplate: true,
            cache: true,
            injectAtEnd: false,
        }),
        viteExternalsPlugin(
            {
                vue: 'Vue',
                lodash: '_',
                moment: 'moment',
                pinia: 'pinia',
            },
            // call after auto-imports
            { enforce: 'post' }
        ),
        splitVendorChunkPlugin(),
    ],
    build: {
        rollupOptions: {
            external: ['vue', 'pinia', 'lodash', 'moment'],
        },
    },
});