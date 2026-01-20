import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),

        VitePWA({
            registerType: 'autoUpdate',
            devOptions: {
                enabled: true,  // Aktifkan PWA di dev mode (npm run dev)
                navigateFallback: '/offline.html',  // Halaman fallback offline
            },
            includeAssets: [
                'favicon.ico',
                'apple-touch-icon.png',
                'masked-icon.svg',
                'pwa/*.png',           // Semua icon di folder public/pwa
                'images/**/*.png',     // Gambar lain kalau ada
            ],
            manifest: {
                name: 'E-Masjid – Sistem Informasi Masjid',
                short_name: 'EMasjid',
                description: 'Jadwal sholat, donasi, kajian, acara, dan informasi masjid digital.',
                theme_color: '#059669',          // emerald-600 dari Tailwind/DaisyUI
                background_color: '#ecfdf5',     // emerald-50 light
                display: 'standalone',
                scope: '/',
                start_url: '/',
                orientation: 'portrait-primary',
                icons: [
                    {
                        src: '/pwa/icon-192.png',
                        sizes: '192x192',
                        type: 'image/png',
                        purpose: 'any'
                    },
                    {
                        src: '/pwa/icon-512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'any maskable'  // Penting untuk icon adaptif Android
                    },
                    // Tambah untuk iOS & Apple Watch (opsional tapi bagus)
                    {
                        src: '/pwa/icon-180.png',
                        sizes: '180x180',
                        type: 'image/png',
                        purpose: 'any'
                    }
                ],
                screenshots: [  // Tampilkan screenshot saat install prompt (opsional)
                    {
                        src: '/pwa/screenshot1.png',
                        sizes: '1280x720',
                        type: 'image/png',
                        form_factor: 'wide',
                        label: 'E-Masjid Homepage'
                    },
                    {
                        src: '/pwa/screenshot2.png',
                        sizes: '750x1334',
                        type: 'image/png',
                        form_factor: 'narrow',
                        label: 'Jadwal Sholat di Mobile'
                    }
                ]
            },
            workbox: {
                globPatterns: ['**/*.{js,css,html,ico,png,svg,jpg,woff2}'],  // Cache semua aset penting
                runtimeCaching: [
                    // Cache gambar dari storage (foto galeri, QRIS, dll)
                    {
                        urlPattern: ({ url }) => url.pathname.startsWith('/storage/'),
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'images-cache',
                            expiration: { maxEntries: 100, maxAgeSeconds: 30 * 24 * 60 * 60 }, // 30 hari
                        }
                    },
                    // Cache API calls (kalau ada endpoint fetch)
                    {
                        urlPattern: ({ url }) => url.pathname.startsWith('/api/'),
                        handler: 'NetworkFirst',
                        options: { cacheName: 'api-cache' }
                    }
                ],
                navigateFallback: '/offline.html',  // Redirect ke halaman offline kalau fetch gagal
                navigateFallbackDenylist: [/^\/api\//]  // Jangan fallback untuk API
            }
        })
    ],

    server: {
        host: '0.0.0.0',
        port: 5173,
        hmr: {
            host: '192.168.1.14', // IP lokal kamu
        },
    },

    build: {
        outDir: 'public/build',
        assetsDir: 'assets',
        manifest: true,
    }
});