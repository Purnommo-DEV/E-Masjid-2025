<?php

namespace App\Models\FinancialV2;

class BusinessRule extends FinancialV2Model
{
    protected $table = 'financial_v2_business_rules';

    protected $casts = ['effective_from' => 'date', 'effective_to' => 'date', 'approved_at' => 'datetime'];
}
