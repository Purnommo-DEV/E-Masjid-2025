<?php

namespace App\Console\Commands;

use App\Models\Acara;
use App\Models\Berita;
use App\Models\Galeri;
use App\Models\Pengumuman;
use App\Models\QurbanReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url as SitemapUrl;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate {--base-url= : Production base URL for sitemap and robots.txt}';

    protected $description = 'Generate public/sitemap.xml dan public/robots.txt untuk E-Masjid';

    private string $baseUrl;

    public function handle(): int
    {
        $this->info('Memulai generate sitemap...');

        try {
            $this->baseUrl = $this->normalizeBaseUrl($this->option('base-url') ?: config('app.url'));

            $sitemap = Sitemap::create();

            $this->addStaticUrls($sitemap);
            $this->addBeritaUrls($sitemap);
            $this->addAcaraUrls($sitemap);
            $this->addPengumumanUrls($sitemap);
            $this->addQurbanReportUrls($sitemap);
            $this->addGaleriImages($sitemap);

            $sitemap->writeToFile(public_path('sitemap.xml'));
            $this->writeRobotsTxt($this->baseUrl);

            $this->info('Sitemap berhasil dibuat di: ' . public_path('sitemap.xml'));
            $this->info('Robots berhasil dibuat di: ' . public_path('robots.txt'));

            Log::info('Sitemap dan robots.txt berhasil digenerate.', [
                'base_url' => $this->baseUrl,
                'generated_at' => now()->toDateTimeString(),
            ]);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Gagal generate sitemap: ' . $e->getMessage());
            Log::error('Gagal generate sitemap: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return self::FAILURE;
        }
    }

    private function addStaticUrls(Sitemap $sitemap): void
    {
        $this->addUrl($sitemap, $this->siteUrl(route('home', [], false)), 1.0, SitemapUrl::CHANGE_FREQUENCY_DAILY);
        $this->addUrl($sitemap, $this->siteUrl(route('berita.index', [], false)), 0.8, SitemapUrl::CHANGE_FREQUENCY_DAILY);
        $this->addUrl($sitemap, $this->siteUrl(route('acara.index', [], false)), 0.8, SitemapUrl::CHANGE_FREQUENCY_DAILY);
        $this->addUrl($sitemap, $this->siteUrl(route('pengumuman.index', [], false)), 0.7, SitemapUrl::CHANGE_FREQUENCY_DAILY);
        $this->addUrl($sitemap, $this->siteUrl(route('program-ramadhan.index', [], false)), 0.7, SitemapUrl::CHANGE_FREQUENCY_WEEKLY);
        $this->addUrl($sitemap, $this->siteUrl(route('qurban.index', [], false)), 0.7, SitemapUrl::CHANGE_FREQUENCY_WEEKLY);
        $this->addUrl($sitemap, $this->siteUrl(route('qurban.laporan', [], false)), 0.6, SitemapUrl::CHANGE_FREQUENCY_MONTHLY);
    }

    private function addBeritaUrls(Sitemap $sitemap): void
    {
        Berita::query()
            ->with(['kategoris', 'coverImage'])
            ->published()
            ->latest('published_at')
            ->get()
            ->each(function (Berita $berita) use ($sitemap) {
                $isProgramRamadhan = $berita->kategoris->contains('slug', 'program-ramadhan-1447h');
                $routeName = $isProgramRamadhan ? 'program-ramadhan.show' : 'berita.show';

                $tag = $this->makeUrl(
                    $this->siteUrl(route($routeName, $berita->slug, false)),
                    0.7,
                    SitemapUrl::CHANGE_FREQUENCY_WEEKLY,
                    $berita->updated_at ?? $berita->published_at
                );

                if ($image = $this->absoluteUrl($berita->cover_url)) {
                    $tag->addImage($image, Str::limit($berita->judul, 100), '', $berita->judul);
                }

                $sitemap->add($tag);
            });
    }

    private function addAcaraUrls(Sitemap $sitemap): void
    {
        Acara::query()
            ->published()
            ->latest('mulai')
            ->get()
            ->each(function (Acara $acara) use ($sitemap) {
                $tag = $this->makeUrl(
                    $this->siteUrl(route('acara.show', $acara->slug, false)),
                    0.75,
                    SitemapUrl::CHANGE_FREQUENCY_WEEKLY,
                    $acara->updated_at ?? $acara->published_at
                );

                if ($image = $this->absoluteUrl(get_image_url($acara->image_path))) {
                    $tag->addImage($image, Str::limit($acara->judul, 100), '', $acara->judul);
                }

                $sitemap->add($tag);
            });
    }

    private function addPengumumanUrls(Sitemap $sitemap): void
    {
        $hasSlug = Schema::hasColumn('pengumumans', 'slug');

        Pengumuman::query()
            ->active()
            ->latest()
            ->get()
            ->each(function (Pengumuman $pengumuman) use ($sitemap, $hasSlug) {
                $identifier = $hasSlug && $pengumuman->slug ? $pengumuman->slug : $pengumuman->id;

                $sitemap->add($this->makeUrl(
                    $this->siteUrl(route('pengumuman.show', $identifier, false)),
                    0.65,
                    SitemapUrl::CHANGE_FREQUENCY_WEEKLY,
                    $pengumuman->updated_at
                ));
            });
    }

    private function addQurbanReportUrls(Sitemap $sitemap): void
    {
        QurbanReport::query()
            ->where('masjid_code', masjid())
            ->where('is_published', true)
            ->orderByDesc('tahun_hijriah')
            ->get()
            ->each(function (QurbanReport $report) use ($sitemap) {
                $sitemap->add($this->makeUrl(
                    $this->siteUrl(route('qurban.laporan', $report->tahun_hijriah, false)),
                    0.65,
                    SitemapUrl::CHANGE_FREQUENCY_MONTHLY,
                    $report->updated_at
                ));
            });
    }

    private function addGaleriImages(Sitemap $sitemap): void
    {
        $galeris = Galeri::query()
            ->with(['media'])
            ->published()
            ->latest('published_at')
            ->get();

        $tag = $this->makeUrl(
            $this->siteUrl(route('galeri.index', [], false)),
            0.7,
            SitemapUrl::CHANGE_FREQUENCY_WEEKLY,
            $galeris->max('updated_at') ?? now()
        );

        $galeris->each(function (Galeri $galeri) use ($tag) {
            foreach ($galeri->media as $media) {
                if ($image = $this->absoluteUrl(get_image_url($media->image_path))) {
                    $tag->addImage($image, Str::limit($galeri->judul, 100), '', $galeri->judul);
                }
            }

            if ($galeri->tipe === 'video' && $thumbnail = $this->absoluteUrl($galeri->thumbnail_url)) {
                $tag->addImage($thumbnail, Str::limit($galeri->judul, 100), '', $galeri->judul);
            }
        });

        $sitemap->add($tag);
    }

    private function addUrl(
        Sitemap $sitemap,
        string $url,
        float $priority,
        string $changeFrequency,
        mixed $lastModified = null
    ): void {
        $sitemap->add($this->makeUrl($url, $priority, $changeFrequency, $lastModified ?? now()));
    }

    private function makeUrl(
        string $url,
        float $priority,
        string $changeFrequency,
        mixed $lastModified = null
    ): SitemapUrl {
        $tag = SitemapUrl::create($url)
            ->setPriority($priority)
            ->setChangeFrequency($changeFrequency);

        if ($lastModified) {
            $tag->setLastModificationDate($lastModified);
        }

        return $tag;
    }

    private function absoluteUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        return Str::startsWith($url, ['http://', 'https://'])
            ? $url
            : $this->siteUrl($url);
    }

    private function normalizeBaseUrl(string $url): string
    {
        return rtrim($url, '/');
    }

    private function siteUrl(string $path = ''): string
    {
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $path = ltrim($path, '/');

        return $path === ''
            ? $this->baseUrl
            : $this->baseUrl . '/' . $path;
    }

    private function writeRobotsTxt(string $baseUrl): void
    {
        $content = implode(PHP_EOL, [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin/',
            'Disallow: /admin',
            'Disallow: /login',
            'Disallow: /logout',
            'Disallow: /register',
            'Disallow: /profile',
            'Disallow: /dashboard',
            'Disallow: /api/',
            'Disallow: /test-gemini',
            'Disallow: /test-deepseek',
            'Disallow: /test-groq',
            '',
            'Sitemap: ' . $baseUrl . '/sitemap.xml',
            '',
        ]);

        file_put_contents(public_path('robots.txt'), $content);
    }
}
