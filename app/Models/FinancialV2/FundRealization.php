<?php

namespace App\Models\FinancialV2;

use DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FundRealization extends FinancialV2Model
{
    protected $table = 'financial_v2_fund_realizations';

    protected $casts = ['recorded_at' => 'datetime', 'reversed_at' => 'datetime'];

    protected static function booted(): void
    {
        static::deleting(fn (self $model) => throw new DomainException('Fund realizations are retained as links to posted transactions.'));
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(FinancialTransaction::class);
    }

    public function budgetAllocationVersion(): BelongsTo
    {
        return $this->belongsTo(BudgetAllocationVersion::class);
    }
}
