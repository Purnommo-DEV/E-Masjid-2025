<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Interfaces\BannerRepositoryInterface;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function __construct(protected BannerRepositoryInterface $repo) {}

    /**
     * Tampilkan halaman index banner
     */
    public function index()
    {
        return view('masjid.' . masjid() . '.admin.banner.index');
    }

    /**
     * Ambil data untuk DataTable
     */
    public function data()
    {
        $rows = $this->repo->all();

        return response()->json(['data' => $rows]);
    }

    /**
     * Store banner baru
     */
    public function store(Request $request)
    {
        dd(1);
        $validated = $request->validate([
            'judul'           => ['required', 'string', 'max:255'],
            'subjudul'        => ['nullable', 'string', 'max:255'],
            'catatan_singkat' => ['nullable', 'string', 'max:255'],
            'button_label'    => ['nullable', 'string', 'max:50'],
            'urutan'          => ['nullable', 'integer', 'min:0'],
            'button_url'      => ['nullable', 'url'],
            'deskripsi'       => ['nullable', 'string'],
            'is_active'       => ['nullable'],
            'gambar'          => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $banner = $this->repo->create(array_merge($validated, [
            'is_active' => $request->boolean('is_active'),
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Banner berhasil ditambahkan!',
            'data'    => $banner
        ]);
    }

    /**
     * Get data banner untuk edit
     */
    public function edit($id)
    {
        $banner = $this->repo->find($id);

        if (!$banner) {
            return response()->json(['error' => 'Banner tidak ditemukan'], 404);
        }

        return response()->json([
            'id'              => $banner->id,
            'judul'           => $banner->judul,
            'subjudul'        => $banner->subjudul,
            'catatan_singkat' => $banner->catatan_singkat,
            'button_label'    => $banner->button_label,
            'urutan'          => $banner->urutan,
            'button_url'      => $banner->button_url,
            'deskripsi'       => $banner->deskripsi,
            'is_active'       => $banner->is_active,
            'gambar_url'      => $banner->gambar_url,
        ]);
    }

    /**
     * Update banner
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'judul'           => ['required', 'string', 'max:255'],
            'subjudul'        => ['nullable', 'string', 'max:255'],
            'catatan_singkat' => ['nullable', 'string', 'max:255'],
            'button_label'    => ['nullable', 'string', 'max:50'],
            'urutan'          => ['nullable', 'integer', 'min:0'],
            'button_url'      => ['nullable', 'url'],
            'deskripsi'       => ['nullable', 'string'],
            'is_active'       => ['nullable'],
            'gambar'          => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $banner = $this->repo->update($id, array_merge($validated, [
            'is_active' => $request->boolean('is_active'),
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Banner berhasil diperbarui!',
            'data'    => $banner
        ]);
    }

    /**
     * Delete banner
     */
    public function destroy($id)
    {
        $deleted = $this->repo->delete($id);
        
        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Banner gagal dihapus!'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Banner berhasil dihapus!'
        ]);
    }
}