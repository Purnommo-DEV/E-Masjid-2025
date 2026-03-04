<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Interfaces\BeritaServiceInterface;
use App\Models\Berita;
use RalphJSmit\Laravel\SEO\Support\SEOData;

class BeritaGuestController extends Controller
{
    protected $beritaService;

    public function __construct(BeritaServiceInterface $beritaService)
    {
        $this->beritaService = $beritaService;
    }

    public function index()
    {
        $beritas = $this->beritaService->paginate(9);

        // SEO khusus untuk halaman list berita
        $seoData = new SEOData(
            title: 'Berita Terkini',
            description: 'Kumpulan berita kegiatan, kajian, pengumuman dan informasi terbaru Masjid Raudhotul Jannah Taman Cipulir Estate.',
            image: secure_asset('images/default-share.jpg'),
        );

        return view('masjid.' . masjid() . '.guest.berita.index', compact('beritas'))
            ->with('seoData', $seoData);
    }

    public function show($slug)
    {
        $berita = Berita::where('slug', $slug)
            ->where('is_published', true)
            ->with(['kategoris', 'media'])
            ->firstOrFail();

        $related = $this->beritaService->related(3, $berita->id);

        // Tidak perlu pass seoData manual → model Berita sudah handle via trait HasSEO
        return view('masjid.' . masjid() . '.guest.berita.show', compact('berita', 'related'));
    }
}