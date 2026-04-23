<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Interfaces\GaleriRepositoryInterface;
use Illuminate\Http\Request;

class GaleriController extends Controller
{
    public function __construct(protected GaleriRepositoryInterface $repo) {}

    public function index()
    {
        return view('masjid.' . masjid() . '.admin.galeri.index');
    }

    public function data()
    {
        return response()->json(['data' => $this->repo->all()]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'tipe' => 'required|in:foto,video',
            'url_video' => 'nullable|url|required_if:tipe,video',
            'fotos.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // 2MB max
            'kategori_id' => 'required|array',
        ], [
            'url_video.required_if' => 'URL YouTube wajib diisi jika tipe Video!',
            'fotos.*.max' => 'Maksimal ukuran file 2MB per foto!',
            'fotos.*.image' => 'File harus berupa gambar!',
            'fotos.*.mimes' => 'Format gambar harus JPG, PNG, atau WebP!',
        ]);

        $this->repo->create([
            'judul' => $request->judul,
            'keterangan' => $request->keterangan,
            'tipe' => $request->tipe,
            'url_video' => $request->url_video,
            'fotos' => $request->file('fotos'),
            'is_published' => $request->has('is_published'),
            'kategori_ids' => $request->kategori_id ?? [],
        ]);

        return response()->json(['success' => true, 'message' => 'Galeri berhasil disimpan!']);
    }

    public function edit($id)
    {
        $galeri = $this->repo->find($id);

        return response()->json([
            'id' => $galeri->id,
            'judul' => $galeri->judul,
            'keterangan' => $galeri->keterangan,
            'tipe' => $galeri->tipe,
            'url_video' => $galeri->url_video,
            'fotos' => $galeri->fotos,
            'kategori_ids' => $galeri->kategoris->pluck('id')->toArray(),
            'is_published' => $galeri->is_published,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'tipe' => 'required|in:foto,video',
            'url_video' => 'nullable|url|required_if:tipe,video',
            'fotos.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // 2MB max
            'kategori_id' => 'required|array',
        ], [
            'url_video.required_if' => 'URL YouTube wajib diisi jika tipe Video!',
            'fotos.*.max' => 'Maksimal ukuran file 2MB per foto!',
            'fotos.*.image' => 'File harus berupa gambar!',
            'fotos.*.mimes' => 'Format gambar harus JPG, PNG, atau WebP!',
        ]);

        $this->repo->update($id, [
            'judul' => $request->judul,
            'keterangan' => $request->keterangan,
            'tipe' => $request->tipe,
            'url_video' => $request->url_video,
            'fotos' => $request->file('fotos'),
            'is_published' => $request->has('is_published'),
            'kategori_ids' => $request->kategori_id ?? [],
            'deleted_fotos' => $request->deleted_fotos,
        ]);

        return response()->json(['success' => true, 'message' => 'Galeri berhasil diperbarui!']);
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        return response()->json(['success' => true, 'message' => 'Galeri berhasil dihapus!']);
    }

    public function apiFotos($id)
    {
        $galeri = $this->repo->find($id);

        return response()->json([
            'id' => $galeri->id,
            'judul' => $galeri->judul,
            'fotos' => $galeri->fotos,
        ]);
    }
}