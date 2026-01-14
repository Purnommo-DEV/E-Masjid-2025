<?php
namespace App\Services\mrj;

use App\Interfaces\PengumumanServiceInterface;
use App\Models\Pengumuman;
use Illuminate\Support\Str;

class PengumumanService implements PengumumanServiceInterface
{
    public function latestForHome(int $limit = 4): array
    {
        $items = Pengumuman::query()
            ->latest()
            ->limit($limit)
            ->get();

        return $items->map(function ($p) {
            $tanggal = $p->created_at?->translatedFormat('d M Y') ?? '-';
            $isi = $p->isi ? (string) Str::limit(strip_tags($p->isi), 120) : '-';

            return [
                'id'      => (int) $p->id,
                'judul'   => (string) $p->judul,
                'isi'     => (string) $isi,
                'tanggal' => (string) $tanggal,
                'url'     => route('pengumuman.show', $p->id),
            ];
        })->toArray();
    }
}
