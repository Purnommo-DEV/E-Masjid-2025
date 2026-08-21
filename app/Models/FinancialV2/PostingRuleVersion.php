<?php

namespace App\Models\FinancialV2;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostingRuleVersion extends FinancialV2Model
{
    protected $table = 'financial_v2_posting_rule_versions';

    protected $casts = ['effective_from' => 'date', 'effective_to' => 'date', 'approved_at' => 'datetime'];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(PostingRule::class, 'posting_rule_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PostingRuleLine::class);
    }

    public function evidenceRequirements(): HasMany
    {
        return $this->hasMany(EvidenceRequirement::class);
    }
}
