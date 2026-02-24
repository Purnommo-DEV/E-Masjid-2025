<?php

namespace App\Repositories\mrj;

use App\Interfaces\QuoteHarianRepositoryInterface;
use App\Models\QuoteHarian;

class QuoteHarianRepository implements QuoteHarianRepositoryInterface
{
    protected $model;

    public function __construct(QuoteHarian $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->query();
    }

    public function find($id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        $data['is_active'] = isset($data['is_active']) ? 1 : 0;
        $data['order'] = $data['order'] ?? 999;
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        $quote = $this->find($id);
        $data['is_active'] = isset($data['is_active']) ? 1 : 0;
        return $quote->update($data);
    }

    public function delete($id)
    {
        $quote = $this->find($id);
        return $quote->delete();
    }

    public function randomActive()
    {
        return $this->model->where('is_active', 1)
                           ->inRandomOrder()
                           ->first();
    }
}