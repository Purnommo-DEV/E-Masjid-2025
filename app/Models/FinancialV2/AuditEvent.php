<?php

namespace App\Models\FinancialV2;

use DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditEvent extends FinancialV2Model
{
    protected $table = 'financial_v2_audit_events';

    public $timestamps = false;

    protected $casts = ['event_at' => 'datetime', 'created_at' => 'datetime'];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'actor_user_id');
    }

    protected static function booted(): void
    {
        static::updating(fn (self $model) => throw new DomainException('Audit events are append-only.'));
        static::deleting(fn (self $model) => throw new DomainException('Audit events are append-only.'));
    }
}
