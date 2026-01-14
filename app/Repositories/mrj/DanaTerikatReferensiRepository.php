<?php

namespace App\Repositories\mrj;

use App\Models\DanaTerikatReferensi;
use App\Interfaces\DanaTerikatReferensiRepositoryInterface;

class DanaTerikatReferensiRepository implements DanaTerikatReferensiRepositoryInterface
{
    public function all()
    {
        return DanaTerikatReferensi::orderBy('nama')->get();
    }

    public function paginate($limit = 50)
    {
        return DanaTerikatReferensi::orderBy('nama')->paginate($limit);
    }

    public function find($id)
    {
        return DanaTerikatReferensi::findOrFail($id);
    }

    public function create(array $data)
    {
        return DanaTerikatReferensi::create($data);
    }

    public function update($id, array $data)
    {
        $ref = DanaTerikatReferensi::findOrFail($id);
        $ref->update($data);
        return $ref;
    }

    public function delete($id)
    {
        $ref = DanaTerikatReferensi::findOrFail($id);
        return $ref->delete();
    }
}