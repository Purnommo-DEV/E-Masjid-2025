<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Interfaces\GaleriRepositoryInterface;
use Illuminate\Http\Request;
use App\Models\Galeri;

class GaleriController extends Controller
{
    public function __construct(protected GaleriRepositoryInterface $repo) {}

    public function index()
    {
        return view('masjid.'.masjid().'.admin.galeri.index');
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
            'fotos.*' => 'required_if:tipe,foto|image|mimes:jpeg,png,jpg,webp|max:1024',
            'kategori_id' => 'required|array',
        ], [
            'url_video.required_if' => 'URL YouTube wajib diisi jika tipe Video!',
            'fotos.*.required_if' => 'Pilih minimal 1 foto!',
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

        return response()->json(['success' => true, 'message' => 'Galeri disimpan!']);
    }

    public function edit($id)
    {
        $g = $this->repo->find($id);

        $fotos = $g->getMedia('foto')->map(function ($m) {
            $folder = $m->custom_properties['folder'] ?? 'galeri/default';
            return [
                'folder' => $folder,
                'file_name' => $m->file_name,
                'url' => asset('storage/' . $folder . '/' . $m->file_name),
            ];
        })->toArray();

        return response()->json([
            'id' => $g->id,
            'judul' => $g->judul,
            'keterangan' => $g->keterangan,
            'tipe' => $g->tipe,
            'url_video' => $g->url_video,
            'fotos' => $fotos, // Kirim folder + file_name + URL
            'kategori_ids' => $g->kategoris->pluck('id')->toArray(),
            'is_published' => $g->is_published,
        ]);
    }
    
    public function update(Request $request, $id)
    {
        $request->validate([
            'judul' => 'required',
            'tipe' => 'required|in:foto,video',
            'url_video' => 'nullable|url|required_if:tipe,video',
            'fotos.*' => 'image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        $galeri = $this->repo->find($id);
        $oldFolder = $galeri->getMedia('foto')->first()?->custom_properties['folder'] ?? null;

        $this->repo->update($id, [
            'judul' => $request->judul,
            'keterangan' => $request->keterangan,
            'tipe' => $request->tipe,
            'url_video' => $request->url_video,
            'fotos' => $request->file('fotos'),
            'is_published' => $request->has('is_published'),
            'kategori_ids' => $request->kategori_id ?? [],
            'deleted_fotos' => $request->deleted_fotos,
            'old_folder' => $oldFolder,
        ]);

        return response()->json(['success' => true, 'message' => 'Galeri diperbarui!']);
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        return response()->json(['success' => true, 'message' => 'Galeri dihapus!']);
    }

    public function apiFotos($id)
    {
        $g = Galeri::with('media')->findOrFail($id);

        $fotos = $g->getMedia('foto')->map(function ($m) {
            $folder = $m->custom_properties['folder'] ?? 'galeri';
            $folder = ltrim($folder, '/');
            return [
                'file_name' => $m->file_name,
                'url'       => asset('storage/' . $folder . '/' . $m->file_name),
                'caption'   => $m->name ?? null,
            ];
        })->values()->toArray();

        return response()->json([
            'id'    => $g->id,
            'judul' => $g->judul,
            'fotos' => $fotos,
        ]);
    }

}