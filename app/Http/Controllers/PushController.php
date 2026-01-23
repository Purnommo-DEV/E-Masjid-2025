<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use App\Models\UserSubscription;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PushController extends Controller
{
    public function subscribe(Request $request)
    {
        // Validasi input dari frontend
        $data = $request->validate([
            'endpoint' => 'required|url',
            'keys' => 'required|array',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
            'latitude'  => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        // Default zona & kota kalau lokasi tidak dikirim
        $zona_waktu = 'WIB';
        $kota = 'Jakarta';

        // 1. Prioritas: pakai geolocation dari browser (lat/long)
        if ($request->filled('latitude') && $request->filled('longitude')) {
            $lat = $data['latitude'];
            $lon = $data['longitude'];

            // Hitung zona waktu Indonesia berdasarkan longitude (sederhana & akurat untuk Indo)
            $zona_waktu = match (true) {
                $lon < 108 => 'WIB',    // Sumatra, Jawa, Kalimantan
                $lon < 120 => 'WITA',   // Bali, NTB, NTT, Sulawesi
                default    => 'WIT',    // Maluku, Papua
            };

            // Tentukan kota terdekat (sementara statis, bisa upgrade ke reverse geocode API nanti)
            $kota = match ($zona_waktu) {
                'WIT'  => 'Jayapura',
                'WITA' => 'Makassar',
                default => 'Jakarta',
            };

            Log::info('Geolocation diterima dari user', [
                'lat' => $lat,
                'lon' => $lon,
                'zona' => $zona_waktu,
                'kota' => $kota,
            ]);
        } else {
            // Fallback: pakai IP user untuk deteksi kota & zona
            $ip = $request->ip();
            try {
                $ipResponse = Http::timeout(5)->get("https://ipapi.co/$ip/json/");
                if ($ipResponse->successful()) {
                    $ipData = $ipResponse->json();
                    $timezone = $ipData['timezone'] ?? 'Asia/Jakarta';

                    $zona_waktu = match ($timezone) {
                        'Asia/Jakarta' => 'WIB',
                        'Asia/Makassar' => 'WITA',
                        'Asia/Jayapura' => 'WIT',
                        default => 'WIB',
                    };

                    $kota = $ipData['city'] ?? 'Unknown';
                }
            } catch (\Exception $e) {
                Log::warning('Gagal ambil data IP untuk fallback', ['error' => $e->getMessage()]);
            }
        }

        // Simpan atau update subscription
        UserSubscription::updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'keys' => $data['keys'],
                'user_id' => auth()->id() ?? null,
                'zona_waktu' => $zona_waktu,
                'kota' => $kota,
            ]
        );

        Log::info('Subscription berhasil disimpan', [
            'endpoint' => $data['endpoint'],
            'zona_waktu' => $zona_waktu,
            'kota' => $kota,
        ]);

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
