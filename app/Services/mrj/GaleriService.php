<?php

namespace App\Services\mrj;

use App\Interfaces\GaleriServiceInterface;
use App\Models\Galeri;

class GaleriService implements GaleriServiceInterface
{
    public function latestFotos(int $limit = 12): array
    {
        return Galeri::with('media')
            ->where('tipe', 'foto')
            ->where('is_published', true)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($galeri) {
                // Gunakan accessor thumbnail_url dari model
                $thumbnailUrl = $galeri->thumbnail_url;
                
                return [
                    'id'    => $galeri->id,
                    'judul' => $galeri->judul,
                    'img'   => $thumbnailUrl ?: asset('storage/404.png'),
                ];
            })
            ->toArray();
    }

    public function getByKategori(string $slug, int $perPage = 12)
    {
        return Galeri::with('media', 'kategoris')
            ->where('is_published', true)
            ->whereHas('kategoris', function ($q) use ($slug) {
                $q->where('slug', $slug);
            })
            ->latest()
            ->paginate($perPage);
    }

    public function findById($id)
    {
        return Galeri::with('media', 'kategoris')
            ->where('is_published', true)
            ->findOrFail($id);
    }
}