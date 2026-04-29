<?php
// app/Http/Controllers/Admin/Qurban/QurbanRegistrasiController.php

namespace App\Http\Controllers\Admin\Qurban;

use App\Http\Controllers\Controller;
use App\Interfaces\QurbanRegistrasiRepositoryInterface;
use App\Models\QurbanRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QurbanRegistrasiController extends Controller
{
    protected $registrasiRepo;

    public function __construct(QurbanRegistrasiRepositoryInterface $registrasiRepo)
    {
        $this->registrasiRepo = $registrasiRepo;
    }

    /**
     * Tampilkan halaman daftar pendaftar qurban
     */
    public function index()
    {
        $statistics = $this->registrasiRepo->getStatistics();
        return view('masjid.' . masjid() . '.admin.qurban.registrasi', compact('statistics'));
    }

    /**
     * Ambil data untuk DataTable
     */
    public function data(Request $request)
    {
        try {
            $status = $request->get('status', 'all');
            $search = $request->get('search');
            $rows = $this->registrasiRepo->all($status, $search);
            
            // Debug: Log response
            \Log::info('Data response: ', ['count' => count($rows), 'data' => $rows->take(2)]);
            
            return response()->json([
                'data' => $rows,
                'draw' => intval($request->get('draw', 1)),
                'recordsTotal' => $rows->count(),
                'recordsFiltered' => $rows->count()
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error in registrasi data: ' . $e->getMessage());
            return response()->json([
                'data' => [],
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detail pendaftaran
     */
    public function show($id)
    {
        try {
            $registration = $this->registrasiRepo->find($id);
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $registration->id,
                    'kode_registrasi' => $registration->kode_registrasi,
                    'nama_lengkap' => $registration->nama_lengkap,
                    'email' => $registration->email ?? '-',
                    'telepon' => $registration->telepon,
                    'alamat' => $registration->alamat ?? '-',
                    'qurban' => [
                        'jenis' => $registration->qurban->jenis_label ?? '-',
                        'share_badge' => $registration->qurban->share_badge ?? '-',
                        'harga' => $registration->qurban->harga_formatted ?? '-',
                    ],
                    'jumlah_share' => $registration->jumlah_share,
                    'total_harga' => $registration->total_harga_formatted,
                    'catatan' => $registration->catatan ?? '-',
                    'status' => $registration->status,
                    'status_badge' => $registration->status_badge,
                    'status_text' => $registration->status_text,
                    'tanggal_daftar' => $registration->tanggal_daftar,
                    'confirmed_at' => $registration->confirmed_at ? $registration->confirmed_at->format('d/m/Y H:i') : '-',
                    'confirmed_by' => $registration->confirmer ? $registration->confirmer->name : '-',
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Update status pendaftaran
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => ['required', 'in:pending,confirmed,cancelled,completed']
        ]);

        try {
            DB::beginTransaction();
            
            $registration = $this->registrasiRepo->updateStatus($id, $request->status);
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Status berhasil diperbarui!',
                'data' => [
                    'status' => $registration->status,
                    'status_badge' => $registration->status_badge
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hapus pendaftaran (soft delete atau hard delete)
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            
            $registration = $this->registrasiRepo->find($id);
            
            // Kembalikan stok jika statusnya pending atau confirmed
            if (in_array($registration->status, ['pending', 'confirmed'])) {
                $registration->qurban->increment('stok', $registration->jumlah_share);
            }
            
            $registration->delete();
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pendaftaran berhasil dihapus!'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export data ke Excel
     */
    public function export(Request $request)
    {
        $status = $request->get('status', 'all');
        $data = $this->registrasiRepo->exportData($status);
        
        // Implementasi export ke Excel bisa menggunakan Maatwebsite Excel
        // return Excel::download(new QurbanExport($data), 'pendaftar-qurban.xlsx');
        
        return response()->json([
            'success' => true,
            'message' => 'Export akan diproses',
            'data_count' => $data->count()
        ]);
    }

    public function uploadBukti(Request $request, $id)
    {
        $request->validate([
            'bukti_pembayaran' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']
        ]);

        try {
            $registration = $this->registrasiRepo->uploadBukti($id, $request->file('bukti_pembayaran'));
            
            return response()->json([
                'success' => true,
                'message' => 'Bukti pembayaran berhasil diupload!',
                'path' => $registration->bukti_pembayaran_url
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal upload: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ Hapus bukti pembayaran
     */
    public function deleteBukti($id)
    {
        try {
            $this->registrasiRepo->deleteBukti($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Bukti pembayaran berhasil dihapus!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal hapus: ' . $e->getMessage()
            ], 500);
        }
    }
}