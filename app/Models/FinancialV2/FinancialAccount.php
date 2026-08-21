<?php

namespace App\Models\FinancialV2;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FinancialAccount extends FinancialV2Model
{
    protected $table = 'financial_v2_financial_accounts';

    protected $casts = ['opening_date' => 'date', 'closing_date' => 'date'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function bankDetail(): HasOne
    {
        return $this->hasOne(BankAccountDetail::class, 'financial_account_id');
    }

    public function cashDetail(): HasOne
    {
        return $this->hasOne(CashAccountDetail::class, 'financial_account_id');
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(AccountingEntity::class, 'accounting_entity_id');
    }

    public function isUsableOn(string $date): bool
    {
        return $this->status === 'active' && (! $this->closing_date || $this->closing_date->toDateString() >= $date);
    }
}
