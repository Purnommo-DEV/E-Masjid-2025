<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\EvaluasiQurban;
use Illuminate\Http\Request;

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
}