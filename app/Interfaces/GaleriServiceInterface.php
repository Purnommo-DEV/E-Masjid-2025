<?php

namespace App\Interfaces;

interface GaleriServiceInterface
{
    public function latestFotos(int $limit = 12): array;
    public function getByKategori(string $slug, int $perPage = 12);
    public function findById($id);
}