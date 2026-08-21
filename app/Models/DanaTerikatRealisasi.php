<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DanaTerikatRealisasi extends Model
{
    use HasFactory;

    protected $table = 'dana_terikat_realisasi';

    protected $guarded = ['id'];

    protected $casts = [
        'tahun' => 'integer',
        'bulan' => 'integer',
        'jumlah' => 'decimal:0',
        'nominal_saat_realisasi' => 'decimal:0',
    ];

    // ===== RELATIONS =====
    
    public function program(): BelongsTo
    {
        return $this->belongsTo(DanaTerikatProgram::class, 'program_id');
    }

    public function penerima(): BelongsTo
    {
        return $this->belongsTo(DanaTerikatPenerima::class, 'penerima_id');
    }

    public function statusBulanan(): BelongsTo
    {
        return $this->belongsTo(DanaTerikatStatusBulanan::class, 'status_bulanan_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ===== ACCESSORS =====
    
    public function getNamaTampilAttribute(): string
    {
        return $this->nama_saat_realisasi ?? $this->penerima?->nama ?? 'Tidak Diketahui';
    }

    public function getJumlahFormattedAttribute(): string
    {
        return 'Rp ' . number_format($this->jumlah, 0, ',', '.');
    }

    public function getBulanTahunAttribute(): string
    {
        return \Carbon\Carbon::create($this->tahun, $this->bulan, 1)->translatedFormat('F Y');
    }

    public function getTipeBadgeAttribute(): string
    {
        return match($this->tipe_realisasi) {
            'auto_dari_status' => '<span class="badge badge-info">Auto Status</span>',
            'koreksi' => '<span class="badge badge-warning">Koreksi</span>',
            default => '<span class="badge badge-success">Manual</span>',
        };
    }
}