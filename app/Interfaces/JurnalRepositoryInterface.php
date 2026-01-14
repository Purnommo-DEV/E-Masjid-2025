<?php

namespace App\Interfaces;

interface JurnalRepositoryInterface
{
    /** Buat jurnal double-entry manual */
    public function buatJurnal($tanggal, $keterangan, array $entries, $reference = null);

    /** Lock saldo awal + buat semua jurnal pembuka (sudah ada sebelumnya) */
    public function lockSaldoAwal($periodeId);

    /** Isi ulang petty cash dari kas utama */
    public function isiUlangPettyCash($tanggal, $jumlah, $keterangan, $reference = null);

    /** Pengeluaran langsung dari petty cash (kas kecil) */
    public function pengeluaranDariPettyCash($tanggal, $akunBebanId, $jumlah, $keterangan, $reference = null);

    /** Pengeluaran umum langsung dari kas utama / bank */
    public function pengeluaranUmum($tanggal, $akunBebanId, $jumlah, $keterangan, $reference = null);

    // Pemasukkan
    public function penerimaanPemasukan($tanggal, $akunPendapatanId, $jumlah, $keterangan, $reference = null);

    /** Alokasi dana terikat (earmarking) */
    public function alokasiDana($tanggal, $akunSumberId, $akunTujuanId, $jumlah, $keterangan, $reference = null);

    // Penerimaan Zakat
    public function penerimaanZakat($tanggal, $jumlah, $akunLiabilitasId, $muzakki, $reference = null);

    // Penyaluran Zakat
    public function penyaluranZakat($tanggal, $jumlah, $akunLiabilitasId, $keteranganPenyaluran, $reference = null);

    // Terima Dana Terikat
    public function terimaDanaTerikat($tanggal, $jumlah, $program, $donatur, $reference = null);

    // Realisasi Dana Terikat
    public function realisasiDanaTerikat($tanggal, $jumlah, $program, $penerima, $reference = null);
}
