<?php

namespace App\Http\Controllers\Admin\Qurban;

use App\Http\Controllers\Controller;
use App\Interfaces\QurbanGalleryRepositoryInterface;
use App\Models\Qurban;
use Illuminate\Http\Request;

class QurbanGalleryController extends Controller
{
    protected $repo;

    public function __construct(QurbanGalleryRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $galleries = $this->repo->all();
        $qurbans = Qurban::active()->get();
        
        return view('masjid.' . masjid() . '.admin.qurban.gallery', compact('galleries', 'qurbans'));
    }

    public function byPaket($paketId)
    {
        $qurban = Qurban::findOrFail($paketId);
        $galleries = $this->repo->findByPaket($paketId);
        
        return view('masjid.' . masjid() . '.admin.qurban.gallery-paket', compact('qurban', 'galleries'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'qurban_id' => 'required|exists:qurbans,id',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $qurban = Qurban::findOrFail($request->qurban_id);
        
        $gallery = $this->repo->create([
            'qurban_id' => $request->qurban_id,
            'slug' => $qurban->slug,
            'image' => $request->file('image'),
            'title' => $request->title,
            'description' => $request->description,
            'is_cover' => $request->boolean('is_cover'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Gambar berhasil ditambahkan',
            'data' => $gallery
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_cover' => 'boolean',
        ]);

        $gallery = $this->repo->update($id, [
            'title' => $request->title,
            'description' => $request->description,
            'is_cover' => $request->boolean('is_cover'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Gallery berhasil diperbarui'
        ]);
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        
        return response()->json([
            'success' => true,
            'message' => 'Gambar berhasil dihapus'
        ]);
    }

    public function reorder(Request $request, $paketId)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'required|integer',
        ]);

        $this->repo->reorder($paketId, $request->order);

        return response()->json([
            'success' => true,
            'message' => 'Urutan berhasil diubah'
        ]);
    }

    public function setCover($paketId, $galleryId)
    {
        $this->repo->setCover($paketId, $galleryId);

        return response()->json([
            'success' => true,
            'message' => 'Cover berhasil diubah'
        ]);
    }
}