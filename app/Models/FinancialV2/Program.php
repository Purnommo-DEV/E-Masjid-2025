<?php

namespace App\Models\FinancialV2;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Program extends FinancialV2Model
{
    protected $table = 'financial_v2_programs';

    protected $casts = ['start_date' => 'date', 'end_date' => 'date'];

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }
}
