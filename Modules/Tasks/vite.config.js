// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { viteStaticCopy } from 'vite-plugin-static-copy';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'Modules/Tasks/resources/assets/js/tasks-board.js',
                'Modules/Tasks/resources/assets/css/tasks.css',
            ],
            refresh: true,
        }),
        viteStaticCopy({
            targets: [
                {
                    // Copy the entire 'skins' folder so /skins/... works
                    src: 'node_modules/tinymce/skins',
                    dest: 'skins'
                }
            ]
        })
    ],
});
