import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
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
        tailwindcss(),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        if (
                            id.includes('@tanstack/vue-table') ||
                            id.includes('@tanstack')
                        ) {
                            return 'vendor-table';
                        }
                        if (
                            id.includes('lucide-vue-next') ||
                            id.includes('reka-ui') ||
                            id.includes('radix-vue')
                        ) {
                            return 'vendor-ui';
                        }
                        if (
                            id.includes('/vue/') ||
                            id.includes('\\vue\\') ||
                            id.includes('/@vue/') ||
                            id.includes('\\@vue\\') ||
                            id.includes('@inertiajs') ||
                            id.includes('pinia')
                        ) {
                            return 'vendor-vue';
                        }

                    }
                },
            },
        },
    },
});

