<?php
// app/Http/Controllers/Admin/Qurban/QurbanPaketController.php

namespace App\Http\Controllers\Admin\Qurban;

use App\Http\Controllers\Controller;
use App\Interfaces\QurbanRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QurbanPaketController extends Controller
{
    protected $qurbanRepo;

    public function __construct(QurbanRepositoryInterface $qurbanRepo)
    {
        $this->qurbanRepo = $qurbanRepo;
    }

    /**
     * Tampilkan halaman index paket qurban
     */
    public function index()
    {
        return view('masjid.' . masjid() . '.admin.qurban.paket');
    }

    /**
     * Ambil data untuk DataTable
     */
    public function data()
    {
        $rows = $this->qurbanRepo->all();
        return response()->json(['data' => $rows]);
    }

    /**
     * Store paket qurban baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'jenis_hewan'       => ['required', 'in:sapi,kambing,kerbau'],
            'jenis_icon'        => ['nullable', 'string', 'max:10'],
            'harga'             => ['required', 'string'],
            'harga_full'        => ['nullable', 'string'],
            'max_share'         => ['required', 'integer', 'min:1', 'max:7'],
            'stok'              => ['required', 'integer', 'min:0'],
            'berat_min'         => ['nullable', 'integer', 'min:0'],
            'berat_max'         => ['nullable', 'integer', 'min:0'],
            'deskripsi_singkat' => ['nullable', 'string', 'max:255'],
            'deskripsi_lengkap' => ['nullable', 'string'],
            'urutan'            => ['nullable', 'integer', 'min:0'],
            'is_active'         => ['nullable'],
        ]);

        // Validasi berat_min tidak boleh lebih besar dari berat_max
        if (!empty($validated['berat_min']) && !empty($validated['berat_max'])) {
            if ($validated['berat_min'] > $validated['berat_max']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Berat minimal tidak boleh lebih besar dari berat maksimal'
                ], 422);
            }
        }

        try {
            DB::beginTransaction();

            $qurban = $this->qurbanRepo->create(array_merge($validated, [
                'is_active' => $request->boolean('is_active'),
            ]));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Paket Qurban berhasil ditambahkan!',
                'data'    => $qurban
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get data paket untuk edit
     */
    public function edit($id)
    {
        try {
            $qurban = $this->qurbanRepo->find($id);
            return response()->json($qurban);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Data tidak ditemukan'], 404);
        }
    }

    /**
     * Update paket qurban
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'jenis_hewan'       => ['required', 'in:sapi,kambing,kerbau'],
            'jenis_icon'        => ['nullable', 'string', 'max:10'],
            'harga'             => ['required', 'string'],
            'harga_full'        => ['nullable', 'string'],
            'max_share'         => ['required', 'integer', 'min:1', 'max:7'],
            'stok'              => ['required', 'integer', 'min:0'],
            'berat_min'         => ['nullable', 'integer', 'min:0'],
            'berat_max'         => ['nullable', 'integer', 'min:0'],
            'deskripsi_singkat' => ['nullable', 'string', 'max:255'],
            'deskripsi_lengkap' => ['nullable', 'string'],
            'urutan'            => ['nullable', 'integer', 'min:0'],
            'is_active'         => ['nullable'],
        ]);

        // Validasi berat_min tidak boleh lebih besar dari berat_max
        if (!empty($validated['berat_min']) && !empty($validated['berat_max'])) {
            if ($validated['berat_min'] > $validated['berat_max']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Berat minimal tidak boleh lebih besar dari berat maksimal'
                ], 422);
            }
        }

        try {
            DB::beginTransaction();

            $qurban = $this->qurbanRepo->update($id, array_merge($validated, [
                'is_active' => $request->boolean('is_active'),
            ]));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Paket Qurban berhasil diperbarui!',
                'data'    => $qurban
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete paket qurban
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $deleted = $this->qurbanRepo->delete($id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Paket Qurban berhasil dihapus!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update stok paket (quick update)
     */
    public function updateStok(Request $request, $id)
    {
        $request->validate([
            'stok' => ['required', 'integer', 'min:0']
        ]);

        try {
            $qurban = $this->qurbanRepo->find($id);
            $qurban->update(['stok' => $request->stok]);

            return response()->json([
                'success' => true,
                'message' => 'Stok berhasil diperbarui!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui stok: ' . $e->getMessage()
            ], 500);
        }
    }
}