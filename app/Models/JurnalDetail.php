<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JurnalDetail extends Model
{
    use HasFactory;

    protected $table = 'jurnal_detail';

    protected $fillable = [
        'jurnal_id',
        'akun_id',
        'debit',
        'kredit',
        'keterangan',
    ];

    protected $casts = [
        'debit' => 'decimal:0',
        'kredit' => 'decimal:0',
    ];

    // ===== RELATIONS =====

    public function jurnal(): BelongsTo
    {
        return $this->belongsTo(Jurnal::class, 'jurnal_id');
    }

    public function akun(): BelongsTo
    {
        return $this->belongsTo(AkunKeuangan::class, 'akun_id');
    }

    // ===== ACCESSORS =====

    public function getNominalAttribute(): float
    {
        return $this->debit > 0 ? $this->debit : $this->kredit;
    }

    public function getIsDebitAttribute(): bool
    {
        return $this->debit > 0;
    }

    public function getIsKreditAttribute(): bool
    {
        return $this->kredit > 0;
    }

    public function getJenisAttribute(): string
    {
        if ($this->debit > 0) return 'Debit';
        if ($this->kredit > 0) return 'Kredit';
        return '-';
    }
}