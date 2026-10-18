<?php

namespace App\Models\FinancialV2;

use DomainException;

final class ReversalPrerequisiteDecision extends FinancialV2Model
{
    public $timestamps = false;
    protected $table = 'financial_v2_reversal_prerequisite_decisions';
    protected $casts = ['proposal_snapshot' => 'array', 'decided_at' => 'datetime', 'created_at' => 'datetime'];
    protected static function booted(): void
    {
        static::creating(fn () => throw new DomainException('Keputusan prerequisite REV hanya dapat ditambahkan oleh persistence private workflow domain.'));
        static::updating(fn () => throw new DomainException('Keputusan prerequisite REV bersifat append-only.'));
        static::deleting(fn () => throw new DomainException('Keputusan prerequisite REV disimpan permanen.'));
    }
}
