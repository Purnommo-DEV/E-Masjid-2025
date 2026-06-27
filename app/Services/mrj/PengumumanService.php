<?php
namespace App\Services\mrj;

use App\Interfaces\PengumumanServiceInterface;
use App\Models\Pengumuman;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PengumumanService implements PengumumanServiceInterface
{
    public function latestForHome(int $limit = 4): array
    {
        $items = Pengumuman::query()
            ->latest()
            ->limit($limit)
            ->get();

        $hasSlug = Schema::hasColumn('pengumumans', 'slug');

        return $items->map(function ($p) use ($hasSlug) {
            $tanggal = $p->created_at?->translatedFormat('d M Y') ?? '-';
            $isi = $p->isi ? (string) Str::limit(strip_tags($p->isi), 120) : '-';
            $identifier = $hasSlug && $p->slug ? $p->slug : $p->id;

            return [
                'id'      => (int) $p->id,
                'judul'   => (string) $p->judul,
                'isi'     => (string) $isi,
                'tanggal' => (string) $tanggal,
                'url'     => route('pengumuman.show', $identifier),
            ];
        })->toArray();
    }
}
