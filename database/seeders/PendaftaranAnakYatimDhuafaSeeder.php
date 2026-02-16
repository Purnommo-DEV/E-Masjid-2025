<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PendaftaranAnakYatimDhuafa; // sesuaikan namespace model kamu
use Illuminate\Support\Str;
use Carbon\Carbon;

class PendaftaranAnakYatimDhuafaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sumberInformasiList = [
            'Ustadz Ahmad Soleh',
            'Ibu Siti Rahmah (Ketua TPQ)',
            'Pak Budi Santoso (RT 03)',
            'Yayasan Al-Hikmah',
            'Masjid Al-Falah',
            'Tetangga Dekat (Bu Rina)',
            'Dinas Sosial Kota',
            'Relawan Santunan Ramadhan',
            'Ibu Guru SD 12',
            'Pak Haji Ismail',
        ];

        $pekerjaanOrtu = [
            'Buruh Harian Lepas',
            'Ibu Rumah Tangga',
            'Pedagang Kaki Lima',
            'Supir Angkot',
            'Petani',
            'Tukang Ojek',
            'PNS',
            'Wiraswasta',
            'Tidak Bekerja',
            'Karyawan Swasta',
        ];

        $tahunProgram = [2024, 2025, 2026];

        // Buat 80 data dummy (bisa diubah jumlahnya)
        for ($i = 1; $i <= 80; $i++) {
            $umurValue = rand(1, 13);
            $umurSatuan = $umurValue >= 5 ? 'tahun' : (rand(0,1) ? 'tahun' : 'bulan');

            // Tanggal lahir random (umur sesuai)
            $umurInMonths = $umurSatuan === 'tahun' ? $umurValue * 12 : $umurValue;
            $tanggalLahir = Carbon::now()->subMonths($umurInMonths + rand(-6, 6))->subDays(rand(0, 30));

            // Random kategori
            $kategori = rand(0, 9) < 6 ? 'yatim_dhuafa' : 'dhuafa'; // lebih banyak yatim dhuafa

            PendaftaranAnakYatimDhuafa::create([
                'tahun_program'     => $tahunProgram[array_rand($tahunProgram)],
                'kategori'          => $kategori,
                'nama_lengkap'      => fake()->name(),
                'nama_panggilan'    => fake()->firstName(),
                'jenis_kelamin'     => rand(0,1) ? 'L' : 'P',
                'tanggal_lahir'     => $tanggalLahir,
                'umur'              => $umurValue,
                'umur_satuan'       => $umurSatuan,
                'nama_orang_tua'    => fake()->name(),
                'pekerjaan_orang_tua' => $pekerjaanOrtu[array_rand($pekerjaanOrtu)],
                'alamat'            => fake()->address(),
                'no_wa'             => '08' . rand(100000000, 9999999999),
                'sumber_informasi'  => $sumberInformasiList[array_rand($sumberInformasiList)],
                'catatan_tambahan'  => rand(0, 5) ? fake()->sentence(rand(5, 15)) : null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }

        $this->command->info('Berhasil menambahkan ' . $i . ' data dummy anak yatim & dhuafa!');
    }
}