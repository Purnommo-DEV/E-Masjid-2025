<?php

namespace App\Models\FinancialV2;

class Counterparty extends FinancialV2Model
{
    public const BENEFICIARY_TYPES = ['YATIM', 'DHUAFA', 'YATIM_DHUAFA', 'BELUM_DITENTUKAN'];

    public function getBeneficiaryTypeLabelAttribute(): string
    {
        return match ($this->beneficiary_type) {
            'YATIM' => 'Yatim',
            'DHUAFA' => 'Dhuafa',
            'YATIM_DHUAFA' => 'Yatim yang Dhuafa',
            default => 'Belum ditentukan',
        };
    }

    protected $table = 'financial_v2_counterparties';

    public function distributionItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DistributionItem::class, 'beneficiary_id');
    }
}
