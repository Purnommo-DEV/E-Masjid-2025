<?php

namespace App\Repositories\mrj;

use App\Interfaces\BeritaRepositoryInterface;
use App\Models\Berita;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class BeritaRepository implements BeritaRepositoryInterface
{
    public function all()
    {
        return Berita::with(['kategoris', 'media'])
            ->latest()
            ->get()
            ->map(fn($b) => [
                'id' => $b->id,
                'judul' => $b->judul,
                'gambar' => $b->getFirstMedia('gambar')
                    ? '<img src="'.asset('storage/'.$b->getFirstMedia('gambar')->custom_properties['folder'].'/'.$b->getFirstMedia('gambar')->file_name).'" width="60" class="rounded">'
                    : '<small class="text-muted">Tanpa Gambar</small>',
                'kategoris' => $b->kategoris->map(fn($k) => 
                    '<span class="badge me-1" style="background:'.$k->warna.'">'.$k->nama.'</span>'
                )->implode(''),
                'status' => '<span class="badge '.($b->is_published ? 'bg-success' : 'bg-warning').'">'
                    .($b->is_published ? 'Published' : 'Draft').'</span>',
                'tanggal' => $b->published_at?->format('d/m/Y') ?? '-'
            ]);
    }

    public function find($id)
    {
        return Berita::findOrFail($id);
    }

    public function create(array $data)
    {
        // 1️⃣ Simpan data utama berita ke database
        $berita = Berita::create([
            'judul' => $data['judul'],
            'slug' => Str::slug($data['judul']),
            'isi' => $data['isi'],
            'excerpt' => $data['excerpt'] ?? Str::limit(strip_tags($data['isi']), 200),
            'is_published' => $data['is_published'] ?? false,
            'published_at' => $data['is_published'] ? now() : null,
            'created_by' => auth()->id(),
        ]);

        // 2️⃣ Simpan relasi kategori (jika ada)
        if (isset($data['kategori_ids'])) {
            $berita->kategoris()->sync($data['kategori_ids']);
        }

        // 3️⃣ Pastikan folder 'berita' tersedia di storage/public
        $folder = 'berita';
        Storage::disk('public')->makeDirectory($folder);

        // 4️⃣ Jika ada file gambar yang diunggah
        if (isset($data['gambar'])) {

            // a. Simpan gambar sementara via Media Library (Spatie)
            $media = $berita->addMedia($data['gambar'])
                ->usingFileName($data['gambar']->getClientOriginalName())
                ->preservingOriginal()
                ->toMediaCollection('gambar');

            // b. Ambil path sementara file yang disimpan Spatie
            $tempPath = $media->getPath(); // contoh: storage/app/public/1234/namafile.jpg
            $newFileName = $media->file_name; // nama file aslinya
            $newPath = storage_path('app/public/' . $folder . '/' . $newFileName); // tujuan akhir di folder berita

            // c. Pindahkan file ke folder 'berita'
            if (file_exists($tempPath)) {
                if (file_exists($newPath)) unlink($newPath); // hapus kalau sudah ada nama yang sama
                rename($tempPath, $newPath); // pindahkan file

                // d. Hapus folder bawaan Spatie (folder ID yang kosong)
                $oldDir = dirname($tempPath);
                if (is_dir($oldDir) && count(scandir($oldDir)) == 2) {
                    rmdir($oldDir);
                }
            }

            // e. Update informasi tambahan pada media record di database
            $media->update([
                'custom_properties' => [
                    'folder' => $folder,
                    'original_name' => $data['gambar']->getClientOriginalName(),
                ],
            ]);
        }

        // 5️⃣ Kembalikan instance berita yang sudah tersimpan
        return $berita;
    }

    public function update($id, array $data)
    {
        $berita = $this->find($id);

        // 1️⃣ Update data utama berita
        $berita->update([
            'judul' => $data['judul'],
            'slug' => Str::slug($data['judul']),
            'isi' => $data['isi'],
            'excerpt' => $data['excerpt'] ?? Str::limit(strip_tags($data['isi']), 200),
            'is_published' => $data['is_published'] ?? false,
            'published_at' => $data['is_published'] ? now() : null,
            'updated_by' => auth()->id(),
        ]);

        // 2️⃣ Update kategori jika ada
        if (isset($data['kategori_ids'])) {
            $berita->kategoris()->sync($data['kategori_ids']);
        }

        // 3️⃣ Proses update gambar jika ada file baru
        if (isset($data['gambar'])) {
            $folder = 'berita';

            // a. Pastikan folder 'berita' tersedia
            Storage::disk('public')->makeDirectory($folder);

            // b. Hapus media lama dari Media Library dan file fisiknya
            $oldMedias = $berita->getMedia('gambar');
            foreach ($oldMedias as $oldMedia) {
                $oldFilePath = storage_path('app/public/' . $folder . '/' . $oldMedia->file_name);
                if (file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                }
                $oldMedia->delete();
            }

            // c. Simpan gambar baru
            $media = $berita->addMedia($data['gambar'])
                ->usingFileName($data['gambar']->getClientOriginalName())
                ->preservingOriginal()
                ->toMediaCollection('gambar');

            // d. Pindahkan gambar ke folder 'berita'
            $tempPath = $media->getPath();
            $newFileName = $media->file_name;
            $newPath = storage_path('app/public/' . $folder . '/' . $newFileName);

            if (file_exists($tempPath)) {
                if (file_exists($newPath)) unlink($newPath);
                rename($tempPath, $newPath);

                // e. Hapus folder sementara Spatie
                $oldDir = dirname($tempPath);
                if (is_dir($oldDir) && count(scandir($oldDir)) == 2) {
                    rmdir($oldDir);
                }
            }

            // f. Update metadata media record
            $media->update([
                'custom_properties' => [
                    'folder' => $folder,
                    'original_name' => $data['gambar']->getClientOriginalName(),
                ],
            ]);
        }

        // 4️⃣ Return berita setelah diupdate
        return $berita->fresh();
    }

    public function delete($id)
    {
        $berita = $this->find($id);

        if (!$berita) {
            return false;
        }

        // 1️⃣ Ambil semua media di koleksi 'gambar'
        $medias = $berita->getMedia('gambar');

        foreach ($medias as $media) {
            // Lokasi file sebenarnya yang kamu simpan
            $filePath = storage_path('app/public/berita/' . $media->file_name);

            // Hapus file fisik jika ada
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Hapus data media di DB
            $media->delete();
        }

        // 2️⃣ Hapus data berita
        $berita->delete();

        return true;
    }
}