<?php

namespace App\Interfaces;

interface KategoriRepositoryInterface
{
    public function all(?string $tipe = null);
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}