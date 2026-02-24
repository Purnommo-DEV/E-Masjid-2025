<?php

namespace App\Repositories\mrj;

use App\Interfaces\KhutbahJumatRepositoryInterface;
use App\Models\KhutbahJumat;

class KhutbahJumatRepository implements KhutbahJumatRepositoryInterface
{
    protected $model;

    public function __construct(KhutbahJumat $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->orderBy('tanggal_asli', 'desc')->get();
    }

    public function find($id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        $data['is_active'] = isset($data['is_active']) ? 1 : 0;
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        $khutbah = $this->find($id);
        $data['is_active'] = isset($data['is_active']) ? 1 : 0;
        return $khutbah->update($data);
    }

    public function delete($id)
    {
        $khutbah = $this->find($id);
        return $khutbah->delete();
    }

    public function comingSoon()
    {
        return $this->model->where('is_active',1)
            ->where('status','coming_soon')
            ->orderBy('tanggal_asli','asc')
            ->first();
    }

    public function active()
    {
        return $this->model->where('is_active', 1);
    }
}