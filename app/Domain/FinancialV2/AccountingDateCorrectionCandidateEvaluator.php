<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\AccountingDateCorrectionLineage;
use App\Models\FinancialV2\AccountingDateCorrectionBatch;
use App\Models\FinancialV2\AccountingDateCorrectionBatchItemExecutionPair;
use App\Models\FinancialV2\AccountingDateCorrectionRequest;
use App\Models\FinancialV2\AccountingPeriod;
use App\Models\FinancialV2\ApprovalDecision;
use App\Models\FinancialV2\AttachmentLink;
use App\Models\FinancialV2\Distribution;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\ReplacementRealizationLineage;
use App\Models\FinancialV2\ReplacementRealizationRequest;
use App\Models\FinancialV2\ReversalConfigurationRequest;
use Illuminate\Support\Facades\Schema;

/** Read-only assessment. It never creates a correction request or reserves a voucher. */
final class AccountingDateCorrectionCandidateEvaluator
{
    public function __construct(
        private readonly FinancialTransactionConfigurationResolver $configuration,
        private readonly AccountingDateCorrectionFinancialPreviewContract $financialPreview,
        private readonly BankMutationService $bankMutations,
        private readonly AccountingDateCorrectionWorkflowClassifier $workflowClassifier,
        private readonly AccountingDateCorrectionGovernancePolicy $governancePolicy,
    ) {}

    public const CANDIDATES = [
        'a2eec515-6015-4c9d-8344-2deb3819385a' => '2026-09-13',
        'a2eef5b0-6485-40d1-9f03-8e9fd0510f98' => '2026-09-26',
        'a2eefd9f-9976-4348-b42f-7b66d50879a6' => '2026-09-27',
        'a2ef0da6-1ca3-4216-ac8f-38ac6f3ab571' => '2026-09-30',
        'a2ef0da6-2662-4b31-a8d9-8b79c2c084d3' => '2026-09-30',
        'a2ef0da6-2c04-4474-9524-44f5a0358295' => '2026-09-30',
        'a2ef0da6-31ad-41d5-a6f4-e4c185175aa0' => '2026-09-30',
        'a2ef0da6-3756-4bff-9c90-ef786f9e93d2' => '2026-09-30',
        'a2f1ce2d-e855-44c6-afdc-13a43b5802cb' => '2026-09-30',
    ];

    /** @return list<array<string,mixed>> */
    public function evaluateKnownCandidates(): array
    {
        return collect(self::CANDIDATES)
            ->map(fn (string $target, string $id): array => $this->evaluate($id, $target))
            ->values()->all();
    }

    /** @return array<string,mixed> */
    public function evaluate(string $transactionId, string $targetDate): array
    {
        $transaction = FinancialTransaction::query()
            ->with(['type', 'category', 'counterparty', 'splits', 'latestDateCorrection', 'realization.budgetAllocationVersion.allocation'])
            ->find($transactionId);
        if (! $transaction) {
            return $this->result($transactionId, $targetDate, null, 'needs_governance_review', [
                $this->blocker('TRANSACTION_NOT_FOUND', 'Transaksi tidak tersedia pada database yang sedang dievaluasi; kondisi environment lain tidak boleh diasumsikan.'),
            ]);
        }

        $blockers = [];
        if ($transaction->status !== 'posted') $blockers[] = $this->blocker('TRANSACTION_NOT_POSTED', 'Transaksi tidak berstatus posted.');
        if (! in_array($transaction->type?->code, ['PAY', 'RCV'], true)) $blockers[] = $this->blocker('UNSUPPORTED_TRANSACTION_TYPE', 'Hanya PAY dan RCV yang didukung planner saat ini.');
        if ($targetDate >= $transaction->accounting_date->toDateString()) $blockers[] = $this->blocker('UNSUPPORTED_DATE_DIRECTION', 'Target harus lebih awal daripada tanggal akuntansi original.');

        $counterpartyStatus = $transaction->counterparty?->status ?? 'missing';
        if ($counterpartyStatus !== 'active') {
            $blockers[] = $this->blocker('REPLACEMENT_COUNTERPARTY_NOT_ACTIVE', 'Counterparty replacement harus tersedia dan aktif kecuali kebijakan canonical secara eksplisit mengizinkan kondisi lain.', ['status' => $counterpartyStatus]);
        }

        $journals = Journal::query()->with('lines')->where('transaction_id', $transaction->id)->where('journal_status', 'posted')->get();
        if ($journals->count() !== 1) $blockers[] = $this->blocker('POSTED_JOURNAL_COUNT_INVALID', 'Transaksi harus mempunyai tepat satu jurnal posted.', ['count' => $journals->count()]);
        $journal = $journals->count() === 1 ? $journals->first() : null;
        $journalCounterparties = $journal?->lines->pluck('counterparty_id')->filter()->unique()->values()->all() ?? [];
        $counterpartySnapshotMatches = $journalCounterparties === []
            ? $transaction->counterparty_id === null
            : count($journalCounterparties) === 1 && $journalCounterparties[0] === $transaction->counterparty_id;
        if (! $counterpartySnapshotMatches) $blockers[] = $this->blocker('COUNTERPARTY_SNAPSHOT_MISMATCH', 'Counterparty transaksi tidak konsisten dengan dimensi counterparty JournalLine original.', ['journal_counterparty_ids' => $journalCounterparties]);
        $period = AccountingPeriod::query()->where('accounting_entity_id', $transaction->accounting_entity_id)->whereDate('start_date', '<=', $targetDate)->whereDate('end_date', '>=', $targetDate)->first();
        if (! $period) $blockers[] = $this->blocker('TARGET_PERIOD_MISSING', 'Periode target tidak tersedia.');
        elseif ($period->status !== 'open') $blockers[] = $this->blocker('TARGET_PERIOD_NOT_OPEN', 'Periode target tidak open.', ['status' => $period->status]);

        $realization = $transaction->realization;
        $version = $realization?->budgetAllocationVersion;
        if ($version && ($version->status !== 'approved' || $version->effective_from?->toDateString() > $targetDate || ($version->effective_to && $version->effective_to->toDateString() < $targetDate))) {
            $blockers[] = $this->blocker('ALLOCATION_NOT_EFFECTIVE', 'Allocation Version tidak approved/effective pada tanggal target.');
        }
        if ($realization) {
            $distributions = Distribution::query()->where('realization_id', $realization->id)->get(['id', 'status']);
            $correction = AccountingDateCorrectionRequest::query()
                ->where('original_transaction_id', $transaction->id)
                ->whereDate('target_accounting_date', $targetDate)
                ->latest('requested_at')->first();
            $blockers = array_merge($blockers, $this->replacementWorkflowBlockers($correction, $realization->id, $distributions));
        }

        $evidenceCount = AttachmentLink::query()->where('accounting_entity_id', $transaction->accounting_entity_id)->where('target_type', 'transaction')->where('target_id', $transaction->id)->where('status', 'active')->count();
        if ($evidenceCount === 0) $blockers[] = $this->blocker('ACTIVE_EVIDENCE_MISSING', 'Tidak terdapat evidence aktif yang terhubung ke transaksi.');
        $approvalFacts = ApprovalDecision::query()->where('accounting_entity_id', $transaction->accounting_entity_id)->where('transaction_id', $transaction->id)->orderBy('step_no')->get(['step_no', 'decision', 'decision_at']);
        if (AccountingDateCorrectionRequest::query()->where('original_transaction_id', $transaction->id)->whereIn('status', ['blocked', 'pending_approval', 'governance_approved', 'execution_ready', 'executing'])->exists()) $blockers[] = $this->blocker('ACTIVE_CORRECTION_REQUEST_EXISTS', 'Sudah terdapat correction request aktif.');
        if (AccountingDateCorrectionLineage::query()->where('reversal_transaction_id', $transaction->id)->orWhere('replacement_transaction_id', $transaction->id)->exists()) $blockers[] = $this->blocker('TRANSACTION_IS_CORRECTION_OUTPUT', 'Transaksi sudah menjadi output koreksi lain.');

        $activeRev = ReversalConfigurationRequest::query()->where('accounting_entity_id', $transaction->accounting_entity_id)->where('status', 'activated')->latest('activated_at')->first();
        if (! $activeRev) $blockers[] = $this->blocker('ACTIVE_REV_CONFIGURATION_MISSING', 'Konfigurasi REV aktif dan sah belum tersedia.');

        $fundIds = $transaction->splits->pluck('fund_id')->filter()->unique()->values();
        $programIds = $transaction->splits->pluck('program_id')->filter()->unique()->values();
        $policy = ['status' => 'not_evaluated', 'reason' => null, 'fund_ids' => $fundIds->all(), 'program_ids' => $programIds->all()];
        try {
            if ($programIds->count() > 1) throw new FinancialPostingException('E-CONFIGURATION-MISSING', 'Lebih dari satu Program ditemukan pada transaksi.');
            $resolved = $this->configuration->resolve([
                'accounting_entity_id' => $transaction->accounting_entity_id, 'transaction_type_id' => $transaction->transaction_type_id,
                'date' => $targetDate, 'financial_account_id' => $transaction->primary_financial_account_id,
                'fund_ids' => $fundIds->all(), 'category_id' => $transaction->category_id,
                'program_id' => $programIds->first(), 'require_evidence_configuration' => $realization !== null,
            ]);
            $policy = ['status' => 'compatible', 'reason' => null, 'fund_ids' => $fundIds->all(), 'program_ids' => $programIds->all(),
                'posting_rule_version_id' => $resolved->postingRuleVersion->id,
                'fund_policy_ids' => $resolved->fundPolicies->pluck('id')->all(), 'required_approval_steps' => $resolved->requiredApprovalSteps];
        } catch (\Throwable $exception) {
            $policy = ['status' => 'blocked', 'reason' => $exception->getMessage(), 'fund_ids' => $fundIds->all(), 'program_ids' => $programIds->all()];
            $blockers[] = $this->blocker($exception instanceof FinancialPostingException ? $exception->failureCode : 'TARGET_POLICY_NOT_EVALUATED', 'Konfigurasi/policy canonical pada tanggal target tidak dapat diselesaikan.', ['detail' => $exception->getMessage()]);
        }

        $batch = ['is_bank_mutation' => $this->bankMutations->isBankMutation($transaction), 'correlation_id' => $transaction->correlation_id, 'member_count' => null, 'member_ids' => []];
        if ($batch['is_bank_mutation']) {
            if (! $transaction->correlation_id) {
                $blockers[] = $this->blocker('BANK_BATCH_IDENTITY_MISSING', 'Mutasi Bank tidak mempunyai correlation_id canonical.');
            } else {
                // Governed bank membership is defined by the original posted
                // rows. Reversal/replacement drafts inherit correlation_id for
                // lineage and must never expand the authoritative group.
                $members = FinancialTransaction::query()->where('accounting_entity_id', $transaction->accounting_entity_id)->where('correlation_id', $transaction->correlation_id)->where('status', 'posted')->orderBy('id')->pluck('id');
                $batch['member_count'] = $members->count(); $batch['member_ids'] = $members->all();
                if ($members->count() > 1) {
                    [$batchBlockers, $workflowState] = $this->bankBatchWorkflowBlockers($transaction->accounting_entity_id, $transaction->correlation_id, $targetDate, $members->count());
                    $blockers = array_merge($blockers, $batchBlockers);
                    $batch['workflow_state'] = $workflowState;
                }
            }
        }

        $financialPlan = ['status' => 'not_evaluated', 'blocking_reasons' => []];
        if ($journal) {
            try {
                $financialPlan = $this->financialPreview->preview($transaction, $journal, $targetDate)->toArray();
                foreach ($financialPlan['blocking_reasons'] ?? [] as $plannerBlocker) $blockers[] = $plannerBlocker;
            } catch (\Throwable $exception) {
                $financialPlan = ['status' => 'not_evaluated', 'blocking_reasons' => [], 'error' => $exception->getMessage()];
                $blockers[] = $this->blocker('CANONICAL_PLAN_NOT_EVALUATED', 'Planner canonical tidak dapat menyelesaikan evaluasi.', ['detail' => $exception->getMessage()]);
            }
        }

        $blockers = array_merge($blockers, $this->workflowClassifier->finalReadiness([
            'governance_policy_implementable' => $this->governancePolicy->implementationReady($this->governancePolicy->ratifiedDecisions()),
            'three_distinct_resolved' => true,
            'canonical_plan_execution_ready' => ($financialPlan['execution_ready'] ?? false) === true,
            'individual_executor_available' => true,
            'requires_bank_batch' => ($batch['is_bank_mutation'] ?? false) && (($batch['member_count'] ?? 0) > 1),
            'batch_executor_available' => true,
        ]));

        $blockers = collect($blockers)->unique(fn (array $row): string => $row['code'])->values()->all();

        $governanceOnly = ['ACTIVE_REV_CONFIGURATION_MISSING', 'CANONICAL_PLAN_NOT_EXECUTION_READY', 'REVERSAL_REASON_CODE_NOT_SELECTED', 'REPLACEMENT_GOVERNANCE_NOT_MATERIALIZED'];
        $status = $blockers === [] && ($financialPlan['execution_ready'] ?? false) === true
            ? 'eligible'
            : (collect($blockers)->every(fn (array $row): bool => in_array($row['code'], $governanceOnly, true)) ? 'needs_governance_review' : 'blocked');
        return $this->result($transactionId, $targetDate, $transaction, $status, $blockers, [
            'source_journal' => $journal ? ['id' => $journal->id, 'status' => $journal->journal_status, 'reversal_of_journal_id' => $journal->reversal_of_journal_id, 'existing_reversal_count' => Journal::query()->where('reversal_of_journal_id', $journal->id)->count(), 'posting_sequence' => $journal->posting_sequence] : null,
            'posted_journal_count' => $journals->count(), 'counterparty_snapshot_matches' => $counterpartySnapshotMatches,
            'target_period' => $period ? ['id' => $period->id, 'status' => $period->status] : null,
            'active_evidence_count' => $evidenceCount, 'approval_decisions' => $approvalFacts->toArray(), 'active_rev_configuration_id' => $activeRev?->id,
            'realization' => $realization ? ['id' => $realization->id, 'status' => $realization->status] : null,
            'policy' => $policy, 'bank_batch' => $batch,
            'canonical_plan' => ['status' => $financialPlan['status'] ?? 'not_evaluated', 'financial_simulation_status' => $financialPlan['financial_simulation_status'] ?? null, 'execution_ready' => (bool) ($financialPlan['execution_ready'] ?? false), 'blocking_reasons' => $financialPlan['blocking_reasons'] ?? []],
        ]);
    }

    /** @return array<string,mixed> */
    private function result(string $id, string $target, ?FinancialTransaction $transaction, string $status, array $blockers, array $facts = []): array
    {
        return ['transaction_id' => $id, 'target_accounting_date' => $target, 'status' => $status,
            'transaction' => $transaction ? ['amount' => DecimalAmount::normalize($transaction->gross_amount), 'status' => $transaction->status, 'type' => $transaction->type?->code, 'business_date' => $transaction->effective_business_date?->toDateString(), 'accounting_date' => $transaction->accounting_date?->toDateString(), 'counterparty_id' => $transaction->counterparty_id, 'counterparty_status' => $transaction->counterparty?->status] : null,
            'facts' => $facts, 'blockers' => $blockers, 'read_only' => true];
    }

    private function blocker(string $code, string $message, array $context = []): array { return ['code' => $code, 'message' => $message] + $context; }

    /** @return list<array<string,mixed>> */
    private function replacementWorkflowBlockers(?AccountingDateCorrectionRequest $correction, string $originalRealizationId, $distributions): array
    {
        $tables = [
            'financial_v2_replacement_realization_requests',
            'financial_v2_replacement_realization_lineages',
            'financial_v2_distribution_correction_lineages',
        ];
        $schemaAvailable = ! collect($tables)->contains(fn (string $table): bool => ! Schema::hasTable($table));
        if (! $schemaAvailable || ! $correction) return $this->workflowClassifier->replacement([
            'source_available' => true, 'schema_available' => $schemaAvailable,
            'correction_request_exists' => $correction !== null, 'instance_exists' => false,
        ]);

        $request = ReplacementRealizationRequest::query()->where('correction_request_id', $correction->id)->latest('created_at')->first();
        if (! $request) return $this->workflowClassifier->replacement([
            'source_available' => true, 'schema_available' => true,
            'correction_request_exists' => true, 'instance_exists' => false,
        ]);
        $lineage = ReplacementRealizationLineage::query()->where('replacement_request_id', $request->id)->first();
        $distributionComplete = $distributions->isEmpty() || ($lineage && ! $distributions->contains(
            fn ($distribution): bool => ! $lineage->distributionLineage()->where('original_distribution_id', $distribution->id)->exists()
        ));
        return $this->workflowClassifier->replacement([
            'source_available' => true, 'schema_available' => true,
            'correction_request_exists' => true, 'instance_exists' => true,
            'instance_status' => $request->status, 'lineage_materialized' => $lineage !== null,
            'distribution_lineage_complete' => $distributionComplete,
        ]);
    }

    /** @return array{0:list<array<string,mixed>>,1:array<string,mixed>} */
    private function bankBatchWorkflowBlockers(string $entityId, string $correlationId, string $targetDate, int $memberCount): array
    {
        $tables = [
            'financial_v2_accounting_date_correction_batches',
            'financial_v2_accounting_date_correction_batch_items',
            'financial_v2_accounting_date_correction_execution_pairs',
            'financial_v2_acct_date_corr_batch_item_pairs',
        ];
        if (collect($tables)->contains(fn (string $table): bool => ! Schema::hasTable($table))) {
            $state = ['source' => 'available', 'schema' => 'missing', 'instance' => 'not_evaluated', 'execution_ready' => false];
            return [$this->workflowClassifier->bankBatch(['source_available' => true, 'schema_available' => false]), $state];
        }

        $batch = AccountingDateCorrectionBatch::query()
            ->where('accounting_entity_id', $entityId)
            ->where('correlation_id', $correlationId)
            ->whereDate('target_accounting_date', $targetDate)
            ->latest('created_at')->first();
        if (! $batch) {
            $state = ['source' => 'available', 'schema' => 'available', 'instance' => 'missing', 'execution_ready' => false];
            return [$this->workflowClassifier->bankBatch(['source_available' => true, 'schema_available' => true, 'instance_exists' => false]), $state];
        }
        if ($batch->status !== 'approved') {
            $state = ['source' => 'available', 'schema' => 'available', 'instance' => $batch->status, 'execution_ready' => false, 'batch_id' => $batch->id];
            return [$this->workflowClassifier->bankBatch(['source_available' => true, 'schema_available' => true, 'instance_exists' => true, 'instance_status' => $batch->status]), $state];
        }

        $items = $batch->items()->get();
        $links = AccountingDateCorrectionBatchItemExecutionPair::query()->with('executionPair')->where('batch_id', $batch->id)->get();
        $ready = $items->count() === $memberCount
            && $links->count() === $items->count()
            && $links->every(fn ($link): bool => $link->executionPair?->status === 'execution_ready');
        $state = ['source' => 'available', 'schema' => 'available', 'instance' => 'approved', 'execution_ready' => $ready, 'batch_id' => $batch->id, 'item_count' => $items->count(), 'linked_pair_count' => $links->count()];
        return [$this->workflowClassifier->bankBatch([
            'source_available' => true, 'schema_available' => true, 'instance_exists' => true,
            'instance_status' => 'approved', 'execution_ready' => $ready,
        ]), $state];
    }
}
