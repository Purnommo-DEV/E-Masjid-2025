<?php

namespace App\Models\FinancialV2;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountDimensionRule extends FinancialV2Model
{
    protected $table = 'financial_v2_account_dimension_rules';

    protected $casts = ['applies_to_debit' => 'boolean', 'applies_to_credit' => 'boolean', 'effective_from' => 'date', 'effective_to' => 'date', 'approved_at' => 'datetime'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
