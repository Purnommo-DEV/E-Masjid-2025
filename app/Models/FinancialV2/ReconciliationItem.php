<?php

namespace App\Models\FinancialV2;

class ReconciliationItem extends FinancialV2Model
{
    protected $table = 'financial_v2_reconciliation_items';

    protected $casts = ['amount' => 'decimal:2'];
}
