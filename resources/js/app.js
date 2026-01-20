// resources/js/app.js
import '../css/app.css'; // penting: Vite akan bundle ini

import Alpine from 'alpinejs'
import collapse from '@alpinejs/collapse'
import.meta.env.VITE_VAPID_PUBLIC_KEY // pastikan ada di .env (VITE_VAPID_PUBLIC_KEY=...)

// Tambahkan log konfirmasi VAPID key di sini (paling atas blok push)
console.log('VITE_VAPID_PUBLIC_KEY:', import.meta.env.VITE_VAPID_PUBLIC_KEY || 'UNDEFINED / KOSONG');

if (!import.meta.env.VITE_VAPID_PUBLIC_KEY) {
    console.error('VAPID PUBLIC KEY KOSONG! Cek .env dan restart npm run dev.');
    return;  // stop eksekusi kalau key kosong
}

Alpine.plugin(collapse)
window.Alpine = Alpine
Alpine.start()

// setup global axios/jQuery CSRF jika perlu (opsional)

if ('serviceWorker' in navigator && 'PushManager' in window) {
    window.addEventListener('load', async () => {
        console.log('Mulai register SW...');
        const reg = await navigator.serviceWorker.register('/sw.js');
        console.log('Service Worker registered', reg);

        console.log('Request izin notifikasi...');
        const permission = await Notification.requestPermission();
        console.log('Permission status:', permission);

        if (permission !== 'granted') {
            console.log('Izin notifikasi ditolak atau default');
            return;
        }

        console.log('Cek subscription existing...');
        let sub = await reg.pushManager.getSubscription();
        if (!sub) {
            console.log('Subscribe baru...');
            sub = await reg.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(import.meta.env.VITE_VAPID_PUBLIC_KEY)
            });
            console.log('Subscription baru:', sub);
        } else {
            console.log('Subscription existing:', sub);
        }

        console.log('Kirim subscription ke server...');
        const response = await fetch('/api/push/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(sub)
        });

        if (response.ok) {
            console.log('Subscription berhasil dikirim ke server');
        } else {
            console.error('Gagal kirim subscription:', await response.text());
        }
    });
}

// Helper function wajib untuk convert public key
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}