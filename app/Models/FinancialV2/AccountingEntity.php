<?php

namespace App\Models\FinancialV2;

use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingEntity extends FinancialV2Model
{
    protected $table = 'financial_v2_accounting_entities';

    protected $casts = ['fiscal_year_start_month' => 'integer'];

    public function calendars(): HasMany
    {
        return $this->hasMany(AccountingCalendar::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function funds(): HasMany
    {
        return $this->hasMany(Fund::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class);
    }
}
