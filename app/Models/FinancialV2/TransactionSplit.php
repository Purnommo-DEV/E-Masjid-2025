<?php

namespace App\Models\FinancialV2;

use DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionSplit extends FinancialV2Model
{
    protected $table = 'financial_v2_transaction_splits';

    protected $casts = ['split_amount' => 'decimal:2'];

    protected static function booted(): void
    {
        $assertDraftParent = function (self $model): void {
            $status = FinancialTransaction::query()->whereKey($model->transaction_id)->value('status');
            if (in_array($status, ['posted', 'reversed'], true)) {
                throw new DomainException('Splits of posted V2 transactions are immutable.');
            }
        };
        static::updating($assertDraftParent);
        static::deleting($assertDraftParent);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(FinancialTransaction::class, 'transaction_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }
}
