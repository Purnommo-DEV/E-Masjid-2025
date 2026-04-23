<?php

namespace App\Repositories\mrj;

use App\Interfaces\AcaraRepositoryInterface;
use App\Models\Acara;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AcaraRepository implements AcaraRepositoryInterface
{
    public function all()
    {
        return Acara::withoutGlobalScope('masjid')
            ->where('masjid_code', masjid())
            ->with('kategoris')
            ->latest()
            ->get()
            ->map(function ($acara) {
                return [
                    'id' => $acara->id,
                    'judul' => $acara->judul,
                    'poster' => $this->renderPosterHtml($acara), // HTML untuk DataTable
                    'kategoris' => $acara->kategoris->map(fn($k) => 
                        '<span class="badge me-1" style="background:' . ($k->warna ?? '#6c757d') . '">' . $k->nama . '</span>'
                    )->implode(''),
                    'tanggal' => $acara->mulai->format('d/m/Y H:i') . ($acara->selesai ? ' - ' . $acara->selesai->format('d/m/Y H:i') : ''),
                    'status' => '<span class="badge ' . ($acara->is_published ? 'bg-success' : 'bg-warning') . '">'
                        . ($acara->is_published ? 'Published' : 'Draft') . '</span>'
                ];
            });
    }

    public function find($id)
    {
        $acara = Acara::withoutGlobalScope('masjid')
            ->where('masjid_code', masjid())
            ->with('kategoris')
            ->findOrFail($id);
        
        // Return data termasuk image_path untuk edit
        return $acara;
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $acara = Acara::create([
                'masjid_code'    => masjid(),
                'judul'          => $data['judul'],
                'slug'           => Str::slug($data['judul']),
                'deskripsi'      => $data['deskripsi'] ?? null,
                'mulai'          => $data['mulai'],
                'selesai'        => $data['selesai'] ?? null,
                'lokasi'         => $data['lokasi'] ?? null,
                'penyelenggara'  => $data['penyelenggara'] ?? null,
                'pemateri'       => $data['pemateri'] ?? null,
                'waktu_teks'     => $data['waktu_teks'] ?? null,
                'is_published'   => $data['is_published'] ?? false,
                'published_at'   => ($data['is_published'] ?? false) ? now() : null,
                'created_by'     => auth()->id(),
            ]);

            // Sync kategori
            if (isset($data['kategori_ids']) && is_array($data['kategori_ids'])) {
                $acara->kategoris()->sync($data['kategori_ids']);
            }

            // Upload poster jika ada (konversi ke WebP)
            if (!empty($data['poster']) && $data['poster'] instanceof UploadedFile) {
                $imagePath = upload_image($data['poster'], 'acara', null, true);
                $acara->update(['image_path' => $imagePath]);
            }

            return $acara->fresh();
        });
    }

    public function update($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $acara = $this->find($id);

            $updateData = [
                'judul'          => $data['judul'],
                'slug'           => Str::slug($data['judul']),
                'deskripsi'      => $data['deskripsi'] ?? null,
                'mulai'          => $data['mulai'],
                'selesai'        => $data['selesai'] ?? null,
                'lokasi'         => $data['lokasi'] ?? null,
                'penyelenggara'  => $data['penyelenggara'] ?? null,
                'pemateri'       => $data['pemateri'] ?? null,
                'waktu_teks'     => $data['waktu_teks'] ?? null,
                'is_published'   => $data['is_published'] ?? false,
                'updated_by'     => auth()->id(),
            ];

            // Handle published_at
            if (($data['is_published'] ?? false) && !$acara->is_published) {
                $updateData['published_at'] = now();
            }

            $acara->update($updateData);

            // Sync kategori
            if (isset($data['kategori_ids']) && is_array($data['kategori_ids'])) {
                $acara->kategoris()->sync($data['kategori_ids']);
            }

            // Upload poster baru jika ada
            if (!empty($data['poster']) && $data['poster'] instanceof UploadedFile) {
                $imagePath = upload_image($data['poster'], 'acara', $acara->image_path, true);
                $acara->update(['image_path' => $imagePath]);
            }

            return $acara->fresh();
        });
    }

    public function delete($id)
    {
        return DB::transaction(function () use ($id) {
            $acara = $this->find($id);

            // Hapus file poster
            if ($acara->image_path) {
                delete_image($acara->image_path);
            }

            // Hapus relasi kategori
            $acara->kategoris()->detach();

            return $acara->delete();
        });
    }

    protected function renderPosterHtml(Acara $acara): string
    {
        $imageUrl = get_image_url($acara->image_path);

        if ($imageUrl) {
            return '<img src="' . $imageUrl . '" 
                           width="60" 
                           height="40" 
                           class="rounded shadow-sm" 
                           style="height: 40px; object-fit: cover;"
                           loading="lazy">';
        }

        return '<small class="text-muted">Tanpa Gambar</small>';
    }
}