<?php

namespace App\Repositories\mrj;

use App\Interfaces\DanaAlokasiRepositoryInterface;
use App\Interfaces\JurnalRepositoryInterface;
use App\Models\DanaAlokasi;
use Illuminate\Support\Facades\DB;

class DanaAlokasiRepository implements DanaAlokasiRepositoryInterface
{
    protected $jurnal;

    public function __construct(JurnalRepositoryInterface $jurnal)
    {
        $this->jurnal = $jurnal;
    }

    public function alokasi(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 1. Simpan alokasi

            $alokasi = DanaAlokasi::create([
                'program_id' => $data['program_id'],
                'akun_sumber_id' => $data['akun_sumber_id'],
                'akun_tujuan_id' => $data['akun_tujuan_id'],
                'tanggal' => $data['tanggal'],
                'jumlah' => $data['jumlah_alokasi'],
                'keterangan' => $data['keterangan'] ?? null,
                'bulan_cakupan' => $data['bulan_cakupan'] ?? 0,
                'created_by' => auth()->id(),
            ]);

            // 2. Buat jurnal alokasi
            $this->jurnal->alokasiDana(
                $data['tanggal'],
                $data['akun_sumber_id'],
                $data['akun_tujuan_id'],
                $data['jumlah_alokasi'],
                $data['keterangan'] ?? 'Alokasi dana untuk santunan Yatim & Dhuafa',
                $alokasi
            );

            return $alokasi;
        });
    }

    public function getRiwayat(int $programId)
    {
        return DanaAlokasi::where('program_id', $programId)
            ->with(['akunSumber', 'akunTujuan', 'createdBy'])
            ->orderBy('tanggal', 'desc')
            ->get();
    }
}
