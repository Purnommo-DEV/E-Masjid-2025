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
use App\Models\PesanJamaah;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use RalphJSmit\Laravel\SEO\Support\SEOData;

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

        $banner    = $this->bannerService->sliderPages(3);
        $acaras    = $this->acaraService->upcoming(6);
        $acaraSelesai = $this->acaraService->latestCompleted(3);
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
            ->limit(20)
            ->get();

        $seoData = new SEOData(
            title: 'Masjid Raudhotul Jannah Taman Cipulir Estate',
            description: 'Website resmi Masjid Raudhotul Jannah Taman Cipulir Estate. Informasi kajian, agenda kegiatan, berita jamaah, serta program Ramadhan dan pelayanan umat.',
            image: secure_asset('images/default-share.jpg'),
        );

        return view('masjid.' . masjid() . '.guest.index', compact(
            'profil',
            'banner',
            'acaras',
            'beritas',
            'acaraSelesai',
            'pengumuman',
            'layanans',
            'galeri',
            'jadwalSholat',
            'sliders',
            'quoteHarianList'
        ))->with('seoData', $seoData);
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
        $validator = Validator::make($request->all(), [
            'nama'     => 'required|string|max:191',
            'telepon'  => 'nullable|string|max:32',
            'pesan'    => 'required|string',
            'g-recaptcha-response' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ada kesalahan validasi.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $recaptchaResponse = $request->input('g-recaptcha-response');
        $secret = env('RECAPTCHA_SECRET_KEY');

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret'   => $secret,
            'response' => $recaptchaResponse,
            'remoteip' => $request->ip(),
        ]);

        $recaptchaData = $response->json();

        if (!$recaptchaData['success'] || $recaptchaData['score'] < 0.5) {
            return response()->json([
                'success' => false,
                'message' => 'Verifikasi reCAPTCHA gagal. Kemungkinan terdeteksi sebagai spam.'
            ], 422);
        }

        $pesan = PesanJamaah::create([
            'nama'     => $request->nama,
            'telepon'  => $request->telepon ?? null,
            'pesan'    => $request->pesan,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Terima kasih — pesan Anda berhasil dikirim.',
            'id'      => $pesan->id,
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
                // Gunakan accessor thumbnail_url dari model
                $thumbnailUrl = $g->thumbnail_url;
                
                return [
                    'id' => $g->id,
                    'judul' => $g->judul,
                    'img' => $thumbnailUrl,
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
        $galeri = Galeri::where('is_published', 1)->findOrFail($id);

        // Gunakan accessor dari model
        $fotos = $galeri->fotos;

        return response()->json([
            'fotos' => $fotos
        ]);
    }

    public function setLocation(Request $request)
    {
        $request->validate([
            'lat'  => 'required|numeric|between:-90,90',
            'lng'  => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0|max:10000',
        ]);

        $lat = $request->lat;
        $lng = $request->lng;

        $url = "https://nominatim.openstreetmap.org/reverse?format=json"
             . "&lat={$lat}&lon={$lng}"
             . "&zoom=10&addressdetails=1";

        try {
            $response = Http::withHeaders([
                'User-Agent'      => 'MasjidRaudhotulJannah/1.0 (contact: your@email.com)',
                'Referer'         => url('/'),
                'Accept-Language' => 'id,en',
            ])->timeout(8)->get($url);

            if (!$response->successful()) {
                \Log::warning("Nominatim gagal - HTTP {$response->status()} | Lat/Lng: {$lat},{$lng}");
                return response()->json(['success' => false, 'message' => 'Gagal mendapatkan lokasi']);
            }

            $data = $response->json();
            $address = $data['address'] ?? [];

            $city = $address['city'] 
                 ?? $address['town'] 
                 ?? $address['village'] 
                 ?? $address['county'] 
                 ?? $address['state_district'] 
                 ?? $address['municipality'] 
                 ?? $address['state'] 
                 ?? null;

            if (!$city) {
                \Log::warning("Nominatim tidak menemukan kota | Response: " . json_encode($data));
                return response()->json(['success' => false, 'message' => 'Lokasi tidak dikenali']);
            }

            $city = strtolower(trim($city));

            session([
                'user_city' => $city,
                'user_lat'  => $lat,
                'user_lng'  => $lng,
            ]);

            return response()->json([
                'success' => true,
                'city'    => ucwords($city),
            ]);
        } catch (\Throwable $e) {
            \Log::error("Exception reverse geocode: " . $e->getMessage() . " | Lat/Lng: {$lat},{$lng}");
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan server']);
        }
    }
}