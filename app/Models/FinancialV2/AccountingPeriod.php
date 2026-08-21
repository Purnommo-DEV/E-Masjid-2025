<?php

namespace App\Models\FinancialV2;

use App\Domain\FinancialV2\PeriodClosingStateGuard;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingPeriod extends FinancialV2Model
{
    protected $table = 'financial_v2_accounting_periods';

    protected $casts = ['start_date' => 'date', 'end_date' => 'date', 'closed_at' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(function (self $model): void {
            if ($model->isDirty(['status', 'closed_at', 'closed_by_user_id', 'reopen_reason_code_id', 'reopen_note'])) {
                PeriodClosingStateGuard::assertClosingWrite();
            }
            if (in_array($model->getOriginal('status'), ['hard_closed', 'reopened'], true) && $model->isDirty('status')) {
                throw new DomainException('Hard Closed and Reopened periods require a separately governed workflow.');
            }
        });
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(AccountingEntity::class, 'accounting_entity_id');
    }

    public function calendar(): BelongsTo
    {
        return $this->belongsTo(AccountingCalendar::class, 'accounting_calendar_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function permitsOrdinaryPosting(): bool
    {
        return $this->status === 'open';
    }
}
