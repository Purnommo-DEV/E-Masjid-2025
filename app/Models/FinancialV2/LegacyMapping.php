<?php

namespace App\Models\FinancialV2;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyMapping extends FinancialV2Model
{
    protected $table = 'financial_v2_legacy_mappings';

    public function mappingSet(): BelongsTo
    {
        return $this->belongsTo(MappingSet::class, 'mapping_set_id');
    }
}
