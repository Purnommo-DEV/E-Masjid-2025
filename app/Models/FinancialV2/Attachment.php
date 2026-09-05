<?php

namespace App\Models\FinancialV2;

use DomainException;

class Attachment extends FinancialV2Model
{
    protected $table = 'financial_v2_attachments';

    protected $casts = [
        'received_at' => 'datetime',
        'byte_size' => 'integer',
        'source_byte_size' => 'integer',
        'image_width' => 'integer',
        'image_height' => 'integer',
    ];

    protected static function booted(): void
    {
        static::deleting(fn (self $model) => throw new DomainException('Financial evidence is retained; use a superseding version instead.'));
    }
}
