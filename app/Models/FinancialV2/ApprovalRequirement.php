<?php

namespace App\Models\FinancialV2;

class ApprovalRequirement extends FinancialV2Model
{
    protected $table = 'financial_v2_approval_requirements';

    protected $casts = ['effective_from' => 'date', 'effective_to' => 'date'];
}
