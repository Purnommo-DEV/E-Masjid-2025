<?php

namespace App\Models\FinancialV2;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankMutationPolicy extends FinancialV2Model
{
    protected $table = 'financial_v2_bank_mutation_policies';

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'required_approval_steps' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function transactionType(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class);
    }

    public function financialAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class);
    }

    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    public function postingRuleVersion(): BelongsTo
    {
        return $this->belongsTo(PostingRuleVersion::class);
    }
}
