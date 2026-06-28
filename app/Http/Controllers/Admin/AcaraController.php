<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Interfaces\AcaraRepositoryInterface;
use Illuminate\Http\Request;

class AcaraController extends Controller
{
    public function __construct(protected AcaraRepositoryInterface $repo) {}

    public function index()
    {
        return view('masjid.' . masjid() . '.admin.acara.index');
    }

    public function data()
    {
        return response()->json(['data' => $this->repo->all()]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable',
            'mulai' => 'required|date',
            'selesai' => 'nullable|date|after_or_equal:mulai',
            'lokasi' => 'nullable|string',
            'penyelenggara' => 'nullable|string',
            'pemateri' => 'nullable|string',
            'waktu_teks' => 'nullable|string',
            'poster' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5024',
            'kategori_id' => 'array',
            'seo.title' => 'nullable|string|max:70',
            'seo.description' => 'nullable|string|max:170',
            'seo.image' => 'nullable|string|max:2048',
            'seo.canonical_url' => 'nullable|url|max:2048',
            'seo.robots' => 'nullable|string|max:50',
        ]);

        $acara = $this->repo->create([
            'judul' => $request->judul,
            'deskripsi' => $request->deskripsi,
            'mulai' => $request->mulai,
            'selesai' => $request->selesai,
            'lokasi' => $request->lokasi,
            'penyelenggara' => $request->penyelenggara,
            'waktu_teks' => $request->waktu_teks,
            'pemateri' => $request->pemateri,
            'poster' => $request->file('poster'),
            'is_published' => $request->has('is_published'),
            'kategori_ids' => $request->kategori_id ?? [],
        ]);

        $this->syncSeo($acara, $request);

        return response()->json(['success' => true, 'message' => 'Acara berhasil disimpan!']);
    }
    
    public function edit($id)
    {
        $acara = $this->repo->find($id);

        return response()->json([
            'id' => $acara->id,
            'judul' => $acara->judul,
            'deskripsi' => $acara->deskripsi,
            'mulai' => $acara->mulai->format('Y-m-d\TH:i'),
            'selesai' => $acara->selesai?->format('Y-m-d\TH:i'),
            'lokasi' => $acara->lokasi,
            'penyelenggara' => $acara->penyelenggara,
            'pemateri' => $acara->pemateri,
            'waktu_teks' => $acara->waktu_teks,
            'poster_url' => $acara->poster_url, // Kirim URL poster (string, bukan array)
            'kategori_ids' => $acara->kategoris->pluck('id')->toArray(),
            'is_published' => $acara->is_published,
            'seo' => [
                'title' => $acara->seo?->title,
                'description' => $acara->seo?->description,
                'image' => $acara->seo?->image,
                'canonical_url' => $acara->seo?->canonical_url,
                'robots' => $acara->seo?->robots,
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable',
            'mulai' => 'required|date',
            'selesai' => 'nullable|date|after_or_equal:mulai',
            'lokasi' => 'nullable|string',
            'penyelenggara' => 'nullable|string',
            'pemateri' => 'nullable|string',
            'waktu_teks' => 'nullable|string',
            'poster' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5024',
            'kategori_id' => 'array',
            'seo.title' => 'nullable|string|max:70',
            'seo.description' => 'nullable|string|max:170',
            'seo.image' => 'nullable|string|max:2048',
            'seo.canonical_url' => 'nullable|url|max:2048',
            'seo.robots' => 'nullable|string|max:50',
        ]);

        $acara = $this->repo->update($id, [
            'judul' => $request->judul,
            'deskripsi' => $request->deskripsi,
            'mulai' => $request->mulai,
            'selesai' => $request->selesai,
            'lokasi' => $request->lokasi,
            'penyelenggara' => $request->penyelenggara,
            'waktu_teks' => $request->waktu_teks,
            'pemateri' => $request->pemateri,
            'poster' => $request->file('poster'),
            'is_published' => $request->has('is_published'),
            'kategori_ids' => $request->kategori_id ?? [],
        ]);

        $this->syncSeo($acara, $request);

        return response()->json(['success' => true, 'message' => 'Acara berhasil diperbarui!']);
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        return response()->json(['success' => true, 'message' => 'Acara berhasil dihapus!']);
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
