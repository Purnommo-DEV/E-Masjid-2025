<?php

namespace App\Models\FinancialV2;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FundPolicyVersion extends FinancialV2Model
{
    protected $table = 'financial_v2_fund_policy_versions';

    protected $casts = ['effective_from' => 'date', 'effective_to' => 'date', 'approved_at' => 'datetime'];

    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    public function rules(): HasMany
    {
        return $this->hasMany(FundPolicyRule::class);
    }
}
