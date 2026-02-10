<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Interfaces\AcaraServiceInterface;
use Illuminate\Http\Request;
use App\Models\Acara;

class AcaraGuestController extends Controller
{
    protected $acaraService;

    public function __construct(AcaraServiceInterface $acaraService) {
        $this->acaraService = $acaraService;
    }

    public function index()
    {
        $acaras = $this->acaraService->paginate(9);  // 9 item per halaman, sesuai request awalmu

        return view('masjid.'.masjid().'.guest.acara.index', compact('acaras'));
    }

    public function show($slug)
    {
        $acara = Acara::where('slug', $slug)
                      ->where('is_published', true)
                      ->firstOrFail();
                      
        // Related: 3 acara mendatang lainnya (kecuali yang sedang dibuka)
        $related = $this->acaraService->upcoming(3, $acara->id);

        return view('masjid.'.masjid().'.guest.acara.show', compact('acara', 'related'));
    }
}
