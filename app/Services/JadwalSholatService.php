<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class JadwalSholatService
{
    /**
     * Ambil jadwal sholat hari ini (per kota user)
     */
    public function getJadwalHariIni()
    {
        $cityId = $this->resolveCityId();
        $cacheKey = 'jadwal_' . now('Asia/Jakarta')->format('Ymd') . '_' . $cityId;

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($cityId) {
            return $this->fetchFromKemenag($cityId);
        });
    }

    /**
     * Mapping kota dari session ke ID Kemenag (berdasarkan data API terbaru)
     */
    protected function resolveCityId()
    {
        $sessionCity = session('user_city');

        // Jika session kosong atau mengandung 'bandung' (karena user saat ini di Bandung)
        if (empty($sessionCity) || str_contains(strtolower($sessionCity), 'bandung')) {
            return '1219'; // Kota Bandung
        }

        $normalized = $this->normalizeCity($sessionCity);
        $mapping = config('kota_kemenag', []);

        // Exact match
        if (isset($mapping[$normalized])) {
            return $mapping[$normalized];
        }

        // Fuzzy match lebih toleran
        foreach ($mapping as $name => $id) {
            $sim1 = similar_text($normalized, $name, $percent1);
            $sim2 = similar_text($name, $normalized, $percent2);
            if ($percent1 > 70 || $percent2 > 70 || str_contains($normalized, $name) || str_contains($name, $normalized)) {
                return $id;
            }
        }

        // Fallback ke Kota Bandung (default user location)
        return '1219';
    }

    /**
     * Normalisasi nama kota
     */
    protected function normalizeCity($city)
    {
        if (!$city) {
            return 'bandung'; // default ke bandung karena lokasi user
        }

        $city = strtolower(trim($city));
        $remove = ['kota', 'kabupaten', 'kab.', 'city', 'administrative city', 'adm.', 'kota ', 'kab '];
        $city = str_replace($remove, '', $city);
        $city = trim(preg_replace('/\s+/', ' ', $city));

        $aliases = [
            'south tangerang'       => 'tangerang selatan',
            'tangsel'               => 'tangerang selatan',
            'bandung city'          => 'bandung',
            'kota bandung'          => 'bandung',
            'surabaya city'         => 'surabaya',
            'jakarta selatan city'  => 'jakarta selatan',
            'bdg'                   => 'bandung',
        ];

        return $aliases[$city] ?? $city;
    }

    /**
     * Ambil jadwal dari Kemenag
     */
protected function fetchFromKemenag($cityId)
{
    $now = now('Asia/Jakarta');
    $date = $now->format('d-m-Y');

    // Ambil lokasi dari session (jika ada)
    $userLat = session('user_lat');
    $userLng = session('user_lng');
    $userCity = session('user_city');

    if ($userLat && $userLng) {
        // Gunakan lokasi user dari geolocation
        $latitude  = $userLat;
        $longitude = $userLng;
        $lokasiNama = $userCity ?? 'Lokasi Anda';
    } else {
        // Fallback ke koordinat masjid (misal Taman Cipulir Estate, Tangsel)
        $latitude  = -6.3070; // contoh koordinat Tangsel / Cipulir
        $longitude = 106.7100;
        $lokasiNama = 'Masjid Raudhotul Jannah Taman Cipulir Estate';
    }

    $url = "http://api.aladhan.com/v1/timings/{$date}?latitude={$latitude}&longitude={$longitude}&method=11&adjustment=0";

    try {
        $response = Http::timeout(10)->get($url);

        if (!$response->successful()) {
            Log::warning("Aladhan API gagal - HTTP {$response->status()} | URL: {$url}");
            return $this->fallback();
        }

        $json = $response->json();

        if (!isset($json['code']) || $json['code'] !== 200 || !isset($json['data']['timings'])) {
            Log::warning("Aladhan response invalid | URL: {$url}");
            return $this->fallback();
        }

        $timings = $json['data']['timings'];
        $hijri   = $json['data']['date']['hijri'];

        $hijriFormatted = $hijri['day'] . ' ' . $hijri['month']['en'] . ' ' . $hijri['year'] . ' H';

        return [
            'subuh'           => $timings['Fajr'] ?? '--:--',
            'dzuhur'          => $timings['Dhuhr'] ?? '--:--',
            'ashar'           => $timings['Asr'] ?? '--:--',
            'maghrib'         => $timings['Maghrib'] ?? '--:--',
            'isya'            => $timings['Isha'] ?? '--:--',
            'tanggal_hijriah' => $hijriFormatted ?? '-',
            'lokasi'          => $lokasiNama,
            'sumber'          => 'Kemenag RI via Aladhan API',
        ];
    } catch (\Throwable $e) {
        Log::error("Exception fetch Aladhan: " . $e->getMessage() . " | URL: {$url}");
        return $this->fallback();
    }
}

    protected function fallback()
    {
        return [
            'subuh'     => '--:--',
            'dzuhur'    => '--:--',
            'ashar'     => '--:--',
            'maghrib'   => '--:--',
            'isya'      => '--:--',
            'sumber'    => 'Menunggu lokasi atau koneksi...',
            'lokasi'    => session('user_city', 'jakarta'),
        ];
    }
}