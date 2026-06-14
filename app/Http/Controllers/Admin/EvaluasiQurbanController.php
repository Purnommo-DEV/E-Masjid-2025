<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EvaluasiQurban;
use App\Models\EvaluasiQurbanSummary;
use App\Services\mrj\GeminiAIService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class EvaluasiQurbanController extends Controller
{
    public function index()
    {
        return view('masjid.' . masjid() . '.admin.qurban.evaluasi.index');
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
            ->addColumn('sumber_info_text', function($row) {
                if ($row->sumber_info && str_contains($row->sumber_info, ',')) {
                    $items = explode(',', $row->sumber_info);
                    $display = [];
                    foreach ($items as $item) {
                        $display[] = trim($item);
                    }
                    return implode(', ', $display);
                }
                if ($row->sumber_info == 'Lainnya' && $row->sumber_info_lainnya) {
                    return $row->sumber_info_lainnya;
                }
                return $row->sumber_info ?? '-';
            })
            ->addColumn('wish_informasi_text', fn($row) => $row->wish_informasi ?? '-')
            ->addColumn('wish_pelaksanaan_text', fn($row) => $row->wish_pelaksanaan ?? '-')
            ->addColumn('wish_distribusi_text', fn($row) => $row->wish_distribusi ?? '-')
            ->addColumn('rencana_qurban_badge', function($row) {
                $badges = [
                    'ya' => '<span class="badge badge-success">Ya</span>',
                    'mungkin' => '<span class="badge badge-warning">Mungkin</span>',
                    'tidak' => '<span class="badge badge-danger">Tidak</span>',
                ];
                return $badges[$row->rencana_qurban] ?? '-';
            })
            ->addColumn('aksi', function($row) {
                return '
                    <div class="flex gap-2 justify-center">
                        <button class="action-btn action-btn-info" onclick="detailEvaluasi(' . $row->id . ')" title="Detail">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                        <button class="action-btn action-btn-warning" onclick="generateWish(' . $row->id . ')" title="Generate Wish AI">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2a10 10 0 1010 10A10 10 0 0012 2z M12 6v6l4 2"/>
                            </svg>
                        </button>
                        <button class="action-btn action-btn-danger" onclick="hapusEvaluasi(' . $row->id . ')" title="Hapus">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 6h18M8 6v12a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V6"/>
                            </svg>
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['rating_pendaftaran_star', 'rating_pelaksanaan_star', 'rating_distribusi_star', 'rating_kualitas_star', 'rencana_qurban_badge', 'wish_informasi_text', 'wish_pelaksanaan_text', 'wish_distribusi_text', 'aksi'])
            ->make(true);
    }

    public function show($id)
    {
        $evaluasi = EvaluasiQurban::findOrFail($id);
        return response()->json($evaluasi);
    }

    public function statistik()
    {
        $tahunList = EvaluasiQurban::select('tahun_hijriah')
            ->distinct()
            ->orderBy('tahun_hijriah', 'desc')
            ->pluck('tahun_hijriah')
            ->toArray();
        
        $tahunSelected = request()->get('tahun', '');
        
        return view('masjid.' . masjid() . '.admin.qurban.evaluasi.statistik', compact('tahunList', 'tahunSelected'));
    }

    public function statistikData(Request $request)
    {
        $query = EvaluasiQurban::query();
        
        if ($request->tahun) {
            $query->where('tahun_hijriah', $request->tahun);
        }
        
        $total = $query->count();
        
        return response()->json([
            'total' => $total,
            'rata_pendaftaran' => $total > 0 ? round($query->avg('rating_pendaftaran'), 2) : 0,
            'rata_pelaksanaan' => $total > 0 ? round($query->avg('rating_pelaksanaan'), 2) : 0,
            'rata_distribusi' => $total > 0 ? round($query->avg('rating_distribusi'), 2) : 0,
            'rata_kualitas' => $total > 0 ? round($query->avg('rating_kualitas_hewan'), 2) : 0,
            'rencana_ya' => $query->where('rencana_qurban', 'ya')->count(),
            'rencana_mungkin' => $query->where('rencana_qurban', 'mungkin')->count(),
            'rencana_tidak' => $query->where('rencana_qurban', 'tidak')->count(),
            'sapi' => $query->where('jenis_hewan', 'sapi')->count(),
            'kambing' => $query->where('jenis_hewan', 'kambing')->count(),
        ]);
    }

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
     * Generate wish untuk satu data (AI)
     */
    public function generateWish($id)
    {
        $evaluasi = EvaluasiQurban::findOrFail($id);
        $evaluasi->generateAllWishesWithAI();
        
        return response()->json([
            'success' => true,
            'message' => 'Wish berhasil digenerate dengan AI',
            'data' => $evaluasi
        ]);
    }

    /**
     * Generate wish untuk semua data dalam satu tahun (massal dengan AI)
     */
    public function generateAllWish(Request $request)
    {
        set_time_limit(300); // 5 menit
        ignore_user_abort(true);
        
        $tahun = $request->tahun ?? '1447 H';
        
        // CEK APAKAH API GEMINI BERFUNGSI
        $apiStatus = $this->checkGeminiStatus();
        
        if (!$apiStatus['available']) {
            return response()->json([
                'success' => false,
                'message' => '⚠️ Layanan AI (Gemini) sedang tidak tersedia. ' . $apiStatus['message'],
                'api_status' => $apiStatus,
                'fallback_used' => false
            ], 503);
        }
        
        $evaluasi = EvaluasiQurban::where('tahun_hijriah', $tahun)->get();
        $count = 0;
        $errors = 0;
        
        foreach ($evaluasi as $item) {
            try {
                $item->generateAllWishesWithAI();
                $count++;
                usleep(500000); // delay 0.5 detik
            } catch (\Exception $e) {
                $errors++;
                \Log::error('Generate wish error for ID ' . $item->id . ': ' . $e->getMessage());
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => "{$count} data wish berhasil digenerate (AI), {$errors} gagal untuk tahun {$tahun}",
            'api_status' => $apiStatus
        ]);
    }

    /**
     * Cek status API Gemini
     */
    private function checkGeminiStatus()
    {
        $apiKey = env('GEMINI_API_KEY');
        $model = env('GEMINI_DEFAULT_MODEL', 'models/gemini-2.0-flash');
        
        $url = "https://generativelanguage.googleapis.com/v1beta/{$model}:generateContent?key={$apiKey}";
        
        $data = [
            'contents' => [
                ['parts' => [['text' => 'Test']]]
            ]
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return [
                'available' => true,
                'code' => 200,
                'message' => '✅ AI Gemini tersedia dan siap digunakan'
            ];
        } elseif ($httpCode === 429) {
            return [
                'available' => false,
                'code' => 429,
                'message' => '❌ Kuota API Gemini habis hari ini. Coba lagi besok atau gunakan fallback manual.'
            ];
        } elseif ($httpCode === 401) {
            return [
                'available' => false,
                'code' => 401,
                'message' => '❌ API Key Gemini tidak valid. Periksa konfigurasi di file .env'
            ];
        } else {
            return [
                'available' => false,
                'code' => $httpCode,
                'message' => "❌ API Gemini error (HTTP {$httpCode}). Periksa koneksi atau coba lagi nanti."
            ];
        }
    }

    /**
     * Cek status API Gemini (online/offline)
     */
    public function checkAIStatus()
    {
        $apiKey = env('GEMINI_API_KEY');
        $model = env('GEMINI_DEFAULT_MODEL', 'models/gemini-2.0-flash');
        
        if (empty($apiKey)) {
            return response()->json([
                'available' => false,
                'message' => 'API Key tidak dikonfigurasi'
            ]);
        }
        
        $url = "https://generativelanguage.googleapis.com/v1beta/{$model}:generateContent?key={$apiKey}";
        
        $data = [
            'contents' => [
                ['parts' => [['text' => 'Test']]]
            ]
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return response()->json([
                'available' => true,
                'code' => 200,
                'message' => '✅ AI Gemini tersedia'
            ]);
        } elseif ($httpCode === 429) {
            return response()->json([
                'available' => false,
                'code' => 429,
                'message' => '❌ Kuota API Gemini habis hari ini'
            ]);
        } elseif ($httpCode === 401 || $httpCode === 403) {
            return response()->json([
                'available' => false,
                'code' => $httpCode,
                'message' => '❌ API Key Gemini tidak valid'
            ]);
        } else {
            return response()->json([
                'available' => false,
                'code' => $httpCode,
                'message' => "❌ API Gemini error (HTTP {$httpCode})"
            ]);
        }
    }

    /**
     * Generate ringkasan keseluruhan menggunakan AI dan simpan ke database
     */
    public function generateSummary(Request $request)
    {
        $tahun = $request->tahun ?? '1447 H';
        $force = $request->force ?? false;
        
        // Cek cache
        if (!$force) {
            $existingSummary = EvaluasiQurbanSummary::getByTahun($tahun);
            if ($existingSummary) {
                return response()->json([
                    'success' => true,
                    'summary' => $existingSummary->summary_data,
                    'from_cache' => true,
                    'message' => 'Data ringkasan sudah tersedia (diambil dari cache)'
                ]);
            }
        }
        
        $data = EvaluasiQurban::where('tahun_hijriah', $tahun)->get();
        
        if ($data->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada data evaluasi untuk tahun ' . $tahun
            ]);
        }
        
        // Kumpulkan komentar per kategori
        $komentar = [
            'informasi' => [],
            'pendaftaran' => [],
            'penyembelihan' => [],
            'distribusi' => [],
            'kualitas' => []
        ];
        
        foreach ($data as $item) {
            if ($item->masukan_penyebaran_informasi && !in_array($item->masukan_penyebaran_informasi, ['-', 'Tidak ada'])) {
                $komentar['informasi'][] = $item->masukan_penyebaran_informasi;
            }
            if ($item->saran_pendaftaran && !in_array($item->saran_pendaftaran, ['-', 'Tidak ada', 'Sudah baik'])) {
                $komentar['pendaftaran'][] = $item->saran_pendaftaran;
            }
            if ($item->saran_pelaksanaan && !in_array($item->saran_pelaksanaan, ['-', 'Tidak ada'])) {
                $komentar['penyembelihan'][] = $item->saran_pelaksanaan;
            }
            if ($item->saran_distribusi && !in_array($item->saran_distribusi, ['-', 'Tidak ada'])) {
                $komentar['distribusi'][] = $item->saran_distribusi;
            }
            if ($item->saran_kualitas_hewan && !in_array($item->saran_kualitas_hewan, ['-', 'Tidak ada'])) {
                $komentar['kualitas'][] = $item->saran_kualitas_hewan;
            }
        }
        
        $aiService = new GeminiAIService();
        $summary = $aiService->generateOverallSummary($komentar, $tahun);
        
        // Simpan ke database
        EvaluasiQurbanSummary::saveSummary($tahun, $summary);
        
        return response()->json([
            'success' => true,
            'summary' => $summary,
            'from_cache' => false,
            'message' => 'Ringkasan berhasil di-generate dan disimpan'
        ]);
    }
}