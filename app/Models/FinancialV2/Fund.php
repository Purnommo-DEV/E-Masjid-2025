<?php

namespace App\Models\FinancialV2;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fund extends FinancialV2Model
{
    protected $table = 'financial_v2_funds';

    protected $casts = ['minimum_balance_policy' => 'decimal:2', 'allow_negative_balance' => 'boolean', 'valid_from' => 'date', 'valid_to' => 'date'];

    public function type(): BelongsTo
    {
        return $this->belongsTo(FundType::class, 'fund_type_id');
    }

    public function restriction(): BelongsTo
    {
        return $this->belongsTo(FundRestriction::class, 'fund_restriction_id');
    }

    public function policyVersions(): HasMany
    {
        return $this->hasMany(FundPolicyVersion::class);
    }
}
