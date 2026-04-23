<?php

namespace App\Repositories\mrj;

use App\Interfaces\BannerRepositoryInterface;
use App\Models\Banner;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class BannerRepository implements BannerRepositoryInterface
{
    /**
     * Ambil semua banner untuk DataTable
     */
    public function all()
    {
        return Banner::withoutGlobalScope('masjid')
            ->where('masjid_code', masjid())
            ->latest()
            ->get()
            ->map(function ($banner) {
                return [
                    'id'                => $banner->id,
                    'judul'             => $banner->judul,
                    'subjudul'          => $banner->subjudul,
                    'catatan_singkat'   => $banner->catatan_singkat ?? '-',
                    'button_label'      => $banner->button_label ?? 'Lihat Detail',
                    'urutan'            => $banner->urutan,
                    'is_active'         => $banner->is_active,
                    'status'            => $this->renderStatusBadge($banner->is_active),
                    'gambar'            => $this->renderGambarHtml($banner),
                ];
            });
    }

    /**
     * Cari banner by ID
     */
    public function find($id)
    {
        return Banner::withoutGlobalScope('masjid')
            ->where('masjid_code', masjid())
            ->where('id', $id)
            ->firstOrFail();
    }

    /**
     * Create banner baru
     */
    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $banner = Banner::create([
                'masjid_code'      => masjid(),
                'judul'            => $data['judul'],
                'subjudul'         => $data['subjudul'] ?? null,
                'catatan_singkat'  => $data['catatan_singkat'] ?? null,
                'label_tombol'     => $data['label_tombol'] ?? null,
                'url_tujuan'       => $data['url_tujuan'] ?? null,
                'deskripsi'        => $data['deskripsi'] ?? null,
                'is_active'        => $data['is_active'] ?? false,
                'urutan'           => $data['urutan'] ?? 0,
                'created_by'       => auth()->id(),
            ]);

            // Upload gambar dengan optimasi WebP
            if (!empty($data['gambar']) && $data['gambar'] instanceof UploadedFile) {
                // Opsi 1: Upload + konversi WebP (tanpa resize)
                $imagePath = upload_image($data['gambar'], 'banner', null, true);
                
                // Opsi 2: Upload + resize + konversi WebP (lebih optimal)
                // $imagePath = upload_image_optimized($data['gambar'], 'banner', null, [
                //     'max_width' => 1920,
                //     'max_height' => 1080,
                //     'quality' => 75
                // ]);
                
                $banner->update(['image_path' => $imagePath]);
            }

            return $banner->fresh();
        });
    }

    public function update($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $banner = $this->find($id);
            
            $updateData = [
                'judul'            => $data['judul'],
                'subjudul'         => $data['subjudul'] ?? null,
                'catatan_singkat'  => $data['catatan_singkat'] ?? null,
                'label_tombol'     => $data['label_tombol'] ?? null,
                'url_tujuan'       => $data['url_tujuan'] ?? null,
                'deskripsi'        => $data['deskripsi'] ?? null,
                'is_active'        => $data['is_active'] ?? false,
                'urutan'           => $data['urutan'] ?? 0,
                'updated_by'       => auth()->id(),
            ];
            
            // Upload gambar baru dengan konversi WebP
            if (!empty($data['gambar']) && $data['gambar'] instanceof UploadedFile) {
                $imagePath = upload_image($data['gambar'], 'banner', $banner->image_path, true);
                
                // Opsi 2: Upload + resize + konversi WebP (lebih optimal)
                // $imagePath = upload_image_optimized($data['gambar'], 'banner', null, [
                //     'max_width' => 1920,
                //     'max_height' => 1080,
                //     'quality' => 75
                // ]);

                $updateData['image_path'] = $imagePath;
            }
            
            $banner->update($updateData);
            
            return $banner->fresh();
        });
    }

    /**
     * Delete banner
     */
    public function delete($id)
    {
        return DB::transaction(function () use ($id) {
            $banner = $this->find($id);
            
            // Hapus file gambar
            if ($banner->image_path) {
                delete_image($banner->image_path);
            }
            
            return $banner->delete();
        });
    }

    /**
     * Render status badge
     */
    protected function renderStatusBadge(bool $isActive): string
    {
        $badgeClass = $isActive ? 'bg-success' : 'bg-secondary';
        $statusText = $isActive ? 'Aktif' : 'Nonaktif';
        
        return '<span class="badge ' . $badgeClass . '">' . $statusText . '</span>';
    }

    /**
     * Render gambar HTML untuk DataTable
     */
    protected function renderGambarHtml(Banner $banner): string
    {
        $imageUrl = $banner->gambar_url;
        
        // Cek apakah gambar default atau tidak
        if ($imageUrl && $imageUrl !== asset('assets/e-masjid/images/masjid-banner.jpg')) {
            return '<img src="' . $imageUrl . '" 
                           width="80" 
                           height="50" 
                           class="rounded shadow-sm object-cover" 
                           style="height: 50px; object-fit: cover;"
                           loading="lazy">';
        }
        
        return '<small class="text-muted">Tanpa Gambar</small>';
    }
}