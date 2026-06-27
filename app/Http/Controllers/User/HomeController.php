<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Interfaces\AcaraServiceInterface;
use App\Interfaces\BannerServiceInterface;
use App\Interfaces\BeritaServiceInterface;
use App\Interfaces\GaleriServiceInterface;
use App\Interfaces\KhutbahJumatRepositoryInterface;
use App\Interfaces\PengumumanServiceInterface;
use App\Interfaces\QuoteHarianRepositoryInterface;
use App\Interfaces\SlideMotivasiRepositoryInterface;
use App\Models\Galeri;
use App\Models\KhutbahJumat;
use App\Models\Layanan;
use App\Models\PesanJamaah;
use App\Models\ProfilMasjid;
use App\Models\Pengumuman;
use App\Services\JadwalSholatService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
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

        $banner = $this->bannerService->sliderPages(3);
        $acaras = $this->acaraService->upcoming(6);
        $acaraSelesai = $this->acaraService->latestCompleted(3);
        $beritas = $this->beritaService->latestForHome(4);
        $pengumuman = $this->pengumumanService->latestForHome(4);

        $layanans = Layanan::where('is_active', true)
            ->orderBy('urutan')
            ->get();

        // Get Friday sermon data
        $jumat = $this->getJadwalJumat();

        // Format data for view
        $jumatData = null;
        if ($jumat) {
            $jumatData = [
                'khatib' => $jumat->khatib,
                'tgl' => $jumat->tanggal,
                'jam' => $jumat->jam,
                'tema' => $jumat->tema,
                'keterangan' => $jumat->keterangan,
            ];
        }

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

        return view('masjid.'.masjid().'.guest.index', compact(
            'profil',
            'banner',
            'acaras',
            'beritas',
            'acaraSelesai',
            'pengumuman',
            'layanans',
            'galeri',
            'jadwalSholat',
            'jumatData',
            'sliders',
            'quoteHarianList'
        ))->with('seoData', $seoData);
    }

    private function getJadwalJumat()
    {
        $now = Carbon::now('Asia/Jakarta');
        $today = $now->toDateString();
        $currentDay = $now->dayOfWeek;

        // Buat cache key berdasarkan hari ini
        $cacheKey = 'jadwal_jumat_'.$today;

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($today, $currentDay) {
            // Jika hari ini Jumat
            if ($currentDay == 5) {
                // Cek jadwal hari ini
                $jadwal = KhutbahJumat::where('tanggal_asli', $today)
                    ->where('is_active', true)
                    ->first();

                if ($jadwal) {
                    return $jadwal;
                }
            }

            // Cari jadwal berikutnya
            $jadwal = KhutbahJumat::where('tanggal_asli', '>=', $today)
                ->where('is_active', true)
                ->orderBy('tanggal_asli', 'asc')
                ->first();

            // Jika tidak ada, ambil jadwal terakhir
            if (! $jadwal) {
                $jadwal = KhutbahJumat::where('is_active', true)
                    ->orderBy('tanggal_asli', 'desc')
                    ->first();
            }

            return $jadwal;
        });
    }

    public function acaraIndex() {}

    public function acaraShow() {}

    public function beritaIndex() {}

    public function beritaShow() {}

    public function pengumumanIndex()
    {
        Carbon::setLocale('id');
        app()->setLocale('id');

        $pengumumans = Pengumuman::query()
            ->active()
            ->latest()
            ->paginate(12);

        $seoData = new SEOData(
            title: 'Pengumuman Masjid | ' . masjid_name(),
            description: 'Pengumuman resmi, informasi penting, dan kabar terbaru dari ' . masjid_name() . '.',
            image: secure_asset('images/default-share.jpg'),
            url: route('pengumuman.index'),
            canonical_url: route('pengumuman.index'),
        );

        return view('masjid.'.masjid().'.guest.pengumuman.index', compact('pengumumans'))
            ->with('seoData', $seoData);
    }

    public function pengumumanShow($slug)
    {
        Carbon::setLocale('id');
        app()->setLocale('id');

        $hasSlug = Schema::hasColumn('pengumumans', 'slug');

        $pengumuman = Pengumuman::query()
            ->active()
            ->when($hasSlug, function ($query) use ($slug) {
                $query->where(function ($q) use ($slug) {
                    $q->where('slug', $slug);

                    if (ctype_digit((string) $slug)) {
                        $q->orWhere('id', $slug);
                    }
                });
            }, fn ($query) => $query->whereKey($slug))
            ->firstOrFail();

        $related = Pengumuman::query()
            ->active()
            ->whereKeyNot($pengumuman->id)
            ->latest()
            ->limit(4)
            ->get();

        $identifier = $hasSlug && $pengumuman->slug ? $pengumuman->slug : $pengumuman->id;
        $detailUrl = route('pengumuman.show', $identifier);
        $description = Str::limit(strip_tags($pengumuman->isi ?? ''), 155);

        $seoData = new SEOData(
            title: $pengumuman->judul . ' | ' . masjid_name(),
            description: $description,
            author: 'Tim Masjid',
            image: secure_asset('images/default-share.jpg'),
            url: $detailUrl,
            published_time: $pengumuman->created_at,
            modified_time: $pengumuman->updated_at,
            type: 'article',
            canonical_url: $detailUrl,
        );

        return view('masjid.'.masjid().'.guest.pengumuman.show', compact('pengumuman', 'related'))
            ->with('seoData', $seoData);
    }

    public function galeriIndex()
    {
        Carbon::setLocale('id');
        app()->setLocale('id');

        $galeris = Galeri::query()
            ->with(['media', 'kategoris'])
            ->published()
            ->latest('published_at')
            ->paginate(12);

        $seoData = new SEOData(
            title: 'Galeri Kegiatan | ' . masjid_name(),
            description: 'Dokumentasi foto dan video kegiatan, kajian, program sosial, Ramadhan, dan qurban di ' . masjid_name() . '.',
            image: secure_asset('images/default-share.jpg'),
            url: route('galeri.index'),
            canonical_url: route('galeri.index'),
        );

        return view('masjid.'.masjid().'.guest.galeri.index', compact('galeris'))
            ->with('seoData', $seoData);
    }

    public function kirimPesan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:191',
            'telepon' => 'nullable|string|max:32',
            'pesan' => 'required|string',
            'g-recaptcha-response' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ada kesalahan validasi.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $recaptchaResponse = $request->input('g-recaptcha-response');
        $secret = env('RECAPTCHA_SECRET_KEY');

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $secret,
            'response' => $recaptchaResponse,
            'remoteip' => $request->ip(),
        ]);

        $recaptchaData = $response->json();

        if (! $recaptchaData['success'] || $recaptchaData['score'] < 0.5) {
            return response()->json([
                'success' => false,
                'message' => 'Verifikasi reCAPTCHA gagal. Kemungkinan terdeteksi sebagai spam.',
            ], 422);
        }

        $pesan = PesanJamaah::create([
            'nama' => $request->nama,
            'telepon' => $request->telepon ?? null,
            'pesan' => $request->pesan,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Terima kasih — pesan Anda berhasil dikirim.',
            'id' => $pesan->id,
        ], 201);
    }

    public function galeriPublic()
    {
        $galeri = Galeri::where('is_published', 1)
            ->whereHas('kategoris', function ($q) {
                $q->where('nama', 'Ramadhan 1447H');
            })
            ->latest()
            ->get()
            ->map(function ($g) {
                // Gunakan accessor thumbnail_url dari model
                $thumbnailUrl = $g->thumbnail_url;

                return [
                    'id' => $g->id,
                    'judul' => $g->judul,
                    'img' => $thumbnailUrl,
                ];
            })
            ->filter(fn ($g) => $g['img'] !== null)
            ->values();

        return response()->json([
            'data' => $galeri,
        ]);
    }

    public function galeriDetail($id)
    {
        $galeri = Galeri::where('is_published', 1)->findOrFail($id);

        // Gunakan accessor dari model
        $fotos = $galeri->fotos;

        return response()->json([
            'fotos' => $fotos,
        ]);
    }

    public function setLocation(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0|max:10000',
        ]);

        $lat = $request->lat;
        $lng = $request->lng;

        $url = 'https://nominatim.openstreetmap.org/reverse?format=json'
             ."&lat={$lat}&lon={$lng}"
             .'&zoom=10&addressdetails=1';

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'MasjidRaudhotulJannah/1.0 (contact: your@email.com)',
                'Referer' => url('/'),
                'Accept-Language' => 'id,en',
            ])->timeout(8)->get($url);

            if (! $response->successful()) {
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

            if (! $city) {
                \Log::warning('Nominatim tidak menemukan kota | Response: '.json_encode($data));

                return response()->json(['success' => false, 'message' => 'Lokasi tidak dikenali']);
            }

            $city = strtolower(trim($city));

            session([
                'user_city' => $city,
                'user_lat' => $lat,
                'user_lng' => $lng,
            ]);

            return response()->json([
                'success' => true,
                'city' => ucwords($city),
            ]);
        } catch (\Throwable $e) {
            \Log::error('Exception reverse geocode: '.$e->getMessage()." | Lat/Lng: {$lat},{$lng}");

            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan server']);
        }
    }
}
