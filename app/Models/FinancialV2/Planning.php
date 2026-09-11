<?php

namespace App\Models\FinancialV2;

use DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Planning extends FinancialV2Model
{
    public const STATUSES = ['draft', 'approved', 'converted', 'cancelled'];

    protected $table = 'financial_v2_plannings';

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'target_recipient_count' => 'integer',
        'amount_per_recipient' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'converted_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $planning): void {
            $original = $planning->getOriginal('status');
            if (in_array($original, ['converted', 'cancelled'], true)) {
                throw new DomainException('Converted and cancelled Plannings are immutable.');
            }

            if ($original === 'approved') {
                $allowed = $planning->status === 'converted'
                    ? ['status', 'converted_at', 'converted_by_user_id', 'updated_by_user_id', 'updated_at']
                    : ($planning->status === 'cancelled'
                        ? ['status', 'cancelled_at', 'cancelled_by_user_id', 'cancellation_reason', 'updated_by_user_id', 'updated_at']
                        : []);
                if ($allowed === [] || array_diff(array_keys($planning->getDirty()), $allowed) !== []) {
                    throw new DomainException('Approved Plannings are immutable except for governed conversion or cancellation.');
                }
            }

            if ($original === 'draft' && $planning->isDirty('status')) {
                $requiredTimestamp = $planning->status === 'approved' ? 'approved_at' : ($planning->status === 'cancelled' ? 'cancelled_at' : null);
                if (! $requiredTimestamp || blank($planning->{$requiredTimestamp})) {
                    throw new DomainException('Planning status changes must use the governed lifecycle.');
                }
            }
        });
        static::deleting(fn () => throw new DomainException('Plannings are retained for governance and audit.'));
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function fundings(): HasMany
    {
        return $this->hasMany(PlanningFunding::class)->orderBy('line_no');
    }

    public function allocation(): HasOne
    {
        return $this->hasOne(BudgetAllocation::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by_user_id');
    }

    public function convertedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'converted_by_user_id');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'cancelled_by_user_id');
    }
}
