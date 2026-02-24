<?php

namespace App\Interfaces;

interface LaporanRamadhanHarianRepositoryInterface
{
    public function all();
    public function find($id);
    public function donatur();
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}