<?php

namespace App\Models\FinancialV2;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingCalendar extends FinancialV2Model
{
    protected $table = 'financial_v2_accounting_calendars';

    protected $casts = ['start_date' => 'date', 'end_date' => 'date'];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(AccountingEntity::class, 'accounting_entity_id');
    }

    public function periods(): HasMany
    {
        return $this->hasMany(AccountingPeriod::class);
    }
}
