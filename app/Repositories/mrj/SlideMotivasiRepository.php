<?php

namespace App\Repositories\mrj;

use App\Interfaces\SlideMotivasiRepositoryInterface;
use App\Models\SlideMotivasi;

class SlideMotivasiRepository implements SlideMotivasiRepositoryInterface
{
    protected $model;

    public function __construct(SlideMotivasi $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->orderBy('order', 'asc')->get();
    }

    public function find($id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        $data['is_active'] = isset($data['is_active']) ? 1 : 0;
        $data['order'] = $data['order'] ?? 999; // default tinggi jika tidak diisi
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        $slide = $this->find($id);
        $data['is_active'] = isset($data['is_active']) ? 1 : 0;
        return $slide->update($data);
    }

    public function delete($id)
    {
        $slide = $this->find($id);
        return $slide->delete();
    }

    public function ordered()
    {
        return $this->model->where('is_active', 1)
                           ->orderBy('order', 'asc')
                           ->get();
    }
}