<?php

namespace App\Models\FinancialV2;

use App\Domain\FinancialV2\FinancialFactWriteGuard;
use DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalLine extends FinancialV2Model
{
    protected $table = 'financial_v2_journal_lines';

    protected $casts = ['debit_amount' => 'decimal:2', 'credit_amount' => 'decimal:2'];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => FinancialFactWriteGuard::assertPostingEngineWrite('JournalLine'));
        static::updating(fn (self $model) => throw new DomainException('V2 journal lines are immutable.'));
        static::deleting(fn (self $model) => throw new DomainException('V2 journal lines are append-only.'));
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
