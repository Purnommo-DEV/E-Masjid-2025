<?php

namespace App\Models\FinancialV2;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreasuryTransfer extends FinancialV2Model
{
    protected $table = 'financial_v2_treasury_transfers';

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(FinancialTransaction::class);
    }

    public function sourceFinancialAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'source_financial_account_id');
    }

    public function destinationFinancialAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'destination_financial_account_id');
    }
}
