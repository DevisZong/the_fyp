import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],

    server: {
        allowedHosts: [
        'localhost',
        '4e23-197-250-51-246.ngrok-free.app' 
        ],
        host: '0.0.0.0',
        hmr: {
            // host: '192.168.0.15'
        },
        proxy: {
            '/api': {
                target: 'http://the_fyp.test',
                changeOrigin: true,
                secure: false,
            },
        },
    },
});
