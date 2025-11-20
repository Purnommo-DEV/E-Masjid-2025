<?php

namespace App\Interfaces;

interface MasjidRepositoryInterface
{
    public function getHomeData(): array;
    public function getBerita(int $limit = 5): array;
    public function getJadwalSholat(): array;
    // Tambah method lain sesuai kebutuhan
}