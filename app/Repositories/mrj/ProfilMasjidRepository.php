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
            collect($data)->except(['logo', 'struktur'])->toArray()
        );

        $disk = Storage::disk('public');

        foreach (['logo', 'struktur'] as $type) {
            if (empty($data[$type])) continue;

            $folder = "profil/{$type}";
            $file   = $data[$type];
            $name   = $file->getClientOriginalName();
            $targetPath = "{$folder}/{$name}";

            if (!$disk->exists($folder)) $disk->makeDirectory($folder);

            // === HAPUS FILE LAMA ===
            $oldMedia = $profil->getFirstMedia($type);
            if ($oldMedia) {
                $oldPath = "{$folder}/{$oldMedia->file_name}";
                // dd('Hapus Lama', $oldPath, $disk->exists($oldPath));
                if ($disk->exists($oldPath)) $disk->delete($oldPath);
                $oldMedia->delete();
            }

            $media = $profil->addMedia($file)
                ->usingFileName($name)
                ->preservingOriginal()
                ->toMediaCollection($type);

            $tempPath = $media->getPath();
            $finalPath = storage_path("app/public/{$targetPath}");

            if (file_exists($tempPath)) {
                if (!is_dir(dirname($finalPath))) mkdir(dirname($finalPath), 0755, true);
                rename($tempPath, $finalPath);
                $tempDir = dirname($tempPath);
                if (is_dir($tempDir) && count(scandir($tempDir)) == 2) rmdir($tempDir);
            }

            $media->setCustomProperty('folder', $folder);
            $media->setCustomProperty('original_name', $name);
            $media->save();

            // === DEBUG ===
            // dd([
            //     'Final File' => $finalPath,
            //     'Exists?' => file_exists($finalPath),
            //     'Files in Folder' => $disk->files($folder),
            // ]);
        }

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