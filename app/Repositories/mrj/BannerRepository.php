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
                    $imgUrl = asset('storage/'.$folder.'/'.$media->file_name);

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

        $folder = 'banner';

        foreach ($banner->getMedia('banner') as $media) {
            $filePath = storage_path('app/public/'.$folder.'/'.$media->file_name);
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            $media->delete();
        }

        $banner->delete();

        return true;
    }

    protected function handleBannerMedia(Banner $banner, UploadedFile $file, bool $replaceOld = false): void
    {
        $folder = 'banner';

        // pastikan folder ada
        Storage::disk('public')->makeDirectory($folder);

        if ($replaceOld) {
            foreach ($banner->getMedia('banner') as $old) {
                $oldFilePath = storage_path('app/public/'.$folder.'/'.$old->file_name);
                if (file_exists($oldFilePath)) {
                    @unlink($oldFilePath);
                }
                $old->delete();
            }
        }

        // upload baru via Spatie
        $media = $banner->addMedia($file)
            ->usingFileName(Str::random(20).'.'.$file->getClientOriginalExtension())
            ->preservingOriginal()
            ->toMediaCollection('banner');

        // pindah file dari folder tmp spatie ke /storage/app/public/banner
        $tempPath = $media->getPath();
        $newPath  = storage_path('app/public/'.$folder.'/'.$media->file_name);

        if (file_exists($tempPath)) {
            if (file_exists($newPath)) {
                @unlink($newPath);
            }
            rename($tempPath, $newPath);

            $oldDir = dirname($tempPath);
            if (is_dir($oldDir) && count(scandir($oldDir)) === 2) {
                @rmdir($oldDir);
            }
        }

        // simpan metadata
        $media->update([
            'custom_properties' => [
                'folder'        => $folder,
                'original_name' => $file->getClientOriginalName(),
            ],
        ]);
    }
}
