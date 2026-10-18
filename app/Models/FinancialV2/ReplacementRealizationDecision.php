<?php
namespace App\Models\FinancialV2;
use DomainException;
final class ReplacementRealizationDecision extends FinancialV2Model
{
    public $timestamps = false;
    protected $table = 'financial_v2_replacement_realization_decisions';
    protected $casts = ['proposal_snapshot' => 'array', 'decided_at' => 'datetime', 'created_at' => 'datetime'];
    protected static function booted(): void { static::creating(fn () => throw new DomainException('Decision hanya dapat ditulis workflow resmi.')); static::updating(fn () => throw new DomainException('Decision bersifat append-only.')); static::deleting(fn () => throw new DomainException('Decision disimpan permanen.')); }
}
