<?php

namespace App\Models\FinancialV2;

use App\Domain\FinancialV2\FinancialTransactionStateGuard;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FinancialTransaction extends FinancialV2Model
{
    protected $table = 'financial_v2_transactions';

    protected $casts = ['business_date' => 'date', 'accounting_date' => 'date', 'gross_amount' => 'decimal:2'];

    protected static function booted(): void
    {
        static::updating(function (self $model): void {
            FinancialTransactionStateGuard::assertLifecycleWrite();
            if (in_array($model->getOriginal('status'), ['posted', 'reversed'], true)
                && ! ($model->getOriginal('status') === 'posted' && $model->status === 'reversed')) {
                throw new DomainException('Posted V2 transaction is immutable.');
            }
        });
        static::deleting(fn (self $model) => throw new DomainException('V2 financial transactions are never deleted.'));
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class, 'transaction_type_id');
    }

    public function primaryFinancialAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'primary_financial_account_id');
    }

    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(Counterparty::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function splits(): HasMany
    {
        return $this->hasMany(TransactionSplit::class, 'transaction_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(PostingAttempt::class, 'transaction_id');
    }

    public function treasuryTransfer(): HasOne
    {
        return $this->hasOne(TreasuryTransfer::class, 'transaction_id');
    }

    public function interfundTransfer(): HasOne
    {
        return $this->hasOne(InterfundTransfer::class, 'transaction_id');
    }

    public function realization(): HasOne
    {
        return $this->hasOne(FundRealization::class, 'transaction_id');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }
}
