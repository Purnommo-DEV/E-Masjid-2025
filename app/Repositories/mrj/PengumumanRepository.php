<?php

namespace App\Repositories\mrj;

use App\Interfaces\PengumumanRepositoryInterface;
use App\Models\Pengumuman;

class PengumumanRepository implements PengumumanRepositoryInterface
{
    public function all()
    {
        return Pengumuman::latest()->get();
    }

    public function find($id)
    {
        return Pengumuman::findOrFail($id);
    }

    public function create(array $data)
    {
        $data['masjid_code'] = masjid();
        return Pengumuman::create($data);
    }

    public function update($id, array $data)
    {
        $pengumuman = $this->find($id);
        $pengumuman->update($data);
        return $pengumuman;
    }

    public function delete($id)
    {
        $pengumuman = $this->find($id);
        $pengumuman->delete();
        return true;
    }
}