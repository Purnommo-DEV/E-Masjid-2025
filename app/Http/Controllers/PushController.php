<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use App\Models\UserSubscription; // buat model ini nanti

class PushController extends Controller
{
    public function subscribe(Request $request)
    {
        $data = $request->validate([
            'endpoint' => 'required|url',
            'keys' => 'required|array',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        UserSubscription::updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'keys' => json_encode($data['keys']),
                'user_id' => auth()->id() ?? null,
            ]
        );

        return response()->json(['success' => true]);
    }

    // Fungsi statis untuk kirim notif (bisa dipanggil dari mana saja)
    public static function send($title, $body, $url = '/', $endpoint = null, $keys = null)
    {
        \Log::info('Mulai kirim push notification', [
            'title' => $title,
            'endpoint' => $endpoint,
            'p256dh' => substr($keys['p256dh'] ?? '', 0, 10) . '...',  // sensor
            'auth' => substr($keys['auth'] ?? '', 0, 10) . '...',
        ]);

        if (!$endpoint || empty($keys['p256dh']) || empty($keys['auth'])) {
            \Log::warning('Data subscription tidak lengkap');
            return false;
        }

        $auth = [
            'VAPID' => [
                'subject' => 'mailto:admin@emasjid.com',
                'publicKey' => env('VAPID_PUBLIC_KEY'),
                'privateKey' => env('VAPID_PRIVATE_KEY'),
            ],
        ];

        $webPush = new WebPush($auth);

        $subscription = Subscription::create([
            'endpoint' => $endpoint,
            'publicKey' => $keys['p256dh'],
            'authToken' => $keys['auth'],
        ]);

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url,
        ]);

        try {
            $report = $webPush->sendOneNotification($subscription, $payload);
            if ($report->isSuccess()) {
                \Log::info('Push berhasil terkirim ke endpoint: ' . $endpoint);
            } else {
                \Log::error('Push gagal terkirim', [
                    'reason' => $report->getReason(),
                    'endpoint' => $endpoint,
                    'status_code' => $report->getResponse()->getStatusCode(),
                ]);
            }
            return $report->isSuccess();
        } catch (\Exception $e) {
            \Log::error('Exception saat kirim push: ' . $e->getMessage());
            return false;
        }
    }
}