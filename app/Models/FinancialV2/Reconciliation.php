<?php

namespace App\Models\FinancialV2;

use App\Domain\FinancialV2\ReconciliationStateGuard;
use DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reconciliation extends FinancialV2Model
{
    protected $table = 'financial_v2_reconciliations';

    protected $casts = ['business_date' => 'date', 'accounting_date' => 'date', 'statement_balance' => 'decimal:2', 'ledger_balance' => 'decimal:2', 'difference' => 'decimal:2', 'reviewed_at' => 'datetime', 'reconciled_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => ReconciliationStateGuard::assertReconciliationWrite());
        static::updating(function (self $model): void {
            ReconciliationStateGuard::assertReconciliationWrite();
            if ($model->getOriginal('status') === 'completed') {
                throw new DomainException('Completed reconciliations are immutable control records.');
            }
        });
        static::deleting(fn (self $model) => throw new DomainException('Reconciliations are retained for audit traceability.'));
    }

    public function financialAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'accounting_period_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReconciliationItem::class, 'reconciliation_id');
    }
}
