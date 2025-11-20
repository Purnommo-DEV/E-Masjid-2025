<?php
// app/Http/Controllers/Admin/ProfilMasjidController.php
namespace App\Http\Controllers\Admin;

use App\Interfaces\ProfilMasjidRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pengurus;

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
        return view('masjid.'.masjid().'.admin.profil.index', compact('profil', 'pengurus'));
    }

    public function updateProfil(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'alamat' => 'required|string',
            'telepon' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'logo' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'struktur' => 'nullable|image|mimes:jpeg,png,webp|max:3072',
        ]);

        $this->repo->updateProfil([
            'nama' => $request->nama,
            'alamat' => $request->alamat,
            'telepon' => $request->telepon,
            'email' => $request->email,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'logo' => $request->file('logo'),
            'struktur' => $request->file('struktur'),
        ]);

        return response()->json(['success' => true, 'message' => 'Profil diperbarui!']);
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

        return response()->json(['success' => true, 'message' => 'Pengurus ditambahkan!']);
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
            'foto_url' => $pengurus->getFirstMediaUrl('foto'),
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

        return response()->json(['success' => true, 'message' => 'Pengurus diperbarui!']);
    }

    public function destroyPengurus($id)
    {
        $this->repo->deletePengurus($id);
        return response()->json(['success' => true, 'message' => 'Pengurus dihapus!']);
    }

    public function reorderPengurus(Request $request)
    {
        $request->validate(['order' => 'required|array']);
        foreach ($request->order as $index => $id) {
            Pengurus::where('id', $id)->update(['urutan' => $index + 1]);
        }
        return response()->json(['success' => true]);
    }
}