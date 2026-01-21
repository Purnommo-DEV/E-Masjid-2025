/// <reference lib="webworker" />

import { precacheAndRoute } from 'workbox-precaching';

// 🔴 WAJIB ADA — TEMPAT INJECT ASSET
precacheAndRoute(self.__WB_MANIFEST);

/**
 * PUSH NOTIFICATION (WAJIB UNTUK PWA MOBILE)
 */
self.addEventListener('push', event => {
    if (!event.data) return;

    const data = event.data.json();

    event.waitUntil(
        self.registration.showNotification(data.title, {
            body: data.body,
            icon: '/pwa/icon-192.png',
            badge: '/pwa/icon-192.png',
            data: {
                url: data.url || '/',
            },
        })
    );
});

self.addEventListener('notificationclick', event => {
    event.notification.close();
    event.waitUntil(
        clients.openWindow(event.notification.data.url || '/')
    );
});

