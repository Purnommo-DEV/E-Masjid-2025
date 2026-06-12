<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PengumumanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('pengumumans')->insert([
    [
        'masjid_code' => 'mrj',
        'judul' => 'Kajian Rutin Masjid',
        'isi' => 'Mari hadir dalam kajian rutin untuk menambah ilmu dan keimanan.',
        'tipe' => 'notif',
        'is_active' => true,
    ],
    [
        'masjid_code' => 'mrj',
        'judul' => 'Shalat Berjamaah',
        'isi' => 'Mari memakmurkan masjid dengan menjaga shalat berjamaah tepat waktu.',
        'tipe' => 'notif',
        'is_active' => true,
    ],
    [
        'masjid_code' => 'mrj',
        'judul' => 'Infaq dan Sedekah',
        'isi' => 'Salurkan infaq dan sedekah terbaik Anda untuk mendukung kegiatan masjid.',
        'tipe' => 'notif',
        'is_active' => true,
    ],
    [
        'masjid_code' => 'mrj',
        'judul' => 'TPA dan Pendidikan',
        'isi' => 'Dukung kegiatan pendidikan Al-Qur’an bagi generasi muda.',
        'tipe' => 'notif',
        'is_active' => true,
    ],
    [
        'masjid_code' => 'mrj',
        'judul' => 'Jumat Berkah',
        'isi' => 'Mari berbagi kebaikan melalui program sosial dan Jumat Berkah.',
        'tipe' => 'notif',
        'is_active' => true,
    ],
        ]);
    }
}