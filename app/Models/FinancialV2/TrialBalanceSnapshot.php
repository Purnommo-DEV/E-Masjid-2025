<?php

namespace App\Models\FinancialV2;

class TrialBalanceSnapshot extends FinancialV2Model
{
    protected $table = 'financial_v2_trial_balance_snapshots';

    protected $casts = ['as_of_posting_sequence' => 'integer', 'total_debit' => 'decimal:2', 'total_credit' => 'decimal:2', 'certified_at' => 'datetime'];
}
