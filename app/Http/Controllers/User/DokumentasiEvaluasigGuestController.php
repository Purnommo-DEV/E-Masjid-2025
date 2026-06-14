<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\EvaluasiQurban;
use App\Models\EvaluasiQurbanSummary;
use Illuminate\Http\Request;

class DokumentasiEvaluasiController extends Controller
{
    public function index($tahun = null)
    {
        // Jika tahun tidak dipilih, ambil tahun terbaru
        if (!$tahun) {
            $tahun = EvaluasiQurban::select('tahun_hijriah')
                ->distinct()
                ->orderBy('tahun_hijriah', 'desc')
                ->first()
                ->tahun_hijriah ?? '1447 H';
        }
        
        // Ambil semua data evaluasi berdasarkan tahun
        $data = EvaluasiQurban::where('tahun_hijriah', $tahun)
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Ambil daftar tahun untuk dropdown
        $tahunList = EvaluasiQurban::select('tahun_hijriah')
            ->distinct()
            ->orderBy('tahun_hijriah', 'desc')
            ->pluck('tahun_hijriah')
            ->toArray();
        
        // Ambil AI Summary jika ada
        $aiSummary = EvaluasiQurbanSummary::getByTahun($tahun);
        
        // Kelompokkan data per kategori
        $kategori = [
            'informasi' => ['pujian' => [], 'kritik' => []],
            'pendaftaran' => [],
            'penyembelihan' => [],
            'distribusi' => [],
            'kualitas' => []
        ];
        
        foreach ($data as $item) {
            if ($item->masukan_penyebaran_informasi) {
                if ($this->isPositive($item->masukan_penyebaran_informasi)) {
                    $kategori['informasi']['pujian'][] = $item;
                } else {
                    $kategori['informasi']['kritik'][] = $item;
                }
            }
            
            if ($item->saran_pendaftaran && !in_array($item->saran_pendaftaran, ['-', 'Tidak ada', 'Sudah baik'])) {
                $kategori['pendaftaran'][] = $item;
            }
            
            if ($item->saran_pelaksanaan && !in_array($item->saran_pelaksanaan, ['-', 'Tidak ada'])) {
                $kategori['penyembelihan'][] = $item;
            }
            
            if ($item->saran_distribusi && !in_array($item->saran_distribusi, ['-', 'Tidak ada'])) {
                $kategori['distribusi'][] = $item;
            }
            
            if ($item->saran_kualitas_hewan && !in_array($item->saran_kualitas_hewan, ['-', 'Tidak ada'])) {
                $kategori['kualitas'][] = $item;
            }
        }
        
        $totalMasukan = $data->count();
        
        return view('guest.dokumentasi-evaluasi', compact('data', 'tahun', 'tahunList', 'kategori', 'aiSummary', 'totalMasukan'));
    }
    
    private function isPositive($text)
    {
        $positiveWords = ['baik', 'bagus', 'mantap', 'puas', 'terima kasih', 'alhamdulillah', 'pertahankan', 'jelas', 'cepat', 'lancar'];
        $negativeWords = ['kurang', 'sempit', 'lambat', 'tumpuk', 'malam', 'terbatas', 'tidak profesional', 'menumpuk'];
        
        $textLower = strtolower($text);
        
        foreach ($negativeWords as $word) {
            if (str_contains($textLower, $word)) {
                return false;
            }
        }
        
        foreach ($positiveWords as $word) {
            if (str_contains($textLower, $word)) {
                return true;
            }
        }
        
        return false;
    }
}