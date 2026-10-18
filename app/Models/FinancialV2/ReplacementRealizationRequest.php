<?php

namespace App\Models\FinancialV2;

use DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class ReplacementRealizationRequest extends FinancialV2Model
{
    protected $table = 'financial_v2_replacement_realization_requests';
    protected $casts = ['proposal_snapshot' => 'array', 'submitted_at' => 'datetime', 'approved_at' => 'datetime', 'materialized_at' => 'datetime', 'cancelled_at' => 'datetime'];
    protected static function booted(): void
    {
        static::creating(fn () => throw new DomainException('Replacement Realization hanya dapat diajukan oleh workflow domain resmi.'));
        static::updating(fn () => throw new DomainException('Lifecycle dan snapshot Replacement Realization hanya dapat diubah oleh workflow domain resmi.'));
        static::deleting(fn () => throw new DomainException('Workflow Replacement Realization disimpan permanen.'));
    }
    public function correctionRequest(): BelongsTo { return $this->belongsTo(AccountingDateCorrectionRequest::class, 'correction_request_id'); }
    public function originalRealization(): BelongsTo { return $this->belongsTo(FundRealization::class, 'original_realization_id'); }
    public function replacementTransaction(): BelongsTo { return $this->belongsTo(FinancialTransaction::class, 'replacement_transaction_id'); }
    public function decisions(): HasMany { return $this->hasMany(ReplacementRealizationDecision::class, 'replacement_request_id')->orderBy('decided_at')->orderBy('id'); }
    public function lineage(): HasOne { return $this->hasOne(ReplacementRealizationLineage::class, 'replacement_request_id'); }
}
