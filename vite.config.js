import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],

    /**
     * Docker HMR configuration.
     *
     * When running inside the `node` container, Vite binds to 0.0.0.0
     * so it is reachable from the host and other containers. The `hmr`
     * block tells the browser to connect to `localhost:5173` for hot
     * module replacement while the server itself listens on all interfaces.
     */
    server: {
        host: '0.0.0.0',
        port: 5173,
        hmr: {
            host: 'localhost',
            port: 5173,
        },
    },
});
