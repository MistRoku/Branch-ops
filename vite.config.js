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
        // Bind all interfaces so `npm run dev` works on Windows, Linux,
        // WSL2 and Docker without per-OS overrides. Port stays default 5173.
        host: true,
        strictPort: true,
    },
});
