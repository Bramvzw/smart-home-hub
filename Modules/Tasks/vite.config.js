import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    build: {
        outDir: '../../public/build-tasks',
        emptyOutDir: true,
        manifest: true,
    },
    plugins: [
        laravel({
            publicDirectory: '../../public',
            buildDirectory: 'build-tasks',
            input: [
                __dirname + '/resources/assets/css/tasks.css',
                __dirname + '/resources/assets/js/tasks-board.js'
            ],
            refresh: true,
        }),
    ],
});
