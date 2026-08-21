<?php

namespace Database\Seeders;

use App\Models\AkunKeuangan;
use Illuminate\Database\Seeder;

class AkunKeuanganSeeder extends Seeder
{
    public function run()
    {
        $akuns = [
            // ========================================
            // 1. ASET
            // ========================================
            ['kode' => '10001', 'nama' => 'Kas Utama', 'tipe' => 'aset', 'saldo_normal' => 'debit', 'jenis_beban' => 'tidak_berlaku', 'grup' => null],
            ['kode' => '10002', 'nama' => 'Bank Syariah Indonesia (BSI)', 'tipe' => 'aset', 'saldo_normal' => 'debit', 'jenis_beban' => 'tidak_berlaku', 'grup' => null],
            ['kode' => '10003', 'nama' => 'Bank BNI', 'tipe' => 'aset', 'saldo_normal' => 'debit', 'jenis_beban' => 'tidak_berlaku', 'grup' => null],
            ['kode' => '10004', 'nama' => 'Bank Mandiri Syariah', 'tipe' => 'aset', 'saldo_normal' => 'debit', 'jenis_beban' => 'tidak_berlaku', 'grup' => null],
            ['kode' => '10005', 'nama' => 'Kas Kecil (Petty Cash)', 'tipe' => 'aset', 'saldo_normal' => 'debit', 'jenis_beban' => 'tidak_berlaku', 'grup' => null],
            ['kode' => '10006', 'nama' => 'Piutang Donatur', 'tipe' => 'aset', 'saldo_normal' => 'debit', 'jenis_beban' => 'tidak_berlaku', 'grup' => null],
            ['kode' => '11001', 'nama' => 'Tanah & Bangunan Masjid', 'tipe' => 'aset', 'saldo_normal' => 'debit', 'jenis_beban' => 'tidak_berlaku', 'grup' => null],
            ['kode' => '11002', 'nama' => 'Akumulasi Penyusutan Bangunan', 'tipe' => 'aset', 'saldo_normal' => 'kredit', 'jenis_beban' => 'tidak_berlaku', 'grup' => null],

            // ========================================
            // 2. LIABILITAS – ZAKAT, INFAQ, SHODAQOH, DANA TERIKAT
            // ========================================

            // 2a. ZAKAT (grup: zakat)
            ['kode' => '20001', 'nama' => 'Zakat Fitrah Belum Disalurkan', 'tipe' => 'liabilitas', 'saldo_normal' => 'kredit', 'jenis_beban' => 'tidak_berlaku', 'grup' => 'zakat'],
            ['kode' => '20002', 'nama' => 'Zakat Maal', 'tipe' => 'liabilitas', 'saldo_normal' => 'kredit', 'jenis_beban' => 'tidak_berlaku', 'grup' => 'zakat'],
            ['kode' => '20003', 'nama' => 'Fidyah Belum Disalurkan', 'tipe' => 'liabilitas', 'saldo_normal' => 'kredit', 'jenis_beban' => 'tidak_berlaku', 'grup' => 'zakat'],

            // 2b. INFAQ TERIKAT (YATIM/DHUAF A) → FOKUS PROGRAM
            ['kode' => '20004', 'nama' => 'Infaq Terikat (Yatim/Dhuafa)', 'tipe' => 'liabilitas', 'saldo_normal' => 'kredit', 'jenis_beban' => 'tidak_berlaku', 'grup' => null],

            // 2c. INFAQ UMUM
            ['kode' => '20005', 'nama' => 'Infaq Umum', 'tipe' => 'liabilitas', 'saldo_normal' => 'kredit', 'jenis_beban' => 'tidak_berlaku', 'grup' => 'infaq'],

            // 2d. SHODAQOH
            ['kode' => '20006', 'nama' => 'Shodaqoh', 'tipe' => 'liabilitas', 'saldo_normal' => 'kredit', 'jenis_beban' => 'tidak_berlaku', 'grup' => 'shodaqoh'],

            // 🔥 AKUN TITIPAN SEMENTARA
            ['kode' => '20099', 'nama' => 'Dana Titipan (Belum Dikelompokkan)', 'tipe' => 'liabilitas', 'saldo_normal' => 'kredit', 'jenis_beban' => 'tidak_berlaku', 'grup' => null],

            // ========================================
            // 3. PENDAPATAN – KOTAK INFAK (grup: kotak_infak)
            // ========================================
            ['kode' => '30001', 'nama' => 'Infak Kotak Jumat', 'tipe' => 'pendapatan', 'saldo_normal' => 'kredit', 'jenis_beban' => 'tidak_berlaku', 'grup' => 'kotak_infak'],
            ['kode' => '30002', 'nama' => 'Infak Kotak Kajian', 'tipe' => 'pendapatan', 'saldo_normal' => 'kredit', 'jenis_beban' => 'tidak_berlaku', 'grup' => 'kotak_infak'],
            ['kode' => '30003', 'nama' => 'Infak Kotak Ramadhan', 'tipe' => 'pendapatan', 'saldo_normal' => 'kredit', 'jenis_beban' => 'tidak_berlaku', 'grup' => 'kotak_infak'],
            ['kode' => '30004', 'nama' => 'Infak Kotak Qurban', 'tipe' => 'pendapatan', 'saldo_normal' => 'kredit', 'jenis_beban' => 'tidak_berlaku', 'grup' => 'kotak_infak'],
            ['kode' => '30005', 'nama' => 'Infaq/Shadaqah Umum (Kotak)', 'tipe' => 'pendapatan', 'saldo_normal' => 'kredit', 'jenis_beban' => 'tidak_berlaku', 'grup' => 'kotak_infak'],

            // ========================================
            // 4. PENDAPATAN – DONASI BESAR (grup: donasi_besar)
            // ========================================
            ['kode' => '30006', 'nama' => 'Donasi Umum / Non-Terikat', 'tipe' => 'pendapatan', 'saldo_normal' => 'kredit', 'jenis_beban' => 'tidak_berlaku', 'grup' => 'donasi_besar'],
            ['kode' => '30007', 'nama' => 'QRIS / Transfer Infak Umum', 'tipe' => 'pendapatan', 'saldo_normal' => 'kredit', 'jenis_beban' => 'tidak_berlaku', 'grup' => 'donasi_besar'],
            ['kode' => '30008', 'nama' => 'Hibah Non-Terikat', 'tipe' => 'pendapatan', 'saldo_normal' => 'kredit', 'jenis_beban' => 'tidak_berlaku', 'grup' => 'donasi_besar'],

            // ========================================
            // 5. BEBAN KECIL – PETTY CASH (jenis_beban: kecil)
            // ========================================
            ['kode' => '40001', 'nama' => 'Perlengkapan Kebersihan', 'tipe' => 'beban', 'saldo_normal' => 'debit', 'jenis_beban' => 'kecil', 'grup' => null],
            ['kode' => '40002', 'nama' => 'Konsumsi Marbot', 'tipe' => 'beban', 'saldo_normal' => 'debit', 'jenis_beban' => 'kecil', 'grup' => null],
            ['kode' => '40003', 'nama' => 'Air Minum Jamaah', 'tipe' => 'beban', 'saldo_normal' => 'debit', 'jenis_beban' => 'kecil', 'grup' => null],
            ['kode' => '40004', 'nama' => 'Jumat Berkah', 'tipe' => 'beban', 'saldo_normal' => 'debit', 'jenis_beban' => 'kecil', 'grup' => null],
            ['kode' => '40005', 'nama' => 'Biaya Pemeliharaan Kecil', 'tipe' => 'beban', 'saldo_normal' => 'debit', 'jenis_beban' => 'kecil', 'grup' => null],
            ['kode' => '40006', 'nama' => 'Beban Admin Bank', 'tipe' => 'beban', 'saldo_normal' => 'debit', 'jenis_beban' => 'kecil', 'grup' => null],

            // ========================================
            // 6. BEBAN BESAR – RESMI (jenis_beban: besar)
            // ========================================
            ['kode' => '40010', 'nama' => 'Gaji Imam', 'tipe' => 'beban', 'saldo_normal' => 'debit', 'jenis_beban' => 'besar', 'grup' => null],
            ['kode' => '40011', 'nama' => 'Gaji Marbot', 'tipe' => 'beban', 'saldo_normal' => 'debit', 'jenis_beban' => 'besar', 'grup' => null],
            ['kode' => '40012', 'nama' => 'Honor Khatib Jumat', 'tipe' => 'beban', 'saldo_normal' => 'debit', 'jenis_beban' => 'besar', 'grup' => null],
            ['kode' => '40013', 'nama' => 'Honor Pengajian', 'tipe' => 'beban', 'saldo_normal' => 'debit', 'jenis_beban' => 'besar', 'grup' => null],
            ['kode' => '40014', 'nama' => 'Honor Muadzin', 'tipe' => 'beban', 'saldo_normal' => 'debit', 'jenis_beban' => 'besar', 'grup' => null],
            ['kode' => '40015', 'nama' => 'Listrik & Air PDAM', 'tipe' => 'beban', 'saldo_normal' => 'debit', 'jenis_beban' => 'besar', 'grup' => null],
            ['kode' => '40016', 'nama' => 'Internet & Komunikasi', 'tipe' => 'beban', 'saldo_normal' => 'debit', 'jenis_beban' => 'besar', 'grup' => null],
            ['kode' => '40017', 'nama' => 'Penyusutan Aset', 'tipe' => 'beban', 'saldo_normal' => 'debit', 'jenis_beban' => 'besar', 'grup' => null],
            ['kode' => '40018', 'nama' => 'Biaya Operasional Distribusi', 'tipe' => 'beban', 'saldo_normal' => 'debit', 'jenis_beban' => 'besar', 'grup' => null],

            // ========================================
            // 7. EKUITAS
            // ========================================
            ['kode' => '50001', 'nama' => 'Saldo Awal Pembuka / Opening Balance Equity', 'tipe' => 'ekuitas', 'saldo_normal' => 'kredit', 'jenis_beban' => 'tidak_berlaku', 'grup' => null],
            ['kode' => '50002', 'nama' => 'Dana Operasional Masjid', 'tipe' => 'ekuitas', 'saldo_normal' => 'kredit', 'jenis_beban' => 'tidak_berlaku', 'grup' => null],
            ['kode' => '50003', 'nama' => 'Dana Abadi Masjid', 'tipe' => 'ekuitas', 'saldo_normal' => 'kredit', 'jenis_beban' => 'tidak_berlaku', 'grup' => null],
        ];

        foreach ($akuns as $a) {
            AkunKeuangan::updateOrCreate(
                ['kode' => $a['kode']],
                $a
            );
        }

        $this->command->info('========================================');
        $this->command->info('✅ AKUN KEUANGAN FINAL SELESAI!');
        $this->command->info('========================================');
        $this->command->info('');
        $this->command->info('📌 PERUBAHAN & TAMBAHAN:');
        $this->command->info('   • 10003: Bank BNI (Satu rekening untuk semua dana)');
        $this->command->info('   • 20004: Infaq Terikat (Yatim/Dhuafa) (Fokus program)');
        $this->command->info('   • 20005: Infaq Umum (BARU)');
        $this->command->info('   • 20006: Shodaqoh (BARU)');
        $this->command->info('   • 40018: Biaya Operasional Distribusi (BARU)');
        $this->command->info('');
        $this->command->info('📊 GRUP DANA:');
        $this->command->info('   • Zakat      → 20001, 20002, 20003');
        $this->command->info('   • Infaq      → 20005');
        $this->command->info('   • Shodaqoh   → 20006');
        $this->command->info('   • Dana Terikat → 20004 (Yatim/Dhuafa)');
        $this->command->info('');
        $this->command->info('✅ SYARIAH COMPLIANT & KEMENAG READY 2026!');
        $this->command->info('========================================');
    }
}
