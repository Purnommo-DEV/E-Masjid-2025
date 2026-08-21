<?php

namespace App\Models\FinancialV2;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostingRule extends FinancialV2Model
{
    protected $table = 'financial_v2_posting_rules';

    public function transactionType(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(PostingRuleVersion::class);
    }
}
