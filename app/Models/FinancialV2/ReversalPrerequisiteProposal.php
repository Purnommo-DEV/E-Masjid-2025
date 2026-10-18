<?php

namespace App\Models\FinancialV2;

use DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ReversalPrerequisiteProposal extends FinancialV2Model
{
    public const LIFECYCLE_FIELDS = ['status', 'active_entity_guard', 'submitted_at', 'approved_at', 'approved_by_user_id', 'materialized_at', 'materialized_by_user_id', 'transaction_type_id', 'document_sequence_id', 'cancelled_at', 'cancelled_by_user_id', 'cancellation_reason'];
    protected $table = 'financial_v2_reversal_prerequisite_proposals';
    protected $casts = ['proposal_snapshot' => 'array', 'submitted_at' => 'datetime', 'approved_at' => 'datetime', 'materialized_at' => 'datetime', 'cancelled_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(fn () => throw new DomainException('Proposal prerequisite REV hanya dapat dibuat oleh persistence private workflow domain.'));
        static::updating(function (self $model): void {
            throw new DomainException('Proposal prerequisite, lineage, dan seluruh metadata lifecycle hanya dapat diubah oleh persistence private workflow domain.');
        });
        static::deleting(fn () => throw new DomainException('Proposal prerequisite REV disimpan permanen untuk audit.'));
    }

    public function governanceDocument(): BelongsTo { return $this->belongsTo(GovernanceDocument::class); }
    public function transactionType(): BelongsTo { return $this->belongsTo(TransactionType::class); }
    public function documentSequence(): BelongsTo { return $this->belongsTo(DocumentSequence::class); }
    public function decisions(): HasMany { return $this->hasMany(ReversalPrerequisiteDecision::class, 'prerequisite_proposal_id')->orderBy('decided_at'); }
}
