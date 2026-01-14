<?php

namespace App\Interfaces;

interface KeuanganRepositoryInterface
{
    public function ambilSaldoAwal($periode);
    public function cekSaldoAwalManual($periode);
    public function simpanKoreksiSaldoAwal($periode, $jumlah, $keterangan = 'Koreksi manual');
    public function getKotakList();
    public function recountHari($kotakId);
    public function hitungSaldo($start, $end);
    public function hitungKotak(array $data);
    public function createTransaksi(array $data);
    public function updateTransaksi($id, array $data);
    public function deleteTransaksi($id);
    public function getTransaksiForDataTable($start, $end);
}
        