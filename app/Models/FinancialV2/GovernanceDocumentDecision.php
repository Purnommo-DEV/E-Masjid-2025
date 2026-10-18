<?php

namespace App\Models\FinancialV2;

use DomainException;

final class GovernanceDocumentDecision extends FinancialV2Model
{
    public $timestamps = false;
    protected $table = 'financial_v2_governance_document_decisions';
    protected $casts = ['document_snapshot' => 'array', 'decided_at' => 'datetime', 'created_at' => 'datetime'];
    protected static function booted(): void
    {
        static::creating(fn () => throw new DomainException('Keputusan dokumen governance hanya dapat ditambahkan oleh persistence private workflow domain.'));
        static::updating(fn () => throw new DomainException('Keputusan dokumen governance bersifat append-only.'));
        static::deleting(fn () => throw new DomainException('Keputusan dokumen governance disimpan permanen.'));
    }
}
