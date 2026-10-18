<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\AccountingDateCorrectionApproval;
use App\Models\FinancialV2\AccountingDateCorrectionBatch;
use App\Models\FinancialV2\AccountingDateCorrectionEvaluation;
use App\Models\FinancialV2\AccountingDateCorrectionLineage;
use App\Models\FinancialV2\AccountingDateCorrectionRequest;
use App\Models\FinancialV2\AccountingPeriod;
use App\Models\FinancialV2\Attachment;
use App\Models\FinancialV2\AttachmentLink;
use App\Models\FinancialV2\BudgetAllocation;
use App\Models\FinancialV2\BudgetAllocationVersion;
use App\Models\FinancialV2\Distribution;
use App\Models\FinancialV2\DistributionItem;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\FundRealization;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\Reconciliation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Governance only. This service deliberately has no posting/execution method. */
final class AccountingDateCorrectionWorkflowService
{
    private const SUPPORTED_TYPES = ['PAY', 'RCV'];
    private const ACTIVE_STATUSES = ['blocked', 'pending_approval', 'governance_approved', 'execution_ready', 'executing'];

    public function __construct(
        private readonly AuditTrailService $auditTrail,
        private readonly AccountingDateCorrectionFinancialPreviewContract $financialPreview,
        private readonly AccountingDateCorrectionGovernancePolicy $governance,
        private readonly AccountingDateCorrectionPolicyException $policyExceptions,
    ) {}

    /** @return array<string,mixed> */
    public function preview(FinancialTransaction $transaction, array $input, int $actorUserId, ?string $currentRequestId = null): array
    {
        // The complete preview (facts, timeline, balances and checkpoints) must
        // be read from one repeatable-read database view, like official reports.
        return DB::transaction(
            fn (): array => $this->previewFromConsistentSnapshot($transaction, $input, $actorUserId, $currentRequestId),
            3,
        );
    }

    /** @return array<string,mixed> */
    private function previewFromConsistentSnapshot(FinancialTransaction $transaction, array $input, int $actorUserId, ?string $currentRequestId = null): array
    {
        $this->assertActor($actorUserId);
        $transaction = FinancialTransaction::query()->findOrFail($transaction->id);
        $target = $this->targetDate($input['target_accounting_date'] ?? null);
        $attachmentId = (string) ($input['attachment_id'] ?? '');
        if (! Str::isUuid($attachmentId)) throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-EVIDENCE', 'UUID bukti koreksi tidak valid.');

        $transaction->loadMissing(['type', 'category', 'primaryFinancialAccount', 'realization.budgetAllocationVersion.allocation', 'realization.budgetAllocationVersion.fundings', 'latestDateCorrection']);
        if ($transaction->status !== 'posted') throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-STATUS', 'Hanya transaksi posted yang dapat diajukan.');
        if (! in_array($transaction->type?->code, self::SUPPORTED_TYPES, true)) throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-TYPE', 'Fondasi saat ini hanya mendukung PAY dan RCV; REV, transfer, interfund, dan tipe lain ditolak.');
        if ($target->isSameDay($transaction->accounting_date)) throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-NO-CHANGE', 'Tanggal target sama dengan tanggal akuntansi yang berlaku.');
        $evidence = $this->assertEvidence($transaction, $attachmentId);

        $journals = Journal::query()->with('lines')->where('transaction_id', $transaction->id)->where('journal_status', 'posted')->get();
        if ($journals->count() !== 1) throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-JOURNAL-AMBIGUOUS', 'Transaksi harus memiliki tepat satu jurnal posted yang sah.');
        $journal = $journals->first();
        $policyException=$currentRequestId?$this->policyExceptions->propose($transaction,$journal,$target->toDateString(),$evidence,$actorUserId,$currentRequestId):null;

        $sourcePeriod = $this->periodFor($transaction->accounting_entity_id, $transaction->accounting_date->toDateString());
        $targetPeriod = $this->periodFor($transaction->accounting_entity_id, $target->toDateString());
        $blockers = [];
        if (! $sourcePeriod) $blockers[] = $this->blocker('SOURCE_PERIOD_MISSING', 'Periode akuntansi asal tidak ditemukan.');
        elseif ($sourcePeriod->status !== 'open') $blockers[] = $this->blocker('SOURCE_PERIOD_NOT_OPEN', 'Periode akuntansi asal tidak berstatus open; reversal tidak boleh diasumsikan dapat diposting.');
        if (! $targetPeriod) $blockers[] = $this->blocker('TARGET_PERIOD_MISSING', 'Periode akuntansi target tidak ditemukan.');
        elseif ($targetPeriod->status !== 'open') $blockers[] = $this->blocker('TARGET_PERIOD_NOT_OPEN', 'Periode akuntansi target tidak berstatus open.');

        $realization = $transaction->realization;
        $version = $realization?->budgetAllocationVersion;
        $allocation = $version?->allocation;
        if ($realization && $realization->accounting_entity_id !== $transaction->accounting_entity_id) {
            $blockers[] = $this->blocker('REALIZATION_ENTITY_MISMATCH', 'Entity realisasi tidak sama dengan Entity transaksi.');
        }
        if ($version && ($version->accounting_entity_id !== $transaction->accounting_entity_id || ! $allocation || $allocation->accounting_entity_id !== $transaction->accounting_entity_id || $version->budget_allocation_id !== $allocation->id)) {
            $blockers[] = $this->blocker('ALLOCATION_LINEAGE_MISMATCH', 'Lineage Allocation, Allocation Version, realisasi, dan transaksi tidak konsisten.');
        }
        if ($allocation && $allocation->status !== 'approved') {
            $blockers[] = $this->blocker('ALLOCATION_NOT_APPROVED', 'Parent Allocation tidak berstatus approved.');
        }
        if ($version && !$policyException && ($version->status !== 'approved' || $version->effective_from?->gt($target) || ($version->effective_to && $version->effective_to->lt($target)))) {
            $blockers[] = $this->blocker('ALLOCATION_NOT_EFFECTIVE', 'Allocation Version realisasi belum sah pada tanggal target.', ['allocation_version_id' => $version->id, 'effective_from' => $version->effective_from?->toDateString(), 'effective_to' => $version->effective_to?->toDateString()]);
        }

        $distributions = $realization
            ? Distribution::query()->where('realization_id', $realization->id)->with('items')->withCount('items')->orderBy('id')->get()
            : collect();
        if ($distributions->count() > 1) {
            $blockers[] = $this->blocker('DISTRIBUTION_LINEAGE_AMBIGUOUS', 'Lebih dari satu Distribution terhubung ke realisasi yang sama.');
        }
        foreach ($distributions as $distribution) {
            if ($distribution->accounting_entity_id !== $transaction->accounting_entity_id || $distribution->realization_id !== $realization?->id) {
                $blockers[] = $this->blocker('DISTRIBUTION_LINEAGE_MISMATCH', 'Entity atau realisasi Distribution tidak konsisten dengan transaksi.');
            }
        }

        $active = AccountingDateCorrectionRequest::query()->where('original_transaction_id', $transaction->id)
            ->whereIn('status', self::ACTIVE_STATUSES)->when($currentRequestId, fn ($query) => $query->where('id', '!=', $currentRequestId))->first();
        if ($active) $blockers[] = $this->blocker('ACTIVE_REQUEST_EXISTS', 'Transaksi sudah mempunyai permintaan koreksi aktif.', ['request_id' => $active->id]);
        $usedAsCorrectionOutput = AccountingDateCorrectionLineage::query()->where('reversal_transaction_id', $transaction->id)->orWhere('replacement_transaction_id', $transaction->id)->exists();
        if ($usedAsCorrectionOutput) $blockers[] = $this->blocker('CORRECTION_OUTPUT_NOT_ELIGIBLE', 'Transaksi sudah menjadi reversal atau replacement dari koreksi lain.');

        $batch = $this->bankBatchContext($transaction);
        if ($batch && ! $batch['identity_complete']) $blockers[] = $this->blocker('BANK_BATCH_IDENTITY_MISSING', 'Identitas batch canonical Mutasi Bank tidak tersedia.');
        elseif ($batch && $batch['transaction_count'] > 1) {
            $approvedBatch = AccountingDateCorrectionBatch::query()
                ->where('accounting_entity_id', $transaction->accounting_entity_id)
                ->where('correlation_id', $batch['correlation_id'])
                ->where('target_accounting_date', $target->toDateString())
                ->where('status', 'approved')
                ->first();
            if (! $approvedBatch) $blockers[] = $this->blocker('BANK_BATCH_GROUP_WORKFLOW_REQUIRED', 'Batch Mutasi Bank memiliki beberapa baris dan memerlukan workflow koreksi kelompok approved.', ['correlation_id' => $batch['correlation_id'], 'transaction_count' => $batch['transaction_count']]);
        }

        $reconciliations = ['source' => $this->reconciliationContext($transaction, $sourcePeriod), 'target' => $this->reconciliationContext($transaction, $targetPeriod)];
        foreach ($reconciliations as $scope => $items) {
            if (collect($items)->contains(fn ($row) => in_array($row['status'], ['reviewed', 'completed', 'exception'], true))) $blockers[] = $this->blocker(strtoupper($scope).'_RECONCILIATION_LOCKED', 'Rekening mempunyai rekonsiliasi reviewed, completed, atau exception pada periode '.$scope.'.');
        }

        $lineImpact = $journal->lines->sortBy('line_no')->map(fn ($line) => [
            'journal_line_id' => $line->id, 'line_no' => $line->line_no, 'account_id' => $line->account_id,
            'fund_id' => $line->fund_id, 'financial_account_id' => $line->financial_account_id, 'program_id' => $line->program_id,
            'cost_center_id' => $line->cost_center_id, 'counterparty_id' => $line->counterparty_id, 'category_id' => $line->category_id,
            'policy_version_ref' => $line->policy_version_ref, 'line_description' => $line->line_description,
            'debit' => DecimalAmount::normalize($line->debit_amount), 'credit' => DecimalAmount::normalize($line->credit_amount),
        ])->values()->all();

        try {
            $financialPlan = $this->financialPreview->preview($transaction, $journal, $target->toDateString(),$policyException)->toArray();
        } catch (\Throwable) {
            $financialPlan = [
                'status' => 'not_simulated',
                'financial_simulation_status' => 'not_simulated',
                'blocking_reasons' => [$this->blocker('FINANCIAL_PREVIEW_FAILED', 'Planner finansial canonical tidak dapat menyelesaikan simulasi dari kondisi database saat ini.')],
                'warnings' => [],
                'execution_ready' => false,
            ];
        }
        foreach ($financialPlan['blocking_reasons'] ?? [] as $financialBlocker) {
            $blockers[] = $financialBlocker;
        }

        return [
            'transaction_id' => $transaction->id, 'accounting_entity_id' => $transaction->accounting_entity_id,
            'original_journal_id' => $journal->id, 'transaction_status' => $transaction->status,
            'original_accounting_date' => $transaction->accounting_date->toDateString(), 'target_accounting_date' => $target->toDateString(),
            'effective_business_date' => $transaction->effective_business_date->toDateString(), 'transaction_type' => $transaction->type->code,
            'journal_impact' => ['posting_sequence' => $journal->posting_sequence, 'posting_rule_version_id' => $journal->posting_rule_version_id, 'accounting_period_id' => $journal->accounting_period_id, 'journal_status' => $journal->journal_status, 'business_date' => $journal->business_date?->toDateString(), 'accounting_date' => $journal->accounting_date?->toDateString(), 'posted_at' => $journal->posted_at?->toISOString(), 'total_debit' => DecimalAmount::sum($journal->lines->pluck('debit_amount')->all()), 'total_credit' => DecimalAmount::sum($journal->lines->pluck('credit_amount')->all()), 'lines' => $lineImpact],
            'financial_account' => ['id' => $transaction->primary_financial_account_id, 'code' => $transaction->primaryFinancialAccount?->code],
            'source_period' => $this->periodSnapshot($sourcePeriod), 'target_period' => $this->periodSnapshot($targetPeriod),
            'realization' => $realization ? ['id' => $realization->id, 'status' => $realization->status, 'transaction_id' => $realization->transaction_id, 'budget_allocation_version_id' => $realization->budget_allocation_version_id, 'recorded_at' => $realization->recorded_at?->toISOString(), 'reversed_at' => $realization->reversed_at?->toISOString(), 'accounting_entity_id' => $realization->accounting_entity_id] : null,
            'allocation' => $version ? ['allocation_id' => $allocation?->id, 'allocation_status' => $allocation?->status, 'allocation_entity_id' => $allocation?->accounting_entity_id, 'version_id' => $version->id, 'version_entity_id' => $version->accounting_entity_id, 'status' => $version->status, 'allocated_amount' => DecimalAmount::normalize($version->allocated_amount), 'effective_from' => $version->effective_from?->toDateString(), 'effective_to' => $version->effective_to?->toDateString(), 'fundings' => $version->fundings->map(fn ($funding) => ['id' => $funding->id, 'line_no' => $funding->line_no, 'fund_id' => $funding->fund_id, 'amount' => DecimalAmount::normalize($funding->amount)])->values()->all()] : null,
            'distributions' => $distributions->map(fn ($distribution) => ['id' => $distribution->id, 'accounting_entity_id' => $distribution->accounting_entity_id, 'realization_id' => $distribution->realization_id, 'status' => $distribution->status, 'item_count' => $distribution->items_count, 'items' => $distribution->items->sortBy('id')->map(fn ($item) => ['id' => $item->id, 'beneficiary_id' => $item->beneficiary_id, 'recipient_key' => $item->recipient_key, 'amount' => DecimalAmount::normalize($item->amount), 'identity_snapshot' => $item->identity_snapshot])->values()->all()])->values()->all(),
            'bank_batch' => $batch, 'reconciliations' => $reconciliations, 'selected_original_evidence' => $evidence,
            'accounting_date_policy_exception' => $policyException,
            'external_dependency_assurance' => ['status' => 'database_records_only', 'absence_of_reconciliation_is_external_clearance' => false],
            'posting_configuration' => ['status' => $financialPlan['financial_simulation_status'] === 'simulated' ? 'resolved_for_preview' : 'not_resolved', 'replacement_posting_rule_version_id' => $financialPlan['replacement_posting_rule_version_id'] ?? null, 'reversal_posting_rule_version_id' => $financialPlan['reversal_posting_rule_version_id'] ?? null],
            'financial_validation' => ['status' => $financialPlan['status'], 'contract' => AccountingDateCorrectionFinancialPreviewContract::class, 'liquidity_checked' => (bool) ($financialPlan['liquidity_validation']['complete'] ?? false), 'posting_rules_checked' => isset($financialPlan['replacement_posting_rule_version_id'], $financialPlan['reversal_posting_rule_version_id']), 'replacement_previewed' => isset($financialPlan['replacement_lines']), 'replacement_approval_and_evidence_validated' => (bool) ($financialPlan['replacement_prerequisites']['approvals_validated'] ?? false) && (bool) ($financialPlan['replacement_prerequisites']['evidence_validated'] ?? false), 'plan' => $financialPlan],
            'report_effect' => ['status' => $financialPlan['financial_simulation_status'], 'period_effects' => $financialPlan['period_report_effects'] ?? []],
            'blocking_reasons' => $blockers, 'can_request_governance_approval' => $blockers === [],
            'governance_approved_is_execution_ready' => false, 'execution_ready' => false, 'posting_available' => false,
        ];
    }

    public function request(FinancialTransaction $transaction, array $input, int $actorUserId): AccountingDateCorrectionRequest
    {
        $this->assertRequestInput($input, $actorUserId);
        return DB::transaction(function () use ($transaction, $input, $actorUserId) {
            $locked = FinancialTransaction::query()->whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            $fingerprint = $this->requestFingerprint($locked->id, $input, $actorUserId);
            $existing = AccountingDateCorrectionRequest::query()->where('idempotency_key', $input['idempotency_key'])->first();
            if ($existing) {
                if (! hash_equals($existing->request_fingerprint, $fingerprint)) throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-IDEMPOTENCY', 'Kunci idempotensi sudah digunakan untuk permintaan berbeda.');
                return $existing;
            }
            $preview = $this->preview($locked, $input, $actorUserId);
            $status = $preview['blocking_reasons'] === [] ? 'pending_approval' : 'blocked';
            $request = AccountingDateCorrectionRequest::create([
                'accounting_entity_id' => $locked->accounting_entity_id, 'original_transaction_id' => $locked->id, 'active_transaction_guard' => $locked->id,
                'original_journal_id' => $preview['original_journal_id'], 'evidence_attachment_id' => $input['attachment_id'],
                'original_accounting_date' => $locked->accounting_date->toDateString(), 'target_accounting_date' => $preview['target_accounting_date'],
                'reason' => trim($input['reason']), 'status' => $status, 'preview_snapshot' => $preview,
                'blocking_reasons' => $preview['blocking_reasons'] ?: null, 'idempotency_key' => $input['idempotency_key'],
                'request_fingerprint' => $fingerprint, 'requested_by_user_id' => $actorUserId, 'requested_at' => now(),
            ]);
            $journal = Journal::query()->whereKey($preview['original_journal_id'])->firstOrFail();
            $realization = FundRealization::query()->where('transaction_id', $locked->id)->first();
            $this->assertOriginalLineage($request, $locked, $journal, $realization);
            AccountingDateCorrectionLineageGuard::creation(fn () => AccountingDateCorrectionLineage::create([
                'correction_request_id' => $request->id,
                'original_transaction_id' => $locked->id,
                'original_journal_id' => $journal->id,
                'original_realization_id' => $realization?->id,
            ]));
            $this->auditTrail->record($locked->accounting_entity_id, 'accounting_date_correction_requested', 'accounting_date_correction', $request->id, $input['idempotency_key'], $actorUserId, null, ['transaction_id' => $locked->id, 'target_accounting_date' => $preview['target_accounting_date'], 'status' => $status, 'blocking_reasons' => $preview['blocking_reasons']]);
            return $request;
        }, 3);
    }

    public function approve(AccountingDateCorrectionRequest $request, int $actorUserId, ?string $notes = null): AccountingDateCorrectionRequest
    {
        $this->assertActor($actorUserId);
        return DB::transaction(function () use ($request, $actorUserId, $notes) {
            $probe = AccountingDateCorrectionRequest::query()->findOrFail($request->id);
            $this->lockApprovalFacts($probe);
            $locked = AccountingDateCorrectionRequest::query()->lockForUpdate()->findOrFail($request->id);
            if ($locked->status !== 'pending_approval') throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-STATE', 'Hanya request pending_approval yang dapat disetujui.');
            if ((int) $locked->requested_by_user_id === $actorUserId) {
                $user=User::query()->find($actorUserId);
                $this->governance->authorizeSuperAdminOverride($actorUserId,$actorUserId,$actorUserId,$user?->hasRole('SuperAdmin')===true,$locked->original_transaction_id,null,AccountingDateCorrectionGovernancePolicy::OVERRIDE_REASON);
            }
            AccountingDateCorrectionLineage::query()->where('correction_request_id', $locked->id)->lockForUpdate()->firstOrFail();
            $snapshot = $this->preview($locked->originalTransaction, ['target_accounting_date' => $locked->target_accounting_date->toDateString(), 'attachment_id' => $locked->evidence_attachment_id], $actorUserId, $locked->id);
            if ($snapshot['blocking_reasons'] !== []) throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-BLOCKED', 'Validasi ulang menemukan blocker.');
            $fingerprint = $this->conditionFingerprint($snapshot);
            AccountingDateCorrectionApproval::create(['correction_request_id' => $locked->id, 'decision' => 'approved', 'notes' => $notes, 'approval_snapshot' => $snapshot, 'condition_fingerprint' => $fingerprint, 'decided_by_user_id' => $actorUserId, 'decided_at' => now()]);
            AccountingDateCorrectionStateGuard::governance('pending_approval', 'governance_approved', fn () => $locked->update(['status' => 'governance_approved', 'approved_at' => now()]));
            $this->auditTrail->record($locked->accounting_entity_id, 'accounting_date_correction_governance_approved', 'accounting_date_correction', $locked->id, $locked->idempotency_key, $actorUserId, ['status' => 'pending_approval'], ['status' => 'governance_approved', 'condition_fingerprint' => $fingerprint, 'execution_ready' => false]);
            if ((int)$locked->requested_by_user_id===$actorUserId)$this->auditTrail->record($locked->accounting_entity_id,'accounting_date_correction_superadmin_override_approved','accounting_date_correction',$locked->id,$locked->idempotency_key,$actorUserId,null,['reason'=>AccountingDateCorrectionGovernancePolicy::OVERRIDE_REASON,'condition_fingerprint'=>$fingerprint,'recorded_at'=>now()->toIso8601String()]);
            return $locked->fresh(['approval', 'lineage', 'evaluations']);
        }, 3);
    }

    public function reject(AccountingDateCorrectionRequest $request, int $actorUserId, string $notes): AccountingDateCorrectionRequest
    {
        $this->assertActor($actorUserId);
        if (mb_strlen(trim($notes)) < 10) throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-NOTES', 'Alasan penolakan minimal 10 karakter.');
        return DB::transaction(function () use ($request, $actorUserId, $notes) {
            $locked = AccountingDateCorrectionRequest::query()->lockForUpdate()->findOrFail($request->id);
            if ($locked->status !== 'pending_approval') throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-STATE', 'Hanya request pending_approval yang dapat ditolak.');
            if ((int) $locked->requested_by_user_id === $actorUserId) throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-MAKER-CHECKER', 'Pembuat request tidak boleh memutus request sendiri.');
            $snapshot = ['request_id' => $locked->id, 'request_fingerprint' => $locked->request_fingerprint, 'status' => $locked->status, 'decision' => 'rejected'];
            AccountingDateCorrectionApproval::create(['correction_request_id' => $locked->id, 'decision' => 'rejected', 'notes' => trim($notes), 'approval_snapshot' => $snapshot, 'condition_fingerprint' => $this->conditionFingerprint($snapshot), 'decided_by_user_id' => $actorUserId, 'decided_at' => now()]);
            AccountingDateCorrectionStateGuard::governance('pending_approval', 'rejected', fn () => $locked->update(['status' => 'rejected', 'active_transaction_guard' => null]));
            $this->auditTrail->record($locked->accounting_entity_id, 'accounting_date_correction_rejected', 'accounting_date_correction', $locked->id, $locked->idempotency_key, $actorUserId, ['status' => 'pending_approval'], ['status' => 'rejected', 'notes' => trim($notes)]);
            return $locked->fresh(['approval', 'lineage']);
        }, 3);
    }

    public function reevaluate(AccountingDateCorrectionRequest $request, int $actorUserId): AccountingDateCorrectionRequest
    {
        $this->assertActor($actorUserId);
        return DB::transaction(function () use ($request, $actorUserId) {
            $probe = AccountingDateCorrectionRequest::query()->findOrFail($request->id);
            $this->lockApprovalFacts($probe);
            $locked = AccountingDateCorrectionRequest::query()->lockForUpdate()->findOrFail($request->id);
            if (! in_array($locked->status, ['blocked', 'governance_approved'], true)) throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-STATE', 'Hanya request blocked atau governance_approved yang dapat dievaluasi ulang.');
            AccountingDateCorrectionLineage::query()->where('correction_request_id', $locked->id)->lockForUpdate()->firstOrFail();
            $snapshot = $this->preview($locked->originalTransaction, ['target_accounting_date' => $locked->target_accounting_date->toDateString(), 'attachment_id' => $locked->evidence_attachment_id], $actorUserId, $locked->id);
            $fingerprint = $this->conditionFingerprint($snapshot);
            AccountingDateCorrectionEvaluation::create(['correction_request_id' => $locked->id, 'evaluation_snapshot' => $snapshot, 'condition_fingerprint' => $fingerprint, 'evaluated_by_user_id' => $actorUserId, 'evaluated_at' => now()]);
            $from = $locked->status;
            if ($from === 'blocked' && $snapshot['blocking_reasons'] === []) AccountingDateCorrectionStateGuard::governance('blocked', 'pending_approval', fn () => $locked->update(['status' => 'pending_approval']));
            $event = $from === 'governance_approved' ? 'accounting_date_correction_plan_reauthorized' : 'accounting_date_correction_reevaluated';
            $this->auditTrail->record($locked->accounting_entity_id, $event, 'accounting_date_correction', $locked->id, $locked->idempotency_key, $actorUserId, ['status' => $from], ['status' => $locked->status, 'condition_fingerprint' => $fingerprint, 'blocking_reasons' => $snapshot['blocking_reasons'], 'append_only' => true]);
            return $locked->fresh(['evaluations', 'lineage']);
        }, 3);
    }

    public function cancel(AccountingDateCorrectionRequest $request, int $actorUserId): AccountingDateCorrectionRequest
    {
        $this->assertActor($actorUserId);
        return DB::transaction(function () use ($request, $actorUserId) {
            $locked = AccountingDateCorrectionRequest::query()->lockForUpdate()->findOrFail($request->id);
            if (! in_array($locked->status, ['blocked', 'pending_approval'], true)) throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-STATE', 'Hanya request blocked atau pending_approval yang dapat dibatalkan.');
            $from = $locked->status;
            AccountingDateCorrectionStateGuard::governance($from, 'cancelled', fn () => $locked->update(['status' => 'cancelled', 'active_transaction_guard' => null, 'cancelled_at' => now()]));
            $this->auditTrail->record($locked->accounting_entity_id, 'accounting_date_correction_cancelled', 'accounting_date_correction', $locked->id, $locked->idempotency_key, $actorUserId, ['status' => $from], ['status' => 'cancelled']);
            return $locked->fresh(['lineage']);
        }, 3);
    }

    private function lockApprovalFacts(AccountingDateCorrectionRequest $request): void
    {
        // Correction lock order (kept stable): transaction batch -> journals ->
        // periods -> realization -> allocation parent -> allocation version ->
        // distributions -> distribution items -> reconciliations -> request/lineage.
        // Transaction precedes the facts also locked by PostingEngine; allocation
        // parent precedes its version exactly as BudgetAllocationService does.
        $probe = FinancialTransaction::query()->findOrFail($request->original_transaction_id);
        if ($probe->correlation_id) {
            $batch = FinancialTransaction::query()->where('accounting_entity_id', $request->accounting_entity_id)->where('correlation_id', $probe->correlation_id)->orderBy('id')->lockForUpdate()->get();
            $transaction = $batch->firstWhere('id', $request->original_transaction_id) ?? throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-ENTITY', 'Transaksi original tidak ditemukan dalam batch Entity terkait.');
        } else {
            $transaction = FinancialTransaction::query()->whereKey($request->original_transaction_id)->lockForUpdate()->firstOrFail();
        }
        Journal::query()->where('transaction_id', $transaction->id)->orderBy('id')->lockForUpdate()->get();
        $periods = AccountingPeriod::query()->where('accounting_entity_id', $request->accounting_entity_id)
            ->where(function ($query) use ($request) {
                $query->where(fn ($q) => $q->whereDate('start_date', '<=', $request->original_accounting_date)->whereDate('end_date', '>=', $request->original_accounting_date))
                    ->orWhere(fn ($q) => $q->whereDate('start_date', '<=', $request->target_accounting_date)->whereDate('end_date', '>=', $request->target_accounting_date));
            })->orderBy('id')->lockForUpdate()->get();
        $realization = FundRealization::query()->where('transaction_id', $transaction->id)->lockForUpdate()->first();
        if ($realization?->budget_allocation_version_id) {
            $versionProbe = BudgetAllocationVersion::query()->whereKey($realization->budget_allocation_version_id)->firstOrFail();
            BudgetAllocation::query()->whereKey($versionProbe->budget_allocation_id)->lockForUpdate()->firstOrFail();
            BudgetAllocationVersion::query()->whereKey($versionProbe->id)->lockForUpdate()->firstOrFail();
        }
        if ($realization) {
            $distributionIds = Distribution::query()->where('realization_id', $realization->id)->orderBy('id')->lockForUpdate()->pluck('id');
            if ($distributionIds->isNotEmpty()) {
                DistributionItem::query()->whereIn('distribution_id', $distributionIds)->orderBy('id')->lockForUpdate()->get();
            }
        }
        Reconciliation::query()->where('financial_account_id', $transaction->primary_financial_account_id)->whereIn('accounting_period_id', $periods->pluck('id'))->orderBy('id')->lockForUpdate()->get();
        if ($transaction->accounting_entity_id !== $request->accounting_entity_id) throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-ENTITY', 'Entity request dan transaksi tidak konsisten.');
        $request->setRelation('originalTransaction', $transaction);
    }

    private function assertOriginalLineage(
        AccountingDateCorrectionRequest $request,
        FinancialTransaction $transaction,
        Journal $journal,
        ?FundRealization $realization
    ): void {
        if ($request->accounting_entity_id !== $transaction->accounting_entity_id
            || $request->original_transaction_id !== $transaction->id
            || $request->original_journal_id !== $journal->id
            || $journal->transaction_id !== $transaction->id
            || $journal->accounting_entity_id !== $transaction->accounting_entity_id) {
            throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-LINEAGE', 'Request, transaksi, jurnal, dan Entity original tidak konsisten.');
        }
        if ($realization && ($realization->transaction_id !== $transaction->id || $realization->accounting_entity_id !== $transaction->accounting_entity_id)) {
            throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-LINEAGE', 'Realisasi original tidak konsisten dengan transaksi dan Entity.');
        }
    }

    private function assertRequestInput(array $input, int $actorUserId): void
    {
        $this->assertActor($actorUserId); $this->targetDate($input['target_accounting_date'] ?? null);
        if (mb_strlen(trim((string) ($input['reason'] ?? ''))) < 20) throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-REASON', 'Alasan koreksi minimal 20 karakter.');
        foreach (['attachment_id', 'idempotency_key'] as $field) if (! Str::isUuid((string) ($input[$field] ?? ''))) throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-UUID', "{$field} harus UUID valid.");
    }
    private function assertActor(int $actorUserId): void { if ($actorUserId <= 0) throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-ACTOR', 'Actor koreksi tidak valid.'); }
    private function targetDate(mixed $value): CarbonImmutable
    {
        $text = (string) $value;
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $text)) throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-DATE', 'Tanggal target harus berformat YYYY-MM-DD.');
        try { $date = CarbonImmutable::createFromFormat('!Y-m-d', $text); } catch (\Throwable) { $date = false; }
        if (! $date || $date->format('Y-m-d') !== $text) throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-DATE', 'Tanggal target tidak valid.');
        return $date;
    }
    private function assertEvidence(FinancialTransaction $transaction, string $attachmentId): array
    {
        $link = AttachmentLink::query()
            ->where('financial_v2_attachment_links.accounting_entity_id', $transaction->accounting_entity_id)->where('financial_v2_attachment_links.target_type', 'transaction')
            ->where('financial_v2_attachment_links.target_id', $transaction->id)->where('financial_v2_attachment_links.attachment_id', $attachmentId)
            ->where('financial_v2_attachment_links.status', 'active')
            ->first(['id', 'attachment_id', 'evidence_type', 'status']);
        $attachment = $link
            ? Attachment::query()->whereKey($link->attachment_id)->where('status', 'active')->first(['id', 'status', 'content_hash'])
            : null;
        if (! $link || ! $attachment) throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-EVIDENCE', 'Bukti aktif milik transaksi wajib dipilih.');
        if (array_key_exists((string) $transaction->category?->code, BankMutationService::CATEGORY_CODES) && $link->evidence_type !== 'statement') throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-BANK-EVIDENCE', 'Mutasi Bank wajib menggunakan rekening koran aktif.');
        return ['link_id' => $link->id, 'attachment_id' => $link->attachment_id, 'evidence_type' => $link->evidence_type, 'link_status' => $link->status, 'attachment_status' => $attachment->status, 'content_hash' => $attachment->content_hash];
    }
    private function bankBatchContext(FinancialTransaction $transaction): ?array
    {
        if (! array_key_exists((string) $transaction->category?->code, BankMutationService::CATEGORY_CODES)) return null;
        if (! $transaction->correlation_id) return ['identity_complete' => false, 'correlation_id' => null, 'transaction_count' => null, 'transaction_ids' => []];
        $members = FinancialTransaction::query()->where('accounting_entity_id', $transaction->accounting_entity_id)->where('correlation_id', $transaction->correlation_id)->where('status', 'posted')->orderBy('id')->get(['id', 'status', 'accounting_date', 'gross_amount']);
        return ['identity_complete' => true, 'correlation_id' => $transaction->correlation_id, 'transaction_count' => $members->count(), 'transactions' => $members->map(fn ($member) => ['id' => $member->id, 'status' => $member->status, 'accounting_date' => $member->accounting_date?->toDateString(), 'gross_amount' => DecimalAmount::normalize($member->gross_amount)])->all(), 'group_workflow_available' => false];
    }
    private function reconciliationContext(FinancialTransaction $transaction, ?AccountingPeriod $period): array
    {
        if (! $period) return [];
        return Reconciliation::query()->where('financial_account_id', $transaction->primary_financial_account_id)->where('accounting_period_id', $period->id)->orderBy('id')
            ->get(['id', 'accounting_entity_id', 'financial_account_id', 'accounting_period_id', 'business_date', 'accounting_date', 'statement_balance', 'ledger_balance', 'difference', 'status', 'reviewed_at', 'reconciled_at', 'updated_at'])
            ->map(fn ($row) => ['id' => $row->id, 'accounting_entity_id' => $row->accounting_entity_id, 'financial_account_id' => $row->financial_account_id, 'accounting_period_id' => $row->accounting_period_id, 'business_date' => $row->business_date?->toDateString(), 'accounting_date' => $row->accounting_date?->toDateString(), 'statement_balance' => DecimalAmount::normalize($row->statement_balance), 'ledger_balance' => DecimalAmount::normalize($row->ledger_balance), 'difference' => DecimalAmount::normalize($row->difference), 'status' => $row->status, 'reviewed_at' => $row->reviewed_at?->toISOString(), 'reconciled_at' => $row->reconciled_at?->toISOString(), 'updated_at' => $row->updated_at?->toISOString()])->values()->all();
    }
    private function periodFor(string $entityId, string $date): ?AccountingPeriod { return AccountingPeriod::query()->where('accounting_entity_id', $entityId)->whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date)->first(); }
    private function periodSnapshot(?AccountingPeriod $period): ?array { return $period ? ['id' => $period->id, 'status' => $period->status, 'start' => $period->start_date->toDateString(), 'end' => $period->end_date->toDateString()] : null; }
    private function blocker(string $code, string $message, array $context = []): array { return ['code' => $code, 'message' => $message] + $context; }
    private function requestFingerprint(string $transactionId, array $input, int $actorUserId): string { return hash('sha256', implode('|', [$transactionId, $this->targetDate($input['target_accounting_date'])->toDateString(), trim($input['reason']), strtolower($input['attachment_id']), $actorUserId])); }
    private function conditionFingerprint(array $snapshot): string { return hash('sha256', json_encode($this->canonicalize($snapshot), JSON_THROW_ON_ERROR)); }
    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) return $value;
        if (! array_is_list($value)) ksort($value);
        foreach ($value as $key => $item) $value[$key] = $this->canonicalize($item);
        return $value;
    }
}
