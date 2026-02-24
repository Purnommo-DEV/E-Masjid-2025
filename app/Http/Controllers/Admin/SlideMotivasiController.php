<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Interfaces\SlideMotivasiRepositoryInterface;
use Illuminate\Http\Request;

class SlideMotivasiController extends Controller
{
    public function __construct(
        protected SlideMotivasiRepositoryInterface $repo
    ) {}

    public function index()
    {
        return view('masjid.' . masjid() . '.admin.slide-motivasi.index');
    }

    public function data()
    {
        // Mengembalikan semua data untuk DataTables (bisa di-sort di repository jika perlu)
        return response()->json(['data' => $this->repo->all()]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'subtitle'    => 'nullable|string',
            'button_text' => 'required|string|max:100',
            'button_link' => 'required|string|max:255',
            'gradient'    => 'nullable|string|max:100',
            'border_color'=> 'nullable|string|max:100',
            'order'       => 'nullable|integer|min:1',
            'is_active'   => 'nullable|in:on', // checkbox dari form
        ]);

        $this->repo->create($request->only([
            'title', 'subtitle', 'button_text', 'button_link',
            'gradient', 'border_color', 'order', 'is_active'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Slide motivasi berhasil ditambahkan!'
        ]);
    }

    public function edit($id)
    {
        return response()->json($this->repo->find($id));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'subtitle'    => 'nullable|string',
            'button_text' => 'required|string|max:100',
            'button_link' => 'required|string|max:255',
            'gradient'    => 'nullable|string|max:100',
            'border_color'=> 'nullable|string|max:100',
            'order'       => 'nullable|integer|min:1',
            'is_active'   => 'nullable|in:on',
        ]);

        $this->repo->update($id, $request->only([
            'title', 'subtitle', 'button_text', 'button_link',
            'gradient', 'border_color', 'order', 'is_active'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Slide motivasi berhasil diperbarui!'
        ]);
    }

    public function destroy($id)
    {
        $this->repo->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Slide motivasi berhasil dihapus!'
        ]);
    }
}