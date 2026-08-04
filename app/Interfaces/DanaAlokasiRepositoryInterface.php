<?php

namespace App\Interfaces;

interface DanaAlokasiRepositoryInterface
{
    public function alokasi(array $data);

    public function getRiwayat(int $programId);
}
