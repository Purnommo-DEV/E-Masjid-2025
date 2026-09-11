<?php

namespace App\Models\FinancialV2;

use App\Domain\FinancialV2\DistributionDeletionGuard;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Distribution extends FinancialV2Model
{
    protected $table = 'financial_v2_distributions';

    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date', 'finalized_at' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(function (self $model): void {
            $stored = self::findOrFail($model->id);
            if ($stored->status !== 'draft' || $stored->realization_id !== null) {
                throw new \DomainException('Finalized distribution is immutable.');
            }
        });
        static::deleting(function (self $model): void {
            DistributionDeletionGuard::assertDeletionWrite();
            if ($model->status !== 'draft' || $model->realization_id !== null || $model->finalized_at !== null) {
                throw new \DomainException('Only an unrealized operational draft distribution may be deleted.');
            }
        });
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function realization(): BelongsTo
    {
        return $this->belongsTo(FundRealization::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DistributionItem::class);
    }

    public function copiedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'copied_from_id');
    }
}
