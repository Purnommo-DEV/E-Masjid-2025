<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SeoPage;
use Illuminate\Http\Request;

class SeoPageController extends Controller
{
    public function index()
    {
        $definitions = $this->definitions();

        $pages = collect($definitions)->map(function (string $label, string $key) {
            $seoPage = SeoPage::firstOrCreate([
                'page_key' => $key,
            ], [
                'masjid_code' => masjid(),
            ]);

            return [
                'key' => $key,
                'label' => $label,
                'seo' => $seoPage,
            ];
        });

        return view('masjid.' . masjid() . '.admin.seo-pages.index', compact('pages'));
    }

    public function update(Request $request)
    {
        $definitions = $this->definitions();
        $allowedKeys = array_keys($definitions);

        $validated = $request->validate([
            'pages' => ['required', 'array'],
            'pages.*.title' => ['nullable', 'string', 'max:70'],
            'pages.*.description' => ['nullable', 'string', 'max:170'],
            'pages.*.image' => ['nullable', 'string', 'max:2048'],
            'pages.*.canonical_url' => ['nullable', 'url', 'max:2048'],
            'pages.*.robots' => ['nullable', 'string', 'max:50'],
        ]);

        foreach ($validated['pages'] as $pageKey => $data) {
            if (! in_array($pageKey, $allowedKeys, true)) {
                continue;
            }

            SeoPage::updateOrCreate([
                'masjid_code' => masjid(),
                'page_key' => $pageKey,
            ], [
                'title' => $this->emptyToNull($data['title'] ?? null),
                'description' => $this->emptyToNull($data['description'] ?? null),
                'image' => $this->emptyToNull($data['image'] ?? null),
                'canonical_url' => $this->emptyToNull($data['canonical_url'] ?? null),
                'robots' => $this->emptyToNull($data['robots'] ?? null),
            ]);
        }

        return back()->with('success', 'SEO halaman berhasil diperbarui.');
    }

    private function definitions(): array
    {
        return [
            'home' => 'Beranda',
            'berita.index' => 'Daftar Berita',
            'acara.index' => 'Daftar Acara',
            'pengumuman.index' => 'Daftar Pengumuman',
            'galeri.index' => 'Galeri',
            'program-ramadhan.index' => 'Program Ramadhan',
            'qurban.index' => 'Program Qurban',
            'qurban.laporan' => 'Laporan Qurban',
        ];
    }

    private function emptyToNull(?string $value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;

        return $value === '' ? null : $value;
    }
}
