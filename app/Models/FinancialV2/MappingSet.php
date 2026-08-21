<?php

namespace App\Models\FinancialV2;

use Illuminate\Database\Eloquent\Relations\HasMany;

class MappingSet extends FinancialV2Model
{
    protected $table = 'financial_v2_mapping_sets';

    protected $casts = ['cutover_date' => 'date', 'approved_at' => 'datetime'];

    public function openingBalanceBatches(): HasMany
    {
        return $this->hasMany(OpeningBalanceBatch::class, 'mapping_set_id');
    }
}
