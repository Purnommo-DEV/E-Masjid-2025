<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluasiQurbanSummary extends Model
{
    protected $table = 'evaluasi_qurban_summaries';
    protected $guarded = ['id'];
    
    protected $casts = [
        'summary_data' => 'array',
        'is_active' => 'boolean',
        'generated_at' => 'datetime',
    ];
    
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($summary) {
            if (!$summary->masjid_code) {
                $summary->masjid_code = masjid();
            }
        });
        
        static::addGlobalScope('masjid', function ($query) {
            $query->where('masjid_code', masjid());
        });
    }
    
    public static function getByTahun($tahun)
    {
        return self::where('tahun_hijriah', $tahun)
            ->where('is_active', true)
            ->first();
    }
    
    public static function saveSummary($tahun, $summaryData)
    {
        return self::updateOrCreate(
            ['tahun_hijriah' => $tahun],
            [
                'summary_data' => $summaryData,
                'is_active' => true,
                'generated_at' => now()
            ]
        );
    }
}