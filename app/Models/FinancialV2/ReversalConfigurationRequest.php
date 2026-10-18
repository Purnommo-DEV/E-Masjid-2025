<?php

namespace App\Models\FinancialV2;

use DomainException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ReversalConfigurationRequest extends FinancialV2Model
{
    public const LIFECYCLE_FIELDS = ['status', 'active_entity_guard', 'submitted_at', 'approved_at', 'approved_by_user_id', 'activated_at', 'activated_by_user_id', 'transaction_type_id', 'posting_rule_id', 'posting_rule_version_id', 'reason_code_id', 'approval_requirement_id', 'evidence_requirement_ids', 'document_sequence_id', 'cancelled_at', 'cancelled_by_user_id', 'cancellation_reason'];
    protected $table = 'financial_v2_reversal_configuration_requests';

    protected $casts = [
        'configuration_snapshot' => 'array',
        'evidence_requirement_ids' => 'array',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'activated_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn () => throw new DomainException('Request konfigurasi REV hanya dapat dibuat oleh persistence private workflow domain.'));
        static::updating(fn () => throw new DomainException('Request konfigurasi REV dan seluruh metadata lifecycle hanya dapat diubah oleh persistence private workflow domain.'));
        static::deleting(fn () => throw new DomainException('Permintaan konfigurasi REV disimpan permanen untuk audit.'));
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(ReversalConfigurationDecision::class, 'configuration_request_id')->orderBy('decided_at');
    }

    public function prerequisiteProposal(): BelongsTo
    {
        return $this->belongsTo(ReversalPrerequisiteProposal::class, 'prerequisite_proposal_id');
    }

    public function governanceDocumentDecision(): BelongsTo
    {
        return $this->belongsTo(GovernanceDocumentDecision::class, 'governance_document_decision_id');
    }
}
