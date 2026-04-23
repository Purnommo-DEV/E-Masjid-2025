<?php

namespace App\Services\mrj;

use App\Interfaces\BannerServiceInterface;
use App\Models\Banner;

class BannerService implements BannerServiceInterface
{
    public function sliderPages(int $perPage = 3): array
    {
        // 1️⃣ Ambil banner aktif + urutkan (tanpa with('media'))
        $banners = Banner::query()
            ->where('is_active', true)
            ->whereNotNull('image_path')  // Hanya yang punya gambar
            ->orderBy('urutan')
            ->orderBy('id', 'asc')
            ->get();

        if ($banners->isEmpty()) {
            return [];
        }

        // 2️⃣ Mapping ke array yang ramah blade (langsung pakai image_path)
        $bannerArr = $banners->map(function ($banner) {
            return [
                'id'       => $banner->id,
                'title'    => $banner->judul,
                'subtitle' => $banner->subjudul,
                'note'     => $banner->catatan_singkat,  // ← catatan_singkat
                'image'    => $banner->gambar_url,       // ← pakai accessor
                'button'   => $banner->label_tombol,     // ← label_tombol
                'url'      => $banner->url_tujuan,       // ← url_tujuan
                'deskripsi'=> $banner->deskripsi,
            ];
        })->toArray();

        // 3️⃣ Susun jadi "pages" untuk slider (3 kartu per slide)
        $perPage = max(1, $perPage);
        $total   = count($bannerArr);

        if ($total === 0) {
            return [];
        }

        // Biar bisa looping tanpa jeda → duplikasi array
        $loopSource = array_merge($bannerArr, $bannerArr);
        $pageCount  = (int) ceil($total / $perPage);

        $pages = [];
        for ($i = 0; $i < $pageCount; $i++) {
            $pages[] = array_slice($loopSource, $i * $perPage, $perPage);
        }

        return $pages;
    }
    
    /**
     * Ambil semua banner aktif (untuk keperluan lain)
     */
    public function getAllActive(): \Illuminate\Database\Eloquent\Collection
    {
        return Banner::query()
            ->where('is_active', true)
            ->whereNotNull('image_path')
            ->orderBy('urutan')
            ->get();
    }
    
    /**
     * Ambil banner by ID (untuk detail)
     */
    public function find($id): ?Banner
    {
        return Banner::where('is_active', true)
            ->where('id', $id)
            ->first();
    }
}