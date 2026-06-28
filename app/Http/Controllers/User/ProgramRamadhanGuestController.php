<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Interfaces\BeritaServiceInterface; // asumsi kamu sudah punya service untuk berita
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RalphJSmit\Laravel\SEO\SchemaCollection;
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

        $seoData = seo_page('program-ramadhan.index', new SEOData(
            title: 'Program Ramadhan 1447 H | ' . $namaMasjid,
            description: 'Program Ramadhan 1447 H di ' . $namaMasjid . '. Mari ikuti Program Ramadhan 1447 H dan semarakkan bulan suci dengan ibadah dan kebaikan.',
            image: secure_asset('images/default-ramadhan.png'),
            url: route('program-ramadhan.index'),
            canonical_url: route('program-ramadhan.index'),
        ));

        return view('masjid.' . masjid() . '.guest.program-ramadhan.index', compact('beritas'))
            ->with('seoData', $seoData);
    }

    public function show($slug)
    {
        $berita = $this->beritaService->findProgramBySlug($slug);

        $limit = (int) request()->input('related_limit', 3);
        $related = $this->beritaService->relatedPrograms((int) $berita->id, $limit);
        $detailUrl = route('program-ramadhan.show', $berita->slug);
        $description = $berita->seo?->description ?: ($berita->excerpt ?: Str::limit(strip_tags($berita->isi ?? ''), 158));
        $image = $berita->seo?->image ?: ($berita->cover_url ?? secure_asset('images/default-ramadhan.png'));

        $seoData = new SEOData(
            title: $berita->seo?->title ?: $berita->judul . ' | ' . masjid_name(),
            description: $description,
            author: $berita->seo?->author ?: ($berita->author?->name ?? 'Tim Masjid'),
            image: $image,
            url: $detailUrl,
            published_time: $berita->published_at,
            modified_time: $berita->updated_at,
            type: 'article',
            robots: $berita->seo?->robots,
            canonical_url: $berita->seo?->canonical_url ?: $detailUrl,
            schema: SchemaCollection::make()->addArticle(),
        );

        return view('masjid.' . masjid() . '.guest.program-ramadhan.show', compact('berita', 'related'))
            ->with('seoData', $seoData);
    }
}
