<?php

namespace App\Repositories\mrj;

use App\Interfaces\KategoriRepositoryInterface;
use App\Models\Kategori;

class KategoriRepository implements KategoriRepositoryInterface
{
    public function all(?string $tipe = null)
    {
        $query = Kategori::query();
        if ($tipe) {
            $query->where('tipe', $tipe);
        }
        return $query->get()->map(fn($k) => [
            'id' => $k->id,
            'nama' => $k->nama,
            'tipe' => '<span class="badge bg-info">'.$k->tipe.'</span>',
            'warna' => '<div class="badge" style="background:'.$k->warna.'">Warna</div>'
        ]);
    }

    public function find($id)
    {
        return Kategori::findOrFail($id);
    }

    public function create(array $data)
    {
        return Kategori::create($data);
    }

    public function update($id, array $data)
    {
        $k = $this->find($id);
        $k->update($data);
        return $k;
    }

    public function delete($id)
    {
        $k = $this->find($id);
        $k->delete();
    }
}