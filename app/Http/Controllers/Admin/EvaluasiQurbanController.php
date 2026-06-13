<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EvaluasiQurban;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;

class EvaluasiQurbanController extends Controller
{
    /**
     * Halaman utama data evaluasi
     */
    public function index()
    {
        return view('masjid.' . masjid() . '.admin.evaluasi_qurban.index');
    }

    /**
     * Data untuk DataTables
     */
    public function data(Request $request)
    {
        $evaluasi = EvaluasiQurban::latest();

        // Filter berdasarkan tahun jika ada
        if ($request->tahun) {
            $evaluasi->where('tahun_hijriah', $request->tahun);
        }

        return DataTables::of($evaluasi)
            ->addColumn('rating_pendaftaran_star', fn($row) => $row->rating_pendaftaran_bintang)
            ->addColumn('rating_pelaksanaan_star', fn($row) => $row->rating_pelaksanaan_bintang)
            ->addColumn('rating_distribusi_star', fn($row) => $row->rating_distribusi_bintang)
            ->addColumn('rating_kualitas_star', fn($row) => $row->rating_kualitas_bintang)
            ->addColumn('sumber_info_text', fn($row) => $row->sumber_info_text)
            ->addColumn('tempat_qurban_text', fn($row) => $row->tempat_qurban_text)
            ->addColumn('rencana_qurban_badge', fn($row) => $row->rencana_qurban_badge)
            ->addColumn('aksi', function($row) {
                return '
                    <div class="flex gap-2 justify-center">
                        <button class="btn btn-sm btn-outline-info" onclick="detailEvaluasi(' . $row->id . ')" title="Detail">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="hapusEvaluasi(' . $row->id . ')" title="Hapus">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 6h18M8 6v12a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V6"/>
                            </svg>
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['rating_pendaftaran_star', 'rating_pelaksanaan_star', 'rating_distribusi_star', 'rating_kualitas_star', 'rencana_qurban_badge', 'aksi'])
            ->make(true);
    }

    /**
     * Detail evaluasi (modal)
     */
    public function show($id)
    {
        $evaluasi = EvaluasiQurban::findOrFail($id);
        return response()->json($evaluasi);
    }

    /**
     * Halaman statistik
     */
    public function statistik()
    {
        $tahunSekarang = '1447 H';
        $tahunList = EvaluasiQurban::select('tahun_hijriah')
            ->distinct()
            ->pluck('tahun_hijriah')
            ->toArray();
        
        $total = EvaluasiQurban::count();
        
        if ($total == 0) {
            $statistik = [
                'total' => 0,
                'rata_pendaftaran' => 0,
                'rata_pelaksanaan' => 0,
                'rata_distribusi' => 0,
                'rata_kualitas' => 0,
                'rencana_ya' => 0,
                'rencana_mungkin' => 0,
                'rencana_tidak' => 0,
                'sapi' => 0,
                'kambing' => 0,
                'tempat_masjid' => 0,
                'tempat_pihak_lain' => 0,
                'tempat_keduanya' => 0,
            ];
        } else {
            $statistik = [
                'total' => $total,
                'rata_pendaftaran' => round(EvaluasiQurban::avg('rating_pendaftaran'), 2),
                'rata_pelaksanaan' => round(EvaluasiQurban::avg('rating_pelaksanaan'), 2),
                'rata_distribusi' => round(EvaluasiQurban::avg('rating_distribusi'), 2),
                'rata_kualitas' => round(EvaluasiQurban::avg('rating_kualitas_hewan'), 2),
                'rencana_ya' => EvaluasiQurban::where('rencana_qurban', 'ya')->count(),
                'rencana_mungkin' => EvaluasiQurban::where('rencana_qurban', 'mungkin')->count(),
                'rencana_tidak' => EvaluasiQurban::where('rencana_qurban', 'tidak')->count(),
                'sapi' => EvaluasiQurban::where('jenis_hewan', 'sapi')->count(),
                'kambing' => EvaluasiQurban::where('jenis_hewan', 'kambing')->count(),
                'tempat_masjid' => EvaluasiQurban::where('tempat_qurban', 'masjid')->count(),
                'tempat_pihak_lain' => EvaluasiQurban::where('tempat_qurban', 'pihak_lain')->count(),
                'tempat_keduanya' => EvaluasiQurban::where('tempat_qurban', 'keduanya')->count(),
            ];
        }

        return view('masjid.' . masjid() . '.admin.evaluasi_qurban.statistik', compact('statistik', 'tahunList', 'tahunSekarang'));
    }

    /**
     * Hapus data evaluasi
     */
    public function destroy($id)
    {
        $evaluasi = EvaluasiQurban::findOrFail($id);
        $evaluasi->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Data evaluasi berhasil dihapus'
        ]);
    }

    /**
     * Export ke Excel (opsional)
     */
    public function export()
    {
        $data = EvaluasiQurban::latest()->get();
        
        // Load data ke excel (gunakan Maatwebsite/Excel jika perlu)
        // return Excel::download(new EvaluasiQurbanExport, 'evaluasi_qurban.xlsx');
        
        return response()->json(['message' => 'Fitur export sedang dalam pengembangan']);
    }
}