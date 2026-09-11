<?php

namespace App\Models\FinancialV2;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistributionItem extends FinancialV2Model
{
    protected $table = 'financial_v2_distribution_items';

    protected $casts = ['amount' => 'decimal:2', 'identity_snapshot' => 'array'];

    protected static function booted(): void
    {
        $guard = function (self $model): void {
            foreach (array_unique(array_filter([$model->distribution_id, $model->getOriginal('distribution_id')])) as $id) {
                $parent = Distribution::findOrFail($id);
                if ($parent->status !== 'draft' || $parent->realization_id !== null) {
                    throw new \DomainException('Items of a finalized distribution are immutable.');
                }
            }
        };
        static::saving($guard);
        static::deleting($guard);
    }

    public function distribution(): BelongsTo
    {
        return $this->belongsTo(Distribution::class);
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Counterparty::class, 'beneficiary_id');
    }
}
