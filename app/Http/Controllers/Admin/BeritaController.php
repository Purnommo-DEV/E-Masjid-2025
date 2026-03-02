<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Interfaces\BeritaRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BeritaController extends Controller
{
    public function __construct(protected BeritaRepositoryInterface $repo) {}

    public function index()
    {
        return view('masjid.' . masjid() . '.admin.berita.index');
    }

    public function data()
    {
        return response()->json(['data' => $this->repo->all()]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul'         => 'required|string|max:255',
            'isi'           => 'required',
            'gambar.*'      => 'nullable|image|mimes:jpeg,png,jpg,webp|max:1024',
            'kategori_id'   => 'nullable|array',
            'kategori_id.*' => 'exists:kategori,id',
        ]);

        $berita = $this->repo->create([
            'judul'         => $request->judul,
            'isi'           => $request->isi,
            'excerpt'       => Str::limit(strip_tags($request->isi), 160),
            'is_published'  => $request->boolean('is_published'),
            'kategori_ids'  => $request->input('kategori_id', []),
        ]);

        $files = $request->file('gambar') ?? [];

        if ($files) {
            // Folder: berita/{slug-berita}
            $folder = 'berita/' . $berita->slug;
            Storage::disk('public')->makeDirectory($folder);

            Log::info("STORE - Folder custom (slug): {$folder}");

            foreach ($files as $file) {
                if (!$file instanceof \Illuminate\Http\UploadedFile || !$file->isValid()) continue;

                $media = $berita->addMedia($file)
                                ->usingName(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                                ->toMediaCollection('gambar');

                $tempPath = $media->getPath();
                $newFileName = $media->file_name;
                $newPath = storage_path('app/public/' . $folder . '/' . $newFileName);

                if (file_exists($tempPath)) {
                    if (file_exists($newPath)) unlink($newPath);
                    rename($tempPath, $newPath);

                    $oldDir = dirname($tempPath);
                    if (is_dir($oldDir) && count(scandir($oldDir)) == 2) {
                        rmdir($oldDir);
                    }
                }

                $media->update([
                    'custom_properties' => [
                        'folder'        => $folder,
                        'original_name' => $file->getClientOriginalName(),
                    ],
                ]);
            }
        }

        $mediaCount = $berita->getMedia('gambar')->count();
        Log::info("STORE Berita ID {$berita->id} - Jumlah media: {$mediaCount}");

        return response()->json(['success' => true, 'message' => 'Berita disimpan!']);
    }

    public function show($id)
    {
        $berita = $this->repo->find($id);

        $gambar = $berita->getMedia('gambar')->map(function ($media) {
            $folder = $media->custom_properties['folder'] ?? 'berita/default'; // fallback jika belum diset
            $fileName = $media->file_name;

            return [
                'folder'   => $folder,
                'file_name' => $fileName,
                'url'      => asset('storage/' . $folder . '/' . $fileName),
            ];
        })->toArray();

        return response()->json([
            'id'           => $berita->id,
            'judul'        => $berita->judul,
            'isi'          => $berita->isi,
            'gambar'       => $gambar,
            'kategori_ids' => $berita->kategoris->pluck('id')->toArray(),
            'is_published' => $berita->is_published,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'judul'          => 'required|string|max:255',
            'isi'            => 'required',
            'gambar.*'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:1024',
            'kategori_id'    => 'nullable|array',
            'kategori_id.*'  => 'exists:kategori,id',
            'deleted_gambar' => 'nullable|string',
        ]);

        $berita = $this->repo->update($id, [
            'judul'          => $request->judul,
            'isi'            => $request->isi,
            'excerpt'        => Str::limit(strip_tags($request->isi), 160),
            'is_published'   => $request->boolean('is_published'),
            'kategori_ids'   => $request->input('kategori_id', []),
            'deleted_gambar' => $request->deleted_gambar,
        ]);

        $files = $request->file('gambar') ?? [];

        if ($files) {
            // Folder baru berdasarkan slug terbaru (slug di-update di model)
            $folderBaru = 'berita/' . $berita->slug;
            Storage::disk('public')->makeDirectory($folderBaru);

            // Pindah gambar lama jika slug berubah
            foreach ($berita->getMedia('gambar') as $media) {
                $folderLama = $media->custom_properties['folder'] ?? null;
                if ($folderLama && $folderLama !== $folderBaru) {
                    $oldPath = storage_path('app/public/' . $folderLama . '/' . $media->file_name);
                    $newPath = storage_path('app/public/' . $folderBaru . '/' . $media->file_name);

                    if (file_exists($oldPath)) {
                        if (file_exists($newPath)) unlink($newPath);
                        rename($oldPath, $newPath);
                    }

                    $media->update(['custom_properties' => array_merge($media->custom_properties ?? [], ['folder' => $folderBaru])]);
                }
            }

            // Tambah gambar baru
            foreach ($files as $file) {
                if (!$file instanceof \Illuminate\Http\UploadedFile || !$file->isValid()) continue;

                $media = $berita->addMedia($file)
                                ->usingName(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                                ->toMediaCollection('gambar');

                $tempPath = $media->getPath();
                $newPath = storage_path('app/public/' . $folderBaru . '/' . $media->file_name);

                if (file_exists($tempPath)) {
                    if (file_exists($newPath)) unlink($newPath);
                    rename($tempPath, $newPath);

                    $oldDir = dirname($tempPath);
                    if (is_dir($oldDir) && count(scandir($oldDir)) == 2) rmdir($oldDir);
                }

                $media->update([
                    'custom_properties' => [
                        'folder'        => $folderBaru,
                        'original_name' => $file->getClientOriginalName(),
                    ],
                ]);
            }
        }

        $mediaCount = $berita->getMedia('gambar')->count();
        Log::info("UPDATE Berita ID {$berita->id} - Jumlah media: {$mediaCount}");

        return response()->json(['success' => true, 'message' => 'Berita diperbarui!']);
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        return response()->json(['success' => true, 'message' => 'Berita dihapus!']);
    }
}