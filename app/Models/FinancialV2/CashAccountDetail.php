<?php

namespace App\Models\FinancialV2;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashAccountDetail extends FinancialV2Model
{
    protected $table = 'financial_v2_cash_account_details';

    protected $casts = ['petty_cash_limit' => 'decimal:2'];

    public function financialAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }
}
