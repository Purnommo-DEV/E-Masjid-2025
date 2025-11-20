<?php
namespace App\Http\Controllers\Admin;

use App\Interfaces\PengumumanRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PengumumanController extends Controller
{
    protected $repo;

    public function __construct(PengumumanRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        return view('masjid.'.masjid().'.admin.pengumuman.index');
    }

    public function data() { return response()->json(['data' => $this->repo->all()]); }

    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'tipe' => 'required|in:banner,popup,notif',
            'gambar' => 'required_if:tipe,banner|image|mimes:jpeg,png,webp|max:2048',
            'mulai' => 'nullable|date',
            'selesai' => 'nullable|date|after_or_equal:mulai',
        ]);

        $this->repo->create([
            'judul' => $request->judul,
            'isi' => $request->isi,
            'tipe' => $request->tipe,
            'mulai' => $request->mulai,
            'selesai' => $request->selesai,
            'is_active' => $request->has('is_active'),
            'gambar' => $request->file('gambar'),
        ]);

        return response()->json(['success' => true, 'message' => 'Pengumuman ditambahkan!']);
    }

    public function edit($id)
    {
        $p = $this->repo->find($id);
        $media = $p->getFirstMedia('banner');
        $gambarUrl = $media && isset($media->custom_properties['folder'])
            ? asset('storage/' . $media->custom_properties['folder'] . '/' . $media->file_name)
            : null;

        return response()->json([
            'id' => $p->id,
            'judul' => $p->judul,
            'isi' => $p->isi,
            'tipe' => $p->tipe,
            'mulai' => $p->mulai?->format('Y-m-d\TH:i'),
            'selesai' => $p->selesai?->format('Y-m-d\TH:i'),
            'is_active' => $p->is_active,
            'gambar_url' => $gambarUrl,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'tipe' => 'required|in:banner,popup,notif',
            'gambar' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
        ]);

        $this->repo->update($id, [
            'judul' => $request->judul,
            'isi' => $request->isi,
            'tipe' => $request->tipe,
            'mulai' => $request->mulai,
            'selesai' => $request->selesai,
            'is_active' => $request->has('is_active'),
            'gambar' => $request->file('gambar'),
        ]);

        return response()->json(['success' => true, 'message' => 'Pengumuman diperbarui!']);
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        return response()->json(['success' => true, 'message' => 'Pengumuman dihapus!']);
    }
}