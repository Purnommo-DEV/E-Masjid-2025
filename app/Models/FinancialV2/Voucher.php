<?php

namespace App\Models\FinancialV2;

use DomainException;

class Voucher extends FinancialV2Model
{
    protected $table = 'financial_v2_vouchers';

    protected $casts = ['issued_at' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(function (self $model): void {
            if ($model->getOriginal('status') === 'issued') {
                throw new DomainException('Issued vouchers are immutable.');
            }
        });
        static::deleting(fn (self $model) => throw new DomainException('Vouchers are retained for traceability.'));
    }
}
