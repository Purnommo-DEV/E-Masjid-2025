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
                enabled: false,
            },

            workbox: {
                skipWaiting: true,
                clientsClaim: true,
            },

            manifest: {
                name: 'E-Masjid – Sistem Informasi Masjid',
                short_name: 'EMasjid',
                description: 'Jadwal sholat, donasi, kajian, acara, dan informasi masjid digital.',
                theme_color: '#059669',
                background_color: '#ecfdf5',
                display: 'standalone',
                start_url: '/',
                scope: '/',
                icons: [
                    { src: '/pwa/icon-192.png', sizes: '192x192', type: 'image/png' },
                    { src: '/pwa/icon-512.png', sizes: '512x512', type: 'image/png', purpose: 'any maskable' },
                ],
            },
        }),
    ],
})
