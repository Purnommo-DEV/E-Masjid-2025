<?php

namespace App\Repositories\mrj;

use App\Interfaces\AcaraRepositoryInterface;
use App\Models\Acara;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class AcaraRepository implements AcaraRepositoryInterface
{
    public function all()
    {
        return Acara::with(['kategoris', 'media'])
            ->latest()
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'judul' => $a->judul,
                'poster' => $a->getFirstMedia('poster')
                    ? '<img src="'.asset('storage/'.$a->getFirstMedia('poster')->custom_properties['folder'].'/'.$a->getFirstMedia('poster')->file_name).'" width="60" class="rounded">'
                    : '<small class="text-muted">Tanpa Gambar</small>',
                'kategoris' => $a->kategoris->map(fn($k) => 
                    '<span class="badge me-1" style="background:'.$k->warna.'">'.$k->nama.'</span>'
                )->implode(''),
                'tanggal' => $a->mulai->format('d/m/Y H:i').' - '.$a->selesai?->format('d/m/Y H:i') ?? '-',
                'status' => '<span class="badge '.($a->is_published ? 'bg-success' : 'bg-warning').'">'
                    .($a->is_published ? 'Published' : 'Draft').'</span>'
            ]);
    }

    public function find($id)
    {
        return Acara::findOrFail($id);
    }

    public function create(array $data)
    {
        $acara = Acara::create([
            'judul' => $data['judul'],
            'slug' => Str::slug($data['judul']),
            'deskripsi' => $data['deskripsi'],
            'mulai' => $data['mulai'],
            'selesai' => $data['selesai'],
            'lokasi' => $data['lokasi'],
            'penyelenggara' => $data['penyelenggara'],
            'waktu_teks' => $data['waktu_teks'],
            'pemateri' => $data['pemateri'],
            'is_published' => $data['is_published'] ?? false,
            'published_at' => $data['is_published'] ? now() : null,
            'created_by' => auth()->id(),
        ]);

        // 2️⃣ Simpan relasi kategori (jika ada)
        if (isset($data['kategori_ids'])) {
            $acara->kategoris()->sync($data['kategori_ids']);
        }

        // 3️⃣ Pastikan folder 'acara' tersedia di storage/public
        $folder = 'acara';
        Storage::disk('public')->makeDirectory($folder);

        // 4️⃣ Jika ada file poster yang diunggah
        if (isset($data['poster'])) {

            // a. Simpan poster sementara via Media Library (Spatie)
            $media = $acara->addMedia($data['poster'])
                ->usingFileName($data['poster']->getClientOriginalName())
                ->preservingOriginal()
                ->toMediaCollection('poster');

            // b. Ambil path sementara file yang disimpan Spatie
            $tempPath = $media->getPath(); // contoh: storage/app/public/1234/namafile.jpg
            $newFileName = $media->file_name; // nama file aslinya
            $newPath = storage_path('app/public/' . $folder . '/' . $newFileName); // tujuan akhir di folder acara

            // c. Pindahkan file ke folder 'acara'
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
                    'original_name' => $data['poster']->getClientOriginalName(),
                ],
            ]);
        }

        return $acara;
    }

    public function update($id, array $data)
    {
        $acara = $this->find($id);
        $acara->update([
            'judul' => $data['judul'],
            'slug' => Str::slug($data['judul']),
            'deskripsi' => $data['deskripsi'],
            'mulai' => $data['mulai'],
            'selesai' => $data['selesai'],
            'lokasi' => $data['lokasi'],
            'penyelenggara' => $data['penyelenggara'],
            'waktu_teks' => $data['waktu_teks'],
            'pemateri' => $data['pemateri'],
            'is_published' => $data['is_published'] ?? false,
            'published_at' => $data['is_published'] ? now() : null,
        ]);

        if (isset($data['kategori_ids'])) {
            $acara->kategoris()->sync($data['kategori_ids']);
        }
        // 3️⃣ Proses update poster jika ada file baru
        if (isset($data['poster'])) {
            $folder = 'poster';

            // a. Pastikan folder 'acara' tersedia
            Storage::disk('public')->makeDirectory($folder);

            // b. Hapus media lama dari Media Library dan file fisiknya
            $oldMedias = $acara->getMedia('poster');
            foreach ($oldMedias as $oldMedia) {
                $oldFilePath = storage_path('app/public/' . $folder . '/' . $oldMedia->file_name);
                if (file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                }
                $oldMedia->delete();
            }

            // c. Simpan poster baru
            $media = $acara->addMedia($data['poster'])
                ->usingFileName($data['poster']->getClientOriginalName())
                ->preservingOriginal()
                ->toMediaCollection('poster');

            // d. Pindahkan poster ke folder 'acara'
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
                    'original_name' => $data['poster']->getClientOriginalName(),
                ],
            ]);
        }

        // 4️⃣ Return acara setelah diupdate
        return $acara->fresh();
    }

    public function delete($id)
    {
        $berita = $this->find($id);

        if (!$berita) {
            return false;
        }

        // 1️⃣ Ambil semua media di koleksi 'poster'
        $medias = $berita->getMedia('poster');

        foreach ($medias as $media) {
            // Lokasi file sebenarnya yang kamu simpan
            $filePath = storage_path('app/public/poster/' . $media->file_name);

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