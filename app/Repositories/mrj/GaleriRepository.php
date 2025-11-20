<?php

namespace App\Repositories\mrj;

use App\Interfaces\GaleriRepositoryInterface;
use App\Models\Galeri;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;


class GaleriRepository implements GaleriRepositoryInterface
{
    public function all()
    {
        return Galeri::with(['kategoris', 'media'])
            ->latest()
            ->get()
            ->map(fn($g) => [
                'id' => $g->id,
                'judul' => $g->judul,
                'tipe' => $g->tipe == 'video' 
                    ? '<span class="badge bg-danger">Video</span>'
                    : '<span class="badge bg-primary">Foto</span>',
                'thumbnail' => $g->getFirstMedia('foto')
                    ? '<img src="'.asset('storage/'.$g->getFirstMedia('foto')->custom_properties['folder'].'/'.$g->getFirstMedia('foto')->file_name).'" width="60" class="rounded">'
                    : '<small class="text-muted">Tanpa Gambar</small>',
                'kategoris' => $g->kategoris->map(fn($k) => 
                    '<span class="badge me-1" style="background:'.$k->warna.'">'.$k->nama.'</span>'
                )->implode(''),
                'status' => '<span class="badge '.($g->is_published ? 'bg-success' : 'bg-warning').'">'
                    .($g->is_published ? 'Published' : 'Draft').'</span>'
            ]);
    }

    private function getYouTubeThumbnail($url)
    {
        if (!$url) return '<small class="text-muted">No Video</small>';
        preg_match('/v=([^&]+)/', $url, $matches);
        $videoId = $matches[1] ?? substr(parse_url($url, PHP_URL_PATH), 1);
        return '<img src="https://img.youtube.com/vi/'.$videoId.'/0.jpg" width="80" class="rounded">';
    }

    public function find($id)
    {
        return Galeri::findOrFail($id);
    }

    public function create(array $data)
    {
        $galeri = Galeri::create([
            'judul' => $data['judul'],
            'keterangan' => $data['keterangan'] ?? null,
            'tipe' => $data['tipe'],
            'url_video' => $data['tipe'] == 'video' ? $data['url_video'] : null,
            'is_published' => $data['is_published'] ?? false,
            'published_at' => $data['is_published'] ? now() : null,
            'created_by' => auth()->id(),
        ]);

        // SIMPAN KATEGORI
        if (isset($data['kategori_ids'])) {
            $galeri->kategoris()->sync($data['kategori_ids']);
        }

        $kategori = $galeri->kategoris()->first();
        $folder = $kategori 
            ? 'galeri/' . Str::slug($kategori->nama) . '_' . $galeri->id
            : 'galeri/uncategorized_' . $galeri->id;

        Storage::disk('public')->makeDirectory($folder);

        if (isset($data['fotos'])) {
            foreach ($data['fotos'] as $foto) {
                // 1. Simpan sementara
                $media = $galeri->addMedia($foto)
                    ->usingFileName($foto->getClientOriginalName())
                    ->preservingOriginal()
                    ->toMediaCollection('foto');

                // 2. Pindahkan file
                $tempPath = $media->getPath();
                $newFileName = $media->file_name;
                $newPath = storage_path('app/public/' . $folder . '/' . $newFileName);

                if (file_exists($tempPath)) {
                    if (file_exists($newPath)) unlink($newPath);
                    rename($tempPath, $newPath);

                    // HAPUS FOLDER ID KOSONG
                    $oldDir = dirname($tempPath);
                    if (is_dir($oldDir) && count(scandir($oldDir)) == 2) {
                        rmdir($oldDir);
                    }
                }

                // 3. Update DB
                $media->update([
                    'custom_properties' => [
                        'folder' => $folder,
                        'original_name' => $foto->getClientOriginalName(),
                    ],
                ]);
            }
        }

        return $galeri;
    }

    public function update($id, array $data)
    {
        $galeri = $this->find($id);
        
        // UPDATE DATA GALERI
        $galeri->update([
            'judul' => $data['judul'],
            'keterangan' => $data['keterangan'] ?? null,
            'tipe' => $data['tipe'],
            'url_video' => $data['tipe'] == 'video' ? $data['url_video'] : null,
            'is_published' => $data['is_published'] ?? false,
            'published_at' => $data['is_published'] ? now() : null,
        ]);

        // UPDATE KATEGORI
        if (isset($data['kategori_ids'])) {
            $galeri->kategoris()->sync($data['kategori_ids']);
        }

        // TENTUKAN FOLDER BARU
        $kategori = $galeri->kategoris()->first();
        $folderBaru = $kategori 
            ? 'galeri/' . Str::slug($kategori->nama) . '_' . $galeri->id
            : 'galeri/uncategorized_' . $galeri->id;

        // BUAT FOLDER BARU
        Storage::disk('public')->makeDirectory($folderBaru);

        // HAPUS FOTO LAMA (PAKAI $data, BUKAN $request)
        if (!empty($data['deleted_fotos'])) {
            $deletedUrls = array_filter(explode(',', $data['deleted_fotos']));
            foreach ($galeri->getMedia('foto') as $media) {
                $folder = $media->custom_properties['folder'] ?? 'galeri/default';
                $fileUrl = asset('storage/' . $folder . '/' . $media->file_name);
                if (in_array($fileUrl, $deletedUrls)) {
                    $filePath = storage_path('app/public/' . $folder . '/' . $media->file_name);
                    if (file_exists($filePath)) unlink($filePath);
                    $media->delete();
                }
            }
        }

        // PINDAHKAN FOTO LAMA KE FOLDER BARU (jika ganti kategori)
        foreach ($galeri->getMedia('foto') as $media) {
            $folderLama = $media->custom_properties['folder'] ?? null;
            if ($folderLama && $folderLama !== $folderBaru) {
                $oldPath = storage_path('app/public/' . $folderLama . '/' . $media->file_name);
                $newPath = storage_path('app/public/' . $folderBaru . '/' . $media->file_name);

                if (file_exists($oldPath)) {
                    if (file_exists($newPath)) unlink($newPath);
                    rename($oldPath, $newPath);
                }

                $media->update([
                    'custom_properties' => array_merge($media->custom_properties, ['folder' => $folderBaru])
                ]);
            }
        }

        // TAMBAH FOTO BARU
        if (isset($data['fotos'])) {
            foreach ($data['fotos'] as $foto) {
                $media = $galeri->addMedia($foto)
                    ->usingFileName($foto->getClientOriginalName())
                    ->preservingOriginal()
                    ->toMediaCollection('foto');

                $tempPath = $media->getPath();
                $newPath = storage_path('app/public/' . $folderBaru . '/' . $media->file_name);

                if (file_exists($tempPath)) {
                    if (file_exists($newPath)) unlink($newPath);
                    rename($tempPath, $newPath);

                    $oldDir = dirname($tempPath);
                    if (is_dir($oldDir) && count(scandir($oldDir)) == 2) {
                        rmdir($oldDir);
                    }
                }

                $media->update([
                    'custom_properties' => [
                        'folder' => $folderBaru,
                        'original_name' => $foto->getClientOriginalName(),
                    ],
                ]);
            }
        }

        // HAPUS FOLDER LAMA (jika kosong)
        $folderLama = $data['old_folder'] ?? null;
        if ($folderLama && $folderLama !== $folderBaru) {
            $pathLama = storage_path('app/public/' . $folderLama);
            if (is_dir($pathLama) && count(scandir($pathLama)) == 2) {
                rmdir($pathLama);
            }
        }

        return $galeri;
    }

    public function delete($id)
    {
        $galeri = $this->find($id);

        // 1. AMBIL FOLDER DARI MEDIA PERTAMA
        // 'gambar_galeri' adalah nama collection yang kamu BUAT SENDIRI
        $folder = $galeri->getMedia('gambar_galeri')->first()?->custom_properties['folder'] ?? null;

        // 2. HAPUS SEMUA MEDIA (DB + FILE)
        $galeri->clearMediaCollection('gambar_galeri'); // Hapus semua media + file (jika path benar)

        // 3. HAPUS FOLDER CUSTOM (jika kosong)
        if ($folder) {
            $folderPath = storage_path('app/public/' . $folder);
            if (is_dir($folderPath)) {
                // Hapus semua file di dalam
                $files = glob($folderPath . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) unlink($file);
                }
                // Hapus folder
                if (count(scandir($folderPath)) == 2) {
                    rmdir($folderPath);
                }
            }
        }

        // 4. HAPUS GALERI
        $galeri->delete();

        return true;
    }
}