<?php

namespace App\Repositories\mrj;

use App\Interfaces\BeritaRepositoryInterface;
use App\Models\Berita;
use App\Models\BeritaMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BeritaRepository implements BeritaRepositoryInterface
{
    public function all()
    {
        return Berita::withoutGlobalScope('masjid')
            ->where('masjid_code', masjid())
            ->with(['kategoris', 'media'])
            ->latest()
            ->get()
            ->map(function ($berita) {
                $firstMedia = $berita->media()->first();
                $gambarHtml = '<small class="text-muted">Tanpa Gambar</small>';
                
                if ($firstMedia) {
                    $imageUrl = get_image_url($firstMedia->image_path);
                    $gambarHtml = '<img src="' . $imageUrl . '" width="60" class="rounded" style="height: 40px; object-fit: cover;">';
                }

                return [
                    'id' => $berita->id,
                    'judul' => $berita->judul,
                    'gambar' => $gambarHtml,
                    'kategoris' => $berita->kategoris->map(fn($k) =>
                        '<span class="badge me-1" style="background:' . ($k->warna ?? '#6c757d') . '">' . $k->nama . '</span>'
                    )->implode(''),
                    'status' => '<span class="badge ' . ($berita->is_published ? 'bg-success' : 'bg-warning') . '">'
                        . ($berita->is_published ? 'Published' : 'Draft') . '</span>',
                    'tanggal' => $berita->published_at?->format('d/m/Y') ?? '-'
                ];
            });
    }

    public function find($id)
    {
        return Berita::withoutGlobalScope('masjid')
            ->where('masjid_code', masjid())
            ->with(['kategoris', 'media'])
            ->findOrFail($id);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $berita = Berita::create([
                'masjid_code'  => masjid(),
                'judul'        => $data['judul'],
                'slug'         => Str::slug($data['judul']),
                'isi'          => $data['isi'],
                'excerpt'      => $data['excerpt'] ?? Str::limit(strip_tags($data['isi']), 160),
                'is_published' => $data['is_published'] ?? false,
                'published_at' => ($data['is_published'] ?? false) ? now() : null,
                'created_by'   => auth()->id(),
            ]);

            // Sync kategori
            if (!empty($data['kategori_ids'])) {
                $berita->kategoris()->sync($data['kategori_ids']);
            }

            // Upload multiple gambar
            if (!empty($data['gambar']) && is_array($data['gambar'])) {
                $this->uploadMultipleImages($berita, $data['gambar']);
            }

            return $berita->fresh();
        });
    }

    public function update($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $berita = $this->find($id);

            $oldSlug = $berita->slug;
            $newSlug = Str::slug($data['judul']);

            $berita->update([
                'judul'        => $data['judul'],
                'slug'         => $newSlug,
                'isi'          => $data['isi'],
                'excerpt'      => $data['excerpt'] ?? Str::limit(strip_tags($data['isi']), 160),
                'is_published' => $data['is_published'] ?? false,
                'published_at' => ($data['is_published'] ?? false) && !$berita->is_published ? now() : $berita->published_at,
                'updated_by'   => auth()->id(),
            ]);

            // Sync kategori
            if (isset($data['kategori_ids'])) {
                $berita->kategoris()->sync($data['kategori_ids']);
            }

            // Hapus gambar yang dipilih
            if (!empty($data['deleted_gambar'])) {
                $this->deleteSelectedImages($berita, $data['deleted_gambar']);
            }

            // Upload gambar baru
            if (!empty($data['gambar']) && is_array($data['gambar'])) {
                $this->uploadMultipleImages($berita, $data['gambar']);
            }

            // Pindahkan folder jika slug berubah
            if ($oldSlug !== $newSlug) {
                $this->moveImagesFolder($berita, $oldSlug, $newSlug);
            }

            return $berita->fresh();
        });
    }

    public function delete($id)
    {
        return DB::transaction(function () use ($id) {
            $berita = $this->find($id);

            // Hapus semua gambar dan folder
            $this->deleteAllImagesAndFolder($berita);

            // Hapus relasi kategori
            $berita->kategoris()->detach();

            // Hapus berita
            $berita->delete();

            return true;
        });
    }

    /**
     * Upload multiple images untuk berita (FIX BUG #1)
     * Pastikan folder sesuai dengan masjid_code
     */
    protected function uploadMultipleImages(Berita $berita, array $files): void
    {
        $masjid = masjid(); // 'mrj'
        $folder = "{$masjid}/berita/{$berita->slug}"; // mrj/berita/slug-berita
        
        // Buat folder jika belum ada
        if (!Storage::disk('public')->exists($folder)) {
            Storage::disk('public')->makeDirectory($folder);
        }

        // Cek apakah sudah ada gambar sebelumnya
        $existingCount = $berita->media()->count();
        $isFirstImage = ($existingCount === 0);

        foreach ($files as $index => $file) {
            if (!$file instanceof UploadedFile) continue;

            // Generate nama file unik
            $filename = time() . '_' . Str::random(10) . '.webp';
            $path = "{$folder}/{$filename}";
            
            // Konversi ke WebP dan upload
            $webpContent = convert_to_webp($file);
            Storage::disk('public')->put($path, $webpContent);
            
            // Simpan ke database
            BeritaMedia::create([
                'berita_id'   => $berita->id,
                'masjid_code' => $masjid,
                'image_path'  => $path,
                'file_name'   => $file->getClientOriginalName(),
                'is_cover'    => ($isFirstImage && $index === 0),
                'urutan'      => $existingCount + $index,
            ]);
        }
    }

    /**
     * Hapus gambar yang dipilih (FIX BUG #2 - hapus file tapi folder tetap)
     */
    protected function deleteSelectedImages(Berita $berita, string $deletedGambarUrls): void
    {
        $deletedUrls = explode(',', $deletedGambarUrls);
        
        foreach ($berita->media as $media) {
            $imageUrl = get_image_url($media->image_path);
            if (in_array($imageUrl, $deletedUrls)) {
                // Hapus file
                delete_image($media->image_path);
                // Hapus record
                $media->delete();
            }
        }
    }

    /**
     * Hapus semua gambar dan folder berita (FIX BUG #2 & #3)
     * Folder akan dihapus jika kosong
     */
    protected function deleteAllImagesAndFolder(Berita $berita): void
    {
        $masjid = masjid();
        $folderPath = "{$masjid}/berita/{$berita->slug}";
        
        // Hapus semua file dalam folder
        foreach ($berita->media as $media) {
            delete_image($media->image_path);
            $media->delete();
        }
        
        // Hapus folder jika kosong (setelah semua file terhapus)
        if (Storage::disk('public')->exists($folderPath)) {
            $files = Storage::disk('public')->files($folderPath);
            if (empty($files)) {
                Storage::disk('public')->deleteDirectory($folderPath);
            }
        }
    }

    /**
     * Pindahkan folder gambar jika slug berubah
     */
    protected function moveImagesFolder(Berita $berita, string $oldSlug, string $newSlug): void
    {
        $masjid = masjid();
        $oldFolder = "{$masjid}/berita/{$oldSlug}";
        $newFolder = "{$masjid}/berita/{$newSlug}";
        
        // Cek apakah folder lama ada
        if (!Storage::disk('public')->exists($oldFolder)) {
            return;
        }
        
        // Buat folder baru
        if (!Storage::disk('public')->exists($newFolder)) {
            Storage::disk('public')->makeDirectory($newFolder);
        }
        
        // Pindahkan semua file
        foreach ($berita->media as $media) {
            $oldPath = $media->image_path;
            $newPath = str_replace($oldFolder, $newFolder, $oldPath);
            
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->move($oldPath, $newPath);
                $media->update(['image_path' => $newPath]);
            }
        }
        
        // Hapus folder lama jika kosong
        $remainingFiles = Storage::disk('public')->files($oldFolder);
        if (empty($remainingFiles)) {
            Storage::disk('public')->deleteDirectory($oldFolder);
        }
    }
}