<?php

namespace App\Models;

use App\Services\mrj\DeepSeekAIService;
use App\Services\mrj\GeminiAIService;
use App\Services\mrj\GroqAIService;
use App\Services\mrj\OpenRouterAIService;
use Illuminate\Database\Eloquent\Model;

class EvaluasiQurban extends Model
{
    protected $table = 'evaluasi_qurbans';
    protected $guarded = ['id'];

    protected $casts = [
        'rating_pendaftaran' => 'integer',
        'rating_pelaksanaan' => 'integer',
        'rating_distribusi' => 'integer',
        'rating_kualitas_hewan' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($evaluasi) {
            if (!$evaluasi->masjid_code) {
                $evaluasi->masjid_code = masjid();
            }
            if (!$evaluasi->tahun_hijriah) {
                $evaluasi->tahun_hijriah = '1447 H';
            }
            if (!$evaluasi->tempat_qurban) {
                $evaluasi->tempat_qurban = 'masjid';
            }
        });

        static::addGlobalScope('masjid', function ($query) {
            $query->where('masjid_code', masjid());
        });
    }

    // GEMINI
    public function generateWishWithAI($text, $category)
    {
        if (empty($text) || in_array($text, ['-', 'Tidak ada', 'Sudah baik', 'Bagus', 'Cukup', 'NULL', ''])) {
            return null;
        }
        
        try {
            $aiService = new GeminiAIService();
            return $aiService->generateWishForComment($text, $category);
        } catch (\Exception $e) {
            \Log::error('Gemini Wish Error: ' . $e->getMessage());
            return $this->fallbackWish($text, $category);
        }
    }

    // DEEPSEEK
    // public function generateWishWithAI($text, $category)
    // {
    //     if (empty($text) || in_array($text, ['-', 'Tidak ada', 'Sudah baik', 'Bagus', 'Cukup', 'NULL', ''])) {
    //         return null;
    //     }
        
    //     // Coba DeepSeek dulu
    //     try {
    //         $aiService = new DeepSeekAIService();
    //         $result = $aiService->generateWishForComment($text, $category);
    //         if ($result && strlen($result) > 10) {
    //             \Log::info('DeepSeek success', ['category' => $category, 'result' => $result]);
    //             return $result;
    //         }
    //     } catch (\Exception $e) {
    //         \Log::error('DeepSeek AI Error: ' . $e->getMessage());
    //     }
        
    //     // Fallback ke manual mapping jika DeepSeek gagal
    //     return $this->manualWish($text, $category);
    // }

    // GROQ
    // public function generateWishWithAI($text, $category)
    // {
    //     if (empty($text) || in_array($text, ['-', 'Tidak ada', 'Sudah baik', 'Bagus', 'Cukup', 'NULL', ''])) {
    //         return null;
    //     }
        
    //     // Coba Groq API dulu
    //     try {
    //         $aiService = new GroqAIService();
    //         $result = $aiService->generateWishForComment($text, $category);
    //         if ($result && strlen($result) > 10) {
    //             \Log::info('Groq success', ['category' => $category, 'result' => $result]);
    //             return $result;
    //         }
    //     } catch (\Exception $e) {
    //         \Log::error('Groq AI Error: ' . $e->getMessage());
    //     }
        
    //     // Fallback ke manual mapping jika Groq gagal
    //     return $this->manualWish($text, $category);
    // }

    // OPENROUTER
    // public function generateWishWithAI($text, $category)
    // {
    //     if (empty($text) || in_array($text, ['-', 'Tidak ada', 'Sudah baik', 'Bagus', 'Cukup', 'NULL', ''])) {
    //         return null;
    //     }
        
    //     // Coba OpenRouter
    //     try {
    //         $aiService = new OpenRouterAIService();
    //         $result = $aiService->generateWishForComment($text, $category);
            
    //         if ($result && strlen($result) > 10) {
    //             \Log::info('OpenRouter success', ['category' => $category, 'result' => $result]);
    //             return $result;
    //         }
            
    //         // Jika result kosong atau terlalu pendek, anggap gagal
    //         throw new \Exception('AI menghasilkan output yang tidak valid (terlalu pendek atau kosong)');
            
    //     } catch (\Exception $e) {
    //         $errorMessage = $e->getMessage();
    //         \Log::error('OpenRouter AI Error: ' . $errorMessage);
            
    //         // Tampilkan error dan STOP (tidak lanjut ke manual)
    //         throw new \Exception("❌ AI GAGAL: {$errorMessage} | Kategori: {$category}");
    //     }
    // }

    public function generateAllWishesWithAI()
    {
        $this->wish_informasi = $this->generateWishWithAI($this->masukan_penyebaran_informasi, 'informasi');
        $this->wish_pendaftaran = $this->generateWishWithAI($this->saran_pendaftaran, 'pendaftaran');
        $this->wish_pelaksanaan = $this->generateWishWithAI($this->saran_pelaksanaan, 'pelaksanaan');
        $this->wish_distribusi = $this->generateWishWithAI($this->saran_distribusi, 'distribusi');
        $this->wish_kualitas = $this->generateWishWithAI($this->saran_kualitas_hewan, 'kualitas');
        $this->wish_umum = $this->generateWishWithAI($this->hal_perbaikan, 'umum');
        
        $this->saveQuietly();
    }

    /**
     * Fallback wish jika AI error
     */
    private function fallbackWish($comment, $category)
    {
        $commentLower = strtolower($comment);
        
        $fallbackMap = [
            'spanduk' => '📢 Spanduk/banner diperjelas dan dipasang lebih awal',
            'terang' => '📢 Spanduk dibuat lebih terang dan mudah dibaca',
            'harga' => '📢 Informasi harga disebarkan H-1 bulan sebelum Idul Adha',
            'google form' => '💳 Tambahkan opsi pendaftaran via Google Form',
            'qris' => '💳 Tambahkan metode pembayaran QRIS',
            'terbatas' => '🔪 Tempat penyembelihan diperluas atau diatur shift',
            'lambat' => '🔪 Percepat proses pencacahan, tambah tim jagal dan mesin',
            'menyaksikan' => '🔪 Pisahkan hewan yang belum disembelih dengan terpal',
            'profesional' => '🔪 Cari tim jagal yang lebih profesional',
            'vendor' => '🔪 Buat perjanjian vendor yang detail',
            'antrian' => '🔪 Kurangi antrian dengan menambah tim jagal',
            'kupon' => '🚚 Kupon tambahan langsung diberikan bersamaan dengan daging',
            'tepat sasaran' => '🚚 Distribusi daging harus tepat sasaran',
            'request bagian' => '🚚 Beri opsi request bagian daging yang diinginkan',
            'sehat' => '🥩 Pertahankan kualitas hewan yang sehat',
            'syarat' => '🥩 Pastikan hewan memenuhi syarat syariat',
        ];
        
        foreach ($fallbackMap as $key => $wish) {
            if (str_contains($commentLower, $key)) {
                return $wish;
            }
        }
        
        return '✅ Tinjau kembali komentar untuk perbaikan';
    }

    // Rating Bintang Accessors
    public function getRatingPendaftaranBintangAttribute(): string
    {
        return $this->ratingToStars($this->rating_pendaftaran);
    }

    public function getRatingPelaksanaanBintangAttribute(): string
    {
        return $this->ratingToStars($this->rating_pelaksanaan);
    }

    public function getRatingDistribusiBintangAttribute(): string
    {
        return $this->ratingToStars($this->rating_distribusi);
    }

    public function getRatingKualitasBintangAttribute(): string
    {
        return $this->ratingToStars($this->rating_kualitas_hewan);
    }

    private function ratingToStars($rating): string
    {
        $full = floor($rating);
        $half = $rating - $full > 0;
        $stars = str_repeat('★', $full);
        if ($half) {
            $stars .= '½';
        }
        $stars .= str_repeat('☆', 5 - ceil($rating));
        return '<span class="stars">' . $stars . '</span>';
    }

    public function getSumberInfoTextAttribute(): string
    {
        if ($this->sumber_info == 'Lainnya' && $this->sumber_info_lainnya) {
            return $this->sumber_info_lainnya;
        }
        return $this->sumber_info ?? '-';
    }

    public function getRencanaQurbanBadgeAttribute(): string
    {
        $badges = [
            'ya' => '<span class="badge badge-success">✅ Ya</span>',
            'mungkin' => '<span class="badge badge-warning">🤔 Mungkin</span>',
            'tidak' => '<span class="badge badge-danger">❌ Tidak</span>',
        ];
        return $badges[$this->rencana_qurban] ?? '-';
    }
}