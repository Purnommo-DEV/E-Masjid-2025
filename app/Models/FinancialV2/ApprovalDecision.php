<?php

namespace App\Models\FinancialV2;

class ApprovalDecision extends FinancialV2Model
{
    protected $table = 'financial_v2_approval_decisions';

    protected $casts = ['decision_at' => 'datetime'];
}
