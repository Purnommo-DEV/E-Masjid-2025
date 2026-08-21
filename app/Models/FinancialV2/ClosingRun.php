<?php

namespace App\Models\FinancialV2;

use App\Domain\FinancialV2\PeriodClosingStateGuard;
use DomainException;

class ClosingRun extends FinancialV2Model
{
    protected $table = 'financial_v2_closing_runs';

    protected $casts = ['business_date' => 'date', 'accounting_date' => 'date', 'completed_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => PeriodClosingStateGuard::assertClosingWrite());
        static::updating(function (self $model): void {
            PeriodClosingStateGuard::assertClosingWrite();
            if ($model->getOriginal('status') === 'completed') {
                throw new DomainException('Completed ClosingRun is retained as an immutable control record.');
            }
        });
        static::deleting(fn (self $model) => throw new DomainException('Closing runs are retained for audit traceability.'));
    }
}
