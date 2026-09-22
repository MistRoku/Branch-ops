import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
    server: {
        // Fixed for Laragon Apache + `php artisan serve` used together.
        // Use explicit IPv4 loopback so browsers never resolve `http://[::]:5173`
        // from a stale `public/hot` file. Both Laragon vhosts and artisan serve
        // can load HMR from 127.0.0.1:5173.
        host: '127.0.0.1',
        port: 5173,
        strictPort: true,
        cors: true,
        hmr: {
            host: '127.0.0.1',
            port: 5173,
        },
    },
});
