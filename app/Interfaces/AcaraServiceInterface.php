<?php

namespace App\Interfaces;
use App\Models\Acara;

interface AcaraServiceInterface
{
    public function upcoming(int $limit = 6): array;
    public function latestCompleted(int $limit = 3): array;
    public function paginate(int $perPage = 12);
    public function getBySlug(string $slug);
    public function getRelated(Acara $acara, int $limit = 3): array;
}