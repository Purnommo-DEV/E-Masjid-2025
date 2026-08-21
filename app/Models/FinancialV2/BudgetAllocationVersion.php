<?php

namespace App\Models\FinancialV2;

use DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetAllocationVersion extends FinancialV2Model
{
    protected $table = 'financial_v2_budget_allocation_versions';

    protected $casts = ['allocated_amount' => 'decimal:2', 'effective_from' => 'date', 'effective_to' => 'date', 'approved_at' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(function (self $model): void {
            $isGovernedSupersession = $model->getOriginal('status') === 'approved'
                && $model->status === 'superseded'
                && count(array_diff(array_keys($model->getDirty()), ['status', 'effective_to', 'updated_by_user_id', 'updated_at'])) === 0;
            $isGovernedCancellation = $model->getOriginal('status') === 'approved'
                && $model->status === 'cancelled'
                && count(array_diff(array_keys($model->getDirty()), ['status', 'updated_by_user_id', 'updated_at'])) === 0;
            if (in_array($model->getOriginal('status'), ['approved', 'superseded'], true) && ! $isGovernedSupersession && ! $isGovernedCancellation) {
                throw new DomainException('Approved budget allocation versions are immutable; create a governed revision.');
            }
        });
        static::deleting(fn (self $model) => throw new DomainException('Budget allocation versions are retained for governance and audit.'));
    }

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(BudgetAllocation::class, 'budget_allocation_id');
    }
}
