<?php

namespace App\Services\mrj;

use App\Interfaces\BeritaServiceInterface;
use App\Models\Berita;
use Illuminate\Support\Str;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

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
            ->latest('published_at')
            ->limit($limit)
            ->get();

        return $beritas->map(function ($berita) {
            // Ambil gambar cover atau gambar pertama
            $coverUrl = $berita->cover_url;
            $imgUrl = $coverUrl ?: asset('storage/404.png');

            $excerpt = $berita->excerpt
                ? (string) Str::limit(strip_tags($berita->excerpt), 140)
                : (string) Str::limit(strip_tags($berita->isi ?? ''), 140);

            $waktu = $berita->published_at
                ? $berita->published_at->translatedFormat('d F Y')
                : $berita->created_at?->translatedFormat('d F Y') ?? '-';

            return [
                'id'        => (int) $berita->id,
                'judul'     => (string) $berita->judul,
                'slug'      => (string) $berita->slug,
                'ringkas'   => (string) $excerpt,
                'waktu'     => (string) $waktu,
                'gambar'    => $imgUrl,
                'kategori'  => $berita->kategoris->pluck('nama')->first() ?? 'Berita',
                'url'       => route('berita.show', $berita->slug),
            ];
        })->toArray();
    }

    /**
     * Untuk halaman index berita (paginate full model)
     */
    public function paginate(int $perPage = 9): LengthAwarePaginator
    {
        $query = Berita::query()
            ->with(['kategoris', 'media'])
            ->where('is_published', true);

        // Sort default: terbaru berdasarkan published_at
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

        // Filter by kategori
        if (request()->has('kategori')) {
            $kategoriSlug = request('kategori');
            $query->whereHas('kategoris', function ($q) use ($kategoriSlug) {
                $q->where('slug', $kategoriSlug);
            });
        }

        // Search
        if (request()->has('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                  ->orWhere('isi', 'like', "%{$search}%");
            });
        }

        $beritas = $query->paginate($perPage);

        // Transform data untuk view (tambahkan URL gambar)
        $beritas->getCollection()->transform(function ($berita) {
            $coverUrl = $berita->cover_url;
            
            return (object) [
                'id'        => $berita->id,
                'judul'     => $berita->judul,
                'slug'      => $berita->slug,
                'ringkas'   => $berita->excerpt,
                'waktu'     => $berita->published_at?->translatedFormat('d M Y') ?? '-',
                'gambar'    => $coverUrl ?: asset('storage/404.png'),
                'kategori'  => $berita->kategoris->pluck('nama')->first() ?? 'Berita',
                'url'       => route('berita.show', $berita->slug),
            ];
        });

        return $beritas;
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

        return $beritas->map(function ($berita) {
            $coverUrl = $berita->cover_url;
            $excerpt = Str::limit(strip_tags($berita->isi ?? ''), 100);

            return [
                'id'       => (int) $berita->id,
                'judul'    => (string) $berita->judul,
                'slug'     => (string) $berita->slug,
                'ringkas'  => (string) $excerpt,
                'waktu'    => $berita->published_at?->translatedFormat('d M Y') ?? '-',
                'gambar'   => $coverUrl ?: asset('storage/404.png'),
                'kategori' => $berita->kategoris->pluck('nama')->first() ?? 'Berita',
                'url'      => route('berita.show', $berita->slug),
            ];
        })->toArray();
    }

    /**
     * Ambil daftar program Ramadhan (berita dengan kategori tertentu) - untuk halaman index
     */
    public function paginateProgramRamadhan(int $perPage = 9): LengthAwarePaginator
    {
        $beritas = Berita::with(['kategoris', 'media'])
            ->where('is_published', true)
            ->whereHas('kategoris', function ($query) {
                $query->where('slug', 'program-ramadhan-1447h'); // GANTI SESUAI SLUG KATEGORI KAMU
            })
            ->latest('published_at')
            ->paginate($perPage);

        // Transform data
        $beritas->getCollection()->transform(function ($berita) {
            $coverUrl = $berita->cover_url;
            
            return (object) [
                'id'        => $berita->id,
                'judul'     => $berita->judul,
                'slug'      => $berita->slug,
                'ringkas'   => $berita->excerpt,
                'waktu'     => $berita->published_at?->translatedFormat('d M Y') ?? '-',
                'gambar'    => $coverUrl ?: asset('storage/404.png'),
                'url'       => route('berita.show', $berita->slug),
            ];
        });

        return $beritas;
    }

    /**
     * Cari satu program berdasarkan slug (untuk halaman detail)
     */
    public function findProgramBySlug(string $slug): Berita
    {
        return Berita::with(['kategoris', 'media', 'author'])
            ->where('slug', $slug)
            ->where('is_published', true)
            ->whereHas('kategoris', function ($query) {
                $query->where('slug', 'program-ramadhan-1447h'); // GANTI SESUAI SLUG KATEGORI
            })
            ->firstOrFail();
    }

    /**
     * Ambil program terkait dalam kategori Ramadhan (untuk related di halaman detail)
     */
    public function relatedPrograms(int $excludeId, int $limit = 3): Collection
    {
        return Berita::with(['kategoris', 'media'])
            ->where('is_published', true)
            ->where('id', '!=', $excludeId)
            ->whereHas('kategoris', function ($query) {
                $query->where('slug', 'program-ramadhan-1447h'); // GANTI SESUAI SLUG KATEGORI
            })
            ->latest('published_at')
            ->limit($limit)
            ->get()
            ->map(function ($berita) {
                // Tambahkan URL gambar ke object
                $berita->gambar_url = $berita->cover_url;
                return $berita;
            });
    }

    /**
     * Ambil berita berdasarkan slug (untuk halaman detail)
     */
    public function findBySlug(string $slug): Berita
    {
        return Berita::with(['kategoris', 'media', 'author'])
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();
    }

    /**
     * Ambil semua berita untuk sitemap
     */
    public function getAllForSitemap(): Collection
    {
        return Berita::with(['kategoris', 'media'])
            ->where('is_published', true)
            ->latest('published_at')
            ->get(['id', 'slug', 'judul', 'published_at', 'updated_at']);
    }
}