<?php

namespace App\Models\FinancialV2;

use DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetAllocationFunding extends FinancialV2Model
{
    protected $table = 'financial_v2_budget_allocation_fundings';

    protected $casts = ['amount' => 'decimal:2'];

    protected static function booted(): void
    {
        $assertDraftVersion = function (self $model): void {
            $status = BudgetAllocationVersion::query()->whereKey($model->budget_allocation_version_id)->value('status');
            if ($status !== 'draft') {
                throw new DomainException('Funding lines of finalized Budget Allocation Versions are immutable.');
            }
        };

        static::updating($assertDraftVersion);
        static::deleting($assertDraftVersion);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(BudgetAllocationVersion::class, 'budget_allocation_version_id');
    }

    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }
}
