<?php

namespace App\Repositories\mrj;

use App\Interfaces\LayananRepositoryInterface;
use App\Models\Layanan;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class LayananRepository implements LayananRepositoryInterface
{
    public function all()
    {
        return Layanan::latest()->get()->map(function ($l) {
            return [
                'id' => $l->id,
                'judul' => $l->judul,
                'icon' => $l->icon,
                'deskripsi' => Str::limit(strip_tags($l->deskripsi ?? ''), 120),
                'urutan' => $l->urutan ?? '-',
                'status' => $l->is_active ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Nonaktif</span>',
            ];
        });
    }

    public function find($id)
    {
        return Layanan::findOrFail($id);
    }

    public function create(array $data)
    {
        $layanan = Layanan::create([
            'judul' => $data['judul'],
            'slug' => Str::slug($data['judul']),
            'icon' => $data['icon'] ?? null,
            'deskripsi' => $data['deskripsi'] ?? null,
            'is_active' => isset($data['is_active']) ? (bool)$data['is_active'] : true,
            'urutan' => $data['urutan'] ?? 0,
            'created_by' => auth()->id(),
        ]);
        return $layanan;
    }

    public function update($id, array $data)
    {
        $layanan = $this->find($id);

        $layanan->update([
            'judul' => $data['judul'],
            'slug' => Str::slug($data['judul']),
            'icon' => $data['icon'] ?? null,
            'deskripsi' => $data['deskripsi'] ?? null,
            'is_active' => isset($data['is_active']) ? (bool)$data['is_active'] : $layanan->is_active,
            'urutan' => $data['urutan'] ?? $layanan->urutan,
            'updated_by' => auth()->id(),
        ]);

        return $layanan->fresh();
    }

    public function delete($id)
    {
        $layanan = $this->find($id);
        $layanan->delete();

        return true;
    }
}
