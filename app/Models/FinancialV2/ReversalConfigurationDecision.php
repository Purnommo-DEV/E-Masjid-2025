<?php

namespace App\Models\FinancialV2;

use DomainException;

final class ReversalConfigurationDecision extends FinancialV2Model
{
    public $timestamps = false;

    protected $table = 'financial_v2_reversal_configuration_decisions';

    protected $casts = ['configuration_snapshot' => 'array', 'decided_at' => 'datetime', 'created_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(fn () => throw new DomainException('Keputusan konfigurasi REV hanya dapat ditambahkan oleh persistence private workflow domain.'));
        static::updating(fn () => throw new DomainException('Keputusan governance konfigurasi REV bersifat append-only.'));
        static::deleting(fn () => throw new DomainException('Keputusan governance konfigurasi REV disimpan permanen.'));
    }
}
