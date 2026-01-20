<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use App\Models\UserSubscription;

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
                'keys' => $data['keys'], // ✅ array, bukan json_encode
                'user_id' => auth()->id(),
            ]
        );

        return response()->json(['success' => true]);
    }

    public static function send($title, $body, $url = '/', $endpoint = null, $keys = null)
    {
        if (!$endpoint || empty($keys['p256dh']) || empty($keys['auth'])) {
            return false;
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => 'mailto:admin@emasjid.com',
                'publicKey' => env('VAPID_PUBLIC_KEY'),
                'privateKey' => env('VAPID_PRIVATE_KEY'),
            ],
        ]);

        $subscription = Subscription::create([
            'endpoint' => $endpoint,
            'keys' => [
                'p256dh' => $keys['p256dh'],
                'auth'   => $keys['auth'],
            ],
        ]);

        $payload = json_encode([
            'title' => $title,
            'body'  => $body,
            'url'   => $url,
        ]);

        $report = $webPush->sendOneNotification($subscription, $payload);

        return $report->isSuccess();
    }
}
