<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\AccountingPeriod;
use App\Models\FinancialV2\ApprovalDecision;
use App\Models\FinancialV2\ApprovalRequirement;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\FundRealization;
use App\Models\FinancialV2\InterfundTransfer;
use App\Models\FinancialV2\TransactionSplit;
use App\Models\FinancialV2\TransactionType;
use App\Models\FinancialV2\TreasuryTransfer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates and advances operational source transactions. It never creates a
 * Journal, JournalLine, LedgerEntry, Voucher, or balance; PostingEngine does.
 */
final class FinancialTransactionLifecycleService
{
    public function __construct(
        private readonly AuditTrailService $auditTrail,
        private readonly FinancialV2TransactionRunner $transactions,
    ) {}

    /** @param array<string, mixed> $input @param array<int, array<string, mixed>> $splits */
    public function createReceipt(array $input, array $splits, ?int $actorUserId = null): FinancialTransaction
    {
        $this->requireFields($input, ['primary_financial_account_id', 'category_id']);

        return $this->createStandardTransaction(TransactionTypeCode::Receipt, $input, $splits, $actorUserId);
    }

    /** @param array<string, mixed> $input @param array<int, array<string, mixed>> $splits */
    public function createPayment(array $input, array $splits, ?int $actorUserId = null): FinancialTransaction
    {
        $this->requireFields($input, ['primary_financial_account_id', 'counterparty_id', 'category_id']);

        return $this->createStandardTransaction(TransactionTypeCode::Payment, $input, $splits, $actorUserId);
    }

    /** @param array<string, mixed> $input @param array<int, array<string, mixed>> $splits */
    public function createTreasuryTransfer(array $input, array $splits, ?int $actorUserId = null): FinancialTransaction
    {
        $this->requireFields($input, ['source_financial_account_id', 'destination_financial_account_id']);

        return $this->transactions->run(function () use ($input, $splits, $actorUserId): FinancialTransaction {
            $input['primary_financial_account_id'] = $input['source_financial_account_id'];
            $transaction = $this->createStandardTransaction(TransactionTypeCode::TreasuryTransfer, $input, $splits, $actorUserId, false);
            $this->assertSameEntity($transaction->accounting_entity_id, 'financial_v2_financial_accounts', $input['source_financial_account_id']);
            $this->assertSameEntity($transaction->accounting_entity_id, 'financial_v2_financial_accounts', $input['destination_financial_account_id']);
            if ($input['source_financial_account_id'] === $input['destination_financial_account_id']) {
                throw new FinancialDomainException('E-TRANSFER-SAME-ACCOUNT', 'Treasury Transfer source and destination Financial Accounts must differ.');
            }

            $detail = TreasuryTransfer::create([
                'accounting_entity_id' => $transaction->accounting_entity_id,
                'transaction_id' => $transaction->id,
                'source_financial_account_id' => $input['source_financial_account_id'],
                'destination_financial_account_id' => $input['destination_financial_account_id'],
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]);
            $this->auditTrail->record($transaction->accounting_entity_id, 'treasury_transfer_created', 'treasury_transfer', $detail->id, $transaction->correlation_id, $actorUserId, null, ['transaction_id' => $transaction->id]);

            return $transaction;
        });
    }

    /** @param array<string, mixed> $input */
    public function createInterfundTransfer(array $input, ?int $actorUserId = null): FinancialTransaction
    {
        $this->requireFields($input, ['primary_financial_account_id', 'source_fund_id', 'destination_fund_id', 'policy_basis_ref', 'reason']);

        return $this->transactions->run(function () use ($input, $actorUserId): FinancialTransaction {
            $transaction = $this->createBaseTransaction(TransactionTypeCode::InterfundTransfer, $input, $actorUserId);
            $this->assertSameEntity($transaction->accounting_entity_id, 'financial_v2_funds', $input['source_fund_id']);
            $this->assertSameEntity($transaction->accounting_entity_id, 'financial_v2_funds', $input['destination_fund_id']);
            if ($input['source_fund_id'] === $input['destination_fund_id']) {
                throw new FinancialDomainException('E-INTERFUND-SAME-FUND', 'Interfund Transfer source and destination Funds must differ.');
            }

            $detail = InterfundTransfer::create([
                'accounting_entity_id' => $transaction->accounting_entity_id,
                'transaction_id' => $transaction->id,
                'source_fund_id' => $input['source_fund_id'],
                'destination_fund_id' => $input['destination_fund_id'],
                'policy_basis_ref' => $input['policy_basis_ref'],
                'reason' => $input['reason'],
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]);
            $this->auditTrail->record($transaction->accounting_entity_id, 'interfund_transfer_created', 'interfund_transfer', $detail->id, $transaction->correlation_id, $actorUserId, null, ['transaction_id' => $transaction->id, 'policy_basis_ref' => $detail->policy_basis_ref]);

            return $transaction;
        });
    }

    /** @param array<string, mixed> $input @param array<int, array<string, mixed>> $splits */
    public function createRealization(array $input, array $splits, string $budgetAllocationVersionId, ?int $actorUserId = null): FinancialTransaction
    {
        return $this->transactions->run(function () use ($input, $splits, $budgetAllocationVersionId, $actorUserId): FinancialTransaction {
            $version = DB::table('financial_v2_budget_allocation_versions as version')
                ->join('financial_v2_budget_allocations as allocation', 'allocation.id', '=', 'version.budget_allocation_id')
                ->where('version.id', $budgetAllocationVersionId)
                ->lockForUpdate()
                ->select('version.*', 'allocation.accounting_entity_id as allocation_entity_id', 'allocation.fund_id as allocation_fund_id', 'allocation.program_id as allocation_program_id', 'allocation.status as allocation_status')
                ->first();
            if (! $version || $version->allocation_entity_id !== $input['accounting_entity_id'] || $version->status !== 'approved' || $version->allocation_status !== 'approved') {
                throw new FinancialDomainException('E-REALIZATION-ALLOCATION', 'Fund Realization requires an approved in-scope Budget Allocation Version.');
            }
            $existing = FundRealization::query()
                ->join('financial_v2_transactions as transaction', 'transaction.id', '=', 'financial_v2_fund_realizations.transaction_id')
                ->where('financial_v2_fund_realizations.accounting_entity_id', $input['accounting_entity_id'])
                ->where('financial_v2_fund_realizations.budget_allocation_version_id', $budgetAllocationVersionId)
                ->where('financial_v2_fund_realizations.status', 'draft')
                ->whereIn('transaction.status', RealizationDraftReadService::ACTIVE_TRANSACTION_STATUSES)
                ->lockForUpdate()
                ->value('financial_v2_fund_realizations.transaction_id');
            if ($existing) {
                throw new FinancialDomainException('E-REALIZATION-DRAFT-EXISTS', 'Draft Realisasi untuk alokasi ini sudah tersedia. Buka draft yang ada; transaksi baru tidak dibuat.');
            }

            $transaction = $this->createPayment($input, $splits, $actorUserId);
            $hasFund = $transaction->splits()->where('fund_id', $version->allocation_fund_id)->exists();
            $hasProgram = ! $version->allocation_program_id || $transaction->splits()->where('program_id', $version->allocation_program_id)->exists();
            if (! $hasFund || ! $hasProgram) {
                throw new FinancialDomainException('E-REALIZATION-ALLOCATION', 'Fund Realization dimensions must match its Budget Allocation.');
            }

            $realization = FundRealization::create([
                'accounting_entity_id' => $transaction->accounting_entity_id,
                'transaction_id' => $transaction->id,
                'budget_allocation_version_id' => $budgetAllocationVersionId,
                'status' => 'draft',
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]);
            $this->auditTrail->record($transaction->accounting_entity_id, 'fund_realization_registered', 'fund_realization', $realization->id, $transaction->correlation_id, $actorUserId, null, ['transaction_id' => $transaction->id, 'budget_allocation_version_id' => $budgetAllocationVersionId]);

            return $transaction;
        });
    }

    /** @param array<string, mixed> $changes */
    public function updateDraft(string $transactionId, array $changes, ?int $actorUserId = null): FinancialTransaction
    {
        return $this->transactions->run(function () use ($transactionId, $changes, $actorUserId): FinancialTransaction {
            $transaction = FinancialTransaction::query()->with('type')->lockForUpdate()->findOrFail($transactionId);
            if ($transaction->status !== 'draft') {
                throw new FinancialDomainException('E-TRANSACTION-STATE', 'Only Draft transactions may be edited.');
            }
            $this->assertTransactionWorkPeriod($transaction->accounting_entity_id, (string) ($changes['accounting_date'] ?? $transaction->accounting_date->toDateString()), $transaction->type?->code);
            $allowed = ['source_reference', 'business_date', 'accounting_date', 'description', 'currency_code', 'gross_amount', 'primary_financial_account_id', 'counterparty_id', 'category_id', 'reason_code_id', 'related_transaction_id', 'idempotency_key', 'policy_version_ref'];
            $changes = array_intersect_key($changes, array_flip($allowed));
            $before = $transaction->only(array_keys($changes));
            FinancialTransactionStateGuard::withinLifecycle(fn () => $transaction->update($changes + ['updated_by_user_id' => $actorUserId]));
            $this->auditTrail->record($transaction->accounting_entity_id, 'transaction_draft_updated', 'transaction', $transaction->id, $transaction->correlation_id, $actorUserId, $before, $transaction->fresh()->only(array_keys($changes)));

            return $transaction->fresh();
        });
    }

    /** @param array<int, array<string, mixed>> $splits */
    public function replaceDraftSplits(string $transactionId, array $splits, ?int $actorUserId = null): FinancialTransaction
    {
        return $this->transactions->run(function () use ($transactionId, $splits, $actorUserId): FinancialTransaction {
            $transaction = FinancialTransaction::query()->with('type')->lockForUpdate()->findOrFail($transactionId);
            if ($transaction->status !== 'draft') {
                throw new FinancialDomainException('E-TRANSACTION-STATE', 'Only Draft transaction splits may be replaced.');
            }
            $this->assertTransactionWorkPeriod($transaction->accounting_entity_id, $transaction->accounting_date->toDateString(), $transaction->type?->code);
            $before = $transaction->splits()->orderBy('line_no')->get()->map(fn (TransactionSplit $split) => $this->splitSummary($split))->all();
            $transaction->splits()->get()->each->delete();
            $this->createSplits($transaction, $splits, $actorUserId);
            $after = $transaction->splits()->orderBy('line_no')->get()->map(fn (TransactionSplit $split) => $this->splitSummary($split))->all();
            $this->auditTrail->record($transaction->accounting_entity_id, 'transaction_splits_replaced', 'transaction', $transaction->id, $transaction->correlation_id, $actorUserId, ['splits' => $before], ['splits' => $after]);

            return $transaction->fresh(['splits']);
        });
    }

    public function submit(string $transactionId, ?int $actorUserId = null): FinancialTransaction
    {
        return $this->transition($transactionId, 'draft', 'submitted', 'transaction_submitted', $actorUserId);
    }

    public function verify(string $transactionId, ?int $actorUserId = null): FinancialTransaction
    {
        return $this->transition($transactionId, 'submitted', 'verified', 'transaction_verified', $actorUserId);
    }

    public function approve(string $transactionId, ?int $actorUserId = null): FinancialTransaction
    {
        return $this->transactions->run(function () use ($transactionId, $actorUserId): FinancialTransaction {
            $transaction = FinancialTransaction::query()->with('type')->lockForUpdate()->findOrFail($transactionId);
            if ($transaction->status !== 'verified') {
                throw new FinancialDomainException('E-TRANSACTION-STATE', 'Only Verified transactions may be approved.');
            }
            $this->assertTransactionWorkPeriod($transaction->accounting_entity_id, $transaction->accounting_date->toDateString(), $transaction->type?->code);
            $required = ApprovalRequirement::query()
                ->where('accounting_entity_id', $transaction->accounting_entity_id)
                ->where('transaction_type_id', $transaction->transaction_type_id)
                ->where('status', 'active')
                ->where('effective_from', '<=', $transaction->accounting_date)
                ->where(fn ($query) => $query->whereNull('effective_to')->orWhere('effective_to', '>=', $transaction->accounting_date))
                ->max('required_steps') ?? 0;
            if (ApprovalDecision::query()->where('transaction_id', $transaction->id)->where('decision', 'approved')->count() < $required) {
                throw new FinancialDomainException('E-APPROVAL-REQUIRED', 'Configured approval requirements are incomplete.');
            }

            return $this->changeStatus($transaction, 'approved', 'transaction_approved', $actorUserId);
        });
    }

    public function reject(string $transactionId, string $reason, ?int $actorUserId = null): FinancialTransaction
    {
        return $this->terminalTransition($transactionId, 'rejected', 'transaction_rejected', $reason, $actorUserId);
    }

    public function cancel(string $transactionId, string $reason, ?int $actorUserId = null): FinancialTransaction
    {
        return $this->terminalTransition($transactionId, 'cancelled', 'transaction_cancelled', $reason, $actorUserId);
    }

    public function recordApprovalDecision(string $transactionId, int $stepNo, string $decision, ?int $actorUserId = null, ?string $comment = null): ApprovalDecision
    {
        return $this->transactions->run(function () use ($transactionId, $stepNo, $decision, $actorUserId, $comment): ApprovalDecision {
            $transaction = FinancialTransaction::query()->lockForUpdate()->findOrFail($transactionId);
            if (! in_array($transaction->status, ['submitted', 'verified'], true) || ! in_array($decision, ['approved', 'rejected'], true) || ($decision === 'rejected' && blank($comment))) {
                throw new FinancialDomainException('E-APPROVAL-DECISION', 'Approval decisions require a submitted/verified transaction and a comment when rejected.');
            }
            $approval = ApprovalDecision::create([
                'accounting_entity_id' => $transaction->accounting_entity_id,
                'transaction_id' => $transaction->id,
                'step_no' => $stepNo,
                'decision' => $decision,
                'decision_at' => now(),
                'approver_user_id' => $actorUserId,
                'comment' => $comment,
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]);
            $this->auditTrail->record($transaction->accounting_entity_id, 'approval_decision_recorded', 'approval_decision', $approval->id, $transaction->correlation_id, $actorUserId, null, ['decision' => $decision, 'step_no' => $stepNo]);

            return $approval;
        });
    }

    public function post(string $transactionId, string $idempotencyKey, string $fingerprint, ?int $actorUserId = null): PostingResult
    {
        $transaction = FinancialTransaction::query()->findOrFail($transactionId);
        $this->auditTrail->record($transaction->accounting_entity_id, 'posting_requested', 'transaction', $transaction->id, $transaction->correlation_id, $actorUserId, ['status' => $transaction->status], ['idempotency_key' => $idempotencyKey]);

        return app(PostingEngine::class)->post($transaction->id, $idempotencyKey, $fingerprint, $actorUserId);
    }

    private function transition(string $transactionId, string $from, string $to, string $eventType, ?int $actorUserId): FinancialTransaction
    {
        return $this->transactions->run(function () use ($transactionId, $from, $to, $eventType, $actorUserId): FinancialTransaction {
            $transaction = FinancialTransaction::query()->with('type')->lockForUpdate()->findOrFail($transactionId);
            if ($transaction->status !== $from) {
                throw new FinancialDomainException('E-TRANSACTION-STATE', "Transaction must be {$from} before it can be {$to}.");
            }
            $this->assertTransactionWorkPeriod($transaction->accounting_entity_id, $transaction->accounting_date->toDateString(), $transaction->type?->code);
            if ($to === 'submitted') {
                $this->assertSubmittable($transaction);
            }

            return $this->changeStatus($transaction, $to, $eventType, $actorUserId);
        });
    }

    private function terminalTransition(string $transactionId, string $status, string $eventType, string $reason, ?int $actorUserId): FinancialTransaction
    {
        if (blank($reason)) {
            throw new FinancialDomainException('E-TRANSACTION-REASON', 'Rejected and cancelled transactions require a reason.');
        }

        return $this->transactions->run(function () use ($transactionId, $status, $eventType, $reason, $actorUserId): FinancialTransaction {
            $transaction = FinancialTransaction::query()->with('type')->lockForUpdate()->findOrFail($transactionId);
            if (! in_array($transaction->status, ['draft', 'submitted', 'verified', 'approved'], true)) {
                throw new FinancialDomainException('E-TRANSACTION-STATE', 'Only pre-posted transactions can be rejected or cancelled.');
            }
            $this->assertTransactionWorkPeriod($transaction->accounting_entity_id, $transaction->accounting_date->toDateString(), $transaction->type?->code);
            $before = ['status' => $transaction->status];
            FinancialTransactionStateGuard::withinLifecycle(fn () => $transaction->update(['status' => $status, 'updated_by_user_id' => $actorUserId]));
            $this->auditTrail->record($transaction->accounting_entity_id, $eventType, 'transaction', $transaction->id, $transaction->correlation_id, $actorUserId, $before, ['status' => $status, 'reason' => $reason]);
            $realization = FundRealization::query()->where('transaction_id', $transaction->id)->lockForUpdate()->first();
            if ($realization?->status === 'draft') {
                $realization->update(['status' => 'cancelled', 'updated_by_user_id' => $actorUserId]);
                $this->auditTrail->record($transaction->accounting_entity_id, 'fund_realization_cancelled', 'fund_realization', $realization->id, $transaction->correlation_id, $actorUserId, ['status' => 'draft'], ['status' => 'cancelled', 'reason' => $reason]);
            }

            return $transaction->fresh();
        });
    }

    private function changeStatus(FinancialTransaction $transaction, string $status, string $eventType, ?int $actorUserId): FinancialTransaction
    {
        $before = ['status' => $transaction->status];
        FinancialTransactionStateGuard::withinLifecycle(fn () => $transaction->update(['status' => $status, 'updated_by_user_id' => $actorUserId]));
        $this->auditTrail->record($transaction->accounting_entity_id, $eventType, 'transaction', $transaction->id, $transaction->correlation_id, $actorUserId, $before, ['status' => $status]);

        return $transaction->fresh();
    }

    /** @param array<string, mixed> $input @param array<int, array<string, mixed>> $splits */
    private function createStandardTransaction(TransactionTypeCode $expectedType, array $input, array $splits, ?int $actorUserId, bool $wrap = true): FinancialTransaction
    {
        $create = function () use ($expectedType, $input, $splits, $actorUserId): FinancialTransaction {
            $transaction = $this->createBaseTransaction($expectedType, $input, $actorUserId);
            $this->createSplits($transaction, $splits, $actorUserId);

            return $transaction->fresh(['splits']);
        };

        return $wrap ? $this->transactions->run($create) : $create();
    }

    /** @param array<string, mixed> $input */
    private function createBaseTransaction(TransactionTypeCode $expectedType, array $input, ?int $actorUserId): FinancialTransaction
    {
        $this->requireFields($input, ['accounting_entity_id', 'transaction_type_id', 'business_date', 'accounting_date', 'gross_amount', 'source_reference', 'idempotency_key']);
        $entity = AccountingEntity::query()->findOrFail($input['accounting_entity_id']);
        $type = TransactionType::query()->where('accounting_entity_id', $entity->id)->find($input['transaction_type_id']);
        if (! $type || $type->code !== $expectedType->value) {
            throw new FinancialDomainException('E-TRANSACTION-TYPE', "Expected {$expectedType->value} TransactionType.");
        }
        $this->assertTransactionWorkPeriod($entity->id, $input['accounting_date'], $type->code);
        $amount = DecimalAmount::normalize($input['gross_amount']);
        if (DecimalAmount::compare($amount, '0.00') <= 0 || blank($input['source_reference']) || blank($input['idempotency_key'])) {
            throw new FinancialDomainException('E-TRANSACTION-INPUT', 'Transaction amount, source reference, and idempotency key are required.');
        }

        foreach (['primary_financial_account_id' => 'financial_v2_financial_accounts', 'counterparty_id' => 'financial_v2_counterparties', 'category_id' => 'financial_v2_categories', 'reason_code_id' => 'financial_v2_reason_codes', 'related_transaction_id' => 'financial_v2_transactions'] as $field => $table) {
            if (! empty($input[$field])) {
                $this->assertSameEntity($entity->id, $table, $input[$field]);
            }
        }

        $transaction = FinancialTransaction::create([
            'accounting_entity_id' => $entity->id,
            'transaction_type_id' => $type->id,
            'status' => 'draft',
            'source_reference' => $input['source_reference'],
            'business_date' => $input['business_date'],
            'accounting_date' => $input['accounting_date'],
            'description' => $input['description'] ?? null,
            'currency_code' => $input['currency_code'] ?? $entity->functional_currency,
            'gross_amount' => $amount,
            'primary_financial_account_id' => $input['primary_financial_account_id'] ?? null,
            'counterparty_id' => $input['counterparty_id'] ?? null,
            'category_id' => $input['category_id'] ?? null,
            'reason_code_id' => $input['reason_code_id'] ?? null,
            'related_transaction_id' => $input['related_transaction_id'] ?? null,
            'idempotency_key' => $input['idempotency_key'],
            'policy_version_ref' => $input['policy_version_ref'] ?? null,
            'correlation_id' => $input['correlation_id'] ?? (string) Str::uuid(),
            'created_by_user_id' => $actorUserId,
            'updated_by_user_id' => $actorUserId,
        ]);
        $this->auditTrail->record($entity->id, 'transaction_created', 'transaction', $transaction->id, $transaction->correlation_id, $actorUserId, null, ['transaction_type' => $expectedType->value, 'status' => 'draft', 'gross_amount' => $amount]);

        return $transaction;
    }

    /** @param array<int, array<string, mixed>> $splits */
    private function createSplits(FinancialTransaction $transaction, array $splits, ?int $actorUserId): void
    {
        if ($splits === []) {
            throw new FinancialDomainException('E-SPLIT-UNBALANCED', 'This transaction type requires at least one split.');
        }
        $total = '0.00';
        foreach (array_values($splits) as $index => $split) {
            $this->requireFields($split, ['account_id', 'split_amount']);
            $amount = DecimalAmount::normalize($split['split_amount']);
            if (DecimalAmount::compare($amount, '0.00') <= 0) {
                throw new FinancialDomainException('E-SPLIT-UNBALANCED', 'Transaction split amounts must be positive.');
            }
            foreach (['account_id' => 'financial_v2_accounts', 'fund_id' => 'financial_v2_funds', 'financial_account_id' => 'financial_v2_financial_accounts', 'program_id' => 'financial_v2_programs', 'cost_center_id' => 'financial_v2_cost_centers', 'counterparty_id' => 'financial_v2_counterparties', 'category_id' => 'financial_v2_categories'] as $field => $table) {
                if (! empty($split[$field])) {
                    $this->assertSameEntity($transaction->accounting_entity_id, $table, $split[$field]);
                }
            }
            TransactionSplit::create([
                'accounting_entity_id' => $transaction->accounting_entity_id,
                'transaction_id' => $transaction->id,
                'line_no' => $index + 1,
                'split_amount' => $amount,
                'account_id' => $split['account_id'],
                'fund_id' => $split['fund_id'] ?? null,
                'financial_account_id' => $split['financial_account_id'] ?? null,
                'program_id' => $split['program_id'] ?? null,
                'cost_center_id' => $split['cost_center_id'] ?? null,
                'counterparty_id' => $split['counterparty_id'] ?? null,
                'category_id' => $split['category_id'] ?? null,
                'purpose_note' => $split['purpose_note'] ?? null,
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]);
            $total = DecimalAmount::add($total, $amount);
        }
        if (! DecimalAmount::equals($total, $transaction->gross_amount)) {
            throw new FinancialDomainException('E-SPLIT-UNBALANCED', 'Transaction splits must equal gross amount exactly.');
        }
    }

    private function assertSubmittable(FinancialTransaction $transaction): void
    {
        if (blank($transaction->source_reference) || blank($transaction->idempotency_key) || DecimalAmount::compare($transaction->gross_amount, '0.00') <= 0) {
            throw new FinancialDomainException('E-TRANSACTION-INPUT', 'Draft transaction is missing a stable source identity or amount.');
        }
        if ($transaction->type?->code !== TransactionTypeCode::InterfundTransfer->value
            && (! $transaction->splits()->exists() || ! DecimalAmount::equals(DecimalAmount::sum($transaction->splits()->pluck('split_amount')), $transaction->gross_amount))) {
            throw new FinancialDomainException('E-SPLIT-UNBALANCED', 'Draft transaction splits must equal the gross amount before submission.');
        }
    }

    private function assertSameEntity(string $entityId, string $table, string $id): void
    {
        if (! DB::table($table)->where('id', $id)->where('accounting_entity_id', $entityId)->exists()) {
            throw new FinancialDomainException('E-MASTER-SCOPE', 'A transaction dimension must belong to its AccountingEntity.');
        }
    }

    private function assertTransactionWorkPeriod(string $entityId, string $accountingDate, ?string $transactionTypeCode): void
    {
        $period = AccountingPeriod::query()
            ->forEntity($entityId)
            ->where('start_date', '<=', $accountingDate)
            ->where('end_date', '>=', $accountingDate)
            ->lockForUpdate()
            ->first();

        if ($period?->permitsOrdinaryPosting() || ($period?->status === 'soft_closed' && $transactionTypeCode === TransactionTypeCode::Adjustment->value)) {
            return;
        }

        throw new FinancialDomainException('E-PERIOD-CLOSED', 'The accounting date is not eligible for financial transaction work.');
    }

    /** @param array<string, mixed> $input @param array<int, string> $required */
    private function requireFields(array $input, array $required): void
    {
        foreach ($required as $field) {
            if (! array_key_exists($field, $input) || blank($input[$field])) {
                throw new FinancialDomainException('E-TRANSACTION-INPUT', "{$field} is required.");
            }
        }
    }

    /** @return array<string, mixed> */
    private function splitSummary(TransactionSplit $split): array
    {
        return $split->only(['line_no', 'split_amount', 'account_id', 'fund_id', 'financial_account_id', 'program_id', 'cost_center_id', 'counterparty_id', 'category_id']);
    }
}
