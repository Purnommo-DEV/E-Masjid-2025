<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Interfaces\AcaraServiceInterface;
use Illuminate\Http\Request;
use App\Models\Acara;
use RalphJSmit\Laravel\SEO\Support\SEOData;

class AcaraGuestController extends Controller
{
    protected $acaraService;

    public function __construct(AcaraServiceInterface $acaraService) {
        $this->acaraService = $acaraService;
    }

    public function index()
    {
        $acaras = $this->acaraService->paginate(9);

        // SEO khusus halaman list agenda
        $seoData = new SEOData(
            title: 'Agenda Kegiatan & Kajian',
            description: 'Jadwal kajian, pengajian, ceramah, dan kegiatan Masjid Raudhotul Jannah Taman Cipulir Estate. Lihat agenda terbaru dan jangan sampai terlewat.',
            image: secure_asset('images/default-share.jpg'),
        );

        return view('masjid.' . masjid() . '.guest.acara.index', compact('acaras'))
            ->with('seoData', $seoData);
    }

    public function show($slug)
    {
        $acara = Acara::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        $related = $this->acaraService->upcoming(3, $acara->id);

        // SEO otomatis dari model Acara via trait HasSEO
        return view('masjid.' . masjid() . '.guest.acara.show', compact('acara', 'related'));
    }
}
