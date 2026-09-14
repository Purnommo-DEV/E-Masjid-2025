<?php

namespace App\Models\FinancialV2;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends FinancialV2Model
{
    protected $table = 'financial_v2_programs';

    protected $casts = ['start_date' => 'date', 'end_date' => 'date'];

    public function scopeBusinessActiveOn(Builder $query, string $date): Builder
    {
        $date = CarbonImmutable::parse($date)->toDateString();

        return $query
            ->where('status', 'active')
            ->where(fn (Builder $builder) => $builder->whereNull('start_date')->orWhere('start_date', '<=', $date))
            ->where(fn (Builder $builder) => $builder->whereNull('end_date')->orWhere('end_date', '>=', $date));
    }

    public function scopeBusinessActiveThroughout(Builder $query, string $startDate, string $endDate): Builder
    {
        $startDate = CarbonImmutable::parse($startDate)->toDateString();
        $endDate = CarbonImmutable::parse($endDate)->toDateString();

        return $query
            ->where('status', 'active')
            ->where(fn (Builder $builder) => $builder->whereNull('start_date')->orWhere('start_date', '<=', $startDate))
            ->where(fn (Builder $builder) => $builder->whereNull('end_date')->orWhere('end_date', '>=', $endDate));
    }

    public function isBusinessActiveOn(string $date): bool
    {
        $date = CarbonImmutable::parse($date);

        return $this->status === 'active'
            && (! $this->start_date || $this->start_date->lte($date))
            && (! $this->end_date || $this->end_date->gte($date));
    }

    public function distributions(): HasMany
    {
        return $this->hasMany(Distribution::class);
    }

    public function plannings(): HasMany
    {
        return $this->hasMany(Planning::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }
}
