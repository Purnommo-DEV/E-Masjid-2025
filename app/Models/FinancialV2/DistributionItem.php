<?php

namespace App\Models\FinancialV2;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function identityCorrections(): HasMany
    {
        return $this->hasMany(DistributionItemIdentityCorrection::class, 'distribution_item_id')->orderBy('correction_no');
    }

    public function latestIdentityCorrection(): HasOne
    {
        return $this->hasOne(DistributionItemIdentityCorrection::class, 'distribution_item_id')->ofMany('correction_no', 'max');
    }

    public function getEffectiveBeneficiaryIdAttribute(): ?string
    {
        return $this->latestIdentityCorrection?->corrected_beneficiary_id ?? $this->beneficiary_id;
    }

    public function getEffectiveRecipientKeyAttribute(): string
    {
        return $this->latestIdentityCorrection?->corrected_recipient_key ?? $this->recipient_key;
    }

    public function getEffectiveIdentitySnapshotAttribute(): array
    {
        return $this->latestIdentityCorrection?->corrected_identity_snapshot ?? $this->identity_snapshot;
    }
}
