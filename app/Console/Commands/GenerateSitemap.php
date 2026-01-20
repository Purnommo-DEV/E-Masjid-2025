<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use Illuminate\Support\Facades\Log;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';
    protected $description = 'Generate sitemap.xml untuk seluruh situs E-Masjid';

    public function handle()
    {
        $this->info('Memulai generate sitemap...');

        try {
            $baseUrl = config('app.url'); // dari .env APP_URL

            $sitemap = Sitemap::create();

            // Halaman statis utama
            $sitemap->add(
                Url::create($baseUrl)
                    ->setPriority(1.0)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                    ->setLastModificationDate(now())
            );

            $sitemap->add(
                Url::create($baseUrl . '/jadwal-sholat')
                    ->setPriority(0.9)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
            );

            $sitemap->add(
                Url::create($baseUrl . '/donasi')
                    ->setPriority(0.8)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
            );

            $sitemap->add(
                Url::create($baseUrl . '/acara')
                    ->setPriority(0.7)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
            );

            $sitemap->add(
                Url::create($baseUrl . '/berita')
                    ->setPriority(0.6)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
            );

            $sitemap->add(
                Url::create($baseUrl . '/kontak')
                    ->setPriority(0.5)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
            );

            // Tulis ke file
            $sitemap->writeToFile(public_path('sitemap.xml'));

            $this->info('Sitemap berhasil dibuat di: ' . public_path('sitemap.xml'));
            Log::info('Sitemap berhasil digenerate pada ' . now() . ' dengan base URL: ' . $baseUrl);
        } catch (\Exception $e) {
            $this->error('Gagal generate sitemap: ' . $e->getMessage());
            Log::error('Gagal generate sitemap: ' . $e->getMessage());
        }
    }
}