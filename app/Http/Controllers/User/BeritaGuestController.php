<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Interfaces\BeritaServiceInterface;
use App\Models\Berita;

class BeritaGuestController extends Controller
{
    protected $beritaService;

    public function __construct(BeritaServiceInterface $beritaService)
    {
        $this->beritaService = $beritaService;
    }

    public function index()
    {
        $beritas = $this->beritaService->paginate(9); // 9 per halaman

        return view('masjid.' . masjid() . '.guest.berita.index', compact('beritas'));
    }

    public function show($slug)
    {
        $berita = Berita::where('slug', $slug)
                        ->where('is_published', true)
                        ->with(['kategoris', 'media'])
                        ->firstOrFail();
        
        // Related: 3 berita terbaru lainnya (exclude yang sedang dibuka)
        $related = $this->beritaService->related(3, $berita->id);

        return view('masjid.' . masjid() . '.guest.berita.show', compact('berita', 'related'));
    }
}