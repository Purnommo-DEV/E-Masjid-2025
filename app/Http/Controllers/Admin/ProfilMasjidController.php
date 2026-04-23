<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Interfaces\ProfilMasjidRepositoryInterface;
use App\Models\Pengurus;
use Illuminate\Http\Request;

class ProfilMasjidController extends Controller
{
    protected $repo;

    public function __construct(ProfilMasjidRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $profil = $this->repo->getProfil();
        $pengurus = $this->repo->getPengurus();
        return view('masjid.' . masjid() . '.admin.profil.index', compact('profil', 'pengurus'));
    }

    public function updateProfil(Request $request)
    {
        $request->validate([
            'nama'          => 'required|string|max:255',
            'alamat'        => 'required|string',
            'telepon'       => 'nullable|string|max:20',
            'email'         => 'nullable|email',
            'latitude'      => 'required|numeric|between:-90,90',
            'longitude'     => 'required|numeric|between:-180,180',
            'singkatan'     => 'nullable|string|max:10',
            'logo'          => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'struktur'      => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'bank_name'     => 'nullable|string|max:100',
            'bank_code'     => 'nullable|string|max:10',
            'rekening'      => 'nullable|string|max:50',
            'atas_nama'     => 'nullable|string|max:100',
            'qris'          => 'nullable|image|mimes:png,jpeg,jpg,webp|max:2048',
            'wa_konfirmasi' => 'nullable|string|max:20',
        ], [
            'qris.max' => 'Ukuran file QRIS terlalu besar. Maksimal 2MB. File Anda: ' . round($request->file('qris')?->getSize() / 1024 / 1024, 2) . 'MB',
            'qris.image' => 'File QRIS harus berupa gambar (PNG, JPG, JPEG, WEBP)',
            'qris.mimes' => 'Format QRIS harus PNG, JPG, JPEG, atau WEBP',
            'logo.max' => 'Ukuran logo terlalu besar. Maksimal 2MB',
            'struktur.max' => 'Ukuran file struktur terlalu besar. Maksimal 2MB',
        ]);

        $data = $request->only([
            'nama',
            'singkatan',
            'alamat',
            'telepon',
            'email',
            'latitude',
            'longitude',
            'bank_name',
            'bank_code',
            'rekening',
            'atas_nama',
            'wa_konfirmasi',
        ]);

        $data['logo']     = $request->file('logo');
        $data['struktur'] = $request->file('struktur');
        $data['qris']     = $request->file('qris');

        $this->repo->updateProfil($data);

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui!'
        ]);
    }

    public function storePengurus(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'jabatan' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
            'urutan' => 'nullable|integer',
            'foto' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
        ]);

        $this->repo->createPengurus([
            'nama' => $request->nama,
            'jabatan' => $request->jabatan,
            'keterangan' => $request->keterangan,
            'urutan' => $request->urutan ?? 0,
            'foto' => $request->file('foto'),
        ]);

        return response()->json(['success' => true, 'message' => 'Pengurus berhasil ditambahkan!']);
    }

    public function editPengurus($id)
    {
        $pengurus = Pengurus::findOrFail($id);
        return response()->json([
            'id' => $pengurus->id,
            'nama' => $pengurus->nama,
            'jabatan' => $pengurus->jabatan,
            'keterangan' => $pengurus->keterangan,
            'urutan' => $pengurus->urutan,
            'foto_url' => $pengurus->foto_url,
        ]);
    }

    public function updatePengurus(Request $request, $id)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'jabatan' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
            'urutan' => 'nullable|integer',
            'foto' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
        ]);

        $this->repo->updatePengurus($id, [
            'nama' => $request->nama,
            'jabatan' => $request->jabatan,
            'keterangan' => $request->keterangan,
            'urutan' => $request->urutan ?? 0,
            'foto' => $request->file('foto'),
        ]);

        return response()->json(['success' => true, 'message' => 'Pengurus berhasil diperbarui!']);
    }

    public function destroyPengurus($id)
    {
        $this->repo->deletePengurus($id);
        return response()->json(['success' => true, 'message' => 'Pengurus berhasil dihapus!']);
    }

    public function reorderPengurus(Request $request)
    {
        $request->validate(['order' => 'required|array']);
        $this->repo->reorderPengurus($request->order);
        return response()->json(['success' => true]);
    }
}