<?php

namespace App\Models;

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

    // Auto-set masjid_code & tahun_hijriah saat creating
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
        });

        // Global scope untuk multi-masjid
        static::addGlobalScope('masjid', function ($query) {
            $query->where('masjid_code', masjid());
        });
    }

    // Accessor rating dalam bentuk bintang
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
        return $stars;
    }

    // Accessor untuk menampilkan teks sumber info
    public function getSumberInfoTextAttribute(): string
    {
        if ($this->sumber_info == 'Lainnya' && $this->sumber_info_lainnya) {
            return $this->sumber_info_lainnya;
        }
        
        $sumber = [
            'WhatsApp' => '📱 WhatsApp',
            'Info Tetangga' => '🏠 Info Tetangga',
            'Flyer' => '📄 Flyer',
            'Spanduk/Banner' => '🪧 Spanduk/Banner',
        ];
        
        return $sumber[$this->sumber_info] ?? $this->sumber_info ?? '-';
    }

    // Accessor tempat qurban
    public function getTempatQurbanTextAttribute(): string
    {
        $tempat = [
            'masjid' => '🕌 Masjid Raudhatul Jannah',
            'pihak_lain' => '🏢 Pihak Lain',
            'keduanya' => '🔄 Keduanya',
        ];
        return $tempat[$this->tempat_qurban] ?? $this->tempat_qurban;
    }

    // Accessor rencana qurban badge
    public function getRencanaQurbanBadgeAttribute(): string
    {
        $badges = [
            'ya' => '<span class="badge bg-success">✅ Ya</span>',
            'mungkin' => '<span class="badge bg-warning">🤔 Mungkin</span>',
            'tidak' => '<span class="badge bg-danger">❌ Tidak</span>',
        ];
        return $badges[$this->rencana_qurban] ?? $this->rencana_qurban;
    }
}