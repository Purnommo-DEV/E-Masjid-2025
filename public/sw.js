self.addEventListener('push', event => {
    if (!event.data) {
        console.log('[SW] Push tanpa payload');
        return;
    }

    const data = event.data.json();

    const options = {
        body: data.body,
        icon: '/pwa/icon-192.png',
        badge: '/pwa/icon-192.png',
        vibrate: [100, 50, 100],
        data: {
            url: data.url || '/',
        },
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});
