<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Interfaces\MasjidRepositoryInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
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

        // Binding KeuanganRepositoryInterface dinamis sesuai masjid
        $this->app->bind(\App\Interfaces\KeuanganRepositoryInterface::class, function ($app) {
            $masjidName = masjid();

            $class = "\\App\\Repositories\\{$masjidName}\\KeuanganRepository";

            if (!class_exists($class)) {
                throw new \Exception("KeuanganRepository untuk masjid '{$masjidName}' tidak ditemukan: {$class}");
            }

            return $app->make($class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
