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
        // Fix: kalau $keys string JSON, decode dulu
        if (is_string($keys)) {
            $keys = json_decode($keys, true);
        }

        if (!$endpoint || empty($keys['p256dh']) || empty($keys['auth'])) {
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

        $report = $webPush->sendOneNotification($subscription, $payload);

        return $report->isSuccess();
    }
}