<?php

namespace App\Models\FinancialV2;

use DomainException;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransactionType extends FinancialV2Model
{
    protected $table = 'financial_v2_transaction_types';

    protected $casts = ['has_financial_impact' => 'boolean', 'valid_from' => 'date', 'valid_to' => 'date'];

    protected static function booted(): void
    {
        static::updating(function (self $model): void {
            $protected = ['accounting_entity_id', 'code', 'name', 'voucher_prefix', 'has_financial_impact', 'status', 'valid_from', 'valid_to'];
            if (array_intersect(array_keys($model->getDirty()), $protected) !== []
                && $model->reversalPrerequisiteIsMaterialized()) {
                throw new DomainException('Master REV materialized hanya dapat berubah dalam aktivasi final workflow yang tervalidasi.');
            }
        });
    }

    private function reversalPrerequisiteIsMaterialized(): bool
    {
        return \Illuminate\Support\Facades\Schema::hasTable('financial_v2_reversal_prerequisite_proposals')
            && ReversalPrerequisiteProposal::query()->where('transaction_type_id', $this->id)->where('status', 'materialized')->exists();
    }

    public function postingRules(): HasMany
    {
        return $this->hasMany(PostingRule::class);
    }
}
