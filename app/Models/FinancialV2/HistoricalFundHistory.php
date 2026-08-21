<?php

namespace App\Models\FinancialV2;

use DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Source-only historical Fund explanation.
 *
 * These records are deliberately outside the transaction, Journal, and Ledger
 * tables. They preserve the workbook lineage explaining an opening position.
 */
class HistoricalFundHistory extends FinancialV2Model
{
    protected $table = 'financial_v2_historical_fund_histories';

    protected $casts = [
        'amount' => 'decimal:2',
        'effective_date' => 'date',
        'imported_at' => 'datetime',
        'corrected_at' => 'datetime',
        'source_payload' => 'array',
    ];

    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by_user_id');
    }

    protected static function booted(): void
    {
        static::deleting(fn (self $model) => throw new DomainException('Historical Fund history is retained with an audited status.'));
    }
}
