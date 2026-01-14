<?php
namespace App\Services\mrj;

use App\Interfaces\BeritaServiceInterface;
use App\Models\Berita;
use Illuminate\Support\Str;

class BeritaService implements BeritaServiceInterface
{
    public function latestForHome(int $limit = 4): array
    {
        $beritas = Berita::query()
            ->with(['kategoris', 'media'])
            ->latest()
            ->limit($limit)
            ->get();

        return $beritas->map(function ($b) {
            $media = $b->getFirstMedia('gambar') ?? $b->getFirstMedia('banner'); // fleksibel
            $imgUrl = $media
                ? asset('storage/' . ($media->custom_properties['folder'] ?? 'berita') . '/' . $media->file_name)
                : asset('storage/404.jpg');

            $excerpt = $b->excerpt
                ? (string) Str::limit(strip_tags($b->excerpt), 140)
                : (string) Str::limit(strip_tags($b->isi ?? ''), 140);

            // format tanggal / waktu aman
            $waktu = $b->published_at ? $b->published_at->diffForHumans() : ($b->created_at?->diffForHumans() ?? '-');

            return [
                'id'        => (int) $b->id,
                'judul'     => (string) $b->judul,
                'slug'      => (string) ($b->slug ?? ''),
                'ringkas'   => (string) $excerpt,
                'waktu'     => (string) $waktu,
                'gambar'    => $imgUrl,
                'kategoris' => $b->kategoris->map(fn($k)=>(string)$k->nama)->implode(', '), // string
                'url'       => route('berita.show', $b->slug ?? $b->id),
            ];
        })->toArray();
    }
}
