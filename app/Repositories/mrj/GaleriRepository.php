<?php

namespace App\Repositories\mrj;

use App\Interfaces\GaleriRepositoryInterface;
use App\Models\Galeri;
use App\Models\GaleriMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GaleriRepository implements GaleriRepositoryInterface
{
    public function all()
    {
        return Galeri::withoutGlobalScope('masjid')
            ->where('masjid_code', masjid())
            ->with(['kategoris', 'media'])
            ->latest()
            ->get()
            ->map(function ($galeri) {
                $thumbnailUrl = $galeri->thumbnail_url;
                $thumbnailHtml = '<small class="text-muted">Tanpa Gambar</small>';
                
                if ($thumbnailUrl) {
                    $thumbnailHtml = '<img src="' . $thumbnailUrl . '" width="60" class="rounded" style="height: 40px; object-fit: cover;">';
                }

                $tipeHtml = $galeri->tipe === 'video' 
                    ? '<span class="badge bg-danger">Video</span>'
                    : '<span class="badge bg-primary">Foto</span>';

                return [
                    'id' => $galeri->id,
                    'judul' => $galeri->judul,
                    'tipe' => $tipeHtml,
                    'thumbnail' => $thumbnailHtml,
                    'kategoris' => $galeri->kategoris->map(fn($k) =>
                        '<span class="badge me-1" style="background:' . ($k->warna ?? '#6c757d') . '">' . $k->nama . '</span>'
                    )->implode(''),
                    'status' => '<span class="badge ' . ($galeri->is_published ? 'bg-success' : 'bg-warning') . '">'
                        . ($galeri->is_published ? 'Published' : 'Draft') . '</span>'
                ];
            });
    }

    public function find($id)
    {
        return Galeri::withoutGlobalScope('masjid')
            ->where('masjid_code', masjid())
            ->with(['kategoris', 'media'])
            ->findOrFail($id);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $masjid = masjid();
            
            $galeri = Galeri::create([
                'masjid_code'  => $masjid,
                'judul'        => $data['judul'],
                'keterangan'   => $data['keterangan'] ?? null,
                'tipe'         => $data['tipe'],
                'url_video'    => $data['tipe'] == 'video' ? $data['url_video'] : null,
                'is_published' => $data['is_published'] ?? false,
                'published_at' => ($data['is_published'] ?? false) ? now() : null,
                'created_by'   => auth()->id(),
            ]);

            // Sync kategori
            if (!empty($data['kategori_ids'])) {
                $galeri->kategoris()->sync($data['kategori_ids']);
            }

            // Upload foto jika tipe = foto
            if ($data['tipe'] == 'foto' && !empty($data['fotos'])) {
                $this->uploadMultipleImages($galeri, $data['fotos']);
            }

            return $galeri->fresh();
        });
    }

    public function update($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $galeri = $this->find($id);
            $masjid = masjid();

            $galeri->update([
                'judul'        => $data['judul'],
                'keterangan'   => $data['keterangan'] ?? null,
                'tipe'         => $data['tipe'],
                'url_video'    => $data['tipe'] == 'video' ? $data['url_video'] : null,
                'is_published' => $data['is_published'] ?? false,
                'published_at' => ($data['is_published'] ?? false) && !$galeri->is_published ? now() : $galeri->published_at,
                'updated_by'   => auth()->id(),
            ]);

            // Sync kategori
            if (isset($data['kategori_ids'])) {
                $galeri->kategoris()->sync($data['kategori_ids']);
            }

            // Hapus foto yang dipilih
            if (!empty($data['deleted_fotos'])) {
                $this->deleteSelectedImages($galeri, $data['deleted_fotos']);
            }

            // Upload foto baru jika tipe = foto
            if ($data['tipe'] == 'foto' && !empty($data['fotos'])) {
                $this->uploadMultipleImages($galeri, $data['fotos']);
            }

            return $galeri->fresh();
        });
    }

    public function delete($id)
    {
        return DB::transaction(function () use ($id) {
            $galeri = $this->find($id);

            // Hapus semua gambar dan folder
            $this->deleteAllImagesAndFolder($galeri);

            // Hapus relasi kategori
            $galeri->kategoris()->detach();

            // Hapus galeri
            $galeri->delete();

            return true;
        });
    }

    /**
     * Upload multiple images untuk galeri (dengan auto compress)
     */
    protected function uploadMultipleImages(Galeri $galeri, array $files): void
    {
        $masjid = masjid();
        $slug = Str::slug($galeri->judul);
        $folder = "{$masjid}/galeri/{$slug}";
        
        if (!Storage::disk('public')->exists($folder)) {
            Storage::disk('public')->makeDirectory($folder);
        }

        $existingCount = $galeri->media()->count();

        foreach ($files as $index => $file) {
            if (!$file instanceof UploadedFile) continue;

            try {
                // Cek ukuran file
                $fileSize = $file->getSize();
                if ($fileSize > 2 * 1024 * 1024) {
                    \Log::warning("File skipped: {$file->getClientOriginalName()} - Size: " . round($fileSize / 1024, 2) . "KB (exceeds 2MB)");
                    continue; // Skip file yang > 2MB
                }
                
                $filename = time() . '_' . Str::random(10) . '.webp';
                $path = "{$folder}/{$filename}";
                
                $webpContent = convert_to_webp($file);
                Storage::disk('public')->put($path, $webpContent);
                
                GaleriMedia::create([
                    'galeri_id'   => $galeri->id,
                    'masjid_code' => $masjid,
                    'image_path'  => $path,
                    'file_name'   => $file->getClientOriginalName(),
                    'urutan'      => $existingCount + $index,
                ]);
                
            } catch (\Exception $e) {
                \Log::error("Upload failed: {$file->getClientOriginalName()} - " . $e->getMessage());
            }
        }
    }

    /**
     * Hapus gambar yang dipilih
     */
    protected function deleteSelectedImages(Galeri $galeri, string $deletedFotoUrls): void
    {
        $deletedUrls = explode(',', $deletedFotoUrls);
        
        foreach ($galeri->media as $media) {
            $imageUrl = get_image_url($media->image_path);
            if (in_array($imageUrl, $deletedUrls)) {
                delete_image($media->image_path);
                $media->delete();
            }
        }
    }

    /**
     * Hapus semua gambar dan folder galeri
     */
    protected function deleteAllImagesAndFolder(Galeri $galeri): void
    {
        $masjid = masjid();
        $slug = Str::slug($galeri->judul);
        $folderPath = "{$masjid}/galeri/{$slug}";
        
        // Hapus semua file dalam folder
        foreach ($galeri->media as $media) {
            delete_image($media->image_path);
            $media->delete();
        }
        
        // Hapus folder jika kosong
        if (Storage::disk('public')->exists($folderPath)) {
            $files = Storage::disk('public')->files($folderPath);
            if (empty($files)) {
                Storage::disk('public')->deleteDirectory($folderPath);
            }
        }
    }
}