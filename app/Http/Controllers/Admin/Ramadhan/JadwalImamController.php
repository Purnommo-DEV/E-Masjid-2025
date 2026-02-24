<?php

namespace App\Http\Controllers\Admin\Ramadhan;

use App\Http\Controllers\Controller;
use App\Interfaces\JadwalImamTarawihRepositoryInterface;
use Illuminate\Http\Request;

class JadwalImamController extends Controller
{
    public function __construct(protected JadwalImamTarawihRepositoryInterface $repo) {}

    public function index()
    {
        return view('masjid.' . masjid() . '.admin.ramadhan.jadwal-imam.index');
    }

    public function data()
    {
        return response()->json(['data' => $this->repo->all()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'malam_ke'         => 'required|integer|min:1|max:30|unique:jadwal_imam_tarawih,malam_ke',
            'tanggal'          => 'nullable|date',
            'imam_nama'        => 'required|string|max:150',
            'penceramah_nama'  => 'nullable|string|max:150',
            'tema_tausiyah'    => 'nullable|string|max:255',
            'catatan'          => 'nullable|string',
        ]);

        $this->repo->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Jadwal imam malam ke-' . $request->malam_ke . ' berhasil ditambahkan!'
        ]);
    }

    public function edit($id)
    {
        $jadwal = $this->repo->find($id);
        return response()->json([
            'id'              => $jadwal->id,
            'malam_ke'        => $jadwal->malam_ke,
            'tanggal'         => $jadwal->tanggal ? $jadwal->tanggal->format('Y-m-d') : null,
            'imam_nama'       => $jadwal->imam_nama,
            'penceramah_nama' => $jadwal->penceramah_nama,
            'tema_tausiyah'   => $jadwal->tema_tausiyah,
            'catatan'         => $jadwal->catatan,
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'malam_ke'         => 'required|integer|min:1|max:30|unique:jadwal_imam_tarawih,malam_ke,' . $id,
            'tanggal'          => 'nullable|date',
            'imam_nama'        => 'required|string|max:150',
            'penceramah_nama'  => 'nullable|string|max:150',
            'tema_tausiyah'    => 'nullable|string|max:255',
            'catatan'          => 'nullable|string',
        ]);

        $this->repo->update($id, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Jadwal imam berhasil diperbarui!'
        ]);
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        return response()->json([
            'success' => true,
            'message' => 'Jadwal imam berhasil dihapus!'
        ]);
    }
}