<?php

namespace App\Repositories\mrj;

use App\Interfaces\BannerRepositoryInterface;
use App\Models\Banner;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BannerRepository implements BannerRepositoryInterface
{
    public function all()
    {
        return Banner::with('media')
            ->latest()
            ->get()
            ->map(function ($b) {

                $media = $b->getFirstMedia('banner');

                if ($media) {

                    $folder = $media->custom_properties['folder'] ?? 'banner';

                    // 🔥 FIX: paksa ke struktur baru
                    if (!str_contains($folder, 'e-masjid')) {
                        $folder = 'e-masjid/' . $folder;
                    }

                    $imgUrl = asset('storage/' . $folder . '/' . $media->file_name);

                    $gambarHtml = '<img src="'.$imgUrl
                        .'" width="80" class="rounded shadow-sm object-cover">';
                } else {
                    $gambarHtml = '<small class="text-muted">Tanpa Gambar</small>';
                }

                return [
                    'id'       => $b->id,
                    'judul'    => $b->judul,
                    'subjudul' => $b->subjudul,
                    'status'   => '<span class="badge '.($b->is_active ? 'bg-success' : 'bg-secondary').'">'
                                .($b->is_active ? 'Aktif' : 'Nonaktif').'</span>',
                    'urutan'   => $b->urutan,
                    'gambar'   => $gambarHtml,
                ];
            });
    }

    public function find($id)
    {
        return Banner::findOrFail($id);
    }

    public function create(array $data)
    {
        $banner = Banner::create([
            'judul'      => $data['judul'],
            'subjudul'   => $data['subjudul'] ?? null,
            'catatan'    => $data['catatan'] ?? null,
            'url_tujuan' => $data['url_tujuan'] ?? null,
            'is_active'  => $data['is_active'] ?? false,
            'urutan'     => $data['urutan'] ?? 0,
            'created_by' => auth()->id(),
        ]);

        if (!empty($data['gambar']) && $data['gambar'] instanceof UploadedFile) {
            $this->handleBannerMedia($banner, $data['gambar'], replaceOld: false);
        }

        return $banner;
    }

    public function update($id, array $data)
    {
        $banner = $this->find($id);

        $file = $data['gambar'] ?? null; // sudah pasti UploadedFile/null dari controller
        unset($data['gambar']);

        $banner->update([
            'judul'      => $data['judul'],
            'subjudul'   => $data['subjudul'] ?? null,
            'catatan'    => $data['catatan'] ?? null,
            'url_tujuan' => $data['url_tujuan'] ?? null,
            'is_active'  => $data['is_active'] ?? false,
            'urutan'     => $data['urutan'] ?? 0,
            'updated_by' => auth()->id(),
        ]);

        // 🔥 kalau ada file baru → hapus lama & ganti baru
        if ($file instanceof UploadedFile) {
            $this->handleBannerMedia($banner, $file, replaceOld: true);
        }

        return $banner->fresh();
    }

    public function delete($id)
    {
        $banner = $this->find($id);
        if (!$banner) return false;

        foreach ($banner->getMedia('banner') as $media) {

            // ambil folder dari DB
            $folder = $media->custom_properties['folder'] ?? 'e-masjid/banner';

            $filePath = public_html_path('storage/'.$folder.'/'.$media->file_name);

            // 🔥 hapus file kalau ada
            if (file_exists($filePath)) {
                @unlink($filePath);
            }

            // 🔥 hapus record media
            $media->delete();
        }

        // 🔥 hapus banner
        $banner->delete();

        return true;
    }

    protected function handleBannerMedia(Banner $banner, UploadedFile $file, bool $replaceOld = false): void
    {
        // 📂 folder tujuan (RELATIF URL)
        $folder = 'storage/e-masjid/banner';

        // 📁 path ke public_html
        $destinationPath = public_html_path($folder);

        // 🔧 pastikan folder ada
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        // 🔥 hapus file lama jika replace
        if ($replaceOld) {
            foreach ($banner->getMedia('banner') as $old) {

                $oldFile = $destinationPath . '/' . $old->file_name;

                if (file_exists($oldFile)) {
                    @unlink($oldFile);
                }

                $old->delete();
            }
        }

        // 🚀 upload via Spatie (sementara)
        $media = $banner->addMedia($file)
            ->usingFileName(Str::random(20) . '.' . $file->getClientOriginalExtension())
            ->preservingOriginal()
            ->toMediaCollection('banner');

        // 📦 ambil file sementara
        $tempPath = $media->getPath();

        // 🎯 tujuan akhir
        $newPath = $destinationPath . '/' . $media->file_name;

        // 🔄 pindahkan file ke public_html
        if (file_exists($tempPath)) {

            if (file_exists($newPath)) {
                @unlink($newPath);
            }

            rename($tempPath, $newPath);

            // 🧹 hapus folder temp Spatie
            $oldDir = dirname($tempPath);
            if (is_dir($oldDir) && count(scandir($oldDir)) === 2) {
                @rmdir($oldDir);
            }
        }

        // 💾 simpan metadata (PENTING BANGET)
        $media->update([
            'custom_properties' => [
                'folder'        => 'e-masjid/banner', // ❗ TANPA "storage/"
                'original_name' => $file->getClientOriginalName(),
            ],
        ]);
    }
}
