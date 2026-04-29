<?php

namespace App\Repositories\mrj;

use App\Interfaces\QurbanGalleryRepositoryInterface;
use App\Models\Qurban;
use App\Models\QurbanGallery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class QurbanGalleryRepository implements QurbanGalleryRepositoryInterface
{
    public function all()
    {
        return QurbanGallery::withoutGlobalScope('masjid')
            ->where('masjid_code', masjid())
            ->with('qurban')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findByPaket($paketId)
    {
        return QurbanGallery::withoutGlobalScope('masjid')
            ->where('masjid_code', masjid())
            ->where('qurban_id', $paketId)
            ->orderBy('urutan')
            ->get();
    }

    public function find($id)
    {
        return QurbanGallery::withoutGlobalScope('masjid')
            ->where('masjid_code', masjid())
            ->findOrFail($id);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Upload image
            $imagePath = $this->uploadImage($data['image'], $data['slug']);
            
            // Hitung urutan
            $urutan = QurbanGallery::where('qurban_id', $data['qurban_id'])->count();
            
            // Cek apakah ini gambar pertama
            $isFirst = ($urutan === 0);
            
            // Simpan gallery
            $gallery = QurbanGallery::create([
                'qurban_id'   => $data['qurban_id'],
                'masjid_code' => masjid(),
                'image_path'  => $imagePath,
                'title'       => $data['title'] ?? null,
                'description' => $data['description'] ?? null,
                'is_cover'    => $data['is_cover'] ?? $isFirst,
                'urutan'      => $urutan,
                'created_by'  => auth()->id(),
            ]);
            
            // Update cover di qurban
            if ($isFirst || ($data['is_cover'] ?? false)) {
                Qurban::where('id', $data['qurban_id'])->update(['image_path' => $imagePath]);
            }
            
            return $gallery;
        });
    }

    public function update($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $gallery = $this->find($id);
            $oldIsCover = $gallery->is_cover;
            
            // Update gallery
            $gallery->update([
                'title'       => $data['title'] ?? $gallery->title,
                'description' => $data['description'] ?? $gallery->description,
                'is_cover'    => $data['is_cover'] ?? $gallery->is_cover,
                'urutan'      => $data['urutan'] ?? $gallery->urutan,
                'updated_by'  => auth()->id(),
            ]);
            
            // Jika dijadikan cover, reset cover lain dan update qurban
            if (($data['is_cover'] ?? false) && !$oldIsCover) {
                QurbanGallery::where('qurban_id', $gallery->qurban_id)
                    ->where('id', '!=', $id)
                    ->update(['is_cover' => false]);
                    
                Qurban::where('id', $gallery->qurban_id)->update(['image_path' => $gallery->image_path]);
            }
            
            return $gallery;
        });
    }

    public function delete($id)
    {
        return DB::transaction(function () use ($id) {
            $gallery = $this->find($id);
            
            // Hapus file
            delete_image($gallery->image_path);
            
            // Hapus record
            $gallery->delete();
            
            // Jika yang dihapus adalah cover, set cover ke gambar pertama
            if ($gallery->is_cover) {
                $newCover = QurbanGallery::where('qurban_id', $gallery->qurban_id)
                    ->orderBy('urutan')
                    ->first();
                    
                if ($newCover) {
                    $newCover->update(['is_cover' => true]);
                    Qurban::where('id', $gallery->qurban_id)->update(['image_path' => $newCover->image_path]);
                } else {
                    Qurban::where('id', $gallery->qurban_id)->update(['image_path' => null]);
                }
            }
            
            return true;
        });
    }

    public function reorder($paketId, array $order)
    {
        foreach ($order as $index => $galleryId) {
            QurbanGallery::where('id', $galleryId)
                ->where('qurban_id', $paketId)
                ->update(['urutan' => $index]);
        }
        return true;
    }

    public function setCover($paketId, $galleryId)
    {
        return DB::transaction(function () use ($paketId, $galleryId) {
            // Reset semua cover
            QurbanGallery::where('qurban_id', $paketId)->update(['is_cover' => false]);
            
            // Set cover baru
            $gallery = QurbanGallery::findOrFail($galleryId);
            $gallery->update(['is_cover' => true]);
            
            // Update cover di qurban
            Qurban::where('id', $paketId)->update(['image_path' => $gallery->image_path]);
            
            return $gallery;
        });
    }

    protected function uploadImage($file, $slug)
    {
        return upload_image($file, "qurban/{$slug}", null, true);
    }
}