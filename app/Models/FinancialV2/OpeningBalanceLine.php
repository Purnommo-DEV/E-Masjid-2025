<?php

namespace App\Models\FinancialV2;

use App\Domain\FinancialV2\OpeningBalanceStateGuard;
use DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpeningBalanceLine extends FinancialV2Model
{
    protected $table = 'financial_v2_opening_balance_lines';

    protected $casts = ['debit_amount' => 'decimal:2', 'credit_amount' => 'decimal:2', 'source_debit_amount' => 'decimal:2', 'source_credit_amount' => 'decimal:2', 'reconciliation_difference' => 'decimal:2'];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => OpeningBalanceStateGuard::assertOpeningBalanceWrite());
        static::updating(function (self $model): void {
            OpeningBalanceStateGuard::assertOpeningBalanceWrite();
            if (in_array($model->openingBalanceBatch?->status, ['reviewed', 'approved', 'posting', 'posted', 'superseded_by_correction'], true)) {
                throw new DomainException('Reviewed or posted opening balance lines are immutable.');
            }
        });
        static::deleting(fn (self $model) => throw new DomainException('Opening balance lines are retained.'));
    }

    public function openingBalanceBatch(): BelongsTo
    {
        return $this->belongsTo(OpeningBalanceBatch::class, 'opening_balance_batch_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function financialAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class);
    }

    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
