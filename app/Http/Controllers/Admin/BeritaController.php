<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Interfaces\BeritaRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BeritaController extends Controller
{
    public function __construct(protected BeritaRepositoryInterface $repo) {}

    public function index()
    {
        return view('masjid.' . masjid() . '.admin.berita.index');
    }

    public function data()
    {
        return response()->json(['data' => $this->repo->all()]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul'         => 'required|string|max:255',
            'isi'           => 'required',
            'gambar.*'      => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'kategori_id'   => 'nullable|array',
            'kategori_id.*' => 'exists:kategori,id',
            'seo.title' => 'nullable|string|max:70',
            'seo.description' => 'nullable|string|max:170',
            'seo.image' => 'nullable|string|max:2048',
            'seo.canonical_url' => 'nullable|url|max:2048',
            'seo.robots' => 'nullable|string|max:50',
        ]);

        $berita = $this->repo->create([
            'judul'         => $request->judul,
            'isi'           => $request->isi,
            'excerpt'       => Str::limit(strip_tags($request->isi), 160),
            'is_published'  => $request->boolean('is_published'),
            'kategori_ids'  => $request->input('kategori_id', []),
            'gambar'        => $request->file('gambar') ?? [],
        ]);

        $this->syncSeo($berita, $request);

        return response()->json(['success' => true, 'message' => 'Berita berhasil disimpan!']);
    }

    public function show($id)
    {
        $berita = $this->repo->find($id);

        return response()->json([
            'id'           => $berita->id,
            'judul'        => $berita->judul,
            'isi'          => $berita->isi,
            'gambar'       => $berita->gallery, // dari accessor model
            'kategori_ids' => $berita->kategoris->pluck('id')->toArray(),
            'is_published' => $berita->is_published,
            'seo'          => [
                'title' => $berita->seo?->title,
                'description' => $berita->seo?->description,
                'image' => $berita->seo?->image,
                'canonical_url' => $berita->seo?->canonical_url,
                'robots' => $berita->seo?->robots,
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'judul'          => 'required|string|max:255',
            'isi'            => 'required',
            'gambar.*'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'kategori_id'    => 'nullable|array',
            'kategori_id.*'  => 'exists:kategori,id',
            'deleted_gambar' => 'nullable|string',
            'seo.title' => 'nullable|string|max:70',
            'seo.description' => 'nullable|string|max:170',
            'seo.image' => 'nullable|string|max:2048',
            'seo.canonical_url' => 'nullable|url|max:2048',
            'seo.robots' => 'nullable|string|max:50',
        ]);

        $berita = $this->repo->update($id, [
            'judul'          => $request->judul,
            'isi'            => $request->isi,
            'excerpt'        => Str::limit(strip_tags($request->isi), 160),
            'is_published'   => $request->boolean('is_published'),
            'kategori_ids'   => $request->input('kategori_id', []),
            'gambar'         => $request->file('gambar') ?? [],
            'deleted_gambar' => $request->deleted_gambar,
        ]);

        $this->syncSeo($berita, $request);

        return response()->json(['success' => true, 'message' => 'Berita berhasil diperbarui!']);
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        return response()->json(['success' => true, 'message' => 'Berita berhasil dihapus!']);
    }

    private function syncSeo($model, Request $request): void
    {
        $seo = collect($request->input('seo', []))
            ->only(['title', 'description', 'image', 'canonical_url', 'robots'])
            ->map(fn ($value) => is_string($value) && trim($value) === '' ? null : $value)
            ->all();

        $model->seo()->updateOrCreate([], $seo);
    }
}
