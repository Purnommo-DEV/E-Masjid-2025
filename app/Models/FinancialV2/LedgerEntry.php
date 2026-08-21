<?php

namespace App\Models\FinancialV2;

use App\Domain\FinancialV2\FinancialFactWriteGuard;
use DomainException;

class LedgerEntry extends FinancialV2Model
{
    protected $table = 'financial_v2_ledger_entries';

    public $timestamps = false;

    protected $casts = ['accounting_date' => 'date', 'signed_amount' => 'decimal:2', 'created_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => FinancialFactWriteGuard::assertPostingEngineWrite('LedgerEntry'));
        static::updating(fn (self $model) => throw new DomainException('Ledger entries are immutable derived facts.'));
        static::deleting(fn (self $model) => throw new DomainException('Ledger entries are append-only.'));
    }
}
