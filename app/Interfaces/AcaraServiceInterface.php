<?php

namespace App\Interfaces;

interface AcaraServiceInterface
{
    /**
     * Ambil upcoming acara (untuk home/agenda). Mengembalikan collection/array siap dipakai blade.
     *
     * @param int $limit
     * @return array
     */
    public function upcoming(int $limit = 6): array;

    /**
     * Ambil daftar acara paginated (optional).
     *
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(int $perPage = 12);
}
