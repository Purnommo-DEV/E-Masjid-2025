<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Interfaces\KhutbahJumatRepositoryInterface;
use Illuminate\Http\Request;

class KhutbahJumatController extends Controller
{
    public function __construct(
        protected KhutbahJumatRepositoryInterface $repo
    ) {}

    public function index()
    {
        return view('masjid.' . masjid() . '.admin.khutbah-jumat.index');
    }

    public function data()
    {
        return response()->json(['data' => $this->repo->all()]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'khatib'       => 'required|string|max:100',
            'tema'         => 'required|string|max:255',
            'tanggal'      => 'required|string|max:100', // misal "Jum’at, 12 Jan 2026"
            'jam'          => 'required|string|max:50',
            'tanggal_asli' => 'nullable|date', // untuk sorting
            'status'       => 'nullable|in:coming_soon,berlangsung,selesai',
            'keterangan'   => 'nullable|string',
            'is_active'    => 'nullable|in:on',
        ]);

        $this->repo->create($request->only([
            'khatib', 'tema', 'tanggal', 'jam', 'tanggal_asli', 'status', 'keterangan', 'is_active'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Jadwal Khutbah Jumat berhasil ditambahkan!'
        ]);
    }

    public function edit($id)
    {
        return response()->json($this->repo->find($id));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'khatib'       => 'required|string|max:100',
            'tema'         => 'required|string|max:255',
            'tanggal'      => 'required|string|max:100',
            'jam'          => 'required|string|max:50',
            'tanggal_asli' => 'nullable|date',
            'status'       => 'nullable|in:coming_soon,berlangsung,selesai',
            'keterangan'   => 'nullable|string',
            'is_active'    => 'nullable|in:on',
        ]);

        $this->repo->update($id, $request->only([
            'khatib', 'tema', 'tanggal', 'jam', 'tanggal_asli', 'status', 'keterangan', 'is_active'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Jadwal Khutbah Jumat berhasil diperbarui!'
        ]);
    }

    public function destroy($id)
    {
        $this->repo->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Jadwal Khutbah Jumat berhasil dihapus!'
        ]);
    }
}