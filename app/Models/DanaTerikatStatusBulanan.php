<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DanaTerikatStatusBulanan extends Model
{
    use HasFactory;

    protected $table = 'dana_terikat_status_bulanan';

    protected $fillable = [
        'program_id',
        'penerima_id',
        'tahun',
        'bulan',
        'status_dapat',
        'alasan_tidak_dapat',
        'nama_alternatif',
        'nominal_alternatif',
        'verified_by',
        'verified_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status_dapat' => 'boolean',
        'verified_at' => 'datetime',
        'tahun' => 'integer',
        'bulan' => 'integer',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ===== ACCESSORS =====
    
    public function getNamaAktualAttribute(): string
    {
        return $this->nama_alternatif ?? $this->penerima?->nama ?? 'Tidak Diketahui';
    }

    public function getNominalAktualAttribute(): float
    {
        return $this->nominal_alternatif ?? $this->penerima?->nominal_bulanan ?? 0;
    }

    public function getStatusTextAttribute(): string
    {
        return $this->status_dapat ? '✅ Dapat' : '❌ Tidak Dapat';
    }

    public function getStatusBadgeAttribute(): string
    {
        return $this->status_dapat 
            ? '<span class="badge badge-success">✅ Dapat</span>'
            : '<span class="badge badge-error">❌ Tidak Dapat</span>';
    }

    // ===== SCOPES =====
    
    public function scopeByPeriode($query, int $bulan, int $tahun)
    {
        return $query->where('bulan', $bulan)->where('tahun', $tahun);
    }

    public function scopeDapat($query)
    {
        return $query->where('status_dapat', true);
    }

    public function scopeTidakDapat($query)
    {
        return $query->where('status_dapat', false);
    }

    public function scopeBelumDiverifikasi($query)
    {
        return $query->whereNull('verified_at');
    }

    public function scopeSudahDiverifikasi($query)
    {
        return $query->whereNotNull('verified_at');
    }
}