<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Interfaces\BeritaServiceInterface; // asumsi kamu sudah punya service untuk berita
use Illuminate\Http\Request;
use RalphJSmit\Laravel\SEO\Support\SEOData;

class ProgramRamadhanGuestController extends Controller
{
    protected $beritaService;

    public function __construct(BeritaServiceInterface $beritaService)
    {
        $this->beritaService = $beritaService;
    }

    /**
     * Halaman daftar semua program Ramadhan
     */
    public function index()
    {
        $perPage = (int) request()->input('per_page', 9);
        $beritas = $this->beritaService->paginateProgramRamadhan($perPage);

        $namaMasjid = profil('nama') ?? 'Masjid';

        $seoData = new SEOData(
            title: 'Program Ramadhan 1447 H | ' . $namaMasjid,
            description: 'Program Ramadhan 1447 H di ' . $namaMasjid . '. Mari ikuti Program Ramadhan 1447 H dan semarakkan bulan suci dengan ibadah dan kebaikan.',
            image: secure_asset('images/default-ramadhan.png'),
        );

        return view('masjid.' . masjid() . '.guest.program-ramadhan.index', compact('beritas'))
            ->with('seoData', $seoData);
    }

    public function show($slug)
    {
        $berita = $this->beritaService->findProgramBySlug($slug);

        $limit = (int) request()->input('related_limit', 3);
        $related = $this->beritaService->relatedPrograms((int) $berita->id, $limit);

        return view('masjid.' . masjid() . '.guest.program-ramadhan.show', compact('berita', 'related'));
    }
}