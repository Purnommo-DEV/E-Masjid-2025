<?php

namespace App\Models\FinancialV2;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterfundTransfer extends FinancialV2Model
{
    protected $table = 'financial_v2_interfund_transfers';

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(FinancialTransaction::class);
    }

    public function sourceFund(): BelongsTo
    {
        return $this->belongsTo(Fund::class, 'source_fund_id');
    }

    public function destinationFund(): BelongsTo
    {
        return $this->belongsTo(Fund::class, 'destination_fund_id');
    }
}
