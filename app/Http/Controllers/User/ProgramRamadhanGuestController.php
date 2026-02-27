<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Interfaces\BeritaServiceInterface; // asumsi kamu sudah punya service untuk berita
use Illuminate\Http\Request;

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
        $perPage = (int) request()->input('per_page', 9); // ambil dari query string kalau ada, default 9
        $beritas = $this->beritaService->paginateProgramRamadhan($perPage);

        return view('masjid.' . masjid() . '.guest.program-ramadhan.index', compact('beritas'));
    }

    /**
     * Halaman detail satu program
     */
    public function show($slug)
    {
        $berita = $this->beritaService->findProgramBySlug($slug);

        $limit = (int) request()->input('related_limit', 3); // default 3, bisa diubah via URL ?related_limit=5

        $related = $this->beritaService->relatedPrograms((int) $berita->id, $limit);

        return view('masjid.' . masjid() . '.guest.program-ramadhan.show', compact('berita', 'related'));
    }
}