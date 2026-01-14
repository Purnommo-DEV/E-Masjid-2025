<?php

namespace App\Interfaces;

interface AkunKeuanganRepositoryInterface
{
    public function datatables();
    public function all();
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}
