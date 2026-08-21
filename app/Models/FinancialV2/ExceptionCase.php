<?php

namespace App\Models\FinancialV2;

use Illuminate\Database\Eloquent\Relations\HasMany;

class ExceptionCase extends FinancialV2Model
{
    protected $table = 'financial_v2_exception_cases';

    public function logs(): HasMany
    {
        return $this->hasMany(ExceptionLog::class, 'exception_case_id');
    }
}
