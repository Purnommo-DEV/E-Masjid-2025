<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class JadwalSholatService
{
    protected $profil;

    public function __construct()
    {
        $this->profil = \App\Models\ProfilMasjid::first();
    }

    /**
     * Ambil jadwal sholat hari ini (dengan cache 6 jam)
     */
    public function getJadwalHariIni($forceRefresh = false)
    {
        $cacheKey = 'jadwal_sholat_' . now()->format('Ymd') . '_' . ($this->profil?->id ?? 'default');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addHours(6), function () {
            return $this->fetchFromAladhan();
        });
    }

    protected function fetchFromAladhan()
    {
        $latitude  = $this->profil?->latitude  ?? -6.2088; // fallback Jakarta
        $longitude = $this->profil?->longitude ?? 106.8456;
        $method    = 11; // 11 = Kemenag RI (Department of Religious Affairs Indonesia)

        $date = now()->format('d-m-Y');

        $url = "http://api.aladhan.com/v1/timings/{$date}?latitude={$latitude}&longitude={$longitude}&method={$method}";

        try {
            $response = Http::timeout(10)->get($url);

            if ($response->successful()) {
                $data = $response->json()['data'];

                return [
                    'subuh'     => $data['timings']['Fajr']    ?? '--:--',
                    'dzuhur'    => $data['timings']['Dhuhr']   ?? '--:--',
                    'ashar'     => $data['timings']['Asr']     ?? '--:--',
                    'maghrib'   => $data['timings']['Maghrib'] ?? '--:--',
                    'isya'      => $data['timings']['Isha']    ?? '--:--',
                    'tanggal_hijriah' => $data['date']['hijri']['date'] ?? null,
                    'sumber'    => 'Aladhan.com (Metode Kemenag RI)',
                    'lokasi'    => $data['meta']['timezone'] ?? 'Lokasi Masjid',
                ];
            }
        } catch (\Exception $e) {
            \Log::warning('Gagal ambil jadwal sholat dari Aladhan: ' . $e->getMessage());
        }

        // Fallback jika gagal
        return [
            'subuh'     => '--:--',
            'dzuhur'    => '--:--',
            'ashar'     => '--:--',
            'maghrib'   => '--:--',
            'isya'      => '--:--',
            'sumber'    => 'Data sementara tidak tersedia',
            'lokasi'    => 'Lokasi Masjid',
        ];
    }
}