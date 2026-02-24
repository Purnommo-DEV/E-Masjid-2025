<?php

namespace App\Repositories\mrj;

use App\Interfaces\JadwalImamTarawihRepositoryInterface;
use App\Models\JadwalImamTarawih;

class JadwalImamTarawihRepository implements JadwalImamTarawihRepositoryInterface
{
    public function all()
    {
        return JadwalImamTarawih::orderBy('malam_ke', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'id'              => $item->id,
                    'malam_ke'        => $item->malam_ke,
                    'tanggal'         => $item->tanggal ? $item->tanggal->format('d M Y') : '-',
                    'imam_nama'       => $item->imam_nama ?? '-',
                    'penceramah_nama' => $item->penceramah_nama ?? '-',
                    'tema_tausiyah'   => $item->tema_tausiyah ?? '-',   // ← ubah dari 'tema' menjadi 'tema_tausiyah'
                ];
            });
    }
    public function find($id)
    {
        return JadwalImamTarawih::findOrFail($id);
    }

    public function create(array $data)
    {
        return JadwalImamTarawih::create($data);
    }

    public function update($id, array $data)
    {
        $jadwal = $this->find($id);
        $jadwal->update($data);
        return $jadwal;
    }

    public function delete($id)
    {
        $jadwal = $this->find($id);
        $jadwal->delete();
    }
}