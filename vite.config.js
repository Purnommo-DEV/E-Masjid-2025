import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import { VitePWA } from 'vite-plugin-pwa'

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        VitePWA({
            strategies: 'generateSW',
            registerType: 'autoUpdate',
            devOptions: {
                enabled: false, // matikan di dev kalau tidak perlu test PWA
            },
            workbox: {
                skipWaiting: true,
                clientsClaim: true,
            },
            // NONAKTIFKAN manifest injection dari plugin
            manifest: false, // <-- ini kunci! Jangan inject manifest statis
            // Optional: injectManifest kalau kamu punya SW custom
            // injectManifest: { ... } // kalau perlu SW custom
        }),
    ],
})