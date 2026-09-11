<?php

namespace App\Models\FinancialV2;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends FinancialV2Model
{
    protected $table = 'financial_v2_programs';

    protected $casts = ['start_date' => 'date', 'end_date' => 'date'];

    public function distributions(): HasMany
    {
        return $this->hasMany(Distribution::class);
    }

    public function plannings(): HasMany
    {
        return $this->hasMany(Planning::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }
}
