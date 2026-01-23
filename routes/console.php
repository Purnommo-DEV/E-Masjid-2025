<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

use App\Models\UserSubscription;
use App\Models\Acara;
use App\Jobs\SendPushNotificationJob;

/*
|--------------------------------------------------------------------------
| Helper Debug (terminal + log)
|--------------------------------------------------------------------------
*/
function console_debug(string $text, string $prefix = 'INFO'): void
{
    if (!config('app.debug') && !env('SHOLAT_DEBUG')) {
        return;
    }

    $msg = '[' . now()->format('H:i:s') . "] [$prefix] $text";

    // selalu masuk log
    Log::info($msg);

    // hanya muncul di terminal saat schedule:run / artisan manual
    if (app()->runningInConsole()) {
        echo $msg . PHP_EOL;
    }
}

/*
|--------------------------------------------------------------------------
| Ambil waktu sholat dari API (cache per hari per kota)
|--------------------------------------------------------------------------
*/
function getWaktuSholat(string $kota): array
{
    return Cache::remember(
        'waktu_sholat_' . strtolower($kota) . '_' . now()->toDateString(),
        now()->endOfDay(),
        function () use ($kota) {

            $res = Http::timeout(10)->get(
                'https://api.aladhan.com/v1/timingsByCity',
                [
                    'city'    => $kota,
                    'country' => 'Indonesia',
                    'method'  => 11,
                ]
            );

            if (!$res->successful()) {
                throw new Exception("API sholat gagal untuk kota $kota");
            }

            return $res->json('data.timings');
        }
    );
}

/*
|--------------------------------------------------------------------------
| Hadist / Quotes
|--------------------------------------------------------------------------
*/
function getDailyIslamicQuote(): array
{
    return Cache::remember(
        'daily_islamic_quote_' . now()->toDateString(),
        now()->endOfDay(),
        function () {

            $quotes = [
                [
                    'title' => 'Hadits Shahih',
                    'text'  => 'Sebaik-baik manusia adalah yang paling bermanfaat bagi manusia lainnya. (HR. Ahmad)',
                ],
                [
                    'title' => 'Hadits Shahih',
                    'text'  => 'Amalan yang paling dicintai Allah adalah amalan yang terus-menerus meskipun sedikit. (HR. Bukhari & Muslim)',
                ],
                [
                    'title' => 'Hadits Shahih',
                    'text'  => 'Barang siapa yang menempuh jalan untuk mencari ilmu, Allah akan mudahkan baginya jalan menuju surga. (HR. Muslim)',
                ],
                [
                    'title' => 'Nasihat Ulama',
                    'text'  => 'Hati akan rusak jika terlalu sibuk dengan dunia dan lalai dari mengingat Allah.',
                ],
            ];

            return $quotes[array_rand($quotes)];
        }
    );
}


/*
|--------------------------------------------------------------------------
| REGISTER SEMUA SCHEDULER
|--------------------------------------------------------------------------
*/
app()->booted(function () {

    $schedule = app(Schedule::class);


    /*
    |--------------------------------------------------------------------------
    | SCHEDULER QUOTES / HADITS PAGI (06:00 & 08:00)
    |--------------------------------------------------------------------------
    */
    $schedule->call(function () {

        console_debug('Scheduler Quotes Pagi START', 'START');

        $subs = UserSubscription::all();

        if ($subs->isEmpty()) {
            console_debug('Tidak ada subscriber quotes', 'SKIP');
            return;
        }

        $quote = getDailyIslamicQuote();

        console_debug("Quote hari ini: {$quote['text']}", 'INFO');

        foreach ($subs as $sub) {
            SendPushNotificationJob::dispatch(
                $quote['title'],
                $quote['text'],
                '/quotes-harian',
                $sub->endpoint,
                $sub->keys
            )->onQueue('push');
        }

        console_debug('Scheduler Quotes Pagi END', 'END');

    })
    ->name('quotes-pagi')
    ->twiceDaily(6, 8)
    ->withoutOverlapping(600);


    /*
    |--------------------------------------------------------------------------
    | SCHEDULER SHOLAT HARIAN
    |--------------------------------------------------------------------------
    */
    $schedule->call(function () {

        console_debug('Scheduler Sholat Harian START', 'START');

        $timezoneMap = [
            'WIB'  => 'Asia/Jakarta',
            'WITA' => 'Asia/Makassar',
            'WIT'  => 'Asia/Jayapura',
        ];

        $groups = UserSubscription::all()
            ->groupBy(fn ($u) => $u->kota . '|' . $u->zona_waktu);

        if ($groups->isEmpty()) {
            console_debug('Tidak ada subscriber aktif', 'SKIP');
            return;
        }

        foreach ($groups as $key => $subs) {

            [$kota, $zona] = explode('|', $key);
            $timezone = $timezoneMap[$zona] ?? 'Asia/Jakarta';
            $now = now($timezone);

            console_debug("Zona: $zona | Kota: $kota | TZ: $timezone", 'GROUP');
            console_debug("Waktu lokal: {$now->format('Y-m-d H:i:s')}");
            console_debug("Jumlah subscriber: {$subs->count()}");

            try {
                $timings = getWaktuSholat($kota);
            } catch (\Throwable $e) {
                console_debug("Gagal ambil API sholat untuk $kota", 'ERROR');
                Log::error($e->getMessage());
                continue;
            }

            $waktuSholat = [
                'Subuh'   => $timings['Fajr'],
                'Dzuhur'  => $timings['Dhuhr'],
                'Ashar'   => $timings['Asr'],
                'Maghrib' => $timings['Maghrib'],
                'Isya'    => $timings['Isha'],
            ];

            console_debug('Jadwal sholat hari ini:');
            foreach ($waktuSholat as $s => $t) {
                console_debug("• $s → $t");
            }

            foreach ($waktuSholat as $sholat => $adzan) {

                $waktuKirim = Carbon::createFromFormat('H:i', $adzan, $timezone)
                    ->subMinutes(5);

                console_debug(
                    "Cek $sholat | Adzan $adzan | Kirim {$waktuKirim->format('H:i')}",
                    'CHECK'
                );

                if (!$now->between($waktuKirim, $waktuKirim->copy()->addMinute())) {
                    continue;
                }

                $cacheKey = "notif_{$kota}_{$sholat}_" . $now->toDateString();

                if (Cache::has($cacheKey)) {
                    console_debug("$sholat sudah dikirim (cache)", 'SKIP');
                    continue;
                }

                Cache::put($cacheKey, true, $now->copy()->endOfDay());

                console_debug("DISPATCH NOTIF $sholat ke {$subs->count()} user", 'SEND');

                foreach ($subs as $sub) {
                    SendPushNotificationJob::dispatch(
                        "Pengingat Sholat $sholat",
                        "Assalamualaikum, waktu sholat $sholat sebentar lagi pukul $adzan 🕌",
                        '/jadwal-sholat',
                        $sub->endpoint,
                        $sub->keys
                    )->onQueue('push');
                }
            }

            console_debug('Selesai group ini', 'DONE');
        }

        console_debug('Scheduler Sholat Harian END', 'END');

    })
    ->name('pengingat-sholat')
    ->everyMinute()
    ->withoutOverlapping(300);

    /*
    |--------------------------------------------------------------------------
    | SCHEDULER SHOLAT JUMAT
    |--------------------------------------------------------------------------
    */
    $schedule->call(function () {

        console_debug('Scheduler Sholat Jumat START', 'START');

        $waktuKirim = [
            'WIB'  => '09:00',
            'WITA' => '10:00',
            'WIT'  => '11:00',
        ];

        $subs = UserSubscription::all()->groupBy('zona_waktu');

        foreach ($subs as $zona => $list) {

            $timezone = match ($zona) {
                'WITA' => 'Asia/Makassar',
                'WIT'  => 'Asia/Jayapura',
                default => 'Asia/Jakarta',
            };

            $now = now($timezone);

            console_debug("Zona: $zona | {$now->format('l H:i:s')}", 'GROUP');

            if (!$now->isFriday()) {
                console_debug('Bukan hari Jumat', 'SKIP');
                continue;
            }

            $target = Carbon::createFromFormat('H:i', $waktuKirim[$zona], $timezone);

            if (!$now->between($target, $target->copy()->addMinute())) {
                console_debug('Belum masuk waktu kirim', 'WAIT');
                continue;
            }

            $cacheKey = "notif_jumat_{$zona}_" . $now->toDateString();
            if (Cache::has($cacheKey)) {
                console_debug('Notif Jumat sudah dikirim', 'SKIP');
                continue;
            }

            Cache::put($cacheKey, true, $now->copy()->endOfDay());

            console_debug("DISPATCH NOTIF JUMAT ke {$list->count()} user", 'SEND');

            foreach ($list as $sub) {
                SendPushNotificationJob::dispatch(
                    'Pengingat Sholat Jumat',
                    'Assalamualaikum, hari ini Jumat. Yuk sholat Jumat berjamaah 🕌',
                    '/pengumuman-jumat',
                    $sub->endpoint,
                    $sub->keys
                )->onQueue('push');
            }
        }

        console_debug('Scheduler Sholat Jumat END', 'END');

    })
    ->name('pengingat-sholat-jumat')
    ->everyMinute()
    ->withoutOverlapping(600);

    /*
    |--------------------------------------------------------------------------
    | SCHEDULER REMINDER ACARA (H-3 & H-1)
    |--------------------------------------------------------------------------
    */
    $schedule->call(function () {


        console_debug('### VERSION BARU AKTIF ###', 'DEBUG');


        console_debug('Scheduler Reminder Acara START', 'START');

        $now = now();
        $subs = UserSubscription::all();
        

        // SendPushNotificationJob::dispatch(
        //     'FORCE TEST REMINDER',
        //     'Kalau ini masuk, scheduler sudah pakai kode baru',
        //     '/',
        //     $subs->first()->endpoint,
        //     $subs->first()->keys
        // )->onQueue('push');

        // return;
        
        console_debug('Total subscriber reminder: ' . $subs->count(), 'INFO');

        if ($subs->isEmpty()) {
            console_debug('Tidak ada subscriber untuk reminder acara', 'SKIP');
            return;
        }
        $acaras = Acara::where('is_published', true)
            ->where('mulai', '>', $now)
            ->get();

        console_debug("Total acara aktif: {$acaras->count()}");

        foreach ($acaras as $acara) {

            $mulai = Carbon::parse($acara->mulai);

            $diffHari = $now->copy()
                ->startOfDay()
                ->diffInDays($mulai->copy()->startOfDay(), false);

            console_debug("Acara: {$acara->judul} | H-$diffHari");

            $targets = [
                3 => 'H-3',
                2 => 'H-2',
                1 => 'H-1',
            ];

            foreach ($targets as $hari => $label) {

                if ($diffHari < $hari || $diffHari >= ($hari + 1)) {
                    continue;
                }

                $cacheKey = "notif_acara_{$acara->id}_{$label}";

                if (Cache::has($cacheKey)) {
                    console_debug("$label sudah dikirim sebelumnya", 'SKIP');
                    continue;
                }

                Cache::put($cacheKey, true, $mulai);

                console_debug("DISPATCH $label ke {$subs->count()} user", 'SEND');

                foreach ($subs as $sub) {
                    SendPushNotificationJob::dispatch(
                        "Reminder Acara $label ⏰",
                        "{$acara->judul}\n{$mulai->translatedFormat('l, d F Y H:i')}",
                        '/acara/' . $acara->slug,
                        $sub->endpoint,
                        $sub->keys
                    )->onQueue('push');
                }
            }
        }

        console_debug('Scheduler Reminder Acara END', 'END');

    })
    ->name('reminder-acara')
    ->dailyAt('08:00')
    ->withoutOverlapping(3600);


});