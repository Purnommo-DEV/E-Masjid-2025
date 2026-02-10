<?php
namespace App\Repositories\mrj;

use App\Interfaces\ProfilMasjidRepositoryInterface;
use App\Models\ProfilMasjid;
use App\Models\Pengurus;
use Illuminate\Support\Facades\Storage;

class ProfilMasjidRepository implements ProfilMasjidRepositoryInterface
{
    public function getProfil()
    {
        return ProfilMasjid::first() ?? new ProfilMasjid();
    }

    public function updateProfil(array $data)
    {
        $profil = ProfilMasjid::updateOrCreate(
            ['id' => 1],
            collect($data)->except(['logo', 'struktur', 'qris'])->toArray()
        );

        $disk = Storage::disk('public');

        // Fungsi helper untuk upload & replace media
        $uploadAndReplace = function ($file, $type, $folder) use ($profil, $disk) {
            if (!$file instanceof \Illuminate\Http\UploadedFile) {
                return;
            }

            $name = $file->getClientOriginalName();
            $targetPath = "{$folder}/{$name}";

            // Hapus file & media lama jika ada
            $oldMedia = $profil->getFirstMedia($type);
            if ($oldMedia) {
                $oldPath = $oldMedia->getPath(); // path relatif dari storage
                if ($disk->exists($oldPath)) {
                    $disk->delete($oldPath);
                }
                $oldMedia->delete();
            }

            // Simpan file baru ke folder custom
            $disk->putFileAs($folder, $file, $name);

            // Jika pakai Spatie Media Library untuk logo/struktur (opsional, bisa dihapus jika tidak perlu)
            if (in_array($type, ['logo', 'struktur'])) {
                $media = $profil->addMedia($file)
                    ->usingFileName($name)
                    ->preservingOriginal()
                    ->toMediaCollection($type);

                $media->setCustomProperty('folder', $folder);
                $media->setCustomProperty('original_name', $name);
                $media->save();
            }

            // Untuk QRIS, simpan path ke kolom qris
            if ($type === 'qris') {
                $profil->qris = $targetPath;
                $profil->save();
            }
        };

        // Proses upload untuk logo, struktur, dan qris
        $uploadAndReplace($data['logo'] ?? null, 'logo', 'profil/logo');
        $uploadAndReplace($data['struktur'] ?? null, 'struktur', 'profil/struktur');
        $uploadAndReplace($data['qris'] ?? null, 'qris', 'donasi/qris');

        return $profil;
    }

    public function getPengurus()
    {
        return Pengurus::with('media')->orderBy('urutan')->get();
    }

    public function createPengurus(array $data)
    {
        $pengurus = Pengurus::create(collect($data)->except('foto')->toArray());
        if (isset($data['foto'])) {
            $this->upload($pengurus, $data['foto'], 'foto', 'profil/pengurus');
        }
        return $pengurus;
    }

    public function updatePengurus($id, array $data)
    {
        $pengurus = Pengurus::findOrFail($id);
        $pengurus->update(collect($data)->except('foto')->toArray());
        if (isset($data['foto'])) {
            $pengurus->clearMediaCollection('foto');
            $this->upload($pengurus, $data['foto'], 'foto', 'profil/pengurus');
        }
        return $pengurus;
    }

    public function deletePengurus($id)
    {
        $pengurus = Pengurus::findOrFail($id);
        $pengurus->clearMediaCollection('foto');
        $pengurus->delete();
    }

    private function upload($model, $file, $collection, $folder)
    {
        Storage::disk('public')->makeDirectory($folder);
        $model->addMedia($file)
            ->usingFileName($file->getClientOriginalName())
            ->toMediaCollection($collection);


    }
}