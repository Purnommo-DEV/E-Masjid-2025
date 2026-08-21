<?php

namespace App\Models\FinancialV2;

class PostingAttempt extends FinancialV2Model
{
    protected $table = 'financial_v2_posting_attempts';

    protected $casts = ['requested_at' => 'datetime', 'completed_at' => 'datetime'];
}
