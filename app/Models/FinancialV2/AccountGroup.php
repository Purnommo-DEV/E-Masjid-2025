<?php

namespace App\Models\FinancialV2;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountGroup extends FinancialV2Model
{
    protected $table = 'financial_v2_account_groups';

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_group_id');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }
}
