<?php
// app/Repositories/mrj/QurbanRegistrasiRepository.php

namespace App\Repositories\mrj;

use App\Interfaces\QurbanRegistrasiRepositoryInterface;
use App\Models\QurbanRegistration;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class QurbanRegistrasiRepository implements QurbanRegistrasiRepositoryInterface
{
    /**
     * Ambil semua data registrasi
     */
    public function all($status = null, $search = null)
    {
        $query = QurbanRegistration::with('qurban')
            ->where('masjid_code', masjid())
            ->latest();
            
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('kode_registrasi', 'like', "%{$search}%")
                  ->orWhere('telepon', 'like', "%{$search}%");
            });
        }
        
        return $query->get()->map(function ($reg) {
            return [
                'id' => $reg->id,
                'kode_registrasi' => $reg->kode_registrasi,
                'nama_lengkap' => $reg->nama_lengkap,
                'telepon' => $reg->telepon,
                'email' => $reg->email ?? '-',
                'jenis_hewan' => $reg->qurban->jenis_label ?? '-',
                'jenis_icon' => $reg->qurban->jenis_icon ?? '🐐',
                'jumlah_share' => $reg->jumlah_share,
                'total_harga' => $reg->total_harga_formatted,
                'status' => $reg->status,
                'status_badge' => $reg->status_badge,
                'bukti_pembayaran' => $reg->bukti_pembayaran_url,
                'catatan' => $reg->catatan,
                'tanggal_daftar' => $reg->tanggal_daftar,
                'uploaded_at' => $reg->uploaded_at ? $reg->uploaded_at->format('d/m/Y H:i') : null,
            ];
        });
    }

    /**
     * Cari registrasi by ID
     */
    public function find($id)
    {
        return QurbanRegistration::with('qurban', 'confirmer', 'uploader')
            ->where('masjid_code', masjid())
            ->where('id', $id)
            ->firstOrFail();
    }

    /**
     * Update status registrasi
     */
    public function updateStatus($id, $status)
    {
        return DB::transaction(function () use ($id, $status) {
            $registration = $this->find($id);
            $oldStatus = $registration->status;
            $registration->status = $status;
            
            if ($status === 'confirmed' && !$registration->confirmed_at) {
                $registration->confirmed_at = now();
                $registration->confirmed_by = auth()->id();
            }
            
            // Jika dibatalkan, kembalikan stok
            if ($status === 'cancelled' && $oldStatus !== 'cancelled') {
                $qurban = $registration->qurban;
                $qurban->increment('stok', $registration->jumlah_share);
            }
            
            // Jika dari cancelled ke confirmed, kurangi stok lagi
            if ($oldStatus === 'cancelled' && $status === 'confirmed') {
                $qurban = $registration->qurban;
                $qurban->decrement('stok', $registration->jumlah_share);
            }
            
            $registration->save();
            return $registration;
        });
    }

    /**
     * Get statistics
     */
    public function getStatistics()
    {
        $query = QurbanRegistration::where('masjid_code', masjid());
        
        return [
            'total' => (clone $query)->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'confirmed' => (clone $query)->where('status', 'confirmed')->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
            'total_nominal' => (clone $query)->sum('total_harga'),
        ];
    }

    /**
     * Get by date range
     */
    public function getByDateRange($startDate, $endDate)
    {
        return QurbanRegistration::with('qurban')
            ->where('masjid_code', masjid())
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();
    }

    /**
     * Export data
     */
    public function exportData($status = null)
    {
        $query = QurbanRegistration::with('qurban')
            ->where('masjid_code', masjid());
            
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        
        return $query->latest()->get();
    }

    /**
     * ✅ TAMBAHKAN METHOD INI - Upload bukti pembayaran
     */
    public function uploadBukti($id, UploadedFile $file)
    {
        return DB::transaction(function () use ($id, $file) {
            $registration = $this->find($id);
            
            // Upload gambar menggunakan helper (sama seperti banner)
            $imagePath = upload_image($file, 'bukti-pembayaran', $registration->bukti_pembayaran, true);
            
            $registration->update([
                'bukti_pembayaran' => $imagePath,
                'uploaded_at' => now(),
                'uploaded_by' => auth()->id(),
            ]);
            
            return $registration;
        });
    }

    /**
     * ✅ TAMBAHKAN METHOD INI - Hapus bukti pembayaran
     */
    public function deleteBukti($id)
    {
        return DB::transaction(function () use ($id) {
            $registration = $this->find($id);
            
            if ($registration->bukti_pembayaran) {
                delete_image($registration->bukti_pembayaran);
                $registration->update([
                    'bukti_pembayaran' => null,
                    'uploaded_at' => null,
                    'uploaded_by' => null,
                ]);
            }
            
            return $registration;
        });
    }
}