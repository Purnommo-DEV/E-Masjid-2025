<?php

namespace App\Models\FinancialV2;

use DomainException;
use Illuminate\Support\Facades\Schema;

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
        static::updating(function (self $model): void {
            $immutable = ['accounting_entity_id', 'original_filename', 'content_hash', 'byte_size', 'media_type', 'storage_reference', 'status', 'received_at', 'source_metadata', 'source_byte_size', 'image_width', 'image_height'];
            if (array_intersect(array_keys($model->getDirty()), $immutable) !== []
                && Schema::hasTable('financial_v2_governance_documents')
                && GovernanceDocument::query()->where('attachment_id', $model->id)->whereIn('status', ['approved', 'superseded'])->exists()) {
                throw new DomainException('Attachment dokumen governance approved bersifat immutable; unggah dokumen pengganti melalui workflow resmi.');
            }
        });
        static::deleting(fn (self $model) => throw new DomainException('Financial evidence is retained; use a superseding version instead.'));
    }
}
