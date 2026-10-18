<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\ApprovalRequirement;
use App\Models\FinancialV2\Attachment;
use App\Models\FinancialV2\DocumentSequence;
use App\Models\FinancialV2\EvidenceRequirement;
use App\Models\FinancialV2\GovernanceDocument;
use App\Models\FinancialV2\GovernanceDocumentDecision;
use App\Models\FinancialV2\PostingRule;
use App\Models\FinancialV2\PostingRuleLine;
use App\Models\FinancialV2\PostingRuleVersion;
use App\Models\FinancialV2\ReasonCode;
use App\Models\FinancialV2\ReversalConfigurationDecision;
use App\Models\FinancialV2\ReversalConfigurationRequest;
use App\Models\FinancialV2\ReversalPrerequisiteProposal;
use App\Models\FinancialV2\TransactionType;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Governance package for the REV master set. Draft/review records contain no
 * posting master and cannot be resolved by PostingEngine. Materialization is a
 * separate, deliberate activation after maker-checker approval.
 */
final class ReversalConfigurationWorkflowService
{
    private const EVIDENCE_TYPES = ['receipt', 'invoice', 'transfer_proof', 'statement', 'cash_count', 'approval', 'policy', 'other'];

    public function __construct(
        private readonly AuditTrailService $auditTrail,
        private readonly MasterDataGovernanceService $masterGovernance,
        private readonly GovernanceDocumentWorkflowService $governanceDocuments,
        private readonly ReversalPrerequisiteWorkflowService $prerequisites,
        private readonly AccountingDateCorrectionGovernancePolicy $governancePolicy,
    ) {}

    /** @param array<string, mixed> $input */
    public function createDraft(string $entityId, array $input, int $actorUserId): ReversalConfigurationRequest
    {
        $idempotencyKey = (string) $input['idempotency_key'];

        return DB::transaction(function () use ($entityId, $input, $idempotencyKey, $actorUserId): ReversalConfigurationRequest {
            $entity = $this->lockActiveEntity($entityId);
            $prerequisite = $this->prerequisite($entityId, (string) $input['prerequisite_proposal_id'], true);
            [$governanceDocument, $governanceDecision] = $this->approvedGovernanceDocument($prerequisite, true);
            $governanceAttachment = $this->governanceAttachment($entityId, (string) $governanceDocument->attachment_id, true);
            [, $documentSequence] = $this->prerequisites->assertMaterializedMastersMatch($prerequisite, true);
            $snapshot = $this->canonicalSnapshot($entity, $input, $documentSequence, $governanceAttachment, $prerequisite, $governanceDocument, $governanceDecision);
            $this->validateSnapshot($snapshot);
            $hash = $this->hash($snapshot);

            $retry = ReversalConfigurationRequest::query()
                ->where('accounting_entity_id', $entityId)
                ->where('idempotency_key', $idempotencyKey)
                ->first();
            if ($retry) {
                if (! hash_equals($retry->snapshot_hash, $hash)) {
                    throw new FinancialDomainException('E-REV-CONFIG-IDEMPOTENCY', 'Idempotency key telah digunakan untuk substansi konfigurasi yang berbeda.');
                }

                return $retry;
            }

            if (ReversalConfigurationRequest::query()->where('active_entity_guard', $entityId)->lockForUpdate()->exists()) {
                throw new FinancialDomainException('E-REV-CONFIG-ACTIVE', 'Entity masih mempunyai workflow konfigurasi REV aktif.');
            }
            $this->assertNoExistingReversalMaster($entityId, $documentSequence->transaction_type_id);
            $this->assertIdentifiersAvailable($entityId, $snapshot);

            $request = $this->persistDraft([
                'accounting_entity_id' => $entityId,
                'configuration_snapshot' => $snapshot,
                'snapshot_hash' => $hash,
                'governance_document_ref' => $governanceAttachment->id,
                'governance_attachment_id' => $governanceAttachment->id,
                'governance_document_decision_id' => $governanceDecision->id,
                'document_sequence_id' => $documentSequence->id,
                'prerequisite_proposal_id' => $prerequisite->id,
                'idempotency_key' => $idempotencyKey,
                'active_entity_guard' => $entityId,
                'status' => 'draft',
                'created_by_user_id' => $actorUserId,
            ]);
            $this->audit('reversal_configuration_draft_created', $request, $actorUserId);

            return $request;
        }, 3);
    }

    public function submit(string $entityId, string $requestId, int $actorUserId): ReversalConfigurationRequest
    {
        return $this->transition($entityId, $requestId, 'draft', 'pending_review', $actorUserId, 'submitted');
    }

    public function approve(string $entityId, string $requestId, int $actorUserId, ?string $note = null): ReversalConfigurationRequest
    {
        return DB::transaction(function () use ($entityId, $requestId, $actorUserId, $note): ReversalConfigurationRequest {
            $this->lockActiveEntity($entityId);
            $request = $this->locked($entityId, $requestId, 'pending_review');
            $prerequisite = $this->assertPrerequisiteStillMatches($request, true);
            $override = null;
            if ((int) $request->created_by_user_id === $actorUserId) {
                $override = $this->authorizeScopedOverride($prerequisite, $actorUserId);
            }
            $this->assertSnapshotIntegrity($request);
            $this->validateSnapshot($request->configuration_snapshot);
            $this->assertGovernanceEvidenceStillMatches($request, true);
            $documentSequence = $this->documentSequence($entityId, (string) $request->document_sequence_id, true);
            $this->assertDocumentSequenceStillMatches($request, $documentSequence);
            $this->assertNoExistingReversalMaster($entityId, $documentSequence->transaction_type_id);
            $this->assertIdentifiersAvailable($entityId, $request->configuration_snapshot);

            $this->persistTransition($request, 'pending_review', 'approved', ['approved_at' => now(), 'approved_by_user_id' => $actorUserId]);
            $this->decision($request, 'approved', $actorUserId, $override ? 'SUPERADMIN_OVERRIDE '.$override['fingerprint'].' '.AccountingDateCorrectionGovernancePolicy::OVERRIDE_REASON : $note);
            $this->audit('reversal_configuration_approved', $request, $actorUserId);

            return $request->fresh('decisions');
        }, 3);
    }

    public function reject(string $entityId, string $requestId, int $actorUserId, string $note): ReversalConfigurationRequest
    {
        return DB::transaction(function () use ($entityId, $requestId, $actorUserId, $note): ReversalConfigurationRequest {
            $this->lockActiveEntity($entityId);
            $request = $this->locked($entityId, $requestId, 'pending_review');
            if ((int) $request->created_by_user_id === $actorUserId) {
                throw new FinancialDomainException('E-REV-CONFIG-MAKER-CHECKER', 'Pembuat draft tidak boleh memutus review konfigurasi REV miliknya sendiri.');
            }
            $this->persistTransition($request, 'pending_review', 'rejected', ['active_entity_guard' => null]);
            $this->decision($request, 'rejected', $actorUserId, $note);
            $this->audit('reversal_configuration_rejected', $request, $actorUserId);

            return $request->fresh('decisions');
        }, 3);
    }

    public function cancel(string $entityId, string $requestId, int $actorUserId, string $reason): ReversalConfigurationRequest
    {
        return DB::transaction(function () use ($entityId, $requestId, $actorUserId, $reason): ReversalConfigurationRequest {
            $this->lockActiveEntity($entityId);
            $request = ReversalConfigurationRequest::query()->where('accounting_entity_id', $entityId)->lockForUpdate()->find($requestId);
            if (! $request || ! in_array($request->status, ['draft', 'pending_review', 'approved'], true)) throw new FinancialDomainException('E-REV-CONFIG-CANCEL', 'Status workflow REV tidak aman untuk dibatalkan.');
            $from = $request->status;
            $this->persistTransition($request, $from, 'cancelled', ['active_entity_guard' => null, 'cancelled_at' => now(), 'cancelled_by_user_id' => $actorUserId, 'cancellation_reason' => trim($reason)]);
            $this->decision($request, 'cancelled', $actorUserId, $reason);
            $this->audit('reversal_configuration_cancelled', $request, $actorUserId);
            return $request->fresh('decisions');
        }, 3);
    }

    /**
     * Explicit activation materializes the approved package. It never posts a
     * transaction. REV has no PostingRuleLine: its journal is compiled only by
     * reversing the locked original JournalLines in PostingEngine.
     */
    public function activate(string $entityId, string $requestId, int $actorUserId): ReversalConfigurationRequest
    {
        return DB::transaction(function () use ($entityId, $requestId, $actorUserId): ReversalConfigurationRequest {
            $this->lockActiveEntity($entityId);
            $request = $this->locked($entityId, $requestId, 'approved');
            $prerequisite = $this->assertPrerequisiteStillMatches($request, true);
            $isOverride = ($prerequisite->proposal_snapshot['activation_governance']['separation_rule'] ?? null) === 'superadmin_override';
            $override = null;
            if ((int) $request->created_by_user_id === $actorUserId || (int) $request->approved_by_user_id === (int) $request->created_by_user_id) {
                $override = $this->authorizeScopedOverride($prerequisite, $actorUserId);
            }
            if (! $request->approved_at || ! $request->approved_by_user_id || ((int) $request->approved_by_user_id === (int) $request->created_by_user_id && ! $isOverride)) {
                throw new FinancialDomainException('E-REV-CONFIG-APPROVAL', 'Approval maker-checker yang sah belum tersedia.');
            }
            $this->assertSnapshotIntegrity($request);
            $snapshot = $request->configuration_snapshot;
            $this->validateSnapshot($snapshot);
            $this->assertGovernanceEvidenceStillMatches($request, true);
            $documentSequence = $this->documentSequence($entityId, (string) $request->document_sequence_id, true);
            $this->assertDocumentSequenceStillMatches($request, $documentSequence);
            if (($prerequisite->proposal_snapshot['activation_governance']['separation_rule'] ?? null) === 'three_distinct') {
                throw new FinancialDomainException('E-REV-ACTOR-POLICY-UNRESOLVED', 'Aktivasi diblokir: actor matrix final three_distinct belum terdokumentasi sebagai policy executable.');
            }
            $this->assertNoExistingReversalMaster($entityId, $documentSequence->transaction_type_id);
            $this->assertIdentifiersAvailable($entityId, $snapshot);

            $type = TransactionType::query()->lockForUpdate()->findOrFail($documentSequence->transaction_type_id);
            $this->assertDraftTypeMatchesSnapshot($type, $snapshot);
            [$type, $documentSequence] = $this->activatePrerequisitePair($request, $prerequisite, $type, $documentSequence, $actorUserId);
            $rule = PostingRule::query()->create([
                'accounting_entity_id' => $entityId, 'transaction_type_id' => $type->id,
                'code' => $snapshot['posting_rule']['code'], 'name' => $snapshot['posting_rule']['name'],
                'rule_family' => 'reversal', 'status' => 'active',
                'valid_from' => $snapshot['effective_from'], 'valid_to' => $snapshot['effective_to'],
                'created_by_user_id' => $actorUserId, 'updated_by_user_id' => $actorUserId,
            ]);
            $version = PostingRuleVersion::query()->create([
                'accounting_entity_id' => $entityId, 'posting_rule_id' => $rule->id, 'version_no' => 1,
                'effective_from' => $snapshot['effective_from'], 'effective_to' => $snapshot['effective_to'],
                'input_contract_ref' => $snapshot['posting_rule']['input_contract_ref'],
                'journal_template_ref' => $snapshot['posting_rule']['journal_template_ref'],
                'business_rule_refs' => $snapshot['posting_rule']['business_rule_refs'],
                'compilation_strategy' => PostingRuleVersion::STRATEGY_SOURCE_JOURNAL_REVERSAL,
                'status' => 'draft', 'approved_at' => null, 'approved_by_user_id' => null,
                'created_by_user_id' => $actorUserId, 'updated_by_user_id' => $actorUserId,
            ]);
            $reason = ReasonCode::query()->create([
                'accounting_entity_id' => $entityId, 'code' => $snapshot['reason_code']['code'],
                'name' => $snapshot['reason_code']['name'], 'reason_class' => 'reversal',
                'requires_note' => true, 'requires_attachment' => true, 'status' => 'active',
                'valid_from' => $snapshot['effective_from'], 'valid_to' => $snapshot['effective_to'],
                'created_by_user_id' => $actorUserId, 'updated_by_user_id' => $actorUserId,
            ]);
            $approval = ApprovalRequirement::query()->create([
                'accounting_entity_id' => $entityId, 'transaction_type_id' => $type->id,
                'required_steps' => $snapshot['approval']['required_steps'], 'status' => 'active',
                'effective_from' => $snapshot['effective_from'], 'effective_to' => $snapshot['effective_to'],
            ]);
            $evidenceRecords = [];
            foreach ($snapshot['evidence_requirements'] as $evidence) {
                $evidenceRecords[] = EvidenceRequirement::query()->create([
                    'accounting_entity_id' => $entityId, 'posting_rule_version_id' => $version->id,
                    'evidence_type' => $evidence['evidence_type'], 'minimum_count' => $evidence['minimum_count'],
                ]);
            }
            if (PostingRuleLine::query()->where('posting_rule_version_id', $version->id)->exists()) {
                throw new FinancialDomainException('E-REV-CONFIG-RULE-LINES', 'REV source-journal reversal tidak boleh mempunyai Posting Rule Lines.');
            }
            $version = $this->masterGovernance->makePostingRuleVersionEffective($version->id, $actorUserId);

            $this->persistTransition($request, 'approved', 'activated', [
                'active_entity_guard' => null,
                'activated_at' => now(), 'activated_by_user_id' => $actorUserId,
                'transaction_type_id' => $type->id, 'posting_rule_id' => $rule->id,
                'posting_rule_version_id' => $version->id, 'reason_code_id' => $reason->id,
                'approval_requirement_id' => $approval->id,
                'evidence_requirement_ids' => collect($evidenceRecords ?? [])->pluck('id')->values()->all(),
                'document_sequence_id' => $documentSequence->id,
            ]);
            $this->decision($request, 'activated', $actorUserId, ($override ? 'SUPERADMIN_OVERRIDE '.$override['fingerprint'].' ' : '').'Materialisasi master disetujui; tidak ada transaksi atau jurnal yang dibuat.');
            $this->audit('reversal_configuration_activated', $request, $actorUserId);

            return $request->fresh('decisions');
        }, 3);
    }

    private function transition(string $entityId, string $requestId, string $from, string $to, int $actorUserId, string $decision): ReversalConfigurationRequest
    {
        return DB::transaction(function () use ($entityId, $requestId, $from, $to, $actorUserId, $decision): ReversalConfigurationRequest {
            $this->lockActiveEntity($entityId);
            $request = $this->locked($entityId, $requestId, $from);
            $this->assertSnapshotIntegrity($request);
            $this->validateSnapshot($request->configuration_snapshot);
            $this->assertPrerequisiteStillMatches($request, true);
            $this->assertGovernanceEvidenceStillMatches($request, true);
            $documentSequence = $this->documentSequence($entityId, (string) $request->document_sequence_id, true);
            $this->assertDocumentSequenceStillMatches($request, $documentSequence);
            $this->persistTransition($request, $from, $to, ['submitted_at' => now()]);
            $this->decision($request, $decision, $actorUserId);
            $this->audit('reversal_configuration_'.$decision, $request, $actorUserId);

            return $request->fresh('decisions');
        }, 3);
    }

    /**
     * The only application-level operation allowed to activate the materialized
     * prerequisite pair. It is private deliberately: no reusable capability,
     * callback, token, or mutable process-wide context is exposed.
     *
     * @return array{0:TransactionType,1:DocumentSequence}
     */
    private function activatePrerequisitePair(
        ReversalConfigurationRequest $request,
        ReversalPrerequisiteProposal $proposal,
        TransactionType $type,
        DocumentSequence $sequence,
        int $actorUserId,
    ): array {
        if ($request->status !== 'approved' || $proposal->status !== 'materialized'
            || $request->accounting_entity_id !== $proposal->accounting_entity_id
            || $request->prerequisite_proposal_id !== $proposal->id
            || $request->document_sequence_id !== $sequence->id
            || $proposal->transaction_type_id !== $type->id
            || $proposal->document_sequence_id !== $sequence->id
            || ! $request->approved_at || ! $request->approved_by_user_id
            || ((int) $request->approved_by_user_id === (int) $request->created_by_user_id
                && ($proposal->proposal_snapshot['activation_governance']['separation_rule'] ?? null) !== 'superadmin_override')) {
            throw new FinancialDomainException('E-REV-CONFIG-ACTIVATION-CONTEXT', 'Konteks final activation REV tidak sah.');
        }
        if (($proposal->proposal_snapshot['activation_governance']['separation_rule'] ?? null) === 'three_distinct') {
            throw new FinancialDomainException('E-REV-ACTOR-POLICY-UNRESOLVED', 'Aktivasi diblokir sampai actor matrix final three_distinct terdokumentasi sebagai policy executable.');
        }
        $this->assertSnapshotIntegrity($request);
        $this->validateSnapshot($request->configuration_snapshot);
        $currentProposal = $this->assertPrerequisiteStillMatches($request, true);
        $this->assertGovernanceEvidenceStillMatches($request, true);
        if ($currentProposal->id !== $proposal->id
            || ! hash_equals((string) $request->snapshot_hash, $this->hash($request->configuration_snapshot))
            || ! hash_equals((string) $proposal->snapshot_hash, $this->hash($proposal->proposal_snapshot))) {
            throw new FinancialDomainException('E-REV-CONFIG-ACTIVATION-FINGERPRINT', 'Fingerprint request atau prerequisite berubah sebelum aktivasi.');
        }
        $this->assertDraftTypeMatchesSnapshot($type, $request->configuration_snapshot);
        $this->assertDocumentSequenceStillMatches($request, $sequence);
        if ($type->status !== 'draft' || $sequence->status !== 'draft'
            || $type->accounting_entity_id !== $request->accounting_entity_id
            || $sequence->accounting_entity_id !== $request->accounting_entity_id
            || $sequence->transaction_type_id !== $type->id
            || $sequence->prefix !== $type->voucher_prefix) {
            throw new FinancialDomainException('E-REV-PREREQ-ACTIVATION', 'Pasangan prerequisite tidak lagi cocok dengan paket final REV.');
        }

        $typeBefore = ['status' => $type->status, 'valid_from' => $type->valid_from?->format('Y-m-d'), 'valid_to' => $type->valid_to?->format('Y-m-d')];
        $sequenceBefore = ['status' => $sequence->status, 'valid_from' => $sequence->valid_from, 'valid_to' => $sequence->valid_to];
        $effectiveFrom = $request->configuration_snapshot['effective_from'];
        $effectiveTo = $request->configuration_snapshot['effective_to'];
        $typeUpdated = DB::table('financial_v2_transaction_types')->where('id', $type->id)->where('accounting_entity_id', $request->accounting_entity_id)->where('status', 'draft')->update(['status' => 'active', 'valid_from' => $effectiveFrom, 'valid_to' => $effectiveTo, 'updated_by_user_id' => $actorUserId, 'updated_at' => now()]);
        $sequenceUpdated = DB::table('financial_v2_document_sequences')->where('id', $sequence->id)->where('accounting_entity_id', $request->accounting_entity_id)->where('transaction_type_id', $type->id)->where('status', 'draft')->update(['status' => 'active', 'valid_from' => $effectiveFrom, 'valid_to' => $effectiveTo, 'updated_by_user_id' => $actorUserId, 'updated_at' => now()]);
        if ($typeUpdated !== 1 || $sequenceUpdated !== 1) throw new FinancialDomainException('E-REV-PREREQ-ACTIVATION-RACE', 'Pasangan prerequisite berubah saat aktivasi; seluruh transaksi dibatalkan.');

        $type = $type->fresh();
        $sequence = $sequence->fresh();
        $this->auditTrail->record($request->accounting_entity_id, 'reversal_prerequisite_type_activated', 'transaction_type', $type->id, $request->id, $actorUserId, $typeBefore, ['status' => $type->status, 'valid_from' => $type->valid_from?->format('Y-m-d'), 'valid_to' => $type->valid_to?->format('Y-m-d')]);
        $this->auditTrail->record($request->accounting_entity_id, 'reversal_prerequisite_sequence_activated', 'document_sequence', $sequence->id, $request->id, $actorUserId, $sequenceBefore, ['status' => $sequence->status, 'valid_from' => $sequence->valid_from, 'valid_to' => $sequence->valid_to]);
        return [$type, $sequence];
    }

    /** @return array{mode:string,actor_user_id:int,scope_type:string,scope_id:string,reason:string,fingerprint:string} */
    private function authorizeScopedOverride(ReversalPrerequisiteProposal $proposal, int $actorUserId): array
    {
        if (($proposal->proposal_snapshot['activation_governance']['separation_rule'] ?? null) !== 'superadmin_override') {
            throw new FinancialDomainException('E-REV-CONFIG-MAKER-CHECKER', 'Maker tidak boleh menyetujui atau mengaktifkan konfigurasi sendiri tanpa scoped SuperAdmin Override.');
        }

        $user = User::query()->find($actorUserId);

        return $this->governancePolicy->authorizeSuperAdminOverride(
            $actorUserId,
            $actorUserId,
            $actorUserId,
            $user?->hasRole('SuperAdmin') === true,
            null,
            AccountingDateCorrectionGovernancePolicy::BANK_CORRELATION_ID,
            AccountingDateCorrectionGovernancePolicy::OVERRIDE_REASON,
        );
    }

    private function persistTransition(ReversalConfigurationRequest $request, string $from, string $to, array $values): void
    {
        $allowed = ['draft' => ['pending_review', 'cancelled'], 'pending_review' => ['approved', 'rejected', 'cancelled'], 'approved' => ['activated', 'cancelled']];
        if (! in_array($to, $allowed[$from] ?? [], true) || $request->status !== $from) throw new FinancialDomainException('E-REV-CONFIG-STATE', "Transisi {$from} ke {$to} tidak diizinkan.");
        $updated = DB::table($request->getTable())->where('id', $request->id)->where('accounting_entity_id', $request->accounting_entity_id)->where('status', $from)->update(array_merge($values, ['status' => $to, 'updated_at' => now()]));
        if ($updated !== 1) throw new FinancialDomainException('E-REV-CONFIG-RACE', 'Workflow konfigurasi berubah saat transisi; operasi dibatalkan.');
        $request->refresh();
    }

    private function locked(string $entityId, string $requestId, string $status): ReversalConfigurationRequest
    {
        $request = ReversalConfigurationRequest::query()->where('accounting_entity_id', $entityId)->lockForUpdate()->find($requestId);
        if (! $request || $request->status !== $status) {
            throw new FinancialDomainException('E-REV-CONFIG-STATE', 'Status atau Entity workflow konfigurasi REV tidak sesuai.');
        }

        return $request;
    }

    private function lockActiveEntity(string $entityId): object
    {
        $entity = DB::table('financial_v2_accounting_entities')->where('id', $entityId)->lockForUpdate()->first();
        if (! $entity || $entity->status !== 'active') {
            throw new FinancialDomainException('E-REV-CONFIG-ENTITY', 'Accounting Entity harus tetap aktif pada setiap keputusan workflow REV.');
        }

        return $entity;
    }

    private function documentSequence(string $entityId, string $sequenceId, bool $lock): DocumentSequence
    {
        $query = DocumentSequence::query()->with('transactionType')->where('accounting_entity_id', $entityId);
        $sequence = $query->find($sequenceId);
        if ($sequence && $lock) {
            $type = TransactionType::query()->where('accounting_entity_id', $entityId)->lockForUpdate()->find($sequence->transaction_type_id);
            $sequence = DocumentSequence::query()->where('accounting_entity_id', $entityId)->lockForUpdate()->find($sequenceId);
            if ($sequence) $sequence->setRelation('transactionType', $type);
        }
        if (! $sequence || $sequence->status !== 'draft' || ! $sequence->transactionType
            || $sequence->transactionType->accounting_entity_id !== $entityId
            || $sequence->transactionType->code !== 'REV'
            || $sequence->transactionType->status !== 'draft'
            || blank($sequence->code) || blank($sequence->prefix) || blank($sequence->scope_key)
            || $sequence->prefix !== $sequence->transactionType->voucher_prefix
            || ! in_array($sequence->reset_rule, ['never', 'yearly', 'monthly'], true)
            || (int) $sequence->next_value < 1) {
            throw new FinancialDomainException('E-REV-CONFIG-DOCUMENT-SEQUENCE', 'Document Sequence draft dari prerequisite yang disetujui wajib tersedia.');
        }

        return $sequence;
    }

    private function assertDocumentSequenceStillMatches(ReversalConfigurationRequest $request, DocumentSequence $sequence): void
    {
        $snapshot = $request->configuration_snapshot['document_sequence'] ?? [];
        foreach (['id', 'transaction_type_id', 'code', 'prefix', 'scope_key', 'reset_rule', 'status'] as $field) {
            if (($snapshot[$field] ?? null) !== $sequence->{$field}) {
                throw new FinancialDomainException('E-REV-CONFIG-DOCUMENT-SEQUENCE-CHANGED', 'Document Sequence REV berubah sejak snapshot dibuat.');
            }
        }
        if ((int) ($snapshot['next_value'] ?? 0) !== (int) $sequence->next_value) {
            throw new FinancialDomainException('E-REV-CONFIG-DOCUMENT-SEQUENCE-CHANGED', 'Nomor berikutnya pada Document Sequence REV berubah sebelum aktivasi.');
        }
    }

    private function governanceAttachment(string $entityId, string $attachmentId, bool $lock): Attachment
    {
        $query = Attachment::query()->where('accounting_entity_id', $entityId)->where('status', 'active');
        if ($lock) {
            $query->lockForUpdate();
        }
        $attachment = $query->find($attachmentId);
        if (! $attachment || ! preg_match('/^[a-f0-9]{64}(?:[a-f0-9]{64})?$/i', (string) $attachment->content_hash)
            || (int) $attachment->byte_size < 1 || blank($attachment->storage_reference)) {
            throw new FinancialDomainException('E-REV-CONFIG-DOCUMENT', 'Dokumen governance aktif dengan metadata integritas yang sah wajib dipilih dari Entity yang sama.');
        }

        return $attachment;
    }

    private function assertGovernanceEvidenceStillMatches(ReversalConfigurationRequest $request, bool $lock): void
    {
        $attachment = $this->governanceAttachment($request->accounting_entity_id, (string) $request->governance_attachment_id, $lock);
        $snapshot = $request->configuration_snapshot['governance_document'] ?? [];
        if (($snapshot['attachment_id'] ?? null) !== $attachment->id
            || ($snapshot['status'] ?? null) !== $attachment->status
            || ! hash_equals((string) ($snapshot['content_hash'] ?? ''), (string) $attachment->content_hash)
            || ($snapshot['media_type'] ?? null) !== $attachment->media_type
            || (int) ($snapshot['byte_size'] ?? 0) !== (int) $attachment->byte_size) {
            throw new FinancialDomainException('E-REV-CONFIG-DOCUMENT-CHANGED', 'Dokumen governance berubah sejak snapshot dibuat. Buat workflow baru dengan dokumen yang berlaku.');
        }
    }

    private function prerequisite(string $entityId, string $proposalId, bool $lock): ReversalPrerequisiteProposal
    {
        $query = ReversalPrerequisiteProposal::query()->where('accounting_entity_id', $entityId);
        if ($lock) $query->lockForUpdate();
        $proposal = $query->find($proposalId);
        if (! $proposal || $proposal->status !== 'materialized' || ! $proposal->transaction_type_id || ! $proposal->document_sequence_id
            || ! $proposal->approved_by_user_id || ! $proposal->materialized_by_user_id
            || ! hash_equals((string) $proposal->snapshot_hash, $this->hash($proposal->proposal_snapshot))) {
            throw new FinancialDomainException('E-REV-CONFIG-PREREQUISITE', 'Pasangan prerequisite REV yang approved dan materialized wajib tersedia.');
        }

        return $proposal;
    }

    /** @return array{0:GovernanceDocument,1:GovernanceDocumentDecision} */
    private function approvedGovernanceDocument(ReversalPrerequisiteProposal $proposal, bool $lock): array
    {
        [$document, $decision] = $this->governanceDocuments->approvedForUse($proposal->accounting_entity_id, $proposal->governance_document_id, $lock);
        if (! $document || $document->status !== 'approved' || ! $decision
            || $decision->id !== $proposal->governance_document_decision_id
            || ! hash_equals((string) $document->snapshot_hash, (string) $decision->snapshot_hash)) {
            throw new FinancialDomainException('E-REV-CONFIG-GOVERNANCE-DOCUMENT', 'Dokumen governance dan approval canonical yang cocok wajib tersedia.');
        }

        return [$document, $decision];
    }

    private function assertPrerequisiteStillMatches(ReversalConfigurationRequest $request, bool $lock): ReversalPrerequisiteProposal
    {
        $proposal = $this->prerequisite($request->accounting_entity_id, (string) $request->prerequisite_proposal_id, $lock);
        [$document, $decision] = $this->approvedGovernanceDocument($proposal, $lock);
        [$type, $sequence] = $this->prerequisites->assertMaterializedMastersMatch($proposal, $lock);
        $snapshot = $request->configuration_snapshot;
        if (($snapshot['prerequisite']['proposal_id'] ?? null) !== $proposal->id
            || ! hash_equals((string) ($snapshot['prerequisite']['snapshot_hash'] ?? ''), (string) $proposal->snapshot_hash)
            || ($snapshot['governance_document']['id'] ?? null) !== $document->id
            || ($snapshot['governance_document']['approval_decision_id'] ?? null) !== $decision->id
            || $request->governance_document_decision_id !== $decision->id
            || $request->document_sequence_id !== $proposal->document_sequence_id
            || ($snapshot['transaction_type']['id'] ?? null) !== $type->id
            || ($snapshot['document_sequence']['id'] ?? null) !== $sequence->id) {
            throw new FinancialDomainException('E-REV-CONFIG-PREREQUISITE-CHANGED', 'Prerequisite atau approval dokumen berubah sejak snapshot paket REV dibuat.');
        }

        return $proposal;
    }

    /** @param array<string, mixed> $snapshot */
    private function assertDraftTypeMatchesSnapshot(TransactionType $type, array $snapshot): void
    {
        if ($type->accounting_entity_id !== ($snapshot['entity']['id'] ?? null)
            || $type->code !== 'REV' || $type->status !== 'draft'
            || $type->name !== ($snapshot['transaction_type']['name'] ?? null)
            || $type->voucher_prefix !== ($snapshot['transaction_type']['voucher_prefix'] ?? null)
            || ! $type->has_financial_impact) {
            throw new FinancialDomainException('E-REV-CONFIG-TYPE', 'Draft Transaction Type REV tidak lagi cocok dengan snapshot yang disetujui.');
        }
    }

    /** @param array<string, mixed> $input @return array<string, mixed> */
    private function canonicalSnapshot(object $entity, array $input, DocumentSequence $documentSequence, Attachment $governanceAttachment, ReversalPrerequisiteProposal $prerequisite, GovernanceDocument $governanceDocument, GovernanceDocumentDecision $governanceDecision): array
    {
        $evidence = collect($input['evidence_requirements'] ?? [])->filter(fn (array $item): bool => filled($item['evidence_type'] ?? null))->map(fn (array $item): array => [
            'evidence_type' => strtolower(trim((string) $item['evidence_type'])),
            'minimum_count' => (int) $item['minimum_count'],
        ])->sortBy('evidence_type')->values()->all();

        return [
            'schema_version' => 1,
            'entity' => ['id' => $entity->id, 'status' => $entity->status],
            'transaction_semantics' => 'reversal_of_posted_unreversed_original',
            'journal_compilation' => PostingRuleVersion::STRATEGY_SOURCE_JOURNAL_REVERSAL,
            'effective_from' => (string) $input['effective_from'],
            'effective_to' => $input['effective_to'] ?? null,
            'governance_document' => [
                'id' => $governanceDocument->id,
                'approval_decision_id' => $governanceDecision->id,
                'approval_snapshot_hash' => $governanceDocument->snapshot_hash,
                'attachment_id' => $governanceAttachment->id,
                'status' => $governanceAttachment->status,
                'content_hash' => $governanceAttachment->content_hash,
                'media_type' => $governanceAttachment->media_type,
                'byte_size' => (int) $governanceAttachment->byte_size,
            ],
            'prerequisite' => [
                'proposal_id' => $prerequisite->id,
                'snapshot_hash' => $prerequisite->snapshot_hash,
                'approved_by_user_id' => (int) $prerequisite->approved_by_user_id,
                'materialized_by_user_id' => (int) $prerequisite->materialized_by_user_id,
                'activation_separation_rule' => $prerequisite->proposal_snapshot['activation_governance']['separation_rule'] ?? null,
                'maker_user_id' => (int) $prerequisite->created_by_user_id,
            ],
            'workflow_actors' => [
                'governance_document_maker_user_id' => (int) $governanceDocument->created_by_user_id,
                'governance_document_checker_user_id' => (int) $governanceDocument->approved_by_user_id,
                'prerequisite_maker_user_id' => (int) $prerequisite->created_by_user_id,
                'prerequisite_checker_user_id' => (int) $prerequisite->approved_by_user_id,
                'prerequisite_materializer_user_id' => (int) $prerequisite->materialized_by_user_id,
            ],
            'document_sequence' => [
                'id' => $documentSequence->id,
                'transaction_type_id' => $documentSequence->transaction_type_id,
                'code' => $documentSequence->code,
                'prefix' => $documentSequence->prefix,
                'scope_key' => $documentSequence->scope_key,
                'reset_rule' => $documentSequence->reset_rule,
                'next_value' => (int) $documentSequence->next_value,
                'status' => $documentSequence->status,
            ],
            'transaction_type' => [
                'id' => $documentSequence->transaction_type_id,
                'code' => 'REV',
                'name' => $documentSequence->transactionType->name,
                'voucher_prefix' => $documentSequence->transactionType->voucher_prefix,
                'has_financial_impact' => (bool) $documentSequence->transactionType->has_financial_impact,
                'status' => $documentSequence->transactionType->status,
            ],
            'posting_rule' => [
                'code' => strtoupper(trim((string) $input['posting_rule_code'])),
                'name' => trim((string) $input['posting_rule_name']), 'rule_family' => 'reversal',
                'input_contract_ref' => trim((string) $input['input_contract_ref']),
                'journal_template_ref' => trim((string) $input['journal_template_ref']),
                'business_rule_refs' => trim((string) $input['business_rule_refs']),
            ],
            'reason_code' => ['code' => strtoupper(trim((string) $input['reason_code'])), 'name' => trim((string) $input['reason_name']), 'reason_class' => 'reversal'],
            'approval' => ['required_steps' => (int) $input['required_approval_steps']],
            'evidence_requirements' => $evidence,
        ];
    }

    /** @param array<string, mixed> $snapshot */
    private function validateSnapshot(array $snapshot): void
    {
        $effectiveFrom = $this->validatedDate($snapshot['effective_from'] ?? null, 'effective_from');
        $effectiveTo = filled($snapshot['effective_to'] ?? null)
            ? $this->validatedDate($snapshot['effective_to'], 'effective_to')
            : null;
        if ($effectiveTo !== null && $effectiveTo < $effectiveFrom) {
            throw new FinancialDomainException('E-REV-CONFIG-DATE', 'Tanggal efektif akhir tidak boleh mendahului tanggal efektif awal.');
        }
        if (($snapshot['transaction_type']['code'] ?? null) !== 'REV'
            || ($snapshot['transaction_type']['status'] ?? null) !== 'draft'
            || ! ($snapshot['transaction_type']['has_financial_impact'] ?? false)
            || ($snapshot['transaction_semantics'] ?? null) !== 'reversal_of_posted_unreversed_original'
            || ($snapshot['journal_compilation'] ?? null) !== PostingRuleVersion::STRATEGY_SOURCE_JOURNAL_REVERSAL
            || ($snapshot['posting_rule']['rule_family'] ?? null) !== 'reversal') {
            throw new FinancialDomainException('E-REV-CONFIG-SEMANTICS', 'Konfigurasi harus khusus REV dan membalik JournalLine original secara canonical.');
        }
        foreach (['name', 'voucher_prefix'] as $field) {
            if (blank($snapshot['transaction_type'][$field] ?? null)) {
                throw new FinancialDomainException('E-REV-CONFIG-INCOMPLETE', 'Identitas Transaction Type REV belum lengkap.');
            }
        }
        foreach (['code', 'name', 'input_contract_ref', 'journal_template_ref', 'business_rule_refs'] as $field) {
            if (blank($snapshot['posting_rule'][$field] ?? null)) {
                throw new FinancialDomainException('E-REV-CONFIG-INCOMPLETE', 'Dokumen dan identitas Posting Rule REV belum lengkap.');
            }
        }
        if (($snapshot['reason_code']['reason_class'] ?? null) !== 'reversal' || blank($snapshot['reason_code']['code'] ?? null) || blank($snapshot['reason_code']['name'] ?? null)) {
            throw new FinancialDomainException('E-REV-CONFIG-INCOMPLETE', 'Reason Code reversal belum lengkap.');
        }
        if ((int) ($snapshot['approval']['required_steps'] ?? 0) < 1) {
            throw new FinancialDomainException('E-REV-CONFIG-APPROVAL', 'REV wajib mempunyai minimal satu langkah persetujuan transaksi.');
        }
        $evidence = collect($snapshot['evidence_requirements'] ?? []);
        if ($evidence->isEmpty() || $evidence->pluck('evidence_type')->duplicates()->isNotEmpty()) {
            throw new FinancialDomainException('E-REV-CONFIG-EVIDENCE', 'Persyaratan bukti REV wajib tersedia dan tidak boleh duplikat.');
        }
        foreach ($evidence as $item) {
            if (! in_array($item['evidence_type'] ?? null, self::EVIDENCE_TYPES, true) || (int) ($item['minimum_count'] ?? 0) < 1) {
                throw new FinancialDomainException('E-REV-CONFIG-EVIDENCE', 'Jenis atau jumlah minimum bukti REV tidak valid.');
            }
        }
        if (($snapshot['entity']['status'] ?? null) !== 'active'
            || ($snapshot['document_sequence']['status'] ?? null) !== 'draft'
            || blank($snapshot['document_sequence']['id'] ?? null)
            || ($snapshot['document_sequence']['transaction_type_id'] ?? null) !== ($snapshot['transaction_type']['id'] ?? null)
            || ($snapshot['document_sequence']['prefix'] ?? null) !== ($snapshot['transaction_type']['voucher_prefix'] ?? null)
            || ($snapshot['governance_document']['status'] ?? null) !== 'active'
            || blank($snapshot['governance_document']['id'] ?? null)
            || blank($snapshot['governance_document']['approval_decision_id'] ?? null)
            || blank($snapshot['prerequisite']['proposal_id'] ?? null)
            || ! in_array($snapshot['prerequisite']['activation_separation_rule'] ?? null, ['maker_checker', 'three_distinct', 'superadmin_override'], true)
            || blank($snapshot['governance_document']['attachment_id'] ?? null)
            || ! preg_match('/^[a-f0-9]{64}(?:[a-f0-9]{64})?$/i', (string) ($snapshot['governance_document']['content_hash'] ?? ''))) {
            throw new FinancialDomainException('E-REV-CONFIG-DOCUMENT', 'Entity, Document Sequence, dan dokumen governance aktif wajib tersedia dalam snapshot.');
        }
    }

    private function validatedDate(mixed $value, string $field): string
    {
        $date = (string) $value;
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        $errors = \DateTimeImmutable::getLastErrors();
        if (! $parsed || $parsed->format('Y-m-d') !== $date
            || (is_array($errors) && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0))) {
            throw new FinancialDomainException('E-REV-CONFIG-DATE', "Nilai {$field} bukan tanggal canonical yang valid.");
        }

        return $date;
    }

    private function assertNoExistingReversalMaster(string $entityId, string $allowedDraftTypeId): void
    {
        if (TransactionType::query()->where('accounting_entity_id', $entityId)->where('code', 'REV')->whereKeyNot($allowedDraftTypeId)->exists()
            || PostingRule::query()->where('accounting_entity_id', $entityId)->where('rule_family', 'reversal')->exists()
            || ReasonCode::query()->where('accounting_entity_id', $entityId)->where('reason_class', 'reversal')->exists()) {
            throw new FinancialDomainException('E-REV-CONFIG-EXISTS', 'Master REV/reversal telah tersedia. Workflow tidak akan membuat duplikat.');
        }
    }

    /** @param array<string, mixed> $snapshot */
    private function assertIdentifiersAvailable(string $entityId, array $snapshot): void
    {
        if (PostingRule::query()->where('accounting_entity_id', $entityId)->where('code', $snapshot['posting_rule']['code'])->exists()
            || ReasonCode::query()->where('accounting_entity_id', $entityId)->where('code', $snapshot['reason_code']['code'])->exists()) {
            throw new FinancialDomainException('E-REV-CONFIG-IDENTIFIER', 'Kode Posting Rule atau Reason Code sudah digunakan pada Entity ini.');
        }
    }

    private function assertSnapshotIntegrity(ReversalConfigurationRequest $request): void
    {
        if (! hash_equals($request->snapshot_hash, $this->hash($request->configuration_snapshot))) {
            throw new FinancialDomainException('E-REV-CONFIG-INTEGRITY', 'Snapshot konfigurasi REV tidak lolos pemeriksaan integritas.');
        }
        if (($request->configuration_snapshot['entity']['id'] ?? null) !== $request->accounting_entity_id) {
            throw new FinancialDomainException('E-REV-CONFIG-ENTITY', 'Entity pada snapshot tidak cocok dengan request workflow REV.');
        }
    }

    /** @param array<string, mixed> $snapshot */
    private function hash(array $snapshot): string
    {
        return hash('sha256', json_encode(Arr::sortRecursive($snapshot), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }

    private function decision(ReversalConfigurationRequest $request, string $decision, int $actorUserId, ?string $note = null): void
    {
        DB::table((new ReversalConfigurationDecision)->getTable())->insert([
            'id' => (string) Str::uuid(),
            'accounting_entity_id' => $request->accounting_entity_id,
            'configuration_request_id' => $request->id,
            'decision' => $decision, 'configuration_snapshot' => json_encode($request->configuration_snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
            'snapshot_hash' => $request->snapshot_hash, 'note' => $note,
            'actor_user_id' => $actorUserId, 'decided_at' => now(), 'created_at' => now(),
        ]);
    }

    /** @param array<string,mixed> $values */
    private function persistDraft(array $values): ReversalConfigurationRequest
    {
        if (($values['status'] ?? null) !== 'draft' || blank($values['active_entity_guard'] ?? null)) throw new FinancialDomainException('E-REV-CONFIG-DRAFT', 'Persistence hanya menerima draft konfigurasi canonical.');
        $id = (string) Str::uuid();
        $values['configuration_snapshot'] = json_encode($values['configuration_snapshot'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        DB::table((new ReversalConfigurationRequest)->getTable())->insert(array_merge($values, ['id' => $id, 'created_at' => now(), 'updated_at' => now()]));
        return ReversalConfigurationRequest::query()->findOrFail($id);
    }

    private function audit(string $event, ReversalConfigurationRequest $request, int $actorUserId): void
    {
        $this->auditTrail->record($request->accounting_entity_id, $event, 'reversal_configuration_request', $request->id, $request->id, $actorUserId, null, [
            'status' => $request->status, 'snapshot_hash' => $request->snapshot_hash,
            'transaction_type_id' => $request->transaction_type_id,
            'posting_rule_id' => $request->posting_rule_id,
            'posting_rule_version_id' => $request->posting_rule_version_id,
            'reason_code_id' => $request->reason_code_id,
            'approval_requirement_id' => $request->approval_requirement_id,
            'evidence_requirement_ids' => $request->evidence_requirement_ids,
            'document_sequence_id' => $request->document_sequence_id,
            'governance_attachment_id' => $request->governance_attachment_id,
            'governance_document_decision_id' => $request->governance_document_decision_id,
            'prerequisite_proposal_id' => $request->prerequisite_proposal_id,
        ]);
    }
}
