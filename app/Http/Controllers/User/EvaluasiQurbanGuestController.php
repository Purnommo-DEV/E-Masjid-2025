<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\EvaluasiQurban;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class EvaluasiQurbanGuestController extends Controller
{
    public function index()
    {
        $tahun = '1447 H';
        $data = [
            'title' => 'Kritik, Saran, dan Evaluasi Qurban 1447 H - Masjid Raudhatul Jannah',
            'subtitle' => '1447 H',
            'hero_title' => 'Kritik, Saran, dan Evaluasi Qurban',
            'hero_subtitle' => 'Masjid Raudhatul Jannah - Taman Cipulir Estate',
            'masjid_code' => masjid() // 'mrj'
        ];
        
        return view('masjid.' . masjid() . '.guest.program-qurban.form-evaluasi', compact('tahun', 'data'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_shohibul' => 'required|string|max:255',
            'jenis_hewan' => 'required|in:sapi,kambing',
            'sumber_info' => 'nullable|string|max:500', // karena bisa berupa string gabungan
            'masukan_penyebaran_informasi' => 'nullable|string',
            'tempat_qurban' => 'required|in:masjid,pihak_lain,keduanya',
            'rating_pendaftaran' => 'required|integer|min:1|max:5',
            'rating_pelaksanaan' => 'required|integer|min:1|max:5',
            'rating_distribusi' => 'required|integer|min:1|max:5',
            'rating_kualitas_hewan' => 'required|integer|min:1|max:5',
            'saran_pendaftaran' => 'nullable|string',
            'saran_pelaksanaan' => 'nullable|string',
            'saran_distribusi' => 'nullable|string',
            'saran_kualitas_hewan' => 'nullable|string',
            'hal_baik' => 'nullable|string',
            'hal_perbaikan' => 'nullable|string',
            'saran_tambahan' => 'nullable|string',
            'rencana_qurban' => 'required|in:ya,mungkin,tidak',
            'tahun_hijriah' => 'nullable|string',
        ]);

        $evaluasi = EvaluasiQurban::create([
            'nama_shohibul' => $request->nama_shohibul,
            'jenis_hewan' => $request->jenis_hewan,
            'sumber_info' => $request->sumber_info,
            'masukan_penyebaran_informasi' => $request->masukan_penyebaran_informasi,
            'tempat_qurban' => $request->tempat_qurban,
            'rating_pendaftaran' => $request->rating_pendaftaran,
            'rating_pelaksanaan' => $request->rating_pelaksanaan,
            'rating_distribusi' => $request->rating_distribusi,
            'rating_kualitas_hewan' => $request->rating_kualitas_hewan,
            'saran_pendaftaran' => $request->saran_pendaftaran,
            'saran_pelaksanaan' => $request->saran_pelaksanaan,
            'saran_distribusi' => $request->saran_distribusi,
            'saran_kualitas_hewan' => $request->saran_kualitas_hewan,
            'hal_baik' => $request->hal_baik,
            'hal_perbaikan' => $request->hal_perbaikan,
            'saran_tambahan' => $request->saran_tambahan,
            'rencana_qurban' => $request->rencana_qurban,
            'tahun_hijriah' => $request->tahun_hijriah ?? '1447 H',
            'masjid_code' => masjid(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Terima kasih atas partisipasi dan masukannya! Semoga ibadah qurban kita diterima Allah SWT.',
            'data' => $evaluasi
        ]);
    }

    public function evaluasi()
    {
        $tahunList = EvaluasiQurban::select('tahun_hijriah')
            ->distinct()
            ->orderBy('tahun_hijriah', 'desc')
            ->pluck('tahun_hijriah')
            ->toArray();
        
        // Optional: tambahkan data statistik untuk hero section
        $totalData = EvaluasiQurban::count();
        $totalTahun = count($tahunList);
        
        return view('masjid.' . masjid() . '.guest.program-qurban.evaluasi', compact('tahunList', 'totalData', 'totalTahun'));
    }

    public function data(Request $request)
    {
        $evaluasi = EvaluasiQurban::latest();

        if ($request->tahun) {
            $evaluasi->where('tahun_hijriah', $request->tahun);
        }

        return DataTables::of($evaluasi)
            ->addColumn('rating_pendaftaran_star', fn($row) => $row->rating_pendaftaran_bintang)
            ->addColumn('rating_pelaksanaan_star', fn($row) => $row->rating_pelaksanaan_bintang)
            ->addColumn('rating_distribusi_star', fn($row) => $row->rating_distribusi_bintang)
            ->addColumn('rating_kualitas_star', fn($row) => $row->rating_kualitas_bintang)
            ->addColumn('sumber_info_text', fn($row) => $row->sumber_info_text ?? '-')
            ->addColumn('rencana_qurban_text', function($row) {
                $text = [
                    'ya' => 'Ya',
                    'mungkin' => 'Mungkin',
                    'tidak' => 'Tidak'
                ];
                return $text[$row->rencana_qurban] ?? '-';
            })
            ->addColumn('wish_pelaksanaan_text', fn($row) => $row->wish_pelaksanaan ?? '-')
            ->addColumn('wish_distribusi_text', fn($row) => $row->wish_distribusi ?? '-')
            ->addColumn('aksi', function($row) {
                return '<button class="btn-detail" onclick="detailEvaluasi(' . $row->id . ')"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>';
            })
            ->rawColumns(['rating_pendaftaran_star', 'rating_pelaksanaan_star', 'rating_distribusi_star', 'rating_kualitas_star', 'aksi'])
            ->make(true);
    }

    /**
     * Detail evaluasi publik
     */
    public function show($id)
    {
        $evaluasi = EvaluasiQurban::findOrFail($id);
        return response()->json($evaluasi);
    }
}