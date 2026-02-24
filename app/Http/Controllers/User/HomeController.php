<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Interfaces\BannerServiceInterface;
use App\Interfaces\AcaraServiceInterface;
use App\Interfaces\BeritaServiceInterface;
use App\Interfaces\PengumumanServiceInterface;
use App\Interfaces\GaleriServiceInterface;
use App\Services\JadwalSholatService;
use App\Interfaces\SlideMotivasiRepositoryInterface;
use App\Interfaces\QuoteHarianRepositoryInterface;
use App\Interfaces\KhutbahJumatRepositoryInterface;
use App\Models\ProfilMasjid;
use App\Models\Acara;
use App\Models\Berita;
use App\Models\Pengumuman;
use App\Models\Layanan;
use App\Models\Galeri;
use Carbon\Carbon;

class HomeController extends Controller
{
    protected $acaraService;
    protected $bannerService;
    protected $beritaService;
    protected $pengumumanService;
    protected $galeriService;
    protected $jadwalSholatService;
    protected $slideMotivasiRepo;
    protected $quoteHarianRepo;
    protected $khutbahJumatRepo;

    public function __construct(
        AcaraServiceInterface $acaraService,
        BannerServiceInterface $bannerService,
        BeritaServiceInterface $beritaService,
        PengumumanServiceInterface $pengumumanService,
        GaleriServiceInterface $galeriService,
        JadwalSholatService $jadwalSholatService,
        SlideMotivasiRepositoryInterface $slideMotivasiRepo,
        QuoteHarianRepositoryInterface $quoteHarianRepo,
        KhutbahJumatRepositoryInterface $khutbahJumatRepo
    ) {
        $this->acaraService = $acaraService;
        $this->bannerService = $bannerService;
        $this->beritaService = $beritaService;
        $this->pengumumanService = $pengumumanService;
        $this->galeriService = $galeriService;
        $this->jadwalSholatService = $jadwalSholatService;
        $this->slideMotivasiRepo = $slideMotivasiRepo;
        $this->quoteHarianRepo = $quoteHarianRepo;
        $this->khutbahJumatRepo = $khutbahJumatRepo;
    }

    public function index()
    {
        Carbon::setLocale('id');
        app()->setLocale('id');

        $profil = ProfilMasjid::first();

        // 🔹 ambil pages banner via service
        $banner    = $this->bannerService->sliderPages(3);
        $acaras    = $this->acaraService->upcoming(6);
        $beritas   = $this->beritaService->latestForHome(4);
        $pengumuman = $this->pengumumanService->latestForHome(4);

        $layanans = Layanan::where('is_active', true)
            ->orderBy('urutan')
            ->get();

        $galeri = $this->galeriService->latestFotos(12);

        $jadwalSholat = $this->jadwalSholatService->getJadwalHariIni();

        $sliders = $this->slideMotivasiRepo->ordered();
        $quoteHarianList = $this->quoteHarianRepo->all()
            ->where('is_active', true)
            ->inRandomOrder()
            ->limit(20)  // optional, supaya tidak load 100 semua
            ->get();
        // $khutbahJumat = $this->khutbahJumatRepo->comingSoon();
        return view('masjid.'.masjid().'.guest.index', compact(
            'profil',
            'banner',
            'acaras',
            'beritas',
            'pengumuman',
            'layanans',
            'galeri',
            'jadwalSholat',
            'sliders',
            'quoteHarianList'
        ));
    }

    public function acaraIndex(){}
    public function acaraShow(){}

    public function beritaIndex(){}
    public function beritaShow(){}

    public function pengumumanIndex(){}
    public function pengumumanShow(){}

    public function galeriIndex(){}

    public function kirimPesan(Request $request)
    {
        $validated = $request->validate([
            'nama'    => 'required|string|max:191',
            'telepon' => 'nullable|string|max:32',
            'pesan'   => 'required|string',
        ]);

        // simpan
        $pesan = PesanJamaah::create([
            'nama'    => $validated['nama'],
            'telepon' => $validated['telepon'] ?? null,
            'pesan'   => $validated['pesan'],
        ]);

        return response()->json([
            'message' => 'Terima kasih — pesan Anda berhasil dikirim.',
            'id' => $pesan->id,
        ], 201);
    }

    public function galeriPublic()
    {
        $galeri = Galeri::where('is_published', 1)
            ->whereHas('kategoris', function($q){
                $q->where('nama', 'Ramadhan 1447H');
            })
            ->latest()
            ->get()
            ->map(function($g){

                $media = $g->getFirstMedia('foto');

                return [
                    'id' => $g->id,
                    'judul' => $g->judul,
                    'img' => $media
                        ? asset('storage/'.$media->custom_properties['folder'].'/'.$media->file_name)
                        : null,
                ];
            })
            ->filter(fn($g) => $g['img'] !== null)
            ->values();

        return response()->json([
            'data' => $galeri
        ]);
    }

    public function galeriDetail($id)
    {
        // ambil 1 album galeri
        $galeri = Galeri::where('is_published', 1)->findOrFail($id);

        // ambil HANYA foto milik album itu
        $fotos = $galeri->getMedia('foto')->map(function ($media) {
            return [
                'url' => asset('storage/' . $media->custom_properties['folder'] . '/' . $media->file_name),
                'caption' => $media->name ?? '',
            ];
        });

        return response()->json([
            'fotos' => $fotos
        ]);
    }
}
