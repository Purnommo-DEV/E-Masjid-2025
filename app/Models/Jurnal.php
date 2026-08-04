<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Jurnal extends Model
{
    protected $table = "jurnal";
    protected $guarded = ['id'];

    public function akun()
    { 
        return $this->belongsTo(AkunKeuangan::class); 
    }
    
    public function jurnalable() 
    { 
        return $this->morphTo(); 
    }
    
    public function user()
    { 
        return $this->belongsTo(User::class, 'created_by'); 
    }
    // ===== RELATIONS =====

    public function details(): HasMany
    {
        return $this->hasMany(JurnalDetail::class, 'jurnal_id');
    }

    // ===== SCOPES =====

    public function scopeByPeriode($query, $bulan, $tahun)
    {
        return $query->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun);
    }

    public function scopeJenis($query, $jenis)
    {
        return $query->where('jenis', $jenis);
    }

    // ===== ACCESSORS =====

    public function getTotalDebitAttribute()
    {
        return $this->details->sum('debit');
    }

    public function getTotalKreditAttribute()
    {
        return $this->details->sum('kredit');
    }

    public function getIsBalanceAttribute()
    {
        return $this->total_debit == $this->total_kredit;
    }
}
