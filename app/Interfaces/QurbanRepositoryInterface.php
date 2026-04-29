<?php

namespace App\Interfaces;

interface QurbanRepositoryInterface
{
    public function all();
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function getActivePakets();
    public function updateStok($id, $jumlah);
}