<?php

namespace App\Models\FinancialV2;

use Illuminate\Database\Eloquent\Relations\HasMany;

class TransactionType extends FinancialV2Model
{
    protected $table = 'financial_v2_transaction_types';

    protected $casts = ['has_financial_impact' => 'boolean', 'valid_from' => 'date', 'valid_to' => 'date'];

    public function postingRules(): HasMany
    {
        return $this->hasMany(PostingRule::class);
    }
}
