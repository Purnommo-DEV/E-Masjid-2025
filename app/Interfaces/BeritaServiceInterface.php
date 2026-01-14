<?php
namespace App\Interfaces;

interface BeritaServiceInterface
{
    /**
     * Ambil daftar berita terbaru (untuk home)
     * @param int $limit
     * @return array
     */
    public function latestForHome(int $limit = 4): array;
}
