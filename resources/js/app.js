import '../css/app.css'

import Alpine from 'alpinejs'
import collapse from '@alpinejs/collapse'

Alpine.plugin(collapse)
window.Alpine = Alpine
Alpine.start()

/**
 * 🔥 REGISTER SERVICE WORKER (WAJIB)
 */
async function registerServiceWorker() {
    if (!('serviceWorker' in navigator)) return null

    try {
        const registration = await navigator.serviceWorker.register('/push-sw.js')

        console.log('Service Worker registered:', registration)
        return registration
    } catch (err) {
        console.error('SW register gagal:', err)
        return null
    }
}

async function initPushNotifications() {
    const VAPID_KEY = import.meta.env.VITE_VAPID_PUBLIC_KEY

    console.log(
        'VITE_VAPID_PUBLIC_KEY:',
        VAPID_KEY ? 'OK (loaded)' : '❌ UNDEFINED / KOSONG'
    )

    if (!('PushManager' in window)) {
        console.warn('PushManager tidak didukung')
        return
    }

    if (!VAPID_KEY) {
        console.error('VAPID Public Key belum diset')
        return
    }

    try {
        console.log('Register Service Worker...')
        const registration = await registerServiceWorker()

        if (!registration) {
            console.error('Service Worker tidak terdaftar')
            return
        }

        console.log('Service Worker aktif')

        const permission = await Notification.requestPermission()
        console.log('Notification permission:', permission)

        if (permission !== 'granted') return

        let subscription = await registration.pushManager.getSubscription()

        if (!subscription) {
            console.log('Membuat subscription baru...')
            subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(VAPID_KEY),
            })
        }

        await fetch('/api/push/subscribe', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                endpoint: subscription.endpoint,
                keys: subscription.toJSON().keys,
                zona_waktu: 'WIB',
                kota: 'Jakarta',
            }),
        })

        console.log('✅ Subscription berhasil disimpan')
    } catch (err) {
        console.error('❌ Push setup error:', err)
    }
}

window.addEventListener('load', initPushNotifications)

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4)
    const base64 = (base64String + padding)
        .replace(/-/g, '+')
        .replace(/_/g, '/')

    const rawData = window.atob(base64)
    return Uint8Array.from([...rawData].map(c => c.charCodeAt(0)))
}
