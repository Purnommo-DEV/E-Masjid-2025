<?php

namespace App\Models\FinancialV2;

use DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentSequence extends FinancialV2Model
{
    protected $table = 'financial_v2_document_sequences';

    protected static function booted(): void
    {
        static::updating(function (self $model): void {
            $protected = ['accounting_entity_id', 'transaction_type_id', 'code', 'name', 'prefix', 'scope_key', 'reset_rule', 'next_value', 'status', 'valid_from', 'valid_to'];
            if (array_intersect(array_keys($model->getDirty()), $protected) !== []
                && \Illuminate\Support\Facades\Schema::hasTable('financial_v2_reversal_prerequisite_proposals')
                && ReversalPrerequisiteProposal::query()->where('document_sequence_id', $model->id)->where('status', 'materialized')->exists()) {
                throw new DomainException('Document Sequence REV materialized hanya dapat berubah dalam aktivasi final workflow yang tervalidasi.');
            }
        });
    }

    public function transactionType(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class);
    }
}
