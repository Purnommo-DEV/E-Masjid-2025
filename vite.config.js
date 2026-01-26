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
                // Tambahkan ini untuk mencegah precache halaman utama yang mengganggu splash
                navigateFallback: null,  // ← matikan fallback ke '/' agar start_url custom bekerja
                cleanupOutdatedCaches: true,
            },
            disable: true,  // ← KUNCI: matikan plugin PWA saat build
            manifest: false,  // tanpa object manifest sama sekali
            // NONAKTIFKAN manifest injection dari plugin
            // Optional: injectManifest kalau kamu punya SW custom
            // injectManifest: { ... } // kalau perlu SW custom
        }),
    ],
})