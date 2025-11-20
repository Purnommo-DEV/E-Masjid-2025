<?php

namespace App\Repositories\mrj;

use App\Interfaces\MasjidRepositoryInterface;

class MasjidRepository implements MasjidRepositoryInterface
{
    public function getHomeData(): array
    {
        return [
            'title' => 'Masjid Merah Jambu',
            'message' => 'Tema pink dan hangat untuk jamaah kami.',
            'theme' => 'pink',
            'background' => 'linear-gradient(45deg, #ff69b4, #ffc0cb)'
        ];
    }

    public function getBerita(int $limit = 5): array
    {
        return \App\Models\Berita::where('masjid_id', request()->masjid->id)
            ->latest()
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getJadwalSholat(): array
    {
        return [
            ['nama' => 'Subuh', 'waktu' => '04:25'],
            ['nama' => 'Dzuhur', 'waktu' => '11:55'],
            ['nama' => 'Ashar', 'waktu' => '15:20'],
            ['nama' => 'Maghrib', 'waktu' => '17:58'],
            ['nama' => 'Isya', 'waktu' => '19:25'],
        ];
    }
}