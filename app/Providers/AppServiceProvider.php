<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Interfaces\MasjidRepositoryInterface;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{

    protected $listen = [
        \App\Events\AcaraPublished::class => [
            \App\Listeners\SendAcaraPublishedNotification::class,
        ],
    ];
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\JadwalSholatService::class);
        
        // Binding MasjidRepositoryInterface dinamis
        $this->app->bind(\App\Interfaces\MasjidRepositoryInterface::class, function ($app) {
            $masjidName = masjid(); // ambil nama masjid dari helper

            // Default repository namespace jika masjid tidak ada
            $class = "\\App\\Repositories\\{$masjidName}\\MasjidRepository";

            if (!class_exists($class)) {
                throw new \Exception("Repository untuk masjid '{$masjidName}' tidak ditemukan: {$class}");
            }

            return $app->make($class);
        });

        // Binding KategoriRepositoryInterface dinamis sesuai masjid
        $this->app->bind(\App\Interfaces\KategoriRepositoryInterface::class, function ($app) {
            $masjidName = masjid();

            $class = "\\App\\Repositories\\{$masjidName}\\KategoriRepository";

            if (!class_exists($class)) {
                throw new \Exception("KategoriRepository untuk masjid '{$masjidName}' tidak ditemukan: {$class}");
            }

            return $app->make($class);
        });

        // Binding BeritaRepositoryInterface dinamis sesuai masjid
        $this->app->bind(\App\Interfaces\BeritaRepositoryInterface::class, function ($app) {
            $masjidName = masjid();

            $class = "\\App\\Repositories\\{$masjidName}\\BeritaRepository";

            if (!class_exists($class)) {
                throw new \Exception("BeritaRepository untuk masjid '{$masjidName}' tidak ditemukan: {$class}");
            }

            return $app->make($class);
        });

        // Binding AcaraRepositoryInterface dinamis sesuai masjid
        $this->app->bind(\App\Interfaces\AcaraRepositoryInterface::class, function ($app) {
            $masjidName = masjid();

            $class = "\\App\\Repositories\\{$masjidName}\\AcaraRepository";

            if (!class_exists($class)) {
                throw new \Exception("AcaraRepository untuk masjid '{$masjidName}' tidak ditemukan: {$class}");
            }

            return $app->make($class);
        });

        // Binding GaleriRepositoryInterface dinamis sesuai masjid
        $this->app->bind(\App\Interfaces\GaleriRepositoryInterface::class, function ($app) {
            $masjidName = masjid();

            $class = "\\App\\Repositories\\{$masjidName}\\GaleriRepository";

            if (!class_exists($class)) {
                throw new \Exception("GaleriRepository untuk masjid '{$masjidName}' tidak ditemukan: {$class}");
            }

            return $app->make($class);
        });

        // Binding PengumumanRepositoryInterface dinamis sesuai masjid
        $this->app->bind(\App\Interfaces\PengumumanRepositoryInterface::class, function ($app) {
            $masjidName = masjid();

            $class = "\\App\\Repositories\\{$masjidName}\\PengumumanRepository";

            if (!class_exists($class)) {
                throw new \Exception("PengumumanRepository untuk masjid '{$masjidName}' tidak ditemukan: {$class}");
            }

            return $app->make($class);
        });

        // Binding ProfilMasjidRepositoryInterface dinamis sesuai masjid
        $this->app->bind(\App\Interfaces\ProfilMasjidRepositoryInterface::class, function ($app) {
            $masjidName = masjid();

            $class = "\\App\\Repositories\\{$masjidName}\\ProfilMasjidRepository";

            if (!class_exists($class)) {
                throw new \Exception("ProfilMasjidRepository untuk masjid '{$masjidName}' tidak ditemukan: {$class}");
            }

            return $app->make($class);
        });

        // Binding KotakInfakRepositoryInterface dinamis sesuai masjid
        $this->app->bind(\App\Interfaces\KotakInfakRepositoryInterface::class, function ($app) {
            $masjidName = masjid();

            $class = "\\App\\Repositories\\{$masjidName}\\KotakInfakRepository";

            if (!class_exists($class)) {
                throw new \Exception("KotakInfakRepository untuk masjid '{$masjidName}' tidak ditemukan: {$class}");
            }

            return $app->make($class);
        });

        // Binding AkunKeuanganRepositoryInterface dinamis sesuai masjid
        $this->app->bind(\App\Interfaces\AkunKeuanganRepositoryInterface::class, function ($app) {
            $masjidName = masjid();

            $class = "\\App\\Repositories\\{$masjidName}\\AkunKeuanganRepository";

            if (!class_exists($class)) {
                throw new \Exception("AkunKeuanganRepository untuk masjid '{$masjidName}' tidak ditemukan: {$class}");
            }

            return $app->make($class);
        });

        // Binding SaldoAwalRepositoryInterface dinamis sesuai masjid
        $this->app->bind(\App\Interfaces\SaldoAwalRepositoryInterface::class, function ($app) {
            $masjidName = masjid();

            $class = "\\App\\Repositories\\{$masjidName}\\SaldoAwalRepository";

            if (!class_exists($class)) {
                throw new \Exception("SaldoAwalRepository untuk masjid '{$masjidName}' tidak ditemukan: {$class}");
            }

            return $app->make($class);
        });

        // Binding JurnalRepositoryInterface dinamis sesuai masjid
        $this->app->bind(\App\Interfaces\JurnalRepositoryInterface::class, function ($app) {
            $masjidName = masjid();

            $class = "\\App\\Repositories\\{$masjidName}\\JurnalRepository";

            if (!class_exists($class)) {
                throw new \Exception("JurnalRepository untuk masjid '{$masjidName}' tidak ditemukan: {$class}");
            }

            return $app->make($class);
        });

        // Binding ZakatRepositoryInterface dinamis sesuai masjid
        $this->app->bind(\App\Interfaces\ZakatRepositoryInterface::class, function ($app) {
            $masjidName = masjid();

            $class = "\\App\\Repositories\\{$masjidName}\\ZakatRepository";

            if (!class_exists($class)) {
                throw new \Exception("ZakatRepository untuk masjid '{$masjidName}' tidak ditemukan: {$class}");
            }

            return $app->make($class);
        });

        // Binding DanaTerikatRepositoryInterfaceInterface dinamis sesuai masjid
        $this->app->bind(\App\Interfaces\DanaTerikatRepositoryInterface::class, function ($app) {
            $masjidName = masjid();

            $class = "\\App\\Repositories\\{$masjidName}\\DanaTerikatRepository";

            if (!class_exists($class)) {
                throw new \Exception("DanaTerikatRepository untuk masjid '{$masjidName}' tidak ditemukan: {$class}");
            }

            return $app->make($class);
        });

        // Binding DanaTerikatReferensiRepositoryInterfaceInterface dinamis sesuai masjid
        $this->app->bind(\App\Interfaces\DanaTerikatReferensiRepositoryInterface::class, function ($app) {
            $masjidName = masjid();

            $class = "\\App\\Repositories\\{$masjidName}\\DanaTerikatReferensiRepository";

            if (!class_exists($class)) {
                throw new \Exception("DanaTerikatReferensiRepository untuk masjid '{$masjidName}' tidak ditemukan: {$class}");
            }

            return $app->make($class);
        });

        // Binding BannerRepositoryInterfaceInterface dinamis sesuai masjid
        $this->app->bind(\App\Interfaces\BannerRepositoryInterface::class, function ($app) {
            $masjidName = masjid();

            $class = "\\App\\Repositories\\{$masjidName}\\BannerRepository";

            if (!class_exists($class)) {
                throw new \Exception("BannerRepository untuk masjid '{$masjidName}' tidak ditemukan: {$class}");
            }

            return $app->make($class);
        });

        // Binding LayananRepositoryInterfaceInterface dinamis sesuai masjid
        $this->app->bind(\App\Interfaces\LayananRepositoryInterface::class, function ($app) {
            $masjidName = masjid();

            $class = "\\App\\Repositories\\{$masjidName}\\LayananRepository";

            if (!class_exists($class)) {
                throw new \Exception("LayananRepository untuk masjid '{$masjidName}' tidak ditemukan: {$class}");
            }

            return $app->make($class);
        });

        // resolve BannerService dinamis (sesuai lokasi folder kamu)
        $this->app->bind(\App\Interfaces\BannerServiceInterface::class, function ($app) {
            $masjid = masjid(); // contoh: "mrj"
            $class  = "\\App\\Services\\{$masjid}\\BannerService";

            if (!class_exists($class)) {
                throw new \Exception("BannerService untuk masjid {$masjid} tidak ditemukan: {$class}");
            }

            return $app->make($class);
        });

        // resolve AcaraService dinamis (sesuai lokasi folder kamu)
        $this->app->bind(\App\Interfaces\AcaraServiceInterface::class, function ($app) {
            $masjid = masjid(); // contoh: "mrj"
            $class  = "\\App\\Services\\{$masjid}\\AcaraService";

            if (!class_exists($class)) {
                throw new \Exception("AcaraService untuk masjid {$masjid} tidak ditemukan: {$class}");
            }

            return $app->make($class);
        });

        // resolve BeritaService dinamis (sesuai lokasi folder kamu)
        $this->app->bind(\App\Interfaces\BeritaServiceInterface::class, function ($app) {
            $masjid = masjid(); // contoh: "mrj"
            $class  = "\\App\\Services\\{$masjid}\\BeritaService";

            if (!class_exists($class)) {
                throw new \Exception("BeritaService untuk masjid {$masjid} tidak ditemukan: {$class}");
            }

            return $app->make($class);
        });

        // resolve PengumumanService dinamis (sesuai lokasi folder kamu)
        $this->app->bind(\App\Interfaces\PengumumanServiceInterface::class, function ($app) {
            $masjid = masjid(); // contoh: "mrj"
            $class  = "\\App\\Services\\{$masjid}\\PengumumanService";

            if (!class_exists($class)) {
                throw new \Exception("PengumumanService untuk masjid {$masjid} tidak ditemukan: {$class}");
            }

            return $app->make($class);
        });

        // resolve GaleriService dinamis (sesuai lokasi folder kamu)
        $this->app->bind(\App\Interfaces\GaleriServiceInterface::class, function ($app) {
            $masjid = masjid(); // contoh: "mrj"
            $class  = "\\App\\Services\\{$masjid}\\GaleriService";

            if (!class_exists($class)) {
                throw new \Exception("GaleriService untuk masjid {$masjid} tidak ditemukan: {$class}");
            }

            return $app->make($class);
        });

        // Binding SlideMotivasiRepositoryInterfaceInterface dinamis sesuai masjid
        $this->app->bind(\App\Interfaces\SlideMotivasiRepositoryInterface::class, function ($app) {
            $masjidName = masjid();

            $class = "\\App\\Repositories\\{$masjidName}\\SlideMotivasiRepository";

            if (!class_exists($class)) {
                throw new \Exception("SlideMotivasiRepository untuk masjid '{$masjidName}' tidak ditemukan: {$class}");
            }

            return $app->make($class);
        });

        // Binding QuoteHarianRepositoryInterfaceInterface dinamis sesuai masjid
        $this->app->bind(\App\Interfaces\QuoteHarianRepositoryInterface::class, function ($app) {
            $masjidName = masjid();

            $class = "\\App\\Repositories\\{$masjidName}\\QuoteHarianRepository";

            if (!class_exists($class)) {
                throw new \Exception("QuoteHarianRepository untuk masjid '{$masjidName}' tidak ditemukan: {$class}");
            }

            return $app->make($class);
        });

        // Binding QuoteHarianRepositoryInterfaceInterface dinamis sesuai masjid
        $this->app->bind(\App\Interfaces\QuoteHarianRepositoryInterface::class, function ($app) {
            $masjidName = masjid();

            $class = "\\App\\Repositories\\{$masjidName}\\QuoteHarianRepository";

            if (!class_exists($class)) {
                throw new \Exception("QuoteHarianRepository untuk masjid '{$masjidName}' tidak ditemukan: {$class}");
            }

            return $app->make($class);
        });

        // Binding KhutbahJumatRepositoryInterfaceInterface dinamis sesuai masjid
        $this->app->bind(\App\Interfaces\KhutbahJumatRepositoryInterface::class, function ($app) {
            $masjidName = masjid();

            $class = "\\App\\Repositories\\{$masjidName}\\KhutbahJumatRepository";

            if (!class_exists($class)) {
                throw new \Exception("KhutbahJumatRepository untuk masjid '{$masjidName}' tidak ditemukan: {$class}");
            }

            return $app->make($class);
        });
        
        // Binding JadwalImamTarawihRepositoryInterfaceInterface dinamis sesuai masjid
        $this->app->bind(\App\Interfaces\JadwalImamTarawihRepositoryInterface::class, function ($app) {
            $masjidName = masjid();

            $class = "\\App\\Repositories\\{$masjidName}\\JadwalImamTarawihRepository";

            if (!class_exists($class)) {
                throw new \Exception("JadwalImamTarawihRepository untuk masjid '{$masjidName}' tidak ditemukan: {$class}");
            }

            return $app->make($class);
        });

        // Binding LaporanRamadhanHarianRepositoryInterfaceInterface dinamis sesuai masjid
        $this->app->bind(\App\Interfaces\LaporanRamadhanHarianRepositoryInterface::class, function ($app) {
            $masjidName = masjid();

            $class = "\\App\\Repositories\\{$masjidName}\\LaporanRamadhanHarianRepository";

            if (!class_exists($class)) {
                throw new \Exception("LaporanRamadhanHarianRepository untuk masjid '{$masjidName}' tidak ditemukan: {$class}");
            }

            return $app->make($class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Paksa HTTPS kalau lewat Cloudflare / tunnel
        if (request()->header('x-forwarded-proto') === 'https') {
            URL::forceScheme('https');
        }

        View::composer('*', function ($view) {

            $today = Carbon::now();

            // Tanggal estimasi Ramadhan (boleh kamu ubah nanti)
            $ramadhanStart = Carbon::parse('2026-02-19');
            $ramadhanEnd   = Carbon::parse('2026-03-20');

            $isRamadhan = $today->between($ramadhanStart, $ramadhanEnd);

            $view->with('isRamadhan', $isRamadhan);
        });
    }


}
