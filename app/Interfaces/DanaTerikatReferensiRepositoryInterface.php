<?php

namespace App\Interfaces;

interface DanaTerikatReferensiRepositoryInterface
{
    public function all();
    public function paginate($limit = 50);
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}
