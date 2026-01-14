<?php

namespace App\Services\mrj;

use App\Interfaces\GaleriServiceInterface;
use App\Models\Galeri;

class GaleriService implements GaleriServiceInterface
{
    /**
     * Ambil foto-foto terbaru untuk Home (thumbnail grid)
     */
    public function latestFotos(int $limit = 12): array
    {
        return Galeri::with('media')
            ->where('tipe', 'foto')
            ->latest()
            ->take($limit)
            ->get()
            ->map(function ($g) {
                $media = $g->getFirstMedia('foto');

                $img = $media
                    ? asset('storage/' . ($media->custom_properties['folder'] ?? 'galeri/default') . '/' . $media->file_name)
                    : asset('storage/404.jpg');

                return [
                    'id'    => $g->id,
                    'judul' => $g->judul,
                    'img'   => $img,
                ];
            })
            ->toArray();
    }
}
