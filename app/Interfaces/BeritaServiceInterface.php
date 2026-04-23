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
     * Paginate berita untuk halaman index
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 9): LengthAwarePaginator;

    /**
     * Ambil related berita
     * @param int $limit
     * @param int|null $excludeId
     * @return array
     */
    public function related(int $limit = 3, ?int $excludeId = null): array;

    /**
     * Ambil daftar program Ramadhan
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginateProgramRamadhan(int $perPage = 9): LengthAwarePaginator;

    /**
     * Cari program Ramadhan berdasarkan slug
     * @param string $slug
     * @return Berita
     */
    public function findProgramBySlug(string $slug): Berita;

    /**
     * Ambil program terkait Ramadhan
     * @param int $excludeId
     * @param int $limit
     * @return Collection
     */
    public function relatedPrograms(int $excludeId, int $limit = 3): Collection;

    /**
     * Cari berita berdasarkan slug
     * @param string $slug
     * @return Berita
     */
    public function findBySlug(string $slug): Berita;

    /**
     * Ambil semua berita untuk sitemap
     * @return Collection
     */
    public function getAllForSitemap(): Collection;
}