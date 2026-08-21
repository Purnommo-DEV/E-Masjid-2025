<?php

namespace App\Models\FinancialV2;

use DomainException;

class AttachmentLink extends FinancialV2Model
{
    protected $table = 'financial_v2_attachment_links';

    protected static function booted(): void
    {
        static::deleting(fn (self $model) => throw new DomainException('Financial evidence links are retained with an audited status.'));
    }
}
