import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { globSync } from 'node:fs';

// Glob each module's entry assets so new modules are picked up automatically.
const moduleAssets = globSync('Modules/*/resources/assets/{js,css}/*.{js,css}');

// Listed explicitly so the flat glob above doesn't also pull in its imported sub-modules.
const nestedEntries = ['Modules/Spotify/resources/assets/js/core/player.js'];

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                ...moduleAssets,
                ...nestedEntries,
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
