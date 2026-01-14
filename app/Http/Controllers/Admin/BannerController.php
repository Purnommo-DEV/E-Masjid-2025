<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Interfaces\BannerRepositoryInterface;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function __construct(protected BannerRepositoryInterface $repo) {}

    public function index()
    {
        return view('masjid.'.masjid().'.admin.banner.index');
    }

    public function data()
    {
        $rows = $this->repo->all();

        return response()->json(['data' => $rows]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'judul'           => ['required', 'string', 'max:255'],
            'subjudul'        => ['nullable', 'string', 'max:255'],
            'catatan_singkat' => ['nullable', 'string', 'max:255'],
            'label_tombol'    => ['nullable', 'string', 'max:50'],
            'urutan'          => ['nullable', 'integer', 'min:0'],
            'url_tujuan'      => ['nullable', 'url'],
            'deskripsi'       => ['nullable', 'string'],
            // <== nggak pakai boolean / in lagi
            'is_active'       => ['nullable'],
            'gambar'          => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $this->repo->create(array_merge($validated, [
            // tetap pakai helper boolean supaya di DB boolean beneran
            'is_active' => $request->boolean('is_active'),
        ]));

        return response()->json(['success' => true, 'message' => 'Banner ditambahkan!']);
    }

    public function edit($id)
    {
        $b = $this->repo->find($id);

        return response()->json([
            'id'              => $b->id,
            'judul'           => $b->judul,
            'subjudul'        => $b->subjudul,
            'catatan_singkat' => $b->catatan_singkat,
            'label_tombol'    => $b->label_tombol,
            'urutan'          => $b->urutan,
            'url_tujuan'      => $b->url_tujuan,
            'deskripsi'       => $b->deskripsi,
            'is_active'       => $b->is_active,
            'gambar_url'      => $b->gambar_url,
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'judul'           => ['required', 'string', 'max:255'],
            'subjudul'        => ['nullable', 'string', 'max:255'],
            'catatan_singkat' => ['nullable', 'string', 'max:255'],
            'label_tombol'    => ['nullable', 'string', 'max:50'],
            'urutan'          => ['nullable', 'integer', 'min:0'],
            'url_tujuan'      => ['nullable', 'url'],
            'deskripsi'       => ['nullable', 'string'],
            // <== longgar
            'is_active'       => ['nullable'],
            'gambar'          => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $this->repo->update($id, array_merge($validated, [
            'is_active' => $request->boolean('is_active'),
        ]));

        return response()->json(['success' => true, 'message' => 'Banner diperbarui!']);
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        return response()->json(['success' => true, 'message' => 'Banner dihapus!']);
    }
}
