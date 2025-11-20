<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Masjid;
use App\Models\User;

class MasjidSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $mrj = Masjid::create([
            'nama' => 'Masjid Merah Jambu',
            'slug' => 'mrj',
            'alamat' => 'Jl. Merah Jambu No. 1',
            'config' => json_encode(['qris' => 'https://example.com/qris-mrj.png'])
        ]);

        $muh = Masjid::create([
            'nama' => 'Masjid Muhammadiyah',
            'slug' => 'muh',
            'alamat' => 'Jl. Muhammadiyah No. 10'
        ]);
    }
}
