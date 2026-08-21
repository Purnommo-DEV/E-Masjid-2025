<?php

namespace App\Models\FinancialV2;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

abstract class FinancialV2Model extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    public function scopeForEntity(Builder $query, string $accountingEntityId): Builder
    {
        return $query->where('accounting_entity_id', $accountingEntityId);
    }
}
