<?php
// app/Services/mrj/QurbanService.php

namespace App\Services\mrj;

use App\Interfaces\QurbanServiceInterface;
use App\Models\Qurban;
use App\Models\QurbanRegistration;
use Illuminate\Support\Facades\DB;

class QurbanService implements QurbanServiceInterface
{
    public function getAllActive()
    {
        return Qurban::active()
            ->forMasjid()
            ->orderBy('urutan')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($qurban) {
                return (object) [
                    'id' => $qurban->id,
                    'judul' => $qurban->judul,
                    'slug' => $qurban->slug,
                    'short_description' => $qurban->short_description,
                    'deskripsi' => $qurban->deskripsi,
                    'jenis_hewan' => $qurban->jenis_hewan,
                    'jenis_icon' => $qurban->jenis_icon,
                    'harga' => $qurban->harga,
                    'harga_formatted' => $qurban->harga_formatted,
                    'harga_per_share' => $qurban->harga_formatted,
                    'max_share' => $qurban->max_share,
                    'stok' => $qurban->stok,
                    'image_url' => $qurban->image_url,
                    'deadline' => $qurban->deadline,
                    'deadline_formatted' => $qurban->deadline?->translatedFormat('d F Y'),
                    'pelaksanaan_mulai' => $qurban->pelaksanaan_mulai,
                    'pelaksanaan_selesai' => $qurban->pelaksanaan_selesai,
                ];
            });
    }

    public function getFeatured()
    {
        return Qurban::active()
            ->forMasjid()
            ->featured()
            ->orderBy('urutan')
            ->first();
    }

    public function getBySlug($slug)
    {
        return Qurban::where('slug', $slug)
            ->forMasjid()
            ->active()
            ->firstOrFail();
    }

    public function getByJenis($jenis)
    {
        return Qurban::active()
            ->forMasjid()
            ->where('jenis_hewan', $jenis)
            ->orderBy('urutan')
            ->get();
    }

    public function register(array $data)
    {
        return DB::transaction(function () use ($data) {
            $qurban = Qurban::forMasjid()->findOrFail($data['qurban_id']);
            $jumlahShare = max(1, (int) ($data['jumlah_share'] ?? 1));

            if ($jumlahShare > $qurban->max_share) {
                throw new \Exception('Jumlah share melebihi batas maksimal (' . $qurban->max_share . ' orang)');
            }

            if ($qurban->stok < $jumlahShare) {
                throw new \Exception('Maaf, stok paket qurban tidak mencukupi.');
            }

            $totalHarga = $qurban->harga * $jumlahShare;
            
            $registration = QurbanRegistration::create([
                'qurban_id' => $data['qurban_id'],
                'masjid_code' => masjid(),
                'nama_lengkap' => $data['nama_lengkap'],
                'telepon' => $data['telepon'],
                'alamat' => $data['alamat'] ?? null,
                'jumlah_share' => $jumlahShare,
                'total_harga' => $totalHarga,
                'status' => 'pending',
                'catatan' => $data['catatan'] ?? null,
            ]);

            $qurban->decrement('stok', $jumlahShare);
            
            return $registration;
        });
    }

    public function getRegistrationByCode($kode)
    {
        return QurbanRegistration::with('qurban')
            ->where('masjid_code', masjid())
            ->where('kode_registrasi', $kode)
            ->firstOrFail();
    }
}
