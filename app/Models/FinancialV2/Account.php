<?php

namespace App\Models\FinancialV2;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends FinancialV2Model
{
    protected $table = 'financial_v2_accounts';

    protected $casts = ['is_posting_account' => 'boolean', 'is_liquidity_account' => 'boolean', 'is_control_account' => 'boolean', 'allow_manual_posting' => 'boolean', 'valid_from' => 'date', 'valid_to' => 'date'];

    public function group(): BelongsTo
    {
        return $this->belongsTo(AccountGroup::class, 'account_group_id');
    }

    public function dimensionRules(): HasMany
    {
        return $this->hasMany(AccountDimensionRule::class);
    }

    public function financialAccounts(): HasMany
    {
        return $this->hasMany(FinancialAccount::class);
    }
}
