<?php

namespace App\Interfaces;

interface ProfilMasjidRepositoryInterface
{
    public function getProfil();
    public function updateProfil(array $data);
    public function getPengurus();
    public function createPengurus(array $data);
    public function updatePengurus($id, array $data);
    public function deletePengurus($id);
}
