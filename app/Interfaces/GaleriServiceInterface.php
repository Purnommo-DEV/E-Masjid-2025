<?php

namespace App\Interfaces;

interface GaleriServiceInterface
{
    public function latestFotos(int $limit = 12): array;
}
