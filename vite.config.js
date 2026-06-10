import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'Modules/Tasks/resources/assets/css/tasks.css',
                'Modules/Tasks/resources/assets/js/tasks-board.js',
                'Modules/Spotify/resources/assets/js/core/player.js',
                'Modules/Calendar/resources/assets/js/calendar.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
