<?php

namespace App\Repositories\mrj;

use App\Interfaces\BeritaRepositoryInterface;
use App\Models\Berita;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class BeritaRepository implements BeritaRepositoryInterface
{
    public function all()
    {
        return Berita::with(['kategoris', 'media'])
            ->latest()
            ->get()
            ->map(function ($b) {
                // Ambil satu gambar pertama sebagai thumbnail (atau null jika tidak ada)
                $media = $b->getFirstMedia('gambar');

                if ($media) {
                    $folder = $media->custom_properties['folder'] ?? 'berita/default';
                    $fileName = $media->file_name;
                    $thumbnailUrl = asset('storage/' . $folder . '/' . $fileName);
                    $gambarHtml = '<img src="' . $thumbnailUrl . '" width="60" class="rounded">';
                } else {
                    $gambarHtml = '<small class="text-muted">Tanpa Gambar</small>';
                }

                return [
                    'id'        => $b->id,
                    'judul'     => $b->judul,
                    'gambar'    => $gambarHtml,
                    'kategoris' => $b->kategoris->map(fn($k) =>
                        '<span class="badge me-1" style="background:' . $k->warna . '">' . $k->nama . '</span>'
                    )->implode(''),
                    'status'    => '<span class="badge ' . ($b->is_published ? 'bg-success' : 'bg-warning') . '">'
                        . ($b->is_published ? 'Published' : 'Draft') . '</span>',
                    'tanggal'   => $b->published_at?->format('d/m/Y') ?? '-'
                ];
            });
    }

    public function find($id)
    {
        return Berita::findOrFail($id);
    }

    public function create(array $data)
    {
        $berita = Berita::create([
            'judul'        => $data['judul'],
            'slug'         => Str::slug($data['judul']),
            'isi'          => $data['isi'],
            'excerpt'      => $data['excerpt'],
            'is_published' => $data['is_published'] ?? false,
            'published_at' => ($data['is_published'] ?? false) ? now() : null,
            'created_by'   => auth()->id(),
        ]);

        if (!empty($data['kategori_ids'])) {
            $berita->kategoris()->sync($data['kategori_ids']);
        }

        return $berita;
    }

    public function update($id, array $data)
    {
        $berita = $this->find($id);

        $berita->update([
            'judul'        => $data['judul'],
            'slug'         => Str::slug($data['judul']),
            'isi'          => $data['isi'],
            'excerpt'      => $data['excerpt'],
            'is_published' => $data['is_published'] ?? false,
            'published_at' => ($data['is_published'] ?? false) ? now() : null,
            'updated_by'   => auth()->id(),
        ]);

        if (!empty($data['kategori_ids'])) {
            $berita->kategoris()->sync($data['kategori_ids']);
        }

        if (!empty($data['deleted_gambar'])) {
            $deletedUrls = array_filter(explode(',', $data['deleted_gambar']));
            foreach ($berita->getMedia('gambar') as $media) {
                if (in_array($media->getUrl(), $deletedUrls)) {
                    $media->delete();
                }
            }
        }

        return $berita->fresh();
    }

    public function delete($id)
    {
        $berita = $this->find($id);

        // 1. Ambil semua media di collection 'gambar'
        $mediaItems = $berita->getMedia('gambar');

        // 2. Hapus file fisik di folder custom
        foreach ($mediaItems as $media) {
            $folder = $media->custom_properties['folder'] ?? null;
            $fileName = $media->file_name;

            if ($folder && $fileName) {
                $filePath = storage_path('app/public/' . $folder . '/' . $fileName);
                if (file_exists($filePath)) {
                    unlink($filePath);
                    Log::info("File dihapus manual: {$filePath}");
                } else {
                    Log::warning("File tidak ditemukan saat hapus: {$filePath}");
                }

                // Opsional: hapus folder jika kosong setelah semua file dihapus
                $folderPath = storage_path('app/public/' . $folder);
                if (is_dir($folderPath) && count(scandir($folderPath)) <= 2) {
                    rmdir($folderPath);
                    Log::info("Folder custom dihapus karena kosong: {$folderPath}");
                }
            }
        }

        // 3. Hapus semua record media di DB (dan file default jika ada)
        $berita->clearMediaCollection('gambar');

        // 4. Hapus berita
        $berita->delete();

        return true;
    }
}