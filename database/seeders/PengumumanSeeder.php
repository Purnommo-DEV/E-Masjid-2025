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
                'judul' => 'Kajian Rutin',
                'isi' => 'Mari hadir dan menuntut ilmu bersama.',
                'tipe' => 'notif',
                'is_active' => true,
            ],
            [
                'masjid_code' => 'mrj',
                'judul' => 'Shalat Berjamaah',
                'isi' => 'Jaga shalat berjamaah tepat waktu di masjid.',
                'tipe' => 'notif',
                'is_active' => true,
            ],
            [
                'masjid_code' => 'mrj',
                'judul' => 'Infaq & Sedekah',
                'isi' => 'Salurkan infaq terbaik untuk kemakmuran masjid.',
                'tipe' => 'notif',
                'is_active' => true,
            ],
            [
                'masjid_code' => 'mrj',
                'judul' => 'TPA Al-Qur’an',
                'isi' => 'Pendaftaran santri baru terbuka.',
                'tipe' => 'notif',
                'is_active' => true,
            ],
            [
                'masjid_code' => 'mrj',
                'judul' => 'Jaga Kebersihan',
                'isi' => 'Mari menjaga kebersihan dan kenyamanan masjid.',
                'tipe' => 'notif',
                'is_active' => true,
            ],
            [
                'masjid_code' => 'mrj',
                'judul' => 'Program Masjid',
                'isi' => 'Ikuti berbagai kegiatan yang tersedia.',
                'tipe' => 'notif',
                'is_active' => true,
            ],
        ]);
    }
}
