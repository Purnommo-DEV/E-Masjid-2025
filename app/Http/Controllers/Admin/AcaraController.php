<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Interfaces\AcaraRepositoryInterface;
use Illuminate\Http\Request;

class AcaraController extends Controller
{
    public function __construct(protected AcaraRepositoryInterface $repo) {}

    public function index()
    {
        return view('masjid.'.masjid().'.admin.acara.index');
    }

    public function data()
    {
        return response()->json(['data' => $this->repo->all()]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable',
            'mulai' => 'required|date',
            'selesai' => 'nullable|date|after_or_equal:mulai',
            'lokasi' => 'nullable|string',
            'penyelenggara' => 'nullable|string',
            'pemateri' => 'nullable|string',
            'waktu_teks' => 'nullable|string',
            'poster' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'kategori_id' => 'array',
        ]);

        $this->repo->create([
            'judul' => $request->judul,
            'deskripsi' => $request->deskripsi,
            'mulai' => $request->mulai,
            'selesai' => $request->selesai,
            'lokasi' => $request->lokasi,
            'penyelenggara' => $request->penyelenggara,
            'waktu_teks' => $request->waktu_teks,
            'pemateri' => $request->pemateri,
            'poster' => $request->file('poster'),
            'is_published' => $request->has('is_published'),
            'kategori_ids' => $request->kategori_id ?? [],
        ]);

        return response()->json(['success' => true, 'message' => 'Acara berhasil disimpan!']);
    }

    public function edit($id)
    {
        $acara = $this->repo->find($id);

        $poster = $acara->getMedia('poster')->map(function ($m) {
            $folder = $m->custom_properties['folder'] ?? 'acara/default';
            return [
                'folder' => $folder,
                'file_name' => $m->file_name,
                'url' => asset('storage/' . $folder . '/' . $m->file_name),
            ];
        })->toArray();
        return response()->json([
            'id' => $acara->id,
            'judul' => $acara->judul,
            'deskripsi' => $acara->deskripsi,
            'mulai' => $acara->mulai->format('Y-m-d\TH:i'),
            'selesai' => $acara->selesai?->format('Y-m-d\TH:i'),
            'lokasi' => $acara->lokasi,
            'penyelenggara' => $acara->penyelenggara,
            'waktu_teks' => $acara->waktu_teks,
            'pemateri' => $acara->pemateri,
            'poster' => $poster, // Kirim folder + file_name + URL
            'kategori_ids' => $acara->kategoris->pluck('id')->toArray(),
            'is_published' => $acara->is_published,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable',
            'mulai' => 'required|date',
            'selesai' => 'nullable|date|after_or_equal:mulai',
            'lokasi' => 'nullable|string',
            'penyelenggara' => 'nullable|string',
            'pemateri' => 'nullable|string',
            'waktu_teks' => 'nullable|string',
            'poster' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'kategori_id' => 'array',
        ]);

        $this->repo->update($id, [
            'judul' => $request->judul,
            'deskripsi' => $request->deskripsi,
            'mulai' => $request->mulai,
            'selesai' => $request->selesai,
            'lokasi' => $request->lokasi,
            'penyelenggara' => $request->penyelenggara,
            'waktu_teks' => $request->waktu_teks,
            'pemateri' => $request->pemateri,
            'poster' => $request->file('poster'),
            'is_published' => $request->has('is_published'),
            'kategori_ids' => $request->kategori_id ?? [],
        ]);

        return response()->json(['success' => true, 'message' => 'Acara diperbarui!']);
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        return response()->json(['success' => true, 'message' => 'Acara dihapus!']);
    }
}