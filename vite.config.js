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
                'Modules/Tasks/resources/assets/css/habits.css',
                'Modules/Tasks/resources/assets/js/habits.js',
                'Modules/Spotify/resources/assets/css/player.css',
                'Modules/Spotify/resources/assets/js/core/player.js',
                'Modules/Calendar/resources/assets/js/calendar.js',
                'Modules/Lighting/resources/assets/css/lighting.css',
                'Modules/Lighting/resources/assets/js/lighting.js',
                'Modules/PhonePing/resources/assets/js/phone-ping.js',
                'Modules/Weather/resources/assets/css/weather.css',
                'Modules/Weather/resources/assets/js/weather.js',
                'Modules/News/resources/assets/css/news.css',
                'Modules/News/resources/assets/js/news.js',
                'Modules/Printer/resources/assets/css/printer.css',
                'Modules/Printer/resources/assets/js/printer.js',
                'Modules/Recipes/resources/assets/css/recepten.css',
                'Modules/Recipes/resources/assets/js/recepten.js',
                'Modules/Entertainment/resources/assets/css/entertainment.css',
                'Modules/Entertainment/resources/assets/js/entertainment.js',
                'Modules/Deals/resources/assets/css/dealtracker.css',
                'Modules/Deals/resources/assets/js/dealtracker.js',
                'Modules/Planner/resources/assets/css/planner.css',
                'Modules/Planner/resources/assets/js/planner.js',
                'Modules/Briefing/resources/assets/css/briefing.css',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
