<?php

namespace App\Services\mrj;

use App\Interfaces\BannerServiceInterface;
use App\Models\Banner;

class BannerService implements BannerServiceInterface
{
    public function sliderPages(int $perPage = 3): array
    {
        // 1️⃣ Ambil banner aktif + media
        $banners = Banner::query()
            ->where('is_active', true)
            ->orderBy('urutan')
            ->with('media')
            ->get();

        if ($banners->isEmpty()) {
            return [];
        }

        // 2️⃣ Mapping ke array yang ramah blade
        $bannerArr = $banners->map(function ($b) {
            $media = $b->getFirstMedia('banner');

            return [
                'title'    => $b->judul,
                'subtitle' => $b->subjudul,
                'note'     => $b->note,
                'image'    => $media
                    ? asset(
                        'storage/' .
                        ($media->custom_properties['folder'] ?? 'banner') .
                        '/' . $media->file_name
                    )
                    : asset('images/masjid-banner.jpg'),
                'button'   => $b->button_label,
                'url'      => $b->button_url,
            ];
        })->toArray();

        // 3️⃣ Susun jadi "pages" untuk slider (3 kartu per slide)
        $perPage = max(1, $perPage);
        $total   = count($bannerArr);

        if ($total === 0) {
            return [];
        }

        // biar bisa looping tanpa jeda → duplikasi array
        $loopSource = array_merge($bannerArr, $bannerArr);
        $pageCount  = (int) ceil($total / $perPage);

        $pages = [];
        for ($i = 0; $i < $pageCount; $i++) {
            $pages[] = array_slice($loopSource, $i * $perPage, $perPage);
        }

        return $pages;
    }
}
