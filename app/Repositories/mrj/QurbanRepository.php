<?php
// app/Repositories/mrj/QurbanRepository.php

namespace App\Repositories\mrj;

use App\Interfaces\QurbanRepositoryInterface;
use App\Models\Qurban;
use Illuminate\Support\Facades\DB;

class QurbanRepository implements QurbanRepositoryInterface
{
    /**
     * Ambil semua qurban untuk DataTable
     */
    public function all()
    {
        return Qurban::withoutGlobalScope('masjid')
            ->where('masjid_code', masjid())
            ->orderBy('urutan')
            ->orderBy('id')
            ->get()
            ->map(function ($qurban) {
                return [
                    'id'                => $qurban->id,
                    'jenis_hewan'       => $qurban->jenis_label,
                    'icon'              => $qurban->jenis_icon,
                    'share'             => $qurban->share_badge,
                    'harga'             => $qurban->harga_formatted,
                    'harga_full'        => $qurban->harga_full_formatted,
                    'stok'              => $qurban->stok,
                    'urutan'            => $qurban->urutan,
                    'berat'             => $qurban->berat_range,
                    'is_active'         => $qurban->is_active,
                    'status'            => $this->renderStatusBadge($qurban->is_active),
                    'action'            => $this->renderActionButtons($qurban->id),
                ];
            });
    }

    /**
     * Cari qurban by ID
     */
    public function find($id)
    {
        return Qurban::withoutGlobalScope('masjid')
            ->where('masjid_code', masjid())
            ->where('id', $id)
            ->firstOrFail();
    }

    /**
     * Ambil paket aktif untuk frontend
     */
    public function getActivePakets()
    {
        return Qurban::withoutGlobalScope('masjid')
            ->where('masjid_code', masjid())
            ->where('is_active', true)
            ->where('stok', '>', 0)
            ->orderBy('urutan')
            ->get();
    }

    /**
     * Update stok qurban
     */
    public function updateStok($id, $jumlah)
    {
        $qurban = $this->find($id);
        $qurban->decrement('stok', $jumlah);
        return $qurban;
    }

    /**
     * Create qurban baru
     */
    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            return Qurban::create([
                'masjid_code'        => masjid(),
                'jenis_hewan'        => $data['jenis_hewan'],
                'jenis_icon'         => $data['jenis_icon'] ?? $this->getIconByJenis($data['jenis_hewan']),
                'harga'              => str_replace('.', '', $data['harga']),
                'harga_full'         => isset($data['harga_full']) ? str_replace('.', '', $data['harga_full']) : null,
                'max_share'          => $data['max_share'],
                'stok'               => $data['stok'],
                'berat_min'          => $data['berat_min'] ?? null,
                'berat_max'          => $data['berat_max'] ?? null,
                'deskripsi_singkat'  => $data['deskripsi_singkat'] ?? null,
                'deskripsi_lengkap'  => $data['deskripsi_lengkap'] ?? null,
                'is_active'          => $data['is_active'] ?? true,
                'urutan'             => $data['urutan'] ?? 0,
                'created_by'         => auth()->id(),
            ]);
        });
    }

    /**
     * Update qurban
     */
    public function update($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $qurban = $this->find($id);
            
            $qurban->update([
                'jenis_hewan'        => $data['jenis_hewan'],
                'jenis_icon'         => $data['jenis_icon'] ?? $this->getIconByJenis($data['jenis_hewan']),
                'harga'              => str_replace('.', '', $data['harga']),
                'harga_full'         => isset($data['harga_full']) ? str_replace('.', '', $data['harga_full']) : null,
                'max_share'          => $data['max_share'],
                'stok'               => $data['stok'],
                'berat_min'          => $data['berat_min'] ?? null,
                'berat_max'          => $data['berat_max'] ?? null,
                'deskripsi_singkat'  => $data['deskripsi_singkat'] ?? null,
                'deskripsi_lengkap'  => $data['deskripsi_lengkap'] ?? null,
                'is_active'          => $data['is_active'] ?? true,
                'urutan'             => $data['urutan'] ?? 0,
                'updated_by'         => auth()->id(),
            ]);
            
            return $qurban->fresh();
        });
    }

    /**
     * Delete qurban
     */
    public function delete($id)
    {
        return DB::transaction(function () use ($id) {
            $qurban = $this->find($id);
            
            // Cek apakah ada registrasi yang pending/confirmed
            $hasActiveRegistrations = $qurban->registrations()
                ->whereIn('status', ['pending', 'confirmed'])
                ->exists();
                
            if ($hasActiveRegistrations) {
                throw new \Exception('Tidak dapat menghapus paket yang memiliki pendaftaran aktif.');
            }
            
            return $qurban->delete();
        });
    }

    /**
     * Get icon by jenis hewan
     */
    protected function getIconByJenis($jenis)
    {
        $icons = [
            'sapi' => '🐂',
            'kambing' => '🐐',
            'kerbau' => '🐃',
        ];
        return $icons[$jenis] ?? '🐐';
    }

    /**
     * Render status badge HTML
     */
    protected function renderStatusBadge(bool $isActive): string
    {
        $badgeClass = $isActive ? 'bg-success' : 'bg-secondary';
        $statusText = $isActive ? 'Aktif' : 'Nonaktif';
        return '<span class="badge ' . $badgeClass . '">' . $statusText . '</span>';
    }

    /**
     * Render action buttons HTML
     */
    protected function renderActionButtons($id): string
    {
        return '
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-warning edit-qurban" data-id="' . $id . '" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button type="button" class="btn btn-danger delete-qurban" data-id="' . $id . '" title="Hapus">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        ';
    }
}