<?php

namespace App\Models\FinancialV2;

class BalanceProjection extends FinancialV2Model
{
    protected $table = 'financial_v2_balance_projections';

    protected $casts = [
        'as_of_accounting_date' => 'date',
        'through_posting_sequence' => 'integer',
        'debit_total' => 'decimal:2',
        'credit_total' => 'decimal:2',
        'balance' => 'decimal:2',
        'built_at' => 'datetime',
    ];
}
