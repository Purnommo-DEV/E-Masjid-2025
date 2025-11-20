<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\JenisKotakInfak;
use App\Models\KategoriKeuangan;

class KeuanganSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        JenisKotakInfak::create(['nama' => 'Jumat', 'wajib_jumat' => true]);
        JenisKotakInfak::create(['nama' => 'Infaq Umum']);
        JenisKotakInfak::create(['nama' => 'Kotak LT 1']);
        JenisKotakInfak::create(['nama' => 'Kotak LT 2']);

        $pemasukan = ['Infak Kotak', 'Zakat', 'Donasi', 'Wakaf'];
        $pengeluaran = ['Gaji Takmir', 'Listrik', 'Air', 'Renovasi'];
        foreach ($pemasukan as $n) KategoriKeuangan::create(['nama' => $n, 'tipe' => 'pemasukan']);
        foreach ($pengeluaran as $n) KategoriKeuangan::create(['nama' => $n, 'tipe' => 'pengeluaran']);
    }
}
