<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Interfaces\KategoriRepositoryInterface;
use Illuminate\Http\Request;

class KategoriController extends Controller
{
    public function __construct(protected KategoriRepositoryInterface $repo) {}

    public function index() { return view('masjid.'.masjid().'.admin.kategori.index'); }
    public function data() { return response()->json(['data' => $this->repo->all()]); }

    public function store(Request $request)
    {
        $request->validate(['nama' => 'required', 'warna' => 'required', 'tipe' => 'required']);
        $this->repo->create([
            'nama' => $request->nama,
            'warna' => $request->warna,
            'tipe' => $request->tipe
        ]);
        return response()->json(['success' => true, 'message' => 'Kategori ditambah!']);
    }

    public function edit($id) { return response()->json($this->repo->find($id)); }

    public function update(Request $request, $id)
    {
        $request->validate
        ([
            'nama' => 'required', 
            'warna' => 'required',
            'tipe' => 'required'
        ]);
        $this->repo->update(
            $id, $request->only
                ([
                    'nama', 
                    'warna', 
                    'tipe'
                ])
            );
        return response()->json(['success' => true, 'message' => 'Kategori diperbarui!']);
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        return response()->json(['success' => true, 'message' => 'Kategori dihapus!']);
    }
}