/// <reference lib="webworker" />

import { precacheAndRoute } from 'workbox-precaching';

// 🔴 WAJIB ADA — TEMPAT INJECT ASSET
precacheAndRoute(self.__WB_MANIFEST);

/**
 * PUSH NOTIFICATION (WAJIB UNTUK PWA MOBILE)
 */
// public/sw.js
self.addEventListener('push', event => {
    const data = event.data.json();
    const title = data.title || 'E-Masjid Notification';
    const options = {
        body: data.body,
        icon: '/pwa/icon-192.png',
        badge: '/pwa/icon-192.png',
        vibrate: [200, 100, 200],
        tag: 'e-masjid-push',
        renotify: true,
        data: {
            url: data.url || '/'  // redirect saat diklik
        }
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', event => {
    event.notification.close();
    event.waitUntil(clients.openWindow(event.notification.data.url));
});
