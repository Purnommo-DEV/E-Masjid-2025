<?php
// app/Repositories/PengumumanRepository.php

namespace App\Repositories\mrj;

use App\Interfaces\PengumumanRepositoryInterface;
use App\Models\Pengumuman;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PengumumanRepository implements PengumumanRepositoryInterface
{
    public function all()
    {
        return Pengumuman::with('media')
            ->latest()
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'judul' => $p->judul,
                'gambar' => $p->getFirstMedia('banner')
                    ? '<img src="'.asset('storage/'.$p->getFirstMedia('banner')->custom_properties['folder'].'/'.$p->getFirstMedia('banner')->file_name).'" width="60" class="rounded shadow-sm">'
                    : '<small class="text-muted">Tanpa Gambar</small>',
                'tipe' => ucfirst($p->tipe),
                'status' => '<span class="badge '.($p->is_active ? 'bg-success' : 'bg-secondary').'">'
                    .($p->is_active ? 'Aktif' : 'Nonaktif').'</span>',
                'periode' => ($p->mulai?->format('d/m/Y H:i') ?? '-') . ' - ' . ($p->selesai?->format('d/m/Y H:i') ?? '-')
            ])->toArray();
    }

    public function find($id)
    {
        return Pengumuman::findOrFail($id);
    }

    public function create(array $data)
    {
        $pengumuman = Pengumuman::create([
            'judul' => $data['judul'],
            'isi' => $data['isi'] ?? null,
            'tipe' => $data['tipe'],
            'mulai' => $data['mulai'] ?? null,
            'selesai' => $data['selesai'] ?? null,
            'is_active' => $data['is_active'] ?? false,
        ]);

        if (isset($data['gambar'])) {
            $this->uploadGambar($pengumuman, $data['gambar']);
        }

        return $pengumuman;
    }

    public function update($id, array $data)
    {
        $pengumuman = $this->find($id);

        $pengumuman->update([
            'judul' => $data['judul'],
            'isi' => $data['isi'] ?? null,
            'tipe' => $data['tipe'],
            'mulai' => $data['mulai'] ?? null,
            'selesai' => $data['selesai'] ?? null,
            'is_active' => $data['is_active'] ?? false,
        ]);

        if (isset($data['gambar'])) {
            $folder = 'pengumuman';

            // a. Pastikan folder 'pengumuman' tersedia
            Storage::disk('public')->makeDirectory($folder);

            // b. Hapus media lama dari Media Library dan file fisiknya
            $oldMedias = $pengumuman->getMedia('banner');
            foreach ($oldMedias as $oldMedia) {
                $oldFilePath = storage_path('app/public/' . $folder . '/' . $oldMedia->file_name);
                if (file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                }
                $oldMedia->delete();
            }

            // c. Simpan banner baru
            $media = $pengumuman->addMedia($data['gambar'])
                ->usingFileName($data['gambar']->getClientOriginalName())
                ->preservingOriginal()
                ->toMediaCollection('banner');

            // d. Pindahkan gambar ke folder 'pengumuman'
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

        // 4️⃣ Return pengumuman setelah diupdate
        return $pengumuman->fresh();
    }

    public function delete($id)
    {
        $pengumuman = $this->find($id);

        if (!$pengumuman) {
            return false;
        }

        // 1️⃣ Ambil semua media di koleksi 'poster'
        $medias = $pengumuman->getMedia('banner');

        foreach ($medias as $media) {
            // Lokasi file sebenarnya yang kamu simpan
            $filePath = storage_path('app/public/pengumuman/' . $media->file_name);

            // Hapus file fisik jika ada
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Hapus data media di DB
            $media->delete();
        }

        // 2️⃣ Hapus data pengumuman
        $pengumuman->delete();

        return true;
    }

    private function uploadGambar($pengumuman, $file)
    {
        $folder = 'pengumuman';
        Storage::disk('public')->makeDirectory($folder);

        $media = $pengumuman->addMedia($file)
            ->usingFileName($file->getClientOriginalName())
            ->preservingOriginal()
            ->toMediaCollection('banner');

        $tempPath = $media->getPath();
        $newPath = storage_path('app/public/' . $folder . '/' . $media->file_name);

        if (file_exists($tempPath)) {
            if (file_exists($newPath)) unlink($newPath);
            rename($tempPath, $newPath);

            $oldDir = dirname($tempPath);
            if (is_dir($oldDir) && count(scandir($oldDir)) == 2) {
                rmdir($oldDir);
            }
        }

        $media->update(['custom_properties' => ['folder' => $folder]]);
    }

    private function deleteGambar($pengumuman)
    {
        $media = $pengumuman->getFirstMedia('banner');
        if ($media && isset($media->custom_properties['folder'])) {
            $folder = $media->custom_properties['folder'];
            $path = storage_path('app/public/' . $folder);
            if (is_dir($path)) {
                foreach (glob($path . '/*') as $file) {
                    if (is_file($file)) unlink($file);
                }
                if (count(scandir($path)) == 2) rmdir($path);
            }
        }
        $pengumuman->clearMediaCollection('banner');
    }
}