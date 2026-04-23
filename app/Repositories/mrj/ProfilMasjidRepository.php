<?php

namespace App\Repositories\mrj;

use App\Interfaces\ProfilMasjidRepositoryInterface;
use App\Models\ProfilMasjid;
use App\Models\Pengurus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ProfilMasjidRepository implements ProfilMasjidRepositoryInterface
{
    public function getProfil()
    {
        return ProfilMasjid::withoutGlobalScope('masjid')
            ->where('masjid_code', masjid())
            ->first();
    }

    public function updateProfil(array $data)
    {
        return DB::transaction(function () use ($data) {
            $profil = $this->getProfil();
            
            if (!$profil) {
                $profil = ProfilMasjid::create([
                    'masjid_code' => masjid(),
                    'nama' => $data['nama'] ?? '',
                ]);
            }

            $updateData = [
                'nama' => $data['nama'],
                'singkatan' => $data['singkatan'] ?? null,
                'alamat' => $data['alamat'],
                'telepon' => $data['telepon'] ?? null,
                'email' => $data['email'] ?? null,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'bank_name' => $data['bank_name'] ?? null,
                'bank_code' => $data['bank_code'] ?? null,
                'rekening' => $data['rekening'] ?? null,
                'atas_nama' => $data['atas_nama'] ?? null,
                'wa_konfirmasi' => $data['wa_konfirmasi'] ?? null,
            ];

            // Upload logo
            if (!empty($data['logo']) && $data['logo'] instanceof UploadedFile) {
                $updateData['logo_path'] = upload_image($data['logo'], 'profil/logo', $profil->logo_path, true);
            }

            // Upload struktur
            if (!empty($data['struktur']) && $data['struktur'] instanceof UploadedFile) {
                $updateData['struktur_path'] = upload_image($data['struktur'], 'profil/struktur', $profil->struktur_path, true);
            }

            // Upload QRIS
            if (!empty($data['qris']) && $data['qris'] instanceof UploadedFile) {
                try {
                    $updateData['qris_path'] = upload_image($data['qris'], 'profil/qris', $profil->qris_path, true);
                } catch (\Exception $e) {
                    throw new \Exception('QRIS: ' . $e->getMessage());
                }
            }

            $profil->update($updateData);

            return $profil;
        });
    }

    public function getPengurus()
    {
        return Pengurus::withoutGlobalScope('masjid')
            ->where('masjid_code', masjid())
            ->orderBy('urutan')
            ->get()
            ->map(function ($pengurus) {
                // Tambahkan foto_url ke setiap pengurus
                $pengurus->foto_url = $pengurus->foto_url;
                return $pengurus;
            });
    }

    public function createPengurus(array $data)
    {
        return DB::transaction(function () use ($data) {
            $pengurus = Pengurus::create([
                'masjid_code' => masjid(),
                'nama' => $data['nama'],
                'jabatan' => $data['jabatan'],
                'keterangan' => $data['keterangan'] ?? null,
                'urutan' => $data['urutan'] ?? 0,
                'created_by' => auth()->id(),
            ]);

            if (!empty($data['foto']) && $data['foto'] instanceof UploadedFile) {
                $fotoPath = upload_image($data['foto'], 'pengurus', null, true);
                $pengurus->update(['foto_path' => $fotoPath]);
            }

            return $pengurus;
        });
    }

    public function updatePengurus($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $pengurus = Pengurus::withoutGlobalScope('masjid')
                ->where('masjid_code', masjid())
                ->where('id', $id)
                ->firstOrFail();

            $updateData = [
                'nama' => $data['nama'],
                'jabatan' => $data['jabatan'],
                'keterangan' => $data['keterangan'] ?? null,
                'urutan' => $data['urutan'] ?? 0,
                'updated_by' => auth()->id(),
            ];

            if (!empty($data['foto']) && $data['foto'] instanceof UploadedFile) {
                $updateData['foto_path'] = upload_image($data['foto'], 'pengurus', $pengurus->foto_path, true);
            }

            $pengurus->update($updateData);

            return $pengurus;
        });
    }

    public function deletePengurus($id)
    {
        return DB::transaction(function () use ($id) {
            $pengurus = Pengurus::withoutGlobalScope('masjid')
                ->where('masjid_code', masjid())
                ->where('id', $id)
                ->firstOrFail();

            if ($pengurus->foto_path) {
                delete_image($pengurus->foto_path);
            }

            return $pengurus->delete();
        });
    }

    public function reorderPengurus(array $order)
    {
        foreach ($order as $index => $id) {
            Pengurus::where('id', $id)->update(['urutan' => $index + 1]);
        }
        return true;
    }
}