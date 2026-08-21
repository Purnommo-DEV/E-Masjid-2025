<?php

namespace App\Models\FinancialV2;

use App\Domain\FinancialV2\FinancialFactWriteGuard;
use DomainException;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Journal extends FinancialV2Model
{
    protected $table = 'financial_v2_journals';

    protected $casts = ['business_date' => 'date', 'accounting_date' => 'date', 'posted_at' => 'datetime', 'total_debit' => 'decimal:2', 'total_credit' => 'decimal:2'];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => FinancialFactWriteGuard::assertPostingEngineWrite('Journal'));
        static::updating(function (self $model): void {
            FinancialFactWriteGuard::assertPostingEngineWrite('Journal');
            if (in_array($model->getOriginal('journal_status'), ['posted', 'reversed'], true)) {
                throw new DomainException('Posted V2 journal is immutable.');
            }
        });
        static::deleting(fn (self $model) => throw new DomainException('V2 journals are append-only.'));
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }
}
