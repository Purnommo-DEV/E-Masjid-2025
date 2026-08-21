<?php

namespace App\Models\FinancialV2;

use App\Domain\FinancialV2\OpeningBalanceStateGuard;
use DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OpeningBalanceBatch extends FinancialV2Model
{
    protected $table = 'financial_v2_opening_balance_batches';

    protected $casts = ['cutover_date' => 'date', 'reviewed_at' => 'datetime', 'approved_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => OpeningBalanceStateGuard::assertOpeningBalanceWrite());
        static::updating(function (self $model): void {
            OpeningBalanceStateGuard::assertOpeningBalanceWrite();
            if (in_array($model->getOriginal('status'), ['posted', 'superseded_by_correction'], true)) {
                throw new DomainException('Posted opening balance is immutable.');
            }
        });
        static::deleting(fn (self $model) => throw new DomainException('Opening balance batches are retained.'));
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OpeningBalanceLine::class, 'opening_balance_batch_id');
    }

    public function mappingSet(): BelongsTo
    {
        return $this->belongsTo(MappingSet::class, 'mapping_set_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'accounting_period_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'journal_id');
    }
}
