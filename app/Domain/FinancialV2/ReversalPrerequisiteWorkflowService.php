<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\DocumentSequence;
use App\Models\FinancialV2\ReversalPrerequisiteDecision;
use App\Models\FinancialV2\ReversalPrerequisiteProposal;
use App\Models\FinancialV2\TransactionType;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ReversalPrerequisiteWorkflowService
{
    public function __construct(
        private readonly AuditTrailService $auditTrail,
        private readonly GovernanceDocumentWorkflowService $governanceDocuments,
        private readonly AccountingDateCorrectionGovernancePolicy $governancePolicy,
    ) {}

    /** @param array<string,mixed> $input */
    public function createDraft(string $entityId, array $input, int $actor): ReversalPrerequisiteProposal
    {
        return DB::transaction(function () use ($entityId, $input, $actor): ReversalPrerequisiteProposal {
            $entity = $this->lockEntity($entityId);
            [$document, $approval] = $this->approvedDocument($entityId, (string) $input['governance_document_id']);
            $snapshot = [
                'entity' => ['id' => $entityId, 'code' => $entity->code],
                'transaction_type' => ['code' => strtoupper(trim((string) $input['transaction_type_code'])), 'name' => trim((string) $input['transaction_type_name']), 'voucher_prefix' => strtoupper(trim((string) $input['voucher_prefix'])), 'has_financial_impact' => true, 'valid_from' => $input['effective_from'], 'valid_to' => $input['effective_to'] ?? null],
                'proposal_metadata' => ['transaction_type_description' => trim((string) $input['transaction_type_description']), 'classification' => 'governance_rationale_not_master_attribute'],
                'document_sequence' => ['code' => strtoupper(trim((string) $input['sequence_code'])), 'name' => trim((string) $input['sequence_name']), 'prefix' => strtoupper(trim((string) $input['voucher_prefix'])), 'scope_key' => trim((string) $input['scope_key']), 'reset_rule' => $input['reset_rule'], 'next_value' => (int) $input['initial_number'], 'valid_from' => $input['effective_from'], 'valid_to' => $input['effective_to'] ?? null],
                'activation_governance' => ['separation_rule' => $input['activation_separation_rule']],
                'governance_document' => ['id' => $document->id, 'approval_decision_id' => $approval->id, 'snapshot_hash' => $document->snapshot_hash, 'created_by_user_id' => (int) $document->created_by_user_id, 'approved_by_user_id' => (int) $document->approved_by_user_id, 'approved_at' => $document->approved_at?->toAtomString()],
                'workflow_actors' => ['proposal_maker_user_id' => $actor],
            ];
            $this->validateSnapshot($snapshot);
            $hash = $this->hash($snapshot); $key = (string) $input['idempotency_key'];
            $retry = ReversalPrerequisiteProposal::query()->where('accounting_entity_id', $entityId)->where('idempotency_key', $key)->first();
            if ($retry) { if (! hash_equals($retry->snapshot_hash, $hash)) throw new FinancialDomainException('E-REV-PREREQ-IDEMPOTENCY', 'Idempotency key dipakai untuk proposal berbeda.'); return $retry; }
            if (ReversalPrerequisiteProposal::query()->where('active_entity_guard', $entityId)->lockForUpdate()->exists()) throw new FinancialDomainException('E-REV-PREREQ-ACTIVE', 'Entity masih memiliki proposal prerequisite REV aktif.');
            $this->assertIdentifiersAvailable($entityId, $snapshot);
            $proposal = $this->persistDraft(['accounting_entity_id' => $entityId, 'governance_document_id' => $document->id, 'governance_document_decision_id' => $approval->id, 'proposal_snapshot' => $snapshot, 'snapshot_hash' => $hash, 'idempotency_key' => $key, 'active_entity_guard' => $entityId, 'status' => 'draft', 'created_by_user_id' => $actor]);
            $this->audit($proposal, 'reversal_prerequisite_draft_created', $actor);
            return $proposal;
        }, 3);
    }

    public function submit(string $entityId, string $id, int $actor): ReversalPrerequisiteProposal { return $this->transition($entityId, $id, 'draft', 'pending_review', 'submitted', $actor); }

    public function approve(string $entityId, string $id, int $actor, ?string $note): ReversalPrerequisiteProposal
    {
        return DB::transaction(function () use ($entityId, $id, $actor, $note): ReversalPrerequisiteProposal {
            $this->lockEntity($entityId); $proposal = $this->locked($entityId, $id, 'pending_review');
            $override = null;
            if ((int) $proposal->created_by_user_id === $actor) {
                $override = $this->authorizeScopedOverride($proposal, $actor);
            }
            $this->assertCurrent($proposal); $this->assertIdentifiersAvailable($entityId, $proposal->proposal_snapshot);
            $this->persistTransition($proposal, 'pending_review', 'approved', ['approved_at' => now(), 'approved_by_user_id' => $actor]);
            $this->decision($proposal, 'approved', $actor, $override ? 'SUPERADMIN_OVERRIDE: '.AccountingDateCorrectionGovernancePolicy::OVERRIDE_REASON.' Fingerprint '.$override['fingerprint'] : $note); $this->audit($proposal, 'reversal_prerequisite_approved', $actor);
            return $proposal->fresh('decisions');
        }, 3);
    }

    public function reject(string $entityId, string $id, int $actor, string $note): ReversalPrerequisiteProposal
    {
        return DB::transaction(function () use ($entityId, $id, $actor, $note): ReversalPrerequisiteProposal {
            $this->lockEntity($entityId); $proposal = $this->locked($entityId, $id, 'pending_review');
            if ((int) $proposal->created_by_user_id === $actor) throw new FinancialDomainException('E-REV-PREREQ-MAKER-CHECKER', 'Maker tidak boleh menolak proposal prerequisite sendiri.');
            $this->persistTransition($proposal, 'pending_review', 'rejected', ['active_entity_guard' => null]);
            $this->decision($proposal, 'rejected', $actor, $note); $this->audit($proposal, 'reversal_prerequisite_rejected', $actor); return $proposal->fresh('decisions');
        }, 3);
    }

    public function materialize(string $entityId, string $id, int $actor): ReversalPrerequisiteProposal
    {
        return DB::transaction(function () use ($entityId, $id, $actor): ReversalPrerequisiteProposal {
            $this->lockEntity($entityId); $proposal = $this->locked($entityId, $id, 'approved');
            if ((int) $proposal->created_by_user_id === $actor) $this->authorizeScopedOverride($proposal, $actor);
            if (($proposal->proposal_snapshot['activation_governance']['separation_rule'] ?? null) === 'three_distinct') throw new FinancialDomainException('E-REV-ACTOR-POLICY-UNRESOLVED', 'Actor matrix final three_distinct belum terdokumentasi sebagai policy executable; materialisasi tetap diblokir.');
            $this->assertCurrent($proposal); $this->assertIdentifiersAvailable($entityId, $proposal->proposal_snapshot);
            if ($proposal->transaction_type_id || $proposal->document_sequence_id) throw new FinancialDomainException('E-REV-PREREQ-ALREADY-MATERIALIZED', 'Proposal telah memiliki referensi master dan tidak boleh dimaterialisasikan ulang.');
            $snapshot = $proposal->proposal_snapshot; $typeData = $snapshot['transaction_type']; $sequenceData = $snapshot['document_sequence'];
            $type = TransactionType::query()->create(['accounting_entity_id' => $entityId, 'code' => $typeData['code'], 'name' => $typeData['name'], 'voucher_prefix' => $typeData['voucher_prefix'], 'has_financial_impact' => true, 'status' => 'draft', 'valid_from' => $typeData['valid_from'], 'valid_to' => $typeData['valid_to'], 'created_by_user_id' => $actor, 'updated_by_user_id' => $actor]);
            $sequence = DocumentSequence::query()->create(['accounting_entity_id' => $entityId, 'transaction_type_id' => $type->id, 'code' => $sequenceData['code'], 'name' => $sequenceData['name'], 'prefix' => $sequenceData['prefix'], 'scope_key' => $sequenceData['scope_key'], 'next_value' => $sequenceData['next_value'], 'reset_rule' => $sequenceData['reset_rule'], 'status' => 'draft', 'valid_from' => $sequenceData['valid_from'], 'valid_to' => $sequenceData['valid_to'], 'created_by_user_id' => $actor, 'updated_by_user_id' => $actor]);
            $this->persistTransition($proposal, 'approved', 'materialized', ['active_entity_guard' => null, 'transaction_type_id' => $type->id, 'document_sequence_id' => $sequence->id, 'materialized_at' => now(), 'materialized_by_user_id' => $actor]);
            $this->assertMaterializedMastersMatch($proposal->fresh(), true);
            $this->decision($proposal, 'materialized', $actor, 'Pasangan master dibuat sebagai draft/nonaktif; belum dapat dipakai PostingEngine.'); $this->audit($proposal, 'reversal_prerequisite_materialized', $actor);
            return $proposal->fresh(['decisions', 'transactionType', 'documentSequence']);
        }, 3);
    }

    public function cancel(string $entityId, string $id, int $actor, string $reason): ReversalPrerequisiteProposal
    {
        return DB::transaction(function () use ($entityId, $id, $actor, $reason): ReversalPrerequisiteProposal {
            $this->lockEntity($entityId);
            $proposal = ReversalPrerequisiteProposal::query()->where('accounting_entity_id', $entityId)->lockForUpdate()->find($id);
            if (! $proposal || ! in_array($proposal->status, ['draft', 'pending_review', 'approved'], true)) throw new FinancialDomainException('E-REV-PREREQ-CANCEL', 'Status proposal tidak aman untuk dibatalkan. Proposal materialized memerlukan prosedur kompensasi.');
            $from = $proposal->status;
            $this->persistTransition($proposal, $from, 'cancelled', ['active_entity_guard' => null, 'cancelled_at' => now(), 'cancelled_by_user_id' => $actor, 'cancellation_reason' => trim($reason)]);
            $this->decision($proposal, 'cancelled', $actor, $reason);
            $this->audit($proposal, 'reversal_prerequisite_cancelled', $actor);
            return $proposal->fresh('decisions');
        }, 3);
    }

    /** @return array{0:TransactionType,1:DocumentSequence} */
    public function assertMaterializedMastersMatch(ReversalPrerequisiteProposal $proposal, bool $lock): array
    {
        if ($proposal->status !== 'materialized' || ! $proposal->transaction_type_id || ! $proposal->document_sequence_id) throw new FinancialDomainException('E-REV-PREREQ-NOT-MATERIALIZED', 'Proposal prerequisite belum materialized.');
        $typeQuery = TransactionType::query()->where('accounting_entity_id', $proposal->accounting_entity_id);
        $sequenceQuery = DocumentSequence::query()->where('accounting_entity_id', $proposal->accounting_entity_id);
        if ($lock) { $typeQuery->lockForUpdate(); $sequenceQuery->lockForUpdate(); }
        $type = $typeQuery->find($proposal->transaction_type_id);
        $sequence = $sequenceQuery->find($proposal->document_sequence_id);
        $expectedType = $proposal->proposal_snapshot['transaction_type'] ?? [];
        $expectedSequence = $proposal->proposal_snapshot['document_sequence'] ?? [];
        $date = static fn ($value) => $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : ($value === null ? null : (string) $value);
        if (! $type || ! $sequence || $sequence->transaction_type_id !== $type->id
            || $type->accounting_entity_id !== $proposal->accounting_entity_id || $sequence->accounting_entity_id !== $proposal->accounting_entity_id
            || $type->code !== ($expectedType['code'] ?? null) || $type->name !== ($expectedType['name'] ?? null)
            || $type->voucher_prefix !== ($expectedType['voucher_prefix'] ?? null) || (bool) $type->has_financial_impact !== (bool) ($expectedType['has_financial_impact'] ?? false)
            || $type->status !== 'draft' || $date($type->valid_from) !== ($expectedType['valid_from'] ?? null) || $date($type->valid_to) !== ($expectedType['valid_to'] ?? null)
            || $sequence->code !== ($expectedSequence['code'] ?? null) || $sequence->name !== ($expectedSequence['name'] ?? null)
            || $sequence->prefix !== ($expectedSequence['prefix'] ?? null) || $sequence->scope_key !== ($expectedSequence['scope_key'] ?? null)
            || $sequence->reset_rule !== ($expectedSequence['reset_rule'] ?? null) || (int) $sequence->next_value !== (int) ($expectedSequence['next_value'] ?? 0)
            || $sequence->status !== 'draft' || $date($sequence->valid_from) !== ($expectedSequence['valid_from'] ?? null) || $date($sequence->valid_to) !== ($expectedSequence['valid_to'] ?? null)) {
            throw new FinancialDomainException('E-REV-PREREQ-MASTER-DRIFT', 'Master materialized tidak lagi cocok dengan snapshot proposal yang disetujui.');
        }
        return [$type, $sequence];
    }

    private function transition(string $entityId, string $id, string $from, string $to, string $decision, int $actor): ReversalPrerequisiteProposal
    {
        return DB::transaction(function () use ($entityId, $id, $from, $to, $decision, $actor): ReversalPrerequisiteProposal {
            $this->lockEntity($entityId); $proposal = $this->locked($entityId, $id, $from); $this->assertCurrent($proposal);
            $this->persistTransition($proposal, $from, $to, ['submitted_at' => now()]); $this->decision($proposal, $decision, $actor, null); $this->audit($proposal, 'reversal_prerequisite_'.$decision, $actor); return $proposal->fresh('decisions');
        }, 3);
    }

    private function assertCurrent(ReversalPrerequisiteProposal $proposal): void
    {
        if (! hash_equals($proposal->snapshot_hash, $this->hash($proposal->proposal_snapshot))) throw new FinancialDomainException('E-REV-PREREQ-SNAPSHOT', 'Snapshot proposal prerequisite tidak lagi utuh.');
        [$document, $decision] = $this->approvedDocument($proposal->accounting_entity_id, $proposal->governance_document_id);
        $snapshot = $proposal->proposal_snapshot['governance_document'] ?? [];
        if ($decision->id !== $proposal->governance_document_decision_id || ($snapshot['snapshot_hash'] ?? null) !== $document->snapshot_hash || ($snapshot['approval_decision_id'] ?? null) !== $decision->id) throw new FinancialDomainException('E-REV-PREREQ-GOVERNANCE-CHANGED', 'Approval dokumen governance tidak lagi cocok dengan snapshot proposal.');
    }

    private function approvedDocument(string $entityId, string $id): array { return $this->governanceDocuments->approvedForUse($entityId, $id, true); }

    private function validateSnapshot(array $snapshot): void
    {
        if (($snapshot['transaction_type']['code'] ?? null) !== 'REV' || blank($snapshot['transaction_type']['name'] ?? null) || blank($snapshot['proposal_metadata']['transaction_type_description'] ?? null) || ($snapshot['proposal_metadata']['classification'] ?? null) !== 'governance_rationale_not_master_attribute' || blank($snapshot['transaction_type']['voucher_prefix'] ?? null) || ($snapshot['document_sequence']['prefix'] ?? null) !== ($snapshot['transaction_type']['voucher_prefix'] ?? null) || ! in_array($snapshot['document_sequence']['reset_rule'] ?? null, ['never', 'yearly', 'monthly'], true) || (int) ($snapshot['document_sequence']['next_value'] ?? 0) < 1 || ! in_array($snapshot['activation_governance']['separation_rule'] ?? null, ['maker_checker', 'three_distinct', 'superadmin_override'], true) || blank($snapshot['transaction_type']['valid_from'] ?? null)) throw new FinancialDomainException('E-REV-PREREQ-INCOMPLETE', 'Keputusan prerequisite REV belum lengkap atau tidak konsisten.');
    }

    private function authorizeScopedOverride(ReversalPrerequisiteProposal $proposal, int $actor): array
    {
        if (($proposal->proposal_snapshot['activation_governance']['separation_rule'] ?? null) !== 'superadmin_override') {
            throw new FinancialDomainException('E-REV-PREREQ-MAKER-CHECKER', 'Maker tidak boleh menyetujui atau mematerialisasikan proposal sendiri tanpa scoped SuperAdmin Override.');
        }
        $user = User::query()->find($actor);
        return $this->governancePolicy->authorizeSuperAdminOverride($actor, $actor, $actor, $user?->hasRole('SuperAdmin') === true, null, AccountingDateCorrectionGovernancePolicy::BANK_CORRELATION_ID, AccountingDateCorrectionGovernancePolicy::OVERRIDE_REASON);
    }

    private function assertIdentifiersAvailable(string $entityId, array $snapshot): void
    {
        if (TransactionType::query()->where('accounting_entity_id', $entityId)->where('code', $snapshot['transaction_type']['code'])->exists() || DocumentSequence::query()->where('accounting_entity_id', $entityId)->where('code', $snapshot['document_sequence']['code'])->exists()) throw new FinancialDomainException('E-REV-PREREQ-DUPLICATE', 'Kode Transaction Type atau Document Sequence sudah digunakan pada Entity ini.');
    }

    private function locked(string $entityId, string $id, string $status): ReversalPrerequisiteProposal
    {
        $proposal = ReversalPrerequisiteProposal::query()->where('accounting_entity_id', $entityId)->lockForUpdate()->find($id); if (! $proposal || $proposal->status !== $status) throw new FinancialDomainException('E-REV-PREREQ-STATE', 'Status atau Entity proposal prerequisite tidak sesuai.'); return $proposal;
    }

    private function lockEntity(string $entityId): object
    {
        $entity = DB::table('financial_v2_accounting_entities')->where('id', $entityId)->where('status', 'active')->lockForUpdate()->first(); if (! $entity) throw new FinancialDomainException('E-REV-PREREQ-ENTITY', 'Accounting Entity aktif tidak tersedia.'); return $entity;
    }

    private function decision(ReversalPrerequisiteProposal $proposal, string $decision, int $actor, ?string $note): void
    {
        DB::table((new ReversalPrerequisiteDecision)->getTable())->insert(['id' => (string) Str::uuid(), 'accounting_entity_id' => $proposal->accounting_entity_id, 'prerequisite_proposal_id' => $proposal->id, 'decision' => $decision, 'proposal_snapshot' => json_encode($proposal->proposal_snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), 'snapshot_hash' => $proposal->snapshot_hash, 'note' => $note, 'actor_user_id' => $actor, 'decided_at' => now(), 'created_at' => now()]);
    }
    /** @param array<string,mixed> $values */
    private function persistDraft(array $values): ReversalPrerequisiteProposal
    {
        if (($values['status'] ?? null) !== 'draft' || blank($values['active_entity_guard'] ?? null)) throw new FinancialDomainException('E-REV-PREREQ-DRAFT', 'Persistence hanya menerima draft prerequisite canonical.');
        $id = (string) Str::uuid();
        DB::table((new ReversalPrerequisiteProposal)->getTable())->insert(array_merge($values, ['id' => $id, 'proposal_snapshot' => json_encode($values['proposal_snapshot'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), 'created_at' => now(), 'updated_at' => now()]));
        return ReversalPrerequisiteProposal::query()->findOrFail($id);
    }
    private function persistTransition(ReversalPrerequisiteProposal $proposal, string $from, string $to, array $values): void
    {
        $allowed = ['draft' => ['pending_review', 'cancelled'], 'pending_review' => ['approved', 'rejected', 'cancelled'], 'approved' => ['materialized', 'cancelled']];
        if (! in_array($to, $allowed[$from] ?? [], true) || $proposal->status !== $from) throw new FinancialDomainException('E-REV-PREREQ-STATE', "Transisi {$from} ke {$to} tidak diizinkan.");
        if ($to === 'materialized') {
            $type = TransactionType::query()->find($values['transaction_type_id'] ?? null);
            $sequence = DocumentSequence::query()->find($values['document_sequence_id'] ?? null);
            if (! $type || ! $sequence || $type->accounting_entity_id !== $proposal->accounting_entity_id || $sequence->accounting_entity_id !== $proposal->accounting_entity_id || $sequence->transaction_type_id !== $type->id) throw new FinancialDomainException('E-REV-PREREQ-LINEAGE', 'Lineage materialisasi tidak sah.');
        }
        $updated = DB::table($proposal->getTable())->where('id', $proposal->id)->where('accounting_entity_id', $proposal->accounting_entity_id)->where('status', $from)->whereNull('transaction_type_id')->whereNull('document_sequence_id')->update(array_merge($values, ['status' => $to, 'updated_at' => now()]));
        if ($updated !== 1) throw new FinancialDomainException('E-REV-PREREQ-RACE', 'Proposal berubah saat transisi; operasi dibatalkan.');
        $proposal->refresh();
    }
    private function audit(ReversalPrerequisiteProposal $proposal, string $event, int $actor): void { $this->auditTrail->record($proposal->accounting_entity_id, $event, 'reversal_prerequisite_proposal', $proposal->id, $proposal->id, $actor, null, ['status' => $proposal->status, 'snapshot_hash' => $proposal->snapshot_hash, 'transaction_type_id' => $proposal->transaction_type_id, 'document_sequence_id' => $proposal->document_sequence_id]); }
    private function hash(array $snapshot): string { return hash('sha256', json_encode(Arr::sortRecursive($snapshot), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)); }
}
