<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Interfaces\LayananRepositoryInterface;
use Illuminate\Http\Request;

class LayananController extends Controller
{
    public function __construct(protected LayananRepositoryInterface $repo) {}

    public function index()
    {
        return view('masjid.'.masjid().'.admin.layanan.index'); // buat file blade admin.layanan.index
    }

    public function data()
    {
        return response()->json(['data' => $this->repo->all()]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required|string',
            'deskripsi' => 'nullable|string',
            'urutan' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'image' => 'nullable|file|image|max:5120',
        ]);

        $data = $request->all();

        // cast is_active properly
        $data['is_active'] = $request->has('is_active') ? (bool)$request->is_active : false;

        $this->repo->create($data);

        return response()->json(['success' => true, 'message' => 'Layanan ditambah']);
    }

    public function edit($id)
    {
        $layanan = $this->repo->find($id);
        return response()->json($layanan);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'judul' => 'required|string',
            'deskripsi' => 'nullable|string',
            'urutan' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $data = $request->all();
        $data['is_active'] = $request->has('is_active') ? (bool)$request->is_active : false;

        $this->repo->update($id, $data);

        return response()->json(['success' => true, 'message' => 'Layanan diperbarui']);
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        return response()->json(['success' => true, 'message' => 'Layanan dihapus']);
    }
}
