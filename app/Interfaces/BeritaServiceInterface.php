<?php
namespace App\Interfaces;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Berita;

interface BeritaServiceInterface
{
    /**
     * Ambil daftar berita terbaru (untuk home)
     * @param int $limit
     * @return array
     */
    public function latestForHome(int $limit = 4): array;

    /**
     * Ambil daftar program Ramadhan (berita dengan kategori tertentu) - untuk halaman index
     */
    public function paginateProgramRamadhan(int $perPage = 9): LengthAwarePaginator;

    /**
     * Cari satu program berdasarkan slug (untuk halaman detail)
     */
    public function findProgramBySlug(string $slug): Berita;

    /**
     * Ambil program terkait dalam kategori Ramadhan (untuk related di halaman detail)
     */
    public function relatedPrograms(int $excludeId, int $limit = 3): Collection;
}
