<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Interfaces\BeritaRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BeritaController extends Controller
{
    public function __construct(protected BeritaRepositoryInterface $repo) {}

    public function index() { return view('masjid.'.masjid().'.admin.berita.index'); }
    public function data() { return response()->json(['data' => $this->repo->all()]); }

    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'isi' => 'required',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:1024', // 10MB = 1024 KB
            'kategori_id' => 'array',
            'kategori_id.*' => 'exists:kategori,id',
        ], [
            'gambar.max' => 'Ukuran gambar maksimal 1MB!',
            'gambar.image' => 'File harus berupa gambar!',
            'gambar.mimes' => 'Format gambar: jpeg, png, jpg, webp',
        ]);

        $this->repo->create([
            'judul' => $request->judul,
            'isi' => $request->isi,
            'excerpt' => Str::limit(strip_tags($request->isi), 160),
            'gambar' => $request->file('gambar'),
            'is_published' => $request->has('is_published'),
            'kategori_ids' => $request->kategori_id ?? [],
        ]);
        return response()->json(['success' => true, 'message' => 'Berita disimpan!']);
    }

    public function edit($id)
    {
        $berita = $this->repo->find($id);
        $gambar = $berita->getMedia('gambar')->map(function ($m) {
            $folder = $m->custom_properties['folder'] ?? 'berita/default';
            return [
                'folder' => $folder,
                'file_name' => $m->file_name,
                'url' => asset('storage/' . $folder . '/' . $m->file_name),
            ];
        })->toArray();
        return response()->json([
            'id' => $berita->id,
            'judul' => $berita->judul,
            'isi' => $berita->isi,
            'gambar' => $gambar, // Kirim folder + file_name + URL
            'kategori_ids' => $berita->kategoris->pluck('id')->toArray(),
            'is_published' => $berita->is_published,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'isi' => 'required',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:1024', // 10MB = 1024 KB
            'kategori_id' => 'array',
            'kategori_id.*' => 'exists:kategori,id',
        ], [
            'gambar.max' => 'Ukuran gambar maksimal 1MB!',
            'gambar.image' => 'File harus berupa gambar!',
            'gambar.mimes' => 'Format gambar: jpeg, png, jpg, webp',
        ]);
        
        $this->repo->update($id, [
            'judul' => $request->judul,
            'isi' => $request->isi,
            'excerpt' => Str::limit(strip_tags($request->isi), 160),
            'gambar' => $request->file('gambar'),
            'is_published' => $request->has('is_published'),
            'kategori_ids' => $request->kategori_id ?? [],
        ]);
        return response()->json(['success' => true, 'message' => 'Berita diperbarui!']);
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        return response()->json(['success' => true, 'message' => 'Berita dihapus!']);
    }
}