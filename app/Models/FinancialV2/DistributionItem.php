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

    /** @return array{value: ?string, label: string, source: string, from_current_master: bool} */
    public function getBeneficiaryTypeResolutionAttribute(): array
    {
        $correction = $this->latestIdentityCorrection;
        $correctedSnapshot = $correction?->corrected_identity_snapshot ?? [];
        if ($correction && array_key_exists('beneficiary_type', $correctedSnapshot)) {
            return $this->beneficiaryTypeResolution($correctedSnapshot['beneficiary_type'] ?? null, 'correction_snapshot');
        }

        $originalSnapshot = $this->identity_snapshot ?? [];
        if (array_key_exists('beneficiary_type', $originalSnapshot)) {
            return $this->beneficiaryTypeResolution($originalSnapshot['beneficiary_type'] ?? null, 'original_snapshot');
        }

        $master = $correction?->correctedBeneficiary ?? $this->beneficiary;
        $entityId = $this->distribution?->accounting_entity_id;
        if ($master
            && $master->party_type === 'beneficiary'
            && is_string($entityId)
            && hash_equals($entityId, (string) $master->accounting_entity_id)) {
            return $this->beneficiaryTypeResolution($master->beneficiary_type, 'current_master', true);
        }

        return $this->beneficiaryTypeResolution(null, 'unavailable');
    }

    /** @return array{value: ?string, label: string, source: string, from_current_master: bool} */
    private function beneficiaryTypeResolution(mixed $value, string $source, bool $fromCurrentMaster = false): array
    {
        $type = is_string($value) && $value !== '' ? $value : null;

        return [
            'value' => $type,
            'label' => Counterparty::beneficiaryTypeLabel($type),
            'source' => $source,
            'from_current_master' => $fromCurrentMaster,
        ];
    }
}
