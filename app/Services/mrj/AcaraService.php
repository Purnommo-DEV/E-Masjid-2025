<?php

namespace App\Services\mrj;

use App\Interfaces\AcaraServiceInterface;
use App\Models\Acara;
use Illuminate\Support\Str;

class AcaraService implements AcaraServiceInterface
{
    /**
     * Ambil upcoming acara (acara yang akan datang)
     */
    public function upcoming(int $limit = 6): array
    {
        $acaras = Acara::query()
            ->with('kategoris')
            ->where('is_published', true)
            ->whereDate('mulai', '>=', now()->toDateString())
            ->orderBy('mulai', 'asc')
            ->limit($limit)
            ->get();

        return $acaras->map(function ($acara) {
            $kategori = $acara->kategoris->pluck('nama')->first() ?? 'Acara';

            return [
                'id'            => $acara->id,
                'title'         => (string) $acara->judul,
                'kategori'      => (string) $kategori,
                'slug'          => $acara->slug,
                'tanggal_label' => $acara->tanggal_label,
                'waktu_teks'    => (string) ($acara->waktu_teks ?? ''),
                'waktu_label'   => $acara->waktu_label,
                'pemateri'      => (string) ($acara->pemateri ?? ''),
                'excerpt'       => $acara->excerpt,
                'lokasi'        => (string) ($acara->lokasi ?? '-'),
                'image'         => get_image_url($acara->image_path) ?? asset('storage/404.png'),
                'mulai'         => $acara->mulai,
                'selesai'       => $acara->selesai,
            ];
        })->toArray();
    }

    public function latestCompleted(int $limit = 3): array
    {
        $acaras = Acara::query()
            ->with('kategoris')
            ->where('is_published', true)
            ->where('mulai', '<', now())
            ->orderBy('mulai', 'desc')
            ->limit($limit)
            ->get();

        return $acaras->map(function ($acara) {
            $kategori = $acara->kategoris->pluck('nama')->first() ?? 'Acara';

            return [
                'title'         => (string) $acara->judul,
                'judul'         => (string) $acara->judul,
                'slug'          => $acara->slug,
                'kategori'      => (string) $kategori,
                'tanggal_label' => $acara->tanggal_label,
                'waktu_label'   => $acara->waktu_label,
                'lokasi'        => (string) ($acara->lokasi ?? '-'),
                'excerpt'       => $acara->excerpt,
                'url'           => route('acara.show', $acara->slug),
            ];
        })->toArray();
    }
    /**
     * Paginate acara (untuk halaman daftar acara)
     */
    public function paginate(int $perPage = 12)
    {
        $query = Acara::query()
            ->with('kategoris')
            ->where('is_published', true);

        // Filter by kategori jika ada
        if (request()->has('kategori')) {
            $kategoriSlug = request('kategori');
            $query->whereHas('kategoris', function ($q) use ($kategoriSlug) {
                $q->where('slug', $kategoriSlug);
            });
        }

        // Filter by search
        if (request()->has('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%")
                  ->orWhere('pemateri', 'like', "%{$search}%");
            });
        }

        // Sort
        if (request()->has('sort')) {
            $sort = request('sort');
            if ($sort === 'mulai_asc') {
                $query->orderBy('mulai', 'asc');
            } elseif ($sort === 'mulai_desc') {
                $query->orderBy('mulai', 'desc');
            } elseif ($sort === 'judul_asc') {
                $query->orderBy('judul', 'asc');
            } elseif ($sort === 'judul_desc') {
                $query->orderBy('judul', 'desc');
            }
        } else {
            $query->orderBy('mulai', 'desc'); // default: acara terdekat
        }

        $acaras = $query->paginate($perPage);

        // Transform data untuk view
        $acaras->getCollection()->transform(function ($acara) {
            $kategori = $acara->kategoris->pluck('nama')->first() ?? 'Acara';
            
            return (object) [
                'id'            => $acara->id,
                'judul'         => $acara->judul,
                'slug'          => $acara->slug,
                'excerpt'       => $acara->excerpt,
                'image_path'    => get_image_url($acara->image_path) ?? asset('storage/404.png'),
                'kategori'      => $kategori,
                'tanggal_label' => $acara->tanggal_label,
                'waktu_label'   => $acara->waktu_label,
                'lokasi'        => $acara->lokasi,
                'pemateri'      => $acara->pemateri,
                'mulai'         => $acara->mulai,
            ];
        });

        return $acaras;
    }

    /**
     * Get acara by slug (untuk halaman detail)
     */
    public function getBySlug(string $slug)
    {
        return Acara::with('kategoris')
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();
    }

    /**
     * Get related acara (acara serupa berdasarkan kategori)
     */
    public function getRelated(Acara $acara, int $limit = 3): array
    {
        $kategoriIds = $acara->kategoris->pluck('id')->toArray();

        $related = Acara::with('kategoris')
            ->where('is_published', true)
            ->where('id', '!=', $acara->id)
            ->where(function ($query) use ($kategoriIds) {
                if (!empty($kategoriIds)) {
                    $query->whereHas('kategoris', function ($q) use ($kategoriIds) {
                        $q->whereIn('kategori_id', $kategoriIds);
                    });
                }
            })
            ->orderBy('mulai', 'asc')
            ->limit($limit)
            ->get();

        return $related->map(function ($item) {
            $kategori = $item->kategoris->pluck('nama')->first() ?? 'Acara';
            
            return [
                'id'            => $item->id,
                'judul'         => $item->judul,
                'slug'          => $item->slug,
                'excerpt'       => $item->excerpt,
                'image_path'    => get_image_url($item->image_path) ?? asset('storage/404.png'),
                'kategori'      => $kategori,
                'tanggal_label' => $item->tanggal_label,
                'mulai'         => $item->mulai,
            ];
        })->toArray();
    }

    /**
     * Get upcoming acara for widget/sidebar
     */
    public function getUpcomingWidget(int $limit = 5): array
    {
        return $this->upcoming($limit);
    }

    /**
     * Count upcoming acara
     */
    public function countUpcoming(): int
    {
        return Acara::query()
            ->where('is_published', true)
            ->whereDate('mulai', '>=', now()->toDateString())
            ->count();
    }

    /**
     * Get archive by month/year
     */
    public function getArchive(string $month, string $year)
    {
        $startDate = "{$year}-{$month}-01";
        $endDate = date('Y-m-t', strtotime($startDate));

        return Acara::with('kategoris')
            ->where('is_published', true)
            ->whereBetween('mulai', [$startDate, $endDate])
            ->orderBy('mulai', 'asc')
            ->get()
            ->map(function ($acara) {
                return [
                    'id'            => $acara->id,
                    'judul'         => $acara->judul,
                    'slug'          => $acara->slug,
                    'image_path'    => get_image_url($acara->image_path) ?? asset('storage/404.png'),
                    'tanggal_label' => $acara->tanggal_label,
                    'mulai'         => $acara->mulai,
                ];
            });
    }
}