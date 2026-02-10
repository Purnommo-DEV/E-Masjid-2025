<?php

namespace App\Services\mrj;

use App\Interfaces\BeritaServiceInterface;
use App\Models\Berita;
use Illuminate\Support\Str;

class BeritaService implements BeritaServiceInterface
{
    /**
     * Untuk widget homepage (latest berita, mapping array ramah Blade)
     */
    public function latestForHome(int $limit = 4): array
    {
        $beritas = Berita::query()
            ->with(['kategoris', 'media'])
            ->where('is_published', true)
            ->latest('published_at') // atau created_at kalau tidak pakai published_at
            ->limit($limit)
            ->get();

        return $beritas->map(function ($b) {
            $media = $b->getFirstMedia('gambar') ?? $b->getFirstMedia('banner');
            $imgUrl = $media
                ? asset('storage/' . ($media->custom_properties['folder'] ?? 'berita') . '/' . $media->file_name)
                : asset('storage/404.jpg');

            $excerpt = $b->excerpt
                ? (string) Str::limit(strip_tags($b->excerpt), 140)
                : (string) Str::limit(strip_tags($b->isi ?? ''), 140);

            $waktu = $b->published_at
                ? $b->published_at->translatedFormat('d F Y')
                : $b->created_at?->translatedFormat('d F Y') ?? '-';

            return [
                'id'        => (int) $b->id,
                'judul'     => (string) $b->judul,
                'slug'      => (string) $b->slug,
                'ringkas'   => (string) $excerpt,
                'waktu'     => (string) $waktu,
                'gambar'    => $imgUrl,
                'kategori'  => $b->kategoris->pluck('nama')->first() ?? 'Berita',
                'url'       => route('berita.show', $b->slug),
            ];
        })->toArray();
    }

    /**
     * Untuk halaman index berita (paginate full model)
     */
    public function paginate(int $perPage = 9)
    {
        $query = Berita::query()
            ->with(['kategoris', 'media'])
            ->where('is_published', true);

        // Sort default: terbaru berdasarkan published_at atau created_at
        $query->orderByDesc('published_at');

        // Optional: support filter/sort dari query string
        if (request()->has('sort')) {
            $sort = request('sort');
            if ($sort === 'terbaru') {
                $query->orderByDesc('published_at');
            } elseif ($sort === 'terlama') {
                $query->orderBy('published_at');
            }
        }

        return $query->paginate($perPage);
    }

    /**
     * Untuk related berita di halaman detail (ambil 3 berita terbaru, exclude yang sedang dibuka)
     */
    public function related(int $limit = 3, ?int $excludeId = null): array
    {
        $query = Berita::query()
            ->with(['kategoris', 'media'])
            ->where('is_published', true)
            ->orderByDesc('published_at');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $beritas = $query->limit($limit)->get();

        return $beritas->map(function ($b) {
            $media = $b->getFirstMedia('gambar') ?? $b->getFirstMedia('banner');
            $imgUrl = $media
                ? asset('storage/' . ($media->custom_properties['folder'] ?? 'berita') . '/' . $media->file_name)
                : asset('storage/404.jpg');

            $excerpt = Str::limit(strip_tags($b->isi ?? ''), 100);

            return [
                'id'       => (int) $b->id,
                'judul'    => (string) $b->judul,
                'slug'     => (string) $b->slug,
                'ringkas'  => (string) $excerpt,
                'waktu'    => $b->published_at?->translatedFormat('d M Y') ?? '-',
                'gambar'   => $imgUrl,
                'kategori' => $b->kategoris->pluck('nama')->first() ?? 'Berita',
                'url'      => route('berita.show', $b->slug),
            ];
        })->toArray();
    }
}