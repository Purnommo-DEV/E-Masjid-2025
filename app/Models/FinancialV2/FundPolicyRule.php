<?php

namespace App\Models\FinancialV2;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FundPolicyRule extends FinancialV2Model
{
    protected $table = 'financial_v2_fund_policy_rules';

    public function version(): BelongsTo
    {
        return $this->belongsTo(FundPolicyVersion::class, 'fund_policy_version_id');
    }
}
