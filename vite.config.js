import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            // docs/design-tokens.md: Plus Jakarta Sans untuk teks, JetBrains Mono
            // khusus angka. Di-host sendiri lewat plugin — mini PC ini tidak
            // selalu punya jalur keluar, jadi jangan bergantung pada CDN saat
            // halaman dibuka.
            fonts: [
                bunny('Plus Jakarta Sans', {
                    weights: [400, 500, 600],
                }),
                bunny('JetBrains Mono', {
                    weights: [500],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
