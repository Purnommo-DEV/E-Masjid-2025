<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Kategori;

class KategoriBeritaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Kategori::create(['nama' => 'Pengumuman', 'slug' => 'pengumuman']);
        Kategori::create(['nama' => 'Kajian', 'slug' => 'kajian']);
        Kategori::create(['nama' => 'Kegiatan', 'slug' => 'kegiatan']);
    }
}
