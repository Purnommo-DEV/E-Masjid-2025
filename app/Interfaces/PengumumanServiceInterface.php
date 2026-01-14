<?php
namespace App\Interfaces;

interface PengumumanServiceInterface
{
    /**
     * Ambil daftar pengumuman terbaru (untuk home)
     * @param int $limit
     * @return array
     */
    public function latestForHome(int $limit = 4): array;
}
