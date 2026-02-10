<?php

namespace App\Services\mrj;

use App\Interfaces\AcaraServiceInterface;
use App\Models\Acara;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AcaraService implements AcaraServiceInterface
{
    /**
     * Ambil upcoming acara, mapping ke array yang ramah blade.
     */
    public function upcoming(int $limit = 6): array
    {
        $acaras = Acara::query()
            ->with(['media', 'kategoris'])
            ->whereDate('mulai', '>=', now()->toDateString())
            ->orderBy('mulai')
            ->limit($limit)
            ->get();

        return $acaras->map(function ($a) {

            // ambil gambar dari media spatie → fallback default
            $media = $a->getFirstMedia('poster');
            $image = $media
                ? asset('storage/poster/'.$media->file_name)
                : asset('storage/404.jpg');

            // kategori: array → gunakan string pertama
            $kategori = $a->kategoris->pluck('nama')->first() ?? 'Acara';

            // excerpt aman (selalu string)
            $excerpt = Str::limit(strip_tags($a->deskripsi ?? ''), 90);

            return [
                'title'         => (string) $a->judul,
                'kategori'      => (string) $kategori,  // pastikan string
                'tanggal_label' => $a->mulai
                    ? $a->mulai->translatedFormat('l, d F')
                    : '-',
                'waktu_teks'         => (string) $a->waktu_teks,
                'waktu_label'   => $a->selesai
                    ? $a->mulai->format('H:i') . ' - ' . $a->selesai->format('H:i')
                    : ($a->mulai?->format('H:i') ?? '-'),
                'pemateri'      => (string) ($a->pemateri ?? ''),
                'excerpt'       => (string) $excerpt,
                'lokasi'        => (string) ($a->lokasi ?? '-'),
                'image'         => $image,
                'slug'          => $a->slug,
                // 'url'           => route('acara.show', $a->slug),
            ];
        })->toArray();
    }

    /**
     * Paginate acara (untuk halaman acara/index)
     */
    public function paginate(int $perPage = 12)
    {
        $query = Acara::query()
            ->with(['kategoris', 'media'])
            ->where('is_published', true);  // tambah kalau ada flag active

        // Contoh sort default + support dari query string (?sort=mulai_asc)
        if (request()->has('sort')) {
            $sort = request('sort');
            if ($sort === 'mulai_asc') {
                $query->orderBy('mulai', 'asc');
            } elseif ($sort === 'mulai_desc') {
                $query->orderBy('mulai', 'desc');
            }
        } else {
            $query->orderBy('mulai', 'desc'); // default terbaru dulu
        }

        return $query->paginate($perPage);
    }
}
