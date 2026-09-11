<?php

namespace App\Models\FinancialV2;

use DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanningFunding extends FinancialV2Model
{
    protected $table = 'financial_v2_planning_fundings';

    protected $casts = ['amount' => 'decimal:2'];

    protected static function booted(): void
    {
        $assertDraft = function (self $funding): void {
            if (Planning::query()->whereKey($funding->planning_id)->value('status') !== 'draft') {
                throw new DomainException('Only Draft Planning funding lines may be changed.');
            }
        };
        static::creating($assertDraft);
        static::updating($assertDraft);
        static::deleting($assertDraft);
    }

    public function planning(): BelongsTo
    {
        return $this->belongsTo(Planning::class);
    }

    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }
}
