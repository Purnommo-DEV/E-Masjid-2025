<?php

namespace App\Models\FinancialV2;

use DomainException;

class Attachment extends FinancialV2Model
{
    protected $table = 'financial_v2_attachments';

    protected $casts = ['received_at' => 'datetime'];

    protected static function booted(): void
    {
        static::deleting(fn (self $model) => throw new DomainException('Financial evidence is retained; use a superseding version instead.'));
    }
}
