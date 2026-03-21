import { defineConfig } from 'vite';
import path from 'path';
import { fileURLToPath } from 'url';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig({
    resolve: {
        alias: {
            'argon2-browser': path.resolve(__dirname, 'node_modules/argon2-browser/dist/argon2-bundled.min.js'),
        },
    },
    optimizeDeps: {
        exclude: ['argon2-browser'],
    },
    plugins: [
        laravel({
            input: [
                'resources/css/v2.css',
                'resources/css/admin.css',
                'resources/js/app.js',
                'resources/js/admin.js',
            ],
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
