<?php

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface DanaTerikatRepositoryInterface
{
    // Untuk tab SALDO
    public function getSaldoData(?int $programId, ?int $tahun): Collection;

    // Untuk tab PENERIMA (DataTables butuh Builder)
    public function getPenerimaQuery(?int $programId, ?int $tahun): Builder;

    // Untuk tab PENERIMAAN
    public function getPenerimaanQuery(?int $programId, ?int $tahun): Builder;

    // Untuk tab REALISASI
    public function getRealisasiQuery(?int $programId, ?int $tahun): Builder;

    // Simpan penerimaan + jurnal (dalam transaksi)
    public function storePenerimaan(array $data);

    // List dData penerima
    public function findPenerima(int $id);
    
    // Edit penerima
    public function updatePenerima(int $id, array $data);
    
    // Simpan penerima
    public function storePenerima(array $data);

    // Proses realisasi bulanan + jurnal
    public function realisasiBulanan(int $programId, int $bulan, int $tahun);

    // Simpan program
    public function storeProgram(array $data);

    // HTML option akun liabilitas
    public function getAkunOptionsHtml(): string;
}
