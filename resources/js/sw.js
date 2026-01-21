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

    const options = {
        body: data.body,
        icon: '/pwa/icon-192.png',
        badge: '/pwa/icon-192.png',
        requireInteraction: true, // 🔥 INI PENTING UNTUK DESKTOP
        silent: false,
        data: {
            url: data.url || '/',
        },
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});


self.addEventListener('notificationclick', event => {
    event.notification.close();

    const url = event.notification.data?.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then(clientList => {
                for (const client of clientList) {
                    if (client.url === url && 'focus' in client) {
                        return client.focus();
                    }
                }
                return clients.openWindow(url);
            })
    );
});
