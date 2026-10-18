<?php

namespace App\Models\FinancialV2;

use DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class GovernanceDocument extends FinancialV2Model
{
    public const LIFECYCLE_FIELDS = ['status', 'active_purpose_guard', 'submitted_at', 'approved_at', 'approved_by_user_id', 'cancelled_at', 'cancelled_by_user_id', 'cancellation_reason'];
    protected $table = 'financial_v2_governance_documents';
    protected $casts = ['document_snapshot' => 'array', 'submitted_at' => 'datetime', 'approved_at' => 'datetime', 'cancelled_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(fn () => throw new DomainException('Dokumen governance hanya dapat dibuat oleh persistence private workflow domain.'));
        static::updating(function (self $model): void {
            throw new DomainException('Dokumen governance dan seluruh metadata lifecycle hanya dapat diubah oleh persistence private workflow domain.');
        });
        static::deleting(fn () => throw new DomainException('Dokumen governance disimpan permanen untuk audit.'));
    }

    public function attachment(): BelongsTo { return $this->belongsTo(Attachment::class); }
    public function decisions(): HasMany { return $this->hasMany(GovernanceDocumentDecision::class)->orderBy('decided_at'); }
}
