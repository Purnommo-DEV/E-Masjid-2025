<?php

namespace App\Models\FinancialV2;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DistributionItemIdentityCorrection extends FinancialV2Model
{
    protected $table = 'financial_v2_distribution_item_identity_corrections';

    public const UPDATED_AT = null;

    protected $casts = [
        'original_identity_snapshot' => 'array',
        'corrected_identity_snapshot' => 'array',
        'corrected_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \DomainException('Distribution identity corrections are append-only.'));
        static::deleting(fn () => throw new \DomainException('Distribution identity corrections are append-only.'));
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(DistributionItem::class, 'distribution_item_id');
    }

    public function correctedBeneficiary(): BelongsTo
    {
        return $this->belongsTo(Counterparty::class, 'corrected_beneficiary_id');
    }

    public function correctedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by_user_id');
    }
}
