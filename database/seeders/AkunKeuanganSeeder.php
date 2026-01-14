<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AkunKeuangan;

class AkunKeuanganSeeder extends Seeder
{
    public function run()
    {
        $akuns = [
            // ================== ASET ==================
            ['kode' => '10001', 'nama' => 'Kas Utama', 'tipe' => 'aset', 'saldo_normal' => 'debit', 'jenis_beban' => 'tidak_berlaku', 'grup' => null],
            ['kode' => '10002', 'nama' => 'Bank BCA Syariah', 'tipe' => 'aset', 'saldo_normal' => 'debit', 'jenis_beban' => 'tidak_berlaku', 'grup' => null],
            ['kode' => '10003', 'nama' => 'Bank BRI Syariah', 'tipe' => 'aset', 'saldo_normal' => 'debit', 'jenis_beban' => 'tidak_berlaku', 'grup' => null],
            ['kode' => '10004', 'nama' => 'Bank Mandiri Syariah', 'tipe' => 'aset', 'saldo_normal' => 'debit', 'jenis_beban' => 'tidak_berlaku', 'grup' => null],
            ['kode' => '10005', 'nama' => 'Kas Kecil (Petty Cash)', 'tipe' => 'aset', 'saldo_normal' => 'debit', 'jenis_beban' => 'tidak_berlaku', 'grup' => null],
            ['kode' => '10006', 'nama' => 'Piutang Donatur', 'tipe' => 'aset', 'saldo_normal' => 'debit', 'jenis_beban' => 'tidak_berlaku', 'grup' => null],
            ['kode' => '11001', 'nama' => 'Tanah & Bangunan Masjid', 'tipe' => 'aset', 'saldo_normal' => 'debit', 'jenis_beban' => 'tidak_berlaku', 'grup' => null],
            ['kode' => '11002', 'nama' => 'Akumulasi Penyusutan Bangunan', 'tipe' => 'aset', 'saldo_normal' => 'kredit', 'jenis_beban' => 'tidak_berlaku', 'grup' => null],

            // ================== LIABILITAS – ZAKAT & FIDYAH (GRUP: zakat) ==================
            ['kode' => '20001', 'nama' => 'Zakat Fitrah Belum Disalurkan', 'tipe' => 'liabilitas', 'saldo_normal' => 'kredit', 'jenis_beban' => 'tidak_berlaku', 'grup' => 'zakat'],
            ['kode' => '20002', 'nama' => 'Zakat Maal Belum Disalurkan', 'tipe' => 'liabilitas', 'saldo_normal' => 'kredit', 'jenis_beban' => 'tidak_berlaku', 'grup' => 'zakat'],
            ['kode' => '20003', 'nama' => 'Fidyah Belum Disalurkan', 'tipe' => 'liabilitas', 'saldo_normal' => 'kredit', 'jenis_beban' => 'tidak_berlaku', 'grup' => 'zakat'],
            ['kode' => '20004', 'nama' => 'Infaq Terikat (Yatim/Proyek)', 'tipe' => 'liabilitas', 'saldo_normal' => 'kredit', 'jenis_beban' => 'tidak_berlaku', 'grup' => null],

            // ================== PENDAPATAN – KOTAK INFAK (GRUP: kotak_infak) ==================
            ['kode' => '30001', 'nama' => 'Infak Kotak Jumat', 'tipe' => 'pendapatan', 'saldo_normal' => 'kredit', 'jenis_beban' => 'tidak_berlaku', 'grup' => 'kotak_infak'],
            ['kode' => '30002', 'nama' => 'Infak Kotak Kajian', 'tipe' => 'pendapatan', 'saldo_normal' => 'kredit', 'jenis_beban' => 'tidak_berlaku', 'grup' => 'kotak_infak'],
            ['kode' => '30003', 'nama' => 'Infak Kotak Ramadhan', 'tipe' => 'pendapatan', 'saldo_normal' => 'kredit', 'jenis_beban' => 'tidak_berlaku', 'grup' => 'kotak_infak'],
            ['kode' => '30004', 'nama' => 'Infak Kotak Qurban', 'tipe' => 'pendapatan', 'saldo_normal' => 'kredit', 'jenis_beban' => 'tidak_berlaku', 'grup' => 'kotak_infak'],
            ['kode' => '30005', 'nama' => 'Infaq/Shadaqah Umum (Kotak)', 'tipe' => 'pendapatan', 'saldo_normal' => 'kredit', 'jenis_beban' => 'tidak_berlaku', 'grup' => 'kotak_infak'],

            // ================== PENDAPATAN – DONASI BESAR / TRANSFER / QRIS (GRUP: donasi_besar) ==================
            ['kode' => '30006', 'nama' => 'Donasi Umum / Non-Terikat', 'tipe' => 'pendapatan', 'saldo_normal' => 'kredit', 'jenis_beban' => 'tidak_berlaku', 'grup' => 'donasi_besar'],
            ['kode' => '30007', 'nama' => 'QRIS / Transfer Infak Umum', 'tipe' => 'pendapatan', 'saldo_normal' => 'kredit', 'jenis_beban' => 'tidak_berlaku', 'grup' => 'donasi_besar'],
            ['kode' => '30008', 'nama' => 'Hibah Non-Terikat', 'tipe' => 'pendapatan', 'saldo_normal' => 'kredit', 'jenis_beban' => 'tidak_berlaku', 'grup' => 'donasi_besar'],

            // ================== BEBAN KECIL – PETTY CASH ==================
            ['kode' => '40001', 'nama' => 'Perlengkapan Kebersihan', 'tipe' => 'beban', 'saldo_normal' => 'debit', 'jenis_beban' => 'kecil', 'grup' => null],
            ['kode' => '40002', 'nama' => 'Konsumsi Marbot', 'tipe' => 'beban', 'saldo_normal' => 'debit', 'jenis_beban' => 'kecil', 'grup' => null],
            ['kode' => '40003', 'nama' => 'Air Minum Jamaah', 'tipe' => 'beban', 'saldo_normal' => 'debit', 'jenis_beban' => 'kecil', 'grup' => null],
            ['kode' => '40004', 'nama' => 'Jumat Berkah', 'tipe' => 'beban', 'saldo_normal' => 'debit', 'jenis_beban' => 'kecil', 'grup' => null],
            ['kode' => '40005', 'nama' => 'Biaya Pemeliharaan Kecil', 'tipe' => 'beban', 'saldo_normal' => 'debit', 'jenis_beban' => 'kecil', 'grup' => null],
            ['kode' => '40006', 'nama' => 'Beban Admin Bank', 'tipe' => 'beban', 'saldo_normal' => 'debit', 'jenis_beban' => 'kecil', 'grup' => null],

            // ================== BEBAN BESAR – RESMI ==================
            ['kode' => '40010', 'nama' => 'Gaji Imam', 'tipe' => 'beban', 'saldo_normal' => 'debit', 'jenis_beban' => 'besar', 'grup' => null],
            ['kode' => '40011', 'nama' => 'Gaji Marbot', 'tipe' => 'beban', 'saldo_normal' => 'debit', 'jenis_beban' => 'besar', 'grup' => null],
            ['kode' => '40012', 'nama' => 'Honor Khatib Jumat', 'tipe' => 'beban', 'saldo_normal' => 'debit', 'jenis_beban' => 'besar', 'grup' => null],
            ['kode' => '40013', 'nama' => 'Honor Pengajian', 'tipe' => 'beban', 'saldo_normal' => 'debit', 'jenis_beban' => 'besar', 'grup' => null],
            ['kode' => '40014', 'nama' => 'Honor Muadzin', 'tipe' => 'beban', 'saldo_normal' => 'debit', 'jenis_beban' => 'besar', 'grup' => null],
            ['kode' => '40015', 'nama' => 'Listrik & Air PDAM', 'tipe' => 'beban', 'saldo_normal' => 'debit', 'jenis_beban' => 'besar', 'grup' => null],
            ['kode' => '40016', 'nama' => 'Internet & Komunikasi', 'tipe' => 'beban', 'saldo_normal' => 'debit', 'jenis_beban' => 'besar', 'grup' => null],
            ['kode' => '40017', 'nama' => 'Penyusutan Aset', 'tipe' => 'beban', 'saldo_normal' => 'debit', 'jenis_beban' => 'besar', 'grup' => null],

            // ================== EKUITAS ==================
            ['kode' => '50001', 'nama' => 'Surplus/Defisit Tahun Berjalan', 'tipe' => 'ekuitas', 'saldo_normal' => 'kredit', 'jenis_beban' => 'tidak_berlaku', 'grup' => null],
            ['kode' => '50002', 'nama' => 'Dana Abadi Masjid', 'tipe' => 'ekuitas', 'saldo_normal' => 'kredit', 'jenis_beban' => 'tidak_berlaku', 'grup' => null],
            ['kode' => '50003', 'nama' => 'Dana Terikat – Pembangunan', 'tipe' => 'ekuitas', 'saldo_normal' => 'kredit', 'jenis_beban' => 'tidak_berlaku', 'grup' => null],
            ['kode' => '50004', 'nama' => 'Dana Terikat – Operasional', 'tipe' => 'ekuitas', 'saldo_normal' => 'kredit', 'jenis_beban' => 'tidak_berlaku', 'grup' => null],
            ['kode' => '50005', 'nama' => 'Prive / Penarikan Pengurus', 'tipe' => 'ekuitas', 'saldo_normal' => 'debit', 'jenis_beban' => 'tidak_berlaku', 'grup' => null],
        ];

        foreach ($akuns as $a) {
            AkunKeuangan::updateOrCreate(
                ['kode' => $a['kode']], // unique berdasarkan kode
                $a
            );
        }

        $this->command->info('AKUN KEUANGAN MASJID SUDAH 100% LENGKAP + GRUP SIAP PAKAI!');
        $this->command->info('Zakat → grup: zakat | Kotak Infak → grup: kotak_infak | QRIS/Transfer → grup: donasi_besar');
        $this->command->info('SYARIAH COMPLIANT & KEMENAG READY 2025!');
    }
}