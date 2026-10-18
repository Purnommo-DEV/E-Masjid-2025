<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\AccountingDateCorrectionLineage;
use App\Models\FinancialV2\AccountingDateCorrectionRequest;
use App\Models\FinancialV2\Attachment;
use App\Models\FinancialV2\AttachmentLink;
use App\Models\FinancialV2\BudgetAllocationVersion;
use App\Models\FinancialV2\Distribution;
use App\Models\FinancialV2\DistributionCorrectionLineage;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\FundRealization;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\ReplacementRealizationDecision;
use App\Models\FinancialV2\ReplacementRealizationLineage;
use App\Models\FinancialV2\ReplacementRealizationRequest;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Governance and lineage only. This service never creates/posts a transaction,
 * journal or ledger entry and never mutates the original realization/distribution.
 */
final class ReplacementRealizationWorkflowService
{
    public function __construct(private readonly AuditTrailService $auditTrail,private readonly AccountingDateCorrectionGovernancePolicy $governance,private readonly AccountingDateCorrectionPolicyException $policyExceptions) {}

    /** @param array<string,mixed> $input */
    public function createDraft(string $entityId, string $correctionRequestId, array $input, int $actor): ReplacementRealizationRequest
    {
        return DB::transaction(function () use ($entityId, $correctionRequestId, $input, $actor): ReplacementRealizationRequest {
            $this->lockEntity($entityId);
            $correction = AccountingDateCorrectionRequest::query()->where('accounting_entity_id', $entityId)->lockForUpdate()->find($correctionRequestId);
            if (! $correction || ! in_array($correction->status, ['blocked', 'pending_approval', 'governance_approved'], true)) throw new FinancialDomainException('E-REPL-REAL-CORRECTION', 'Correction Request tidak berada pada tahap governance yang dapat menyiapkan Replacement Realization.');
            $lineage = AccountingDateCorrectionLineage::query()->where('correction_request_id', $correction->id)->lockForUpdate()->firstOrFail();
            $original = FundRealization::query()->lockForUpdate()->find($lineage->original_realization_id);
            if (! $original || $original->accounting_entity_id !== $entityId || $original->transaction_id !== $correction->original_transaction_id || $original->status !== 'recorded') throw new FinancialDomainException('E-REPL-REAL-ORIGINAL', 'Original Realization recorded tidak cocok dengan lineage koreksi.');
            $replacementTx = FinancialTransaction::forEntity($entityId)->with(['type', 'splits'])->lockForUpdate()->find($input['replacement_transaction_id'] ?? null);
            $this->assertReplacementTransaction($correction, $original, $replacementTx);
            $evidence = $this->assertEvidence($entityId, $replacementTx->id, (string) ($input['evidence_attachment_id'] ?? ''));
            $policy = (string) ($input['separation_policy'] ?? '');
            if (!in_array($policy,['three_distinct','superadmin_override'],true)) throw new FinancialDomainException('E-REPL-REAL-POLICY', 'Separation policy Replacement Realization tidak dikenal.');
            if($policy==='superadmin_override'){$user=User::query()->find($actor);$this->governance->authorizeSuperAdminOverride($actor,$actor,$actor,$user?->hasRole('SuperAdmin')===true,$correction->original_transaction_id,null,AccountingDateCorrectionGovernancePolicy::OVERRIDE_REASON);}
            $snapshot = $this->snapshot($correction, $original, $replacementTx, $evidence, $policy, $actor);
            $hash = $this->hash($snapshot); $key = (string) ($input['idempotency_key'] ?? '');
            if (! Str::isUuid($key)) throw new FinancialDomainException('E-REPL-REAL-IDEMPOTENCY', 'Idempotency key harus UUID.');
            $retry = ReplacementRealizationRequest::query()->where('accounting_entity_id', $entityId)->where('idempotency_key', $key)->first();
            if ($retry) { if (! hash_equals($retry->snapshot_hash, $hash)) throw new FinancialDomainException('E-REPL-REAL-IDEMPOTENCY', 'Idempotency key telah digunakan untuk substansi berbeda.'); return $retry; }
            if (ReplacementRealizationRequest::query()->where('active_correction_guard', $correction->id)->lockForUpdate()->exists()) throw new FinancialDomainException('E-REPL-REAL-ACTIVE', 'Correction Request telah mempunyai workflow replacement aktif.');
            // The original additive schema predates the governed override and
            // intentionally remains unchanged. Store its base control family
            // while the immutable snapshot records that the scoped override,
            // not maker-checker separation, authorized this request.
            $storedPolicy = $policy === 'superadmin_override' ? 'maker_checker' : $policy;
            $request = $this->insertRequest(['accounting_entity_id' => $entityId, 'correction_request_id' => $correction->id, 'original_realization_id' => $original->id, 'replacement_transaction_id' => $replacementTx->id, 'evidence_attachment_id' => $evidence->id, 'separation_policy' => $storedPolicy, 'status' => 'draft', 'proposal_snapshot' => $snapshot, 'snapshot_hash' => $hash, 'idempotency_key' => $key, 'active_correction_guard' => $correction->id, 'created_by_user_id' => $actor]);
            $this->audit($request, 'replacement_realization_draft_created', $actor);
            return $request;
        }, 3);
    }

    public function submit(string $entityId, string $id, int $actor): ReplacementRealizationRequest { return $this->transition($entityId, $id, 'draft', 'pending_review', 'submitted', $actor); }

    public function approve(string $entityId, string $id, int $actor, ?string $note = null): ReplacementRealizationRequest
    {
        return DB::transaction(function () use ($entityId, $id, $actor, $note): ReplacementRealizationRequest {
            $this->lockEntity($entityId); $request = $this->locked($entityId, $id, 'pending_review');
            $override = $this->usesSuperAdminOverride($request);
            if ((int) $request->created_by_user_id === $actor && ! $override) throw new FinancialDomainException('E-REPL-REAL-MAKER-CHECKER', 'Maker tidak boleh menyetujui proposal sendiri.');
            if($override){$correction=AccountingDateCorrectionRequest::findOrFail($request->correction_request_id);$user=User::query()->find($actor);$this->governance->authorizeSuperAdminOverride($actor,$actor,$actor,$user?->hasRole('SuperAdmin')===true,$correction->original_transaction_id,null,AccountingDateCorrectionGovernancePolicy::OVERRIDE_REASON);}
            $this->assertCurrent($request, true, false);
            $this->persistTransition($request, 'pending_review', 'approved', ['approved_at' => now(), 'approved_by_user_id' => $actor]);
            $this->decision($request, 'approved', $actor, $note); $this->audit($request, 'replacement_realization_approved', $actor);
            return $request->fresh('decisions');
        }, 3);
    }

    public function reject(string $entityId, string $id, int $actor, string $note): ReplacementRealizationRequest
    {
        return DB::transaction(function () use ($entityId, $id, $actor, $note): ReplacementRealizationRequest {
            $this->lockEntity($entityId); $request = $this->locked($entityId, $id, 'pending_review');
            if ((int) $request->created_by_user_id === $actor) throw new FinancialDomainException('E-REPL-REAL-MAKER-CHECKER', 'Maker tidak boleh menolak proposal sendiri.');
            $this->persistTransition($request, 'pending_review', 'rejected', ['active_correction_guard' => null]);
            $this->decision($request, 'rejected', $actor, $note); $this->audit($request, 'replacement_realization_rejected', $actor);
            return $request->fresh('decisions');
        }, 3);
    }

    public function cancel(string $entityId, string $id, int $actor, string $reason): ReplacementRealizationRequest
    {
        return DB::transaction(function () use ($entityId, $id, $actor, $reason): ReplacementRealizationRequest {
            $this->lockEntity($entityId);
            $request = ReplacementRealizationRequest::query()->where('accounting_entity_id', $entityId)->lockForUpdate()->find($id);
            if (! $request || ! in_array($request->status, ['draft', 'pending_review', 'approved'], true) || blank(trim($reason))) throw new FinancialDomainException('E-REPL-REAL-CANCEL', 'Workflow tidak dapat dibatalkan pada status ini atau alasan kosong.');
            $from = $request->status;
            $this->persistTransition($request, $from, 'cancelled', ['active_correction_guard' => null, 'cancelled_at' => now(), 'cancelled_by_user_id' => $actor, 'cancellation_reason' => trim($reason)]);
            $this->decision($request, 'cancelled', $actor, $reason); $this->audit($request, 'replacement_realization_cancelled', $actor);
            return $request->fresh('decisions');
        }, 3);
    }

    public function materialize(string $entityId, string $id, int $actor): ReplacementRealizationRequest
    {
        return DB::transaction(function () use ($entityId, $id, $actor): ReplacementRealizationRequest {
            $this->lockEntity($entityId); $request = $this->locked($entityId, $id, 'approved');
            $override = $this->usesSuperAdminOverride($request);
            if ((int) $request->created_by_user_id === $actor && ! $override) throw new FinancialDomainException('E-REPL-REAL-MAKER-CHECKER', 'Maker tidak boleh mematerialisasikan proposal sendiri.');
            $correction = AccountingDateCorrectionRequest::query()->where('accounting_entity_id', $entityId)->lockForUpdate()->findOrFail($request->correction_request_id);
            if($override){$user=User::query()->find($actor);$this->governance->authorizeSuperAdminOverride($actor,$actor,$actor,$user?->hasRole('SuperAdmin')===true,$correction->original_transaction_id,null,AccountingDateCorrectionGovernancePolicy::OVERRIDE_REASON);}
            if ($correction->status !== 'governance_approved') throw new FinancialDomainException('E-REPL-REAL-NOT-EXECUTION-GATE', 'Materialisasi menunggu governance approval Correction Request; tahap ini tetap tidak memberikan execution_ready.');
            $facts = $this->assertCurrent($request, true, true);
            if (ReplacementRealizationLineage::query()->where('replacement_request_id', $request->id)->exists()) return $request->fresh(['lineage', 'decisions']);
            $replacement = $facts['realization'];
            $lineageSnapshot = ['correction_request_id' => $request->correction_request_id, 'original_realization_id' => $request->original_realization_id, 'replacement_realization_id' => $replacement->id, 'replacement_transaction_id' => $facts['transaction']->id, 'replacement_journal_id' => $facts['journal']->id, 'proposal_snapshot_hash' => $request->snapshot_hash];
            $lineageId = (string) Str::uuid();
            DB::table((new ReplacementRealizationLineage)->getTable())->insert(['id' => $lineageId, 'replacement_request_id' => $request->id, 'correction_request_id' => $request->correction_request_id, 'original_realization_id' => $request->original_realization_id, 'replacement_realization_id' => $replacement->id, 'replacement_transaction_id' => $facts['transaction']->id, 'lineage_snapshot' => json_encode($lineageSnapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), 'snapshot_hash' => $this->hash($lineageSnapshot), 'created_at' => now()]);
            $distribution = Distribution::query()->where('realization_id', $request->original_realization_id)->with(['items' => fn ($q) => $q->orderBy('id')])->lockForUpdate()->first();
            if ($distribution) $this->createDistributionOverlay($entityId, $lineageId, $distribution, $request->original_realization_id, $replacement->id);
            $this->persistTransition($request, 'approved', 'materialized', ['active_correction_guard' => null, 'materialized_at' => now(), 'materialized_by_user_id' => $actor]);
            $this->decision($request, 'materialized', $actor, 'Lineage materialized tanpa posting dan tanpa mengubah original.'); $this->audit($request, 'replacement_realization_materialized', $actor);
            return $request->fresh(['lineage.distributionLineage', 'decisions']);
        }, 3);
    }

    /** @return array{transaction:FinancialTransaction,allocation:BudgetAllocationVersion,journal:Journal,realization:FundRealization} */
    private function assertCurrent(ReplacementRealizationRequest $request, bool $lock, bool $requirePosted): array
    {
        if (! hash_equals($request->snapshot_hash, $this->hash($request->proposal_snapshot))) throw new FinancialDomainException('E-REPL-REAL-SNAPSHOT', 'Snapshot proposal berubah.');
        $correction = AccountingDateCorrectionRequest::query()->where('accounting_entity_id', $request->accounting_entity_id)->when($lock, fn ($q) => $q->lockForUpdate())->findOrFail($request->correction_request_id);
        if (! in_array($correction->status, ['blocked', 'pending_approval', 'governance_approved'], true)) throw new FinancialDomainException('E-REPL-REAL-CORRECTION', 'Correction Request tidak lagi berada pada tahap governance yang sah.');
        $original = FundRealization::query()->when($lock, fn ($q) => $q->lockForUpdate())->findOrFail($request->original_realization_id);
        $transaction = FinancialTransaction::query()->with(['type', 'splits'])->when($lock, fn ($q) => $q->lockForUpdate())->find($request->replacement_transaction_id);
        $this->assertReplacementTransaction($correction, $original, $transaction, $requirePosted);
        $journals = Journal::query()->where('transaction_id', $transaction->id)->where('journal_status', 'posted')->when($lock, fn ($q) => $q->lockForUpdate())->get();
        if ($requirePosted && ($journals->count() !== 1 || ! $journals->first()->posted_at)) throw new FinancialDomainException('E-REPL-REAL-JOURNAL', 'Materialisasi mensyaratkan tepat satu jurnal posted canonical.');
        if (! $requirePosted && $journals->isNotEmpty()) throw new FinancialDomainException('E-REPL-REAL-PREMATURE-JOURNAL', 'Proposal pre-posting tidak boleh menunjuk transaksi yang telah mempunyai jurnal posted.');
        $journal = $journals->first();
        $realization = FundRealization::query()->where('transaction_id', $transaction->id)->when($lock, fn ($q) => $q->lockForUpdate())->first();
        $expectedRealizationStatus = $requirePosted ? 'recorded' : 'draft';
        if (! $realization || $realization->status !== $expectedRealizationStatus || $realization->accounting_entity_id !== $request->accounting_entity_id || $realization->budget_allocation_version_id !== $original->budget_allocation_version_id) throw new FinancialDomainException('E-REPL-REAL-CANONICAL', 'Replacement Realization harus dibuat oleh lifecycle, memakai Allocation Version original, dan berada pada status yang sesuai tahap workflow.');
        $evidence = $this->assertEvidence($request->accounting_entity_id, $transaction->id, $request->evidence_attachment_id);
        $evidenceSnapshot = $request->proposal_snapshot['evidence'] ?? [];
        if (($evidenceSnapshot['id'] ?? null) !== $evidence->id
            || ($evidenceSnapshot['status'] ?? null) !== $evidence->status
            || ! hash_equals((string) ($evidenceSnapshot['content_hash'] ?? ''), (string) $evidence->content_hash)) {
            throw new FinancialDomainException('E-REPL-REAL-EVIDENCE-DRIFT', 'Evidence replacement tidak lagi cocok dengan snapshot proposal.');
        }
        $allocation = BudgetAllocationVersion::query()->with(['allocation', 'fundings'])->when($lock, fn ($q) => $q->lockForUpdate())->findOrFail($original->budget_allocation_version_id);
        $target = $correction->target_accounting_date;
        $exception=$correction->approval?->approval_snapshot['accounting_date_policy_exception']??null;
        if(is_array($exception)){
            $originalTx=FinancialTransaction::query()->findOrFail($correction->original_transaction_id);
            $originalJournal=Journal::query()->where('transaction_id',$originalTx->id)->where('journal_status','posted')->sole();
            $this->policyExceptions->assertMatches($exception,$originalTx,$originalJournal,$target->toDateString());
        }
        if ($allocation->status !== 'approved' || $allocation->allocation?->status !== 'approved' || (!is_array($exception)&&$allocation->effective_from->gt($target)) || (!is_array($exception)&&$allocation->effective_to&&$allocation->effective_to->lt($target))) throw new FinancialDomainException('E-REPL-REAL-ALLOCATION', 'Allocation Version tidak approved/effective pada tanggal target dan exception approval yang cocok tidak tersedia; backdating dilarang.');
        $transactionFunds = $transaction->splits->pluck('fund_id')->filter()->unique()->sort()->values()->all();
        $allocationFunds = $allocation->fundings->pluck('fund_id')->filter()->unique()->sort()->values()->all();
        if ($allocationFunds === [] && $allocation->allocation?->fund_id) $allocationFunds = [$allocation->allocation->fund_id];
        if ($transactionFunds === [] || $transactionFunds !== $allocationFunds) throw new FinancialDomainException('E-REPL-REAL-FUNDING', 'Fund transaksi replacement tidak sama dengan funding Allocation Version.');
        $used = FinancialTransaction::query()->join('financial_v2_fund_realizations as realization', 'realization.transaction_id', '=', 'financial_v2_transactions.id')->where('realization.budget_allocation_version_id', $allocation->id)->where('financial_v2_transactions.status', 'posted')->whereNotIn('realization.id', [$original->id, $realization->id])->sum('financial_v2_transactions.gross_amount');
        if (DecimalAmount::compare(DecimalAmount::add($used, $transaction->gross_amount), $allocation->allocated_amount) > 0) throw new FinancialDomainException('E-REPL-REAL-CAPACITY', 'Kapasitas Allocation net-of-original tidak mencukupi.');
        return compact('transaction', 'allocation', 'journal', 'realization');
    }

    private function assertReplacementTransaction(AccountingDateCorrectionRequest $correction, FundRealization $original, ?FinancialTransaction $transaction, bool $requirePosted = false): void
    {
        $originalTx = FinancialTransaction::with('splits')->findOrFail($correction->original_transaction_id);
        $allowedStatuses = $requirePosted ? ['posted'] : ['approved'];
        if (! $transaction || ! in_array($transaction->status, $allowedStatuses, true) || $transaction->type?->code !== 'PAY' || $transaction->accounting_entity_id !== $correction->accounting_entity_id || $transaction->id === $originalTx->id || ! $transaction->accounting_date->isSameDay($correction->target_accounting_date) || ! DecimalAmount::equals($transaction->gross_amount, $originalTx->gross_amount)) throw new FinancialDomainException('E-REPL-REAL-TRANSACTION', 'Transaksi replacement tidak memenuhi tahap lifecycle, Entity, tipe PAY, target date, dan nominal original.');
        $dimensions = fn ($tx) => $tx->splits->map(fn ($s) => [$s->account_id, $s->fund_id, $s->program_id, DecimalAmount::normalize($s->split_amount)])->sort()->values()->all();
        if ($dimensions($transaction) !== $dimensions($originalTx)) throw new FinancialDomainException('E-REPL-REAL-DIMENSIONS', 'Dimensi account/fund/program/nominal replacement berbeda dari original.');
    }

    private function assertEvidence(string $entityId, string $transactionId, string $attachmentId): Attachment
    {
        $link = AttachmentLink::query()->where('accounting_entity_id', $entityId)->where('target_type', 'transaction')->where('target_id', $transactionId)->where('attachment_id', $attachmentId)->where('status', 'active')->first();
        $attachment = $link ? Attachment::query()->where('accounting_entity_id', $entityId)->where('status', 'active')->find($attachmentId) : null;
        if (! $link || ! $attachment) throw new FinancialDomainException('E-REPL-REAL-EVIDENCE', 'Evidence aktif harus tertaut pada transaksi replacement.');
        return $attachment;
    }

    private function createDistributionOverlay(string $entityId, string $lineageId, Distribution $distribution, string $originalRealizationId, string $replacementRealizationId): void
    {
        if ($distribution->status !== 'finalized' || $distribution->realization_id !== $originalRealizationId) throw new FinancialDomainException('E-DIST-CORRECTION-ORIGINAL', 'Hanya Distribution finalized dengan lineage original yang utuh dapat dioverlay.');
        $snapshot = ['distribution_id' => $distribution->id, 'status' => $distribution->status, 'realization_id' => $distribution->realization_id, 'finalized_at' => $distribution->finalized_at?->toAtomString(), 'items' => $distribution->items->map(fn ($item) => ['id' => $item->id, 'beneficiary_id' => $item->beneficiary_id, 'recipient_key' => $item->recipient_key, 'amount' => DecimalAmount::normalize($item->amount), 'identity_snapshot' => $item->identity_snapshot])->all()];
        DB::table((new DistributionCorrectionLineage)->getTable())->insert(['id' => (string) Str::uuid(), 'accounting_entity_id' => $entityId, 'replacement_realization_lineage_id' => $lineageId, 'original_distribution_id' => $distribution->id, 'original_realization_id' => $originalRealizationId, 'replacement_realization_id' => $replacementRealizationId, 'distribution_snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), 'snapshot_hash' => $this->hash($snapshot), 'created_at' => now()]);
    }

    private function snapshot($correction, $original, $transaction, $evidence, string $policy, int $actor): array { return ['correction' => ['id' => $correction->id, 'target_accounting_date' => $correction->target_accounting_date->toDateString(), 'approval_fingerprint' => $correction->approval?->condition_fingerprint], 'original_realization' => ['id' => $original->id, 'transaction_id' => $original->transaction_id, 'allocation_version_id' => $original->budget_allocation_version_id, 'status' => $original->status], 'replacement_transaction' => ['id' => $transaction->id, 'gross_amount' => DecimalAmount::normalize($transaction->gross_amount), 'accounting_date' => $transaction->accounting_date->toDateString(), 'status' => $transaction->status], 'evidence' => ['id' => $evidence->id, 'content_hash' => $evidence->content_hash, 'status' => $evidence->status], 'governance' => ['separation_policy' => $policy === 'superadmin_override' ? 'maker_checker' : $policy, 'override_mode' => $policy === 'superadmin_override' ? 'superadmin_override' : null, 'override_reason' => $policy === 'superadmin_override' ? AccountingDateCorrectionGovernancePolicy::OVERRIDE_REASON : null, 'maker_user_id' => $actor]]; }
    private function usesSuperAdminOverride(ReplacementRealizationRequest $request): bool { return ($request->proposal_snapshot['governance']['override_mode'] ?? null) === 'superadmin_override'; }
    private function transition(string $entityId, string $id, string $from, string $to, string $decision, int $actor): ReplacementRealizationRequest { return DB::transaction(function () use ($entityId, $id, $from, $to, $decision, $actor) { $this->lockEntity($entityId); $request = $this->locked($entityId, $id, $from); if ($decision === 'submitted' && (int) $request->created_by_user_id !== $actor) throw new FinancialDomainException('E-REPL-REAL-MAKER', 'Hanya maker proposal yang dapat mengajukan review.'); $this->assertCurrent($request, true, false); $this->persistTransition($request, $from, $to, ['submitted_at' => now()]); $this->decision($request, $decision, $actor, null); $this->audit($request, 'replacement_realization_'.$decision, $actor); return $request->fresh('decisions'); }, 3); }
    private function locked(string $entityId, string $id, string $status): ReplacementRealizationRequest { $request = ReplacementRealizationRequest::query()->where('accounting_entity_id', $entityId)->lockForUpdate()->find($id); if (! $request || $request->status !== $status) throw new FinancialDomainException('E-REPL-REAL-STATE', 'Status atau Entity workflow tidak sesuai.'); return $request; }
    private function lockEntity(string $entityId): void { if (! DB::table('financial_v2_accounting_entities')->where('id', $entityId)->where('status', 'active')->lockForUpdate()->exists()) throw new FinancialDomainException('E-REPL-REAL-ENTITY', 'Accounting Entity aktif tidak tersedia.'); }
    private function insertRequest(array $values): ReplacementRealizationRequest { $id = (string) Str::uuid(); DB::table((new ReplacementRealizationRequest)->getTable())->insert(array_merge($values, ['id' => $id, 'proposal_snapshot' => json_encode($values['proposal_snapshot'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), 'created_at' => now(), 'updated_at' => now()])); return ReplacementRealizationRequest::findOrFail($id); }
    private function persistTransition(ReplacementRealizationRequest $request, string $from, string $to, array $values): void { $allowed = ['draft' => ['pending_review', 'cancelled'], 'pending_review' => ['approved', 'rejected', 'cancelled'], 'approved' => ['materialized', 'cancelled']]; if (! in_array($to, $allowed[$from] ?? [], true)) throw new FinancialDomainException('E-REPL-REAL-STATE', 'Transisi workflow tidak diizinkan.'); if (in_array($to, ['materialized', 'rejected', 'cancelled'], true)) $values['active_correction_guard'] = null; $count = DB::table($request->getTable())->where('id', $request->id)->where('status', $from)->update(array_merge($values, ['status' => $to, 'updated_at' => now()])); if ($count !== 1) throw new FinancialDomainException('E-REPL-REAL-RACE', 'Workflow berubah saat transisi.'); $request->refresh(); }
    private function decision(ReplacementRealizationRequest $request, string $decision, int $actor, ?string $note): void { DB::table((new ReplacementRealizationDecision)->getTable())->insert(['id' => (string) Str::uuid(), 'accounting_entity_id' => $request->accounting_entity_id, 'replacement_request_id' => $request->id, 'decision' => $decision, 'proposal_snapshot' => json_encode($request->proposal_snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), 'snapshot_hash' => $request->snapshot_hash, 'note' => $note, 'actor_user_id' => $actor, 'decided_at' => now(), 'created_at' => now()]); }
    private function audit(ReplacementRealizationRequest $request, string $event, int $actor): void { $this->auditTrail->record($request->accounting_entity_id, $event, 'replacement_realization_request', $request->id, $request->correction_request_id, $actor, null, ['status' => $request->status, 'snapshot_hash' => $request->snapshot_hash]); }
    private function hash(array $value): string { return hash('sha256', json_encode(Arr::sortRecursive($value), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)); }
}
