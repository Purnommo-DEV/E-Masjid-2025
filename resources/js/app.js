// resources/js/app.js
import '../css/app.css';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

Alpine.plugin(collapse);
window.Alpine = Alpine;
Alpine.start();

/**
 * ===== PUSH NOTIFICATION SETUP =====
 */
function initPushNotifications() {
    const VAPID_KEY = import.meta.env.VITE_VAPID_PUBLIC_KEY;

    console.log(
        'VITE_VAPID_PUBLIC_KEY:',
        VAPID_KEY ? 'OK (loaded)' : '❌ UNDEFINED / KOSONG'
    );

    // Guard environment
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        console.warn('Browser tidak mendukung Service Worker / Push');
        return;
    }

    // Guard VAPID
    if (!VAPID_KEY) {
        console.error('VAPID Public Key belum diset di .env');
        return;
    }

    window.addEventListener('load', async () => {
        try {
            console.log('Register service worker...');
            const registration = await navigator.serviceWorker.register('/sw.js');
            console.log('Service Worker registered:', registration);

            const permission = await Notification.requestPermission();
            console.log('Notification permission:', permission);

            if (permission !== 'granted') {
                console.warn('Izin notifikasi tidak diberikan');
                return;
            }

            let subscription = await registration.pushManager.getSubscription();

            if (!subscription) {
                console.log('Membuat subscription baru...');
                subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(VAPID_KEY),
                });
            } else {
                console.log('Subscription sudah ada');
            }

            const payload = {
                endpoint: subscription.endpoint,
                keys: subscription.toJSON().keys,
            };

            console.log('Kirim subscription ke server:', payload);

            const response = await fetch('/api/push/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            if (!response.ok) {
                throw new Error(await response.text());
            }

            console.log('✅ Subscription berhasil disimpan di server');
        } catch (error) {
            console.error('❌ Push setup error:', error);
        }
    });
}

// Jalankan (tanpa return di top-level)
initPushNotifications();

/**
 * Helper untuk convert VAPID public key
 */
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding)
        .replace(/-/g, '+')
        .replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }

    return outputArray;
}
