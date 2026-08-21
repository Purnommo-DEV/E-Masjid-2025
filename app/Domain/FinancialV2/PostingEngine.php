<?php

namespace App\Domain\FinancialV2;

use App\Domain\FinancialV2\Reporting\FundFinancialAccountCompositionReadService;
use App\Models\FinancialV2\Account;
use App\Models\FinancialV2\AccountDimensionRule;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\AccountingPeriod;
use App\Models\FinancialV2\ApprovalDecision;
use App\Models\FinancialV2\ApprovalRequirement;
use App\Models\FinancialV2\AttachmentLink;
use App\Models\FinancialV2\AuditEvent;
use App\Models\FinancialV2\BankAccountDetail;
use App\Models\FinancialV2\BudgetAllocationVersion;
use App\Models\FinancialV2\CashAccountDetail;
use App\Models\FinancialV2\DocumentSequence;
use App\Models\FinancialV2\EvidenceRequirement;
use App\Models\FinancialV2\FinancialAccount;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\FundPolicyRule;
use App\Models\FinancialV2\FundPolicyVersion;
use App\Models\FinancialV2\FundRealization;
use App\Models\FinancialV2\IdempotencyKey;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\JournalLine;
use App\Models\FinancialV2\LedgerEntry;
use App\Models\FinancialV2\LegacyMapping;
use App\Models\FinancialV2\OpeningBalanceBatch;
use App\Models\FinancialV2\PostingAttempt;
use App\Models\FinancialV2\PostingRuleLine;
use App\Models\FinancialV2\PostingRuleVersion;
use App\Models\FinancialV2\ReasonCode;
use App\Models\FinancialV2\TransactionSplit;
use App\Models\FinancialV2\TransactionType;
use App\Models\FinancialV2\Voucher;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The sole writer of posted V2 journals and General Ledger facts.
 *
 * Operational controllers/services may create an approved source transaction,
 * but only this class converts it into immutable financial facts.
 */
final class PostingEngine
{
    public function __construct(
        private readonly FinancialV2TransactionRunner $transactions,
        private readonly FundFinancialAccountCompositionReadService $fundFinancialAccounts,
    ) {}

    public function post(string $transactionId, string $idempotencyKey, string $fingerprint, ?int $actorUserId = null): PostingResult
    {
        try {
            return $this->transactions->run(function () use ($transactionId, $idempotencyKey, $fingerprint, $actorUserId): PostingResult {
                $transaction = FinancialTransaction::query()->with(['splits', 'type', 'treasuryTransfer', 'interfundTransfer', 'realization'])->lockForUpdate()->findOrFail($transactionId);
                $idempotency = $this->reserveIdempotencyKey($transaction->accounting_entity_id, 'transaction-posting', $idempotencyKey, $fingerprint, $actorUserId);
                if ($idempotency['result']) {
                    return $idempotency['result'];
                }

                if ($transaction->status !== 'approved') {
                    throw new FinancialPostingException('E-TRANSACTION-STATE', 'Only an approved transaction can be posted.');
                }
                $this->validateTransactionType($transaction);

                $this->lockEntity($transaction->accounting_entity_id);
                $period = $this->eligiblePeriodFor($transaction);
                $version = $this->resolveRuleVersion($transaction);
                $this->validateOperationalTransaction($transaction);
                $this->validateSplits($transaction);
                $this->validateApprovalsAndEvidence($transaction, $version);
                $this->validateCorrectionTransaction($transaction);

                $originalJournal = $this->reversalOriginalJournal($transaction);
                $lines = $originalJournal
                    ? $this->compileReversalLines($transaction, $originalJournal)
                    : $this->compileLines($transaction, $version);

                $lines = $this->validateLines($transaction, $lines);
                $this->validateOperationalLines($transaction, $lines);
                $this->validateFundLiquidityBalances($transaction, $lines);
                $this->validateFundRealization($transaction);

                return $this->commitPosting(
                    $transaction,
                    $idempotency['key'],
                    $version,
                    $period,
                    $lines,
                    $actorUserId,
                    $originalJournal,
                );
            });
        } catch (FinancialPostingException $exception) {
            $this->recordTransactionFailure($transactionId, $idempotencyKey, $fingerprint, $actorUserId, $exception->failureCode, $exception->getMessage());

            throw $exception;
        } catch (QueryException $exception) {
            if (str_contains($exception->getMessage(), 'fv2_idempotency_scope_key_uq')) {
                throw new FinancialPostingException('E-DUPLICATE-POSTING', 'A concurrent request reserved this idempotency key; retry with the same payload.');
            }

            $this->recordTransactionFailure($transactionId, $idempotencyKey, $fingerprint, $actorUserId, 'E-POSTING-TECHNICAL', $exception->getMessage());

            throw $exception;
        }
    }

    /**
     * Posts a previously approved and reconciled opening-balance batch.
     * This method intentionally does not select a production cutover date;
     * that date remains part of the approved batch supplied by a future cutover.
     */
    public function postOpeningBalance(string $batchId, string $transactionTypeId, string $idempotencyKey, string $fingerprint, ?int $actorUserId = null): PostingResult
    {
        return $this->transactions->run(function () use ($batchId, $transactionTypeId, $idempotencyKey, $fingerprint, $actorUserId): PostingResult {
            $batch = OpeningBalanceBatch::query()->with(['lines', 'mappingSet'])->lockForUpdate()->findOrFail($batchId);
            $idempotency = $this->reserveIdempotencyKey($batch->accounting_entity_id, 'opening-balance-posting', $idempotencyKey, $fingerprint, $actorUserId);
            if ($idempotency['result']) {
                return $idempotency['result'];
            }

            $this->validateOpeningBalanceBatch($batch);
            $this->lockEntity($batch->accounting_entity_id);
            $period = AccountingPeriod::query()->forEntity($batch->accounting_entity_id)->lockForUpdate()->findOrFail($batch->accounting_period_id);
            if (! $period->permitsOrdinaryPosting() || ! $period->start_date->lte($batch->cutover_date) || ! $period->end_date->gte($batch->cutover_date)) {
                throw new FinancialPostingException('E-PERIOD-CLOSED', 'The approved opening-balance date is not in an open accounting period.');
            }

            $type = TransactionType::query()->where('accounting_entity_id', $batch->accounting_entity_id)->whereKey($transactionTypeId)->where('status', 'active')->first();
            if (! $type || $type->code !== 'OPB') {
                throw new FinancialPostingException('E-OPENING-TYPE', 'An active OPB transaction type is required for an opening-balance batch.');
            }

            $totalDebit = DecimalAmount::sum($batch->lines->pluck('debit_amount'));
            $firstLine = $batch->lines->sortBy('line_no')->first();
            $transaction = FinancialTransaction::create([
                'accounting_entity_id' => $batch->accounting_entity_id,
                'transaction_type_id' => $type->id,
                'status' => 'approved',
                'source_reference' => 'OPENING-BALANCE:'.$batch->id,
                'business_date' => $batch->cutover_date,
                'accounting_date' => $batch->cutover_date,
                'description' => 'Approved opening balance '.$batch->cutover_reference,
                'gross_amount' => $totalDebit,
                'primary_financial_account_id' => $firstLine->financial_account_id,
                'idempotency_key' => $idempotencyKey,
                'correlation_id' => (string) Str::uuid(),
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]);
            TransactionSplit::create([
                'accounting_entity_id' => $batch->accounting_entity_id,
                'transaction_id' => $transaction->id,
                'line_no' => 1,
                'split_amount' => $totalDebit,
                'account_id' => $firstLine->account_id,
                'fund_id' => $firstLine->fund_id,
                'financial_account_id' => $firstLine->financial_account_id,
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]);
            $version = $this->resolveRuleVersion($transaction);
            $lines = $batch->lines->sortBy('line_no')->map(fn ($line) => [
                'accounting_entity_id' => $batch->accounting_entity_id,
                'account_id' => $line->account_id,
                'debit_amount' => $line->debit_amount,
                'credit_amount' => $line->credit_amount,
                'fund_id' => $line->fund_id,
                'financial_account_id' => $line->financial_account_id,
                'program_id' => $line->program_id,
                'cost_center_id' => null,
                'counterparty_id' => null,
                'category_id' => null,
                'policy_version_ref' => 'OPENING-BALANCE:'.$batch->id,
                'line_description' => $line->line_description,
            ])->all();

            $lines = $this->validateLines($transaction, $lines);
            $this->validateFundLiquidityBalances($transaction, $lines);
            $result = $this->commitPosting($transaction, $idempotency['key'], $version, $period, $lines, $actorUserId);
            OpeningBalanceStateGuard::withinOpeningBalance(fn () => $batch->update(['status' => 'posted', 'journal_id' => $result->journalId, 'updated_by_user_id' => $actorUserId]));
            AuditEvent::create([
                'accounting_entity_id' => $batch->accounting_entity_id,
                'event_at' => now(),
                'event_type' => 'opening_balance_posted',
                'target_type' => 'opening_balance_batch',
                'target_id' => $batch->id,
                'actor_user_id' => $actorUserId,
                'correlation_id' => $transaction->correlation_id,
                'after_summary' => json_encode(['journal_id' => $result->journalId, 'mapping_set_id' => $batch->mapping_set_id]),
                'created_at' => now(),
            ]);

            return $result;
        });
    }

    /** @return array{key: IdempotencyKey, result: ?PostingResult} */
    private function reserveIdempotencyKey(string $entityId, string $scope, string $keyValue, string $fingerprint, ?int $actorUserId): array
    {
        // Do not lock a missing unique-key range before inserting it. Under
        // MySQL REPEATABLE READ, concurrent requests with different keys in
        // the same entity/scope can otherwise take overlapping gap locks and
        // deadlock before the entity serialization lock is reached. The unique
        // index remains the authority: insert atomically, then lock the actual
        // row. A duplicate request waits for the first transaction to commit
        // and subsequently reads its completed result below.
        IdempotencyKey::query()->insertOrIgnore([
            'id' => (string) Str::uuid(),
            'accounting_entity_id' => $entityId,
            'scope_name' => $scope,
            'key_value' => $keyValue,
            'request_fingerprint' => $fingerprint,
            'status' => 'reserved',
            'created_by_user_id' => $actorUserId,
            'updated_by_user_id' => $actorUserId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $key = IdempotencyKey::query()
            ->where('accounting_entity_id', $entityId)
            ->where('scope_name', $scope)
            ->where('key_value', $keyValue)
            ->lockForUpdate()
            ->firstOrFail();

        if ($key?->status === 'completed') {
            if ($key->request_fingerprint !== $fingerprint) {
                throw new FinancialPostingException('E-IDEMPOTENCY-CONFLICT', 'Idempotency key payload differs from its completed request.');
            }

            $attempt = PostingAttempt::query()->where('idempotency_record_id', $key->id)->where('status', 'committed')->firstOrFail();
            $voucherId = Voucher::query()->where('transaction_id', $attempt->transaction_id)->value('id');

            return ['key' => $key, 'result' => new PostingResult($attempt->transaction_id, $attempt->journal_id, $voucherId)];
        }

        if ($key && $key->request_fingerprint !== $fingerprint) {
            throw new FinancialPostingException('E-IDEMPOTENCY-CONFLICT', 'Idempotency key payload differs from its prior request.');
        }

        return ['key' => $key, 'result' => null];
    }

    private function lockEntity(string $entityId): void
    {
        AccountingEntity::query()->lockForUpdate()->findOrFail($entityId);
    }

    private function recordTransactionFailure(string $transactionId, string $idempotencyKey, string $fingerprint, ?int $actorUserId, string $failureCode, string $failureDetail): void
    {
        try {
            $this->transactions->run(function () use ($transactionId, $idempotencyKey, $fingerprint, $actorUserId, $failureCode, $failureDetail): void {
                $transaction = FinancialTransaction::query()->lockForUpdate()->find($transactionId);
                if (! $transaction) {
                    return;
                }

                $key = IdempotencyKey::query()
                    ->where('accounting_entity_id', $transaction->accounting_entity_id)
                    ->where('scope_name', 'transaction-posting')
                    ->where('key_value', $idempotencyKey)
                    ->lockForUpdate()
                    ->first();
                if ($key && ($key->request_fingerprint !== $fingerprint || $key->status === 'completed')) {
                    return;
                }

                $key ??= IdempotencyKey::create([
                    'accounting_entity_id' => $transaction->accounting_entity_id,
                    'scope_name' => 'transaction-posting',
                    'key_value' => $idempotencyKey,
                    'request_fingerprint' => $fingerprint,
                    'status' => 'failed',
                    'created_by_user_id' => $actorUserId,
                    'updated_by_user_id' => $actorUserId,
                ]);
                $key->update(['status' => 'failed']);

                PostingAttempt::create([
                    'accounting_entity_id' => $transaction->accounting_entity_id,
                    'transaction_id' => $transaction->id,
                    'idempotency_record_id' => $key->id,
                    'status' => 'failed',
                    'attempt_no' => (int) PostingAttempt::query()->where('transaction_id', $transaction->id)->max('attempt_no') + 1,
                    'requested_at' => now(),
                    'completed_at' => now(),
                    'failure_code' => $failureCode,
                    'failure_detail' => $failureDetail,
                    'correlation_id' => $transaction->correlation_id,
                    'created_by_user_id' => $actorUserId,
                    'updated_by_user_id' => $actorUserId,
                ]);
                AuditEvent::create([
                    'accounting_entity_id' => $transaction->accounting_entity_id,
                    'event_at' => now(),
                    'event_type' => 'posting_failed',
                    'target_type' => 'transaction',
                    'target_id' => $transaction->id,
                    'actor_user_id' => $actorUserId,
                    'correlation_id' => $transaction->correlation_id,
                    'after_summary' => json_encode(['failure_code' => $failureCode]),
                    'created_at' => now(),
                ]);
            });
        } catch (\Throwable) {
            // Preserve the original posting failure; an independent persistence
            // failure is operationally visible through the exception/log path.
        }
    }

    private function eligiblePeriodFor(FinancialTransaction $transaction): AccountingPeriod
    {
        $period = AccountingPeriod::query()
            ->forEntity($transaction->accounting_entity_id)
            ->where('start_date', '<=', $transaction->accounting_date)
            ->where('end_date', '>=', $transaction->accounting_date)
            ->lockForUpdate()
            ->first();

        if ($period?->permitsOrdinaryPosting()) {
            return $period;
        }
        if ($period?->status === 'soft_closed' && $transaction->type?->code === 'ADJ') {
            return $period;
        }
        if ($period?->status === 'reopened') {
            throw new FinancialPostingException('E-PERIOD-REOPEN-SCOPE', 'Reopened periods remain fail-closed until an approved transaction scope is available.');
        }

        throw new FinancialPostingException('E-PERIOD-CLOSED', 'The accounting date is not eligible for this posting.');
    }

    private function resolveRuleVersion(FinancialTransaction $transaction): PostingRuleVersion
    {
        $date = $transaction->accounting_date;
        $version = PostingRuleVersion::query()
            ->where('accounting_entity_id', $transaction->accounting_entity_id)
            ->where('status', 'effective')
            ->where('effective_from', '<=', $date)
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhere('effective_to', '>=', $date))
            ->whereHas('rule', fn ($query) => $query->where('transaction_type_id', $transaction->transaction_type_id)->where('status', 'active'))
            ->orderByDesc('effective_from')
            ->first();

        if (! $version) {
            throw new FinancialPostingException('E-RULE-NOT-EFFECTIVE', 'No effective posting rule version exists for this transaction type.');
        }

        return $version;
    }

    private function validateSplits(FinancialTransaction $transaction): void
    {
        if ($transaction->type?->code === TransactionTypeCode::InterfundTransfer->value) {
            if (! $transaction->interfundTransfer) {
                throw new FinancialPostingException('E-INTERFUND-DETAIL', 'Interfund Transfer requires governed source and destination Fund details.');
            }

            return;
        }
        if ($transaction->splits->isEmpty() || ! DecimalAmount::equals(DecimalAmount::sum($transaction->splits->pluck('split_amount')), $transaction->gross_amount)) {
            throw new FinancialPostingException('E-SPLIT-UNBALANCED', 'Transaction splits must equal the gross amount.');
        }
    }

    private function validateOperationalTransaction(FinancialTransaction $transaction): void
    {
        if ($transaction->type?->code === TransactionTypeCode::TreasuryTransfer->value) {
            $transfer = $transaction->treasuryTransfer;
            if (! $transfer || $transfer->accounting_entity_id !== $transaction->accounting_entity_id
                || $transfer->source_financial_account_id === $transfer->destination_financial_account_id
                || $transaction->primary_financial_account_id !== $transfer->source_financial_account_id) {
                throw new FinancialPostingException('E-TRANSFER-DETAIL', 'Treasury Transfer requires distinct, traceable source and destination Financial Accounts.');
            }
            $source = FinancialAccount::query()->find($transfer->source_financial_account_id);
            $destination = FinancialAccount::query()->find($transfer->destination_financial_account_id);
            if (! $source || ! $destination || $source->accounting_entity_id !== $transaction->accounting_entity_id
                || $destination->accounting_entity_id !== $transaction->accounting_entity_id
                || ! $source->isUsableOn($transaction->accounting_date->toDateString())
                || ! $destination->isUsableOn($transaction->accounting_date->toDateString())
                || $source->currency_code !== $destination->currency_code) {
                throw new FinancialPostingException('E-TRANSFER-DETAIL', 'Treasury Transfer Financial Accounts must be active, in-scope, and share one currency.');
            }
        }

        if ($transaction->type?->code === TransactionTypeCode::InterfundTransfer->value) {
            $transfer = $transaction->interfundTransfer;
            if (! $transfer || $transfer->accounting_entity_id !== $transaction->accounting_entity_id
                || $transfer->source_fund_id === $transfer->destination_fund_id
                || ! $transaction->primary_financial_account_id
                || blank($transfer->policy_basis_ref) || blank($transfer->reason)) {
                throw new FinancialPostingException('E-INTERFUND-DETAIL', 'Interfund Transfer requires distinct Funds, an attribution Financial Account, policy basis, and reason.');
            }
            $attributionAccount = FinancialAccount::query()->find($transaction->primary_financial_account_id);
            if (! $attributionAccount
                || $attributionAccount->accounting_entity_id !== $transaction->accounting_entity_id
                || ! $attributionAccount->isUsableOn($transaction->accounting_date->toDateString())) {
                throw new FinancialPostingException('E-INTERFUND-ATTRIBUTION', 'Interfund Fund attribution requires an active in-scope Financial Account.');
            }
        }
    }

    private function validateTransactionType(FinancialTransaction $transaction): void
    {
        $type = $transaction->type;
        $date = $transaction->accounting_date->toDateString();
        if (! $type || $type->status !== 'active' || ($type->valid_from && $type->valid_from->gt($date)) || ($type->valid_to && $type->valid_to->lt($date))) {
            throw new FinancialPostingException('E-MASTER-INACTIVE', 'Transaction type is inactive or ineffective on the accounting date.');
        }
    }

    private function validateApprovalsAndEvidence(FinancialTransaction $transaction, PostingRuleVersion $version): void
    {
        $required = ApprovalRequirement::query()
            ->where('accounting_entity_id', $transaction->accounting_entity_id)
            ->where('transaction_type_id', $transaction->transaction_type_id)
            ->where('status', 'active')
            ->where('effective_from', '<=', $transaction->accounting_date)
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhere('effective_to', '>=', $transaction->accounting_date))
            ->max('required_steps') ?? 0;
        if (ApprovalDecision::query()->where('transaction_id', $transaction->id)->where('decision', 'approved')->count() < $required) {
            throw new FinancialPostingException('E-APPROVAL-REQUIRED', 'Required approval steps are incomplete.');
        }

        foreach (EvidenceRequirement::query()->where('posting_rule_version_id', $version->id)->get() as $requirement) {
            if (AttachmentLink::query()->where('target_type', 'transaction')->where('target_id', $transaction->id)->where('evidence_type', $requirement->evidence_type)->where('status', 'active')->count() < $requirement->minimum_count) {
                throw new FinancialPostingException('E-EVIDENCE-REQUIRED', 'Required evidence is missing.');
            }
        }
    }

    private function validateCorrectionTransaction(FinancialTransaction $transaction): void
    {
        $typeCode = $transaction->type?->code;
        if (! in_array($typeCode, ['ADJ', 'REV'], true)) {
            return;
        }

        $reason = ReasonCode::query()->where('accounting_entity_id', $transaction->accounting_entity_id)->find($transaction->reason_code_id);
        if (! $reason || $reason->status !== 'active' || $reason->reason_class !== ($typeCode === 'REV' ? 'reversal' : 'adjustment') || blank($transaction->source_reference) || blank($transaction->description)) {
            throw new FinancialPostingException('E-CORRECTION-RATIONALE', 'Adjustments and reversals require a reason code, source reference, and description.');
        }
        if (ApprovalDecision::query()->where('transaction_id', $transaction->id)->where('decision', 'approved')->count() < 1) {
            throw new FinancialPostingException('E-CORRECTION-APPROVAL', 'Adjustments and reversals require at least one recorded approval.');
        }
        if (! AttachmentLink::query()->where('target_type', 'transaction')->where('target_id', $transaction->id)->where('status', 'active')->exists()) {
            throw new FinancialPostingException('E-CORRECTION-EVIDENCE', 'Adjustments and reversals require active supporting evidence.');
        }
    }

    private function reversalOriginalJournal(FinancialTransaction $transaction): ?Journal
    {
        if ($transaction->type?->code !== 'REV') {
            return null;
        }

        if (! $transaction->related_transaction_id) {
            throw new FinancialPostingException('E-REVERSAL-LINEAGE', 'A reversal must reference the original posted transaction.');
        }

        $original = FinancialTransaction::query()
            ->with('type')
            ->where('accounting_entity_id', $transaction->accounting_entity_id)
            ->lockForUpdate()
            ->find($transaction->related_transaction_id);
        if (! $original || $original->status !== 'posted') {
            throw new FinancialPostingException('E-REVERSAL-LINEAGE', 'The reversal source transaction is not posted in this entity.');
        }
        if ($original->type?->code === 'REV') {
            throw new FinancialPostingException('E-REVERSAL-LINEAGE', 'A reversal cannot target another reversal; use a governed adjustment for any further correction.');
        }

        $journal = Journal::query()->with('lines')->where('transaction_id', $original->id)->where('journal_status', 'posted')->lockForUpdate()->first();
        if (! $journal || Journal::query()->where('reversal_of_journal_id', $journal->id)->exists()) {
            throw new FinancialPostingException('E-REVERSAL-LINEAGE', 'The original journal is unavailable or already reversed.');
        }

        return $journal;
    }

    /** @return array<int, array<string, mixed>> */
    private function compileLines(FinancialTransaction $transaction, PostingRuleVersion $version): array
    {
        $result = [];
        foreach (PostingRuleLine::query()->where('posting_rule_version_id', $version->id)->orderBy('line_no')->get() as $ruleLine) {
            $sources = $ruleLine->amount_source === 'split_amount'
                ? $transaction->splits
                : collect([$transaction->type?->code === TransactionTypeCode::InterfundTransfer->value ? null : ($transaction->splits->count() === 1 ? $transaction->splits->first() : null)]);
            foreach ($sources as $split) {
                if (! $split && $transaction->type?->code !== TransactionTypeCode::InterfundTransfer->value) {
                    throw new FinancialPostingException('E-RULE-AMBIGUOUS', 'A gross transaction rule cannot source dimensions from multiple splits.');
                }
                $amount = $ruleLine->amount_source === 'split_amount' ? $split->split_amount : $transaction->gross_amount;
                $result[] = $this->lineFromRule($transaction, $ruleLine, $split, $amount);
            }
        }

        if (count($result) < 2) {
            throw new FinancialPostingException('E-JOURNAL-UNBALANCED', 'Posting rules must construct at least two lines.');
        }

        return $result;
    }

    /** @return array<int, array<string, mixed>> */
    private function compileReversalLines(FinancialTransaction $transaction, Journal $originalJournal): array
    {
        return $originalJournal->lines->sortBy('line_no')->map(fn (JournalLine $line) => [
            'accounting_entity_id' => $transaction->accounting_entity_id,
            'account_id' => $line->account_id,
            'debit_amount' => $line->credit_amount,
            'credit_amount' => $line->debit_amount,
            'fund_id' => $line->fund_id,
            'financial_account_id' => $line->financial_account_id,
            'program_id' => $line->program_id,
            'cost_center_id' => $line->cost_center_id,
            'counterparty_id' => $line->counterparty_id,
            'category_id' => $line->category_id,
            'policy_version_ref' => $line->policy_version_ref,
            'line_description' => 'Reversal: '.($line->line_description ?? $transaction->description),
        ])->all();
    }

    /** @return array<string, mixed> */
    private function lineFromRule(FinancialTransaction $transaction, PostingRuleLine $rule, ?object $split, mixed $amount): array
    {
        $dimension = function (string $name) use ($transaction, $rule, $split): mixed {
            $source = $rule->{$name.'_source'};

            return match ($source) {
                'transaction_primary' => $transaction->primary_financial_account_id,
                'transaction' => $transaction->{$name.'_id'},
                'split' => $split?->{$name.'_id'},
                'fixed' => $rule->{'fixed_'.$name.'_id'},
                'transfer_source' => $transaction->treasuryTransfer?->source_financial_account_id,
                'transfer_destination' => $transaction->treasuryTransfer?->destination_financial_account_id,
                'interfund_source' => $transaction->interfundTransfer?->source_fund_id,
                'interfund_destination' => $transaction->interfundTransfer?->destination_fund_id,
                default => null,
            };
        };

        return [
            'accounting_entity_id' => $transaction->accounting_entity_id,
            'account_id' => $rule->account_id,
            'debit_amount' => $rule->entry_side === 'debit' ? $amount : 0,
            'credit_amount' => $rule->entry_side === 'credit' ? $amount : 0,
            'fund_id' => $dimension('fund'),
            'financial_account_id' => $dimension('financial_account'),
            'program_id' => $dimension('program'),
            'cost_center_id' => $dimension('cost_center'),
            'counterparty_id' => $dimension('counterparty'),
            'category_id' => $dimension('category'),
            'policy_version_ref' => $transaction->policy_version_ref,
            'line_description' => $rule->line_description_template ?? $transaction->description,
        ];
    }

    /** @param array<int, array<string, mixed>> $lines @return array<int, array<string, mixed>> */
    private function validateLines(FinancialTransaction $transaction, array $lines): array
    {
        if (count($lines) < 2 || ! DecimalAmount::equals(DecimalAmount::sum(array_column($lines, 'debit_amount')), DecimalAmount::sum(array_column($lines, 'credit_amount')))) {
            throw new FinancialPostingException('E-JOURNAL-UNBALANCED', 'Journal debit and credit totals differ.');
        }

        $isReversal = $transaction->type?->code === 'REV';
        foreach ($lines as $index => $line) {
            $account = Account::query()->findOrFail($line['account_id']);
            if ($account->accounting_entity_id !== $transaction->accounting_entity_id || (! $isReversal && ($account->status !== 'active' || ! $account->is_posting_account || ($account->valid_from && $account->valid_from->gt($transaction->accounting_date)) || ($account->valid_to && $account->valid_to->lt($transaction->accounting_date))))) {
                throw new FinancialPostingException('E-MASTER-INACTIVE', 'A journal line uses an inactive or non-posting account.');
            }
            if (! $isReversal && $account->is_liquidity_account && (! $line['financial_account_id'] || ! $line['fund_id'])) {
                throw new FinancialPostingException('E-LIQUIDITY-DIMENSION', 'A liquidity account requires Financial Account and Fund dimensions.');
            }
            if ($line['financial_account_id']) {
                $financialAccount = FinancialAccount::query()->findOrFail($line['financial_account_id']);
                if ($financialAccount->accounting_entity_id !== $transaction->accounting_entity_id || $financialAccount->account_id !== $account->id || (! $isReversal && ! $financialAccount->isUsableOn($transaction->accounting_date->toDateString()))) {
                    throw new FinancialPostingException('E-FINANCIAL-ACCOUNT', 'Financial Account is not valid for this liquidity line.');
                }
                if (! $isReversal && ! $this->hasCompatibleFinancialAccountDetail($financialAccount)) {
                    throw new FinancialPostingException('E-FINANCIAL-ACCOUNT-DETAIL', 'Financial Account lacks its required compatible custody detail.');
                }
            }
            $this->validateOptionalDimensions($transaction, $line, $isReversal);
            foreach ($isReversal ? collect() : AccountDimensionRule::query()->where('account_id', $account->id)->where('effective_from', '<=', $transaction->accounting_date)->where(fn ($query) => $query->whereNull('effective_to')->orWhere('effective_to', '>=', $transaction->accounting_date))->get() as $rule) {
                if (! ($rule->applies_to_debit && DecimalAmount::compare($line['debit_amount'], '0.00') > 0)
                    && ! ($rule->applies_to_credit && DecimalAmount::compare($line['credit_amount'], '0.00') > 0)) {
                    continue;
                }
                $value = $line[$rule->dimension_name.'_id'];
                if ($rule->requirement === 'required' && ! $value) {
                    throw new FinancialPostingException('E-DIMENSION-REQUIRED', "{$rule->dimension_name} is required.");
                }
                if ($rule->requirement === 'forbidden' && $value) {
                    throw new FinancialPostingException('E-DIMENSION-FORBIDDEN', "{$rule->dimension_name} is forbidden.");
                }
            }
            if ($line['fund_id'] && ! $isReversal) {
                $policy = $this->validateFund($transaction, $line);
                if ($policy) {
                    $line['policy_version_ref'] = $policy->id;
                }
            }
            $lines[$index] = $line;
        }

        return $lines;
    }

    /** @param array<string, mixed> $line */
    private function validateFund(FinancialTransaction $transaction, array $line): ?FundPolicyVersion
    {
        $fund = Fund::query()->with('type')->findOrFail($line['fund_id']);
        if ($fund->accounting_entity_id !== $transaction->accounting_entity_id || $fund->status !== 'active' || ($fund->valid_from && $fund->valid_from->gt($transaction->accounting_date)) || ($fund->valid_to && $fund->valid_to->lt($transaction->accounting_date))) {
            throw new FinancialPostingException('E-MASTER-INACTIVE', 'Fund is inactive or outside the accounting entity.');
        }

        if (! in_array($fund->type->classification, ['restricted', 'perpetual_restricted', 'custodial', 'syariah'], true)) {
            return null;
        }

        $policy = FundPolicyVersion::query()->where('fund_id', $fund->id)->where('status', 'effective')->where('effective_from', '<=', $transaction->accounting_date)->where(fn ($query) => $query->whereNull('effective_to')->orWhere('effective_to', '>=', $transaction->accounting_date))->orderByDesc('effective_from')->first();
        $matchingRules = $policy ? FundPolicyRule::query()
            ->where('fund_policy_version_id', $policy->id)
            ->where('transaction_type_id', $transaction->transaction_type_id)
            ->where(fn ($query) => $query->whereNull('account_id')->orWhere('account_id', $line['account_id']))
            ->where(fn ($query) => $query->whereNull('category_id')->orWhere('category_id', $line['category_id']))
            ->where(fn ($query) => $query->whereNull('program_id')->orWhere('program_id', $line['program_id']))
            ->where(fn ($query) => $query->whereNull('cost_center_id')->orWhere('cost_center_id', $line['cost_center_id']))
            ->pluck('decision') : collect();
        if ($matchingRules->contains('prohibited') || ! $matchingRules->contains('allowed')) {
            throw new FinancialPostingException('E-FUND-RESTRICTED', 'Restricted Fund is fail-closed without an allowed policy matrix rule.');
        }

        return $policy;
    }

    private function hasCompatibleFinancialAccountDetail(FinancialAccount $financialAccount): bool
    {
        $hasBankDetail = BankAccountDetail::query()->where('financial_account_id', $financialAccount->id)->exists();
        $hasCashDetail = CashAccountDetail::query()->where('financial_account_id', $financialAccount->id)->exists();

        return match ($financialAccount->account_type) {
            'bank' => $hasBankDetail && ! $hasCashDetail,
            'cash', 'petty_cash' => $hasCashDetail && ! $hasBankDetail,
            'e_wallet' => ! $hasBankDetail && ! $hasCashDetail,
            default => false,
        };
    }

    /** @param array<string, mixed> $line */
    private function validateOptionalDimensions(FinancialTransaction $transaction, array $line, bool $isReversal): void
    {
        if ($isReversal) {
            return;
        }
        foreach ([
            'program_id' => 'financial_v2_programs',
            'cost_center_id' => 'financial_v2_cost_centers',
            'counterparty_id' => 'financial_v2_counterparties',
            'category_id' => 'financial_v2_categories',
        ] as $field => $table) {
            if (! $line[$field]) {
                continue;
            }
            $dimension = DB::table($table)->where('id', $line[$field])->first();
            [$effectiveFrom, $effectiveTo] = $field === 'program_id'
                ? ['start_date', 'end_date']
                : ['valid_from', 'valid_to'];
            if (! $dimension || $dimension->accounting_entity_id !== $transaction->accounting_entity_id || $dimension->status !== 'active' || ($dimension->{$effectiveFrom} && $dimension->{$effectiveFrom} > $transaction->accounting_date->toDateString()) || ($dimension->{$effectiveTo} && $dimension->{$effectiveTo} < $transaction->accounting_date->toDateString())) {
                throw new FinancialPostingException('E-MASTER-INACTIVE', "{$field} is inactive or outside the accounting entity.");
            }
            if ($field === 'category_id' && $dimension->transaction_type_id && $dimension->transaction_type_id !== $transaction->transaction_type_id) {
                throw new FinancialPostingException('E-CATEGORY-TYPE', 'Category is not eligible for this transaction type.');
            }
        }
    }

    /** @param array<int, array<string, mixed>> $lines */
    private function validateOperationalLines(FinancialTransaction $transaction, array $lines): void
    {
        if ($transaction->type?->code === TransactionTypeCode::TreasuryTransfer->value) {
            $transfer = $transaction->treasuryTransfer;
            $sourceTotals = [];
            $destinationTotals = [];
            foreach ($lines as $line) {
                $account = Account::query()->findOrFail($line['account_id']);
                if (! $account->is_liquidity_account || $account->account_class !== 'asset' || ! $line['fund_id']) {
                    throw new FinancialPostingException('E-TRANSFER-ACCOUNTING', 'Treasury Transfer may only post asset liquidity accounts with a Fund dimension.');
                }
                if ($line['financial_account_id'] === $transfer->source_financial_account_id && DecimalAmount::compare($line['credit_amount'], '0.00') > 0) {
                    $sourceTotals[$line['fund_id']] = DecimalAmount::add($sourceTotals[$line['fund_id']] ?? '0.00', $line['credit_amount']);

                    continue;
                }
                if ($line['financial_account_id'] === $transfer->destination_financial_account_id && DecimalAmount::compare($line['debit_amount'], '0.00') > 0) {
                    $destinationTotals[$line['fund_id']] = DecimalAmount::add($destinationTotals[$line['fund_id']] ?? '0.00', $line['debit_amount']);

                    continue;
                }
                throw new FinancialPostingException('E-TRANSFER-ACCOUNTING', 'Treasury Transfer must debit the destination and credit the source Financial Account.');
            }
            if (count($sourceTotals) === 0 || array_keys($sourceTotals) !== array_keys($destinationTotals)) {
                throw new FinancialPostingException('E-TRANSFER-FUND-COMPOSITION', 'Treasury Transfer must preserve each Fund composition.');
            }
            foreach ($sourceTotals as $fundId => $sourceAmount) {
                if (! DecimalAmount::equals($sourceAmount, $destinationTotals[$fundId])) {
                    throw new FinancialPostingException('E-TRANSFER-FUND-COMPOSITION', 'Treasury Transfer Fund source and destination amounts must match.');
                }
            }
        }

        if ($transaction->type?->code === TransactionTypeCode::InterfundTransfer->value) {
            $transfer = $transaction->interfundTransfer;
            $sourceCredit = '0.00';
            $destinationDebit = '0.00';
            foreach ($lines as $line) {
                $account = Account::query()->findOrFail($line['account_id']);
                if ($account->account_class !== 'transfer' || $line['financial_account_id']) {
                    throw new FinancialPostingException('E-INTERFUND-ACCOUNTING', 'Interfund Transfer must use configured transfer accounts and must not move a Financial Account.');
                }
                if ($line['fund_id'] === $transfer->source_fund_id && DecimalAmount::compare($line['credit_amount'], '0.00') > 0) {
                    $sourceCredit = DecimalAmount::add($sourceCredit, $line['credit_amount']);

                    continue;
                }
                if ($line['fund_id'] === $transfer->destination_fund_id && DecimalAmount::compare($line['debit_amount'], '0.00') > 0) {
                    $destinationDebit = DecimalAmount::add($destinationDebit, $line['debit_amount']);

                    continue;
                }
                throw new FinancialPostingException('E-INTERFUND-ACCOUNTING', 'Interfund Transfer must debit destination Fund transfer-in and credit source Fund transfer-out.');
            }
            if (! DecimalAmount::equals($sourceCredit, $transaction->gross_amount) || ! DecimalAmount::equals($destinationDebit, $transaction->gross_amount)) {
                throw new FinancialPostingException('E-INTERFUND-ACCOUNTING', 'Interfund Transfer must preserve a complete source/destination Fund transfer pair.');
            }
        }
    }

    private function validateFundRealization(FinancialTransaction $transaction): void
    {
        if (! $transaction->realization) {
            return;
        }
        if ($transaction->type?->code !== TransactionTypeCode::Payment->value || $transaction->realization->status !== 'draft' || ! $transaction->realization->budget_allocation_version_id) {
            throw new FinancialPostingException('E-REALIZATION-STATE', 'Fund Realization must be a draft link to a Payment and approved Budget Allocation Version.');
        }
        $version = BudgetAllocationVersion::query()->with('allocation')->lockForUpdate()->find($transaction->realization->budget_allocation_version_id);
        if (! $version || ! $version->allocation || $version->status !== 'approved' || $version->allocation->status !== 'approved'
            || $version->accounting_entity_id !== $transaction->accounting_entity_id
            || $version->effective_from->gt($transaction->accounting_date)
            || ($version->effective_to && $version->effective_to->lt($transaction->accounting_date))) {
            throw new FinancialPostingException('E-REALIZATION-ALLOCATION', 'Fund Realization Budget Allocation Version is not effective for this posting date.');
        }
        // This is a locking/current read. The source transaction was eagerly
        // loaded before the allocation-version lock, so an aggregate snapshot
        // could otherwise omit a realization committed while waiting.
        $actual = DecimalAmount::sum(DB::table('financial_v2_fund_realizations as realization')
            ->join('financial_v2_transactions as source_transaction', 'source_transaction.id', '=', 'realization.transaction_id')
            ->join('financial_v2_journals as journal', 'journal.transaction_id', '=', 'source_transaction.id')
            ->where('realization.budget_allocation_version_id', $version->id)
            ->where('realization.status', 'recorded')
            ->where('journal.journal_status', 'posted')
            ->lockForUpdate()
            ->get(['source_transaction.gross_amount'])
            ->pluck('gross_amount'));
        if (DecimalAmount::compare(DecimalAmount::add($actual, $transaction->gross_amount), $version->allocated_amount) > 0) {
            throw new FinancialPostingException('E-BUDGET-INSUFFICIENT', 'Fund Realization exceeds the approved available Budget Allocation.');
        }
    }

    /** @param array<int, array<string, mixed>> $lines */
    private function validateFundLiquidityBalances(FinancialTransaction $transaction, array $lines): void
    {
        $this->validateInterfundAttributionBalance($transaction);

        $proposedEffects = [];
        foreach ($lines as $line) {
            if (! $line['fund_id'] || ! $line['financial_account_id']) {
                continue;
            }
            $account = Account::query()->findOrFail($line['account_id']);
            if (! $account->is_liquidity_account) {
                continue;
            }
            $key = implode('|', [$line['fund_id'], $line['financial_account_id'], $line['account_id']]);
            $proposedEffects[$key] ??= ['fund_id' => $line['fund_id'], 'financial_account_id' => $line['financial_account_id'], 'account_id' => $line['account_id'], 'amount' => '0.00'];
            $signedAmount = $account->normal_balance === 'debit'
                ? DecimalAmount::subtract($line['debit_amount'], $line['credit_amount'])
                : DecimalAmount::subtract($line['credit_amount'], $line['debit_amount']);
            $proposedEffects[$key]['amount'] = DecimalAmount::add($proposedEffects[$key]['amount'], $signedAmount);
        }

        foreach ($proposedEffects as $effect) {
            $fund = Fund::query()->findOrFail($effect['fund_id']);
            if ($fund->allow_negative_balance) {
                continue;
            }
            if (DecimalAmount::compare($effect['amount'], '0.00') < 0
                && $this->fundFinancialAccounts->hasActivityAfter(
                    $transaction->accounting_entity_id,
                    $effect['fund_id'],
                    $effect['financial_account_id'],
                    $transaction->accounting_date->toDateString(),
                )) {
                throw new FinancialPostingException('E-BACKDATED-LIQUIDITY', 'A backdated liquidity reduction is blocked because later posted activity exists for the same Fund and Financial Account.');
            }
            // Use the same posted attribution projection as reporting. Raw
            // liquidity Ledger rows alone would continue treating reclassified
            // cash as owned by the old Fund and authorize the wrong payer.
            $current = $this->fundFinancialAccounts->currentBalance(
                $transaction->accounting_entity_id,
                $effect['fund_id'],
                $effect['financial_account_id'],
                $transaction->accounting_date->toDateString(),
            );
            $minimum = DecimalAmount::normalize($fund->minimum_balance_policy ?? 0);
            if (DecimalAmount::compare(DecimalAmount::add($current, $effect['amount']), $minimum) < 0) {
                throw new FinancialPostingException('E-FUND-INSUFFICIENT', 'Proposed posting breaches the Fund liquidity balance policy.');
            }
        }
    }

    private function validateInterfundAttributionBalance(FinancialTransaction $transaction): void
    {
        $fundId = null;
        $financialAccountId = null;
        $amount = null;

        if ($transaction->type?->code === TransactionTypeCode::InterfundTransfer->value
            && $transaction->primary_financial_account_id
            && $transaction->interfundTransfer) {
            $fundId = $transaction->interfundTransfer->source_fund_id;
            $financialAccountId = $transaction->primary_financial_account_id;
            $amount = $transaction->gross_amount;
        } elseif ($transaction->type?->code === 'REV' && $transaction->related_transaction_id) {
            $original = FinancialTransaction::query()
                ->with(['type', 'interfundTransfer'])
                ->where('accounting_entity_id', $transaction->accounting_entity_id)
                ->lockForUpdate()
                ->find($transaction->related_transaction_id);
            if ($original?->type?->code === TransactionTypeCode::InterfundTransfer->value
                && $original->primary_financial_account_id
                && $original->interfundTransfer) {
                // Reversing the IFT removes attribution from its original
                // destination Fund, so that Fund/account pair is the side
                // whose available liquidity must be protected.
                $fundId = $original->interfundTransfer->destination_fund_id;
                $financialAccountId = $original->primary_financial_account_id;
                $amount = $original->gross_amount;
            }
        }

        if (! $fundId || ! $financialAccountId || $amount === null) {
            return;
        }

        $sourceFund = Fund::query()->findOrFail($fundId);
        if ($sourceFund->allow_negative_balance) {
            return;
        }

        if ($this->fundFinancialAccounts->hasActivityAfter(
            $transaction->accounting_entity_id,
            $sourceFund->id,
            $financialAccountId,
            $transaction->accounting_date->toDateString(),
        )) {
            throw new FinancialPostingException('E-BACKDATED-LIQUIDITY', 'A backdated Fund attribution reduction is blocked because later posted activity exists for the same Fund and Financial Account.');
        }

        // primary_financial_account_id is attribution context for IFT only.
        // It never flows into the IFT JournalLines and therefore never moves
        // custody; it identifies which existing liquidity ownership is moved.
        $current = $this->fundFinancialAccounts->currentBalance(
            $transaction->accounting_entity_id,
            $sourceFund->id,
            $financialAccountId,
            $transaction->accounting_date->toDateString(),
        );
        $minimum = DecimalAmount::normalize($sourceFund->minimum_balance_policy ?? 0);
        if (DecimalAmount::compare(DecimalAmount::subtract($current, $amount), $minimum) < 0) {
            throw new FinancialPostingException('E-FUND-INSUFFICIENT', 'Interfund attribution or its reversal exceeds the Fund liquidity held in that Financial Account.');
        }
    }

    private function validateOpeningBalanceBatch(OpeningBalanceBatch $batch): void
    {
        if ($batch->status !== 'approved' || ! $batch->approved_at || blank($batch->evidence_package_ref) || $batch->lines->isEmpty()) {
            throw new FinancialPostingException('E-OPENING-NOT-APPROVED', 'Opening balance requires an approved batch, evidence package, and lines.');
        }
        if (! $batch->mappingSet || ! in_array($batch->mappingSet->mapping_status, ['approved', 'frozen'], true)) {
            throw new FinancialPostingException('E-OPENING-MAPPING', 'Opening balance mapping must be approved before posting.');
        }
        foreach ($batch->lines as $line) {
            if (blank($line->evidence_ref) || blank($line->mapping_ref) || blank($line->source_reference)) {
                throw new FinancialPostingException('E-OPENING-EVIDENCE', 'Every opening balance line requires source, evidence, and mapping traceability.');
            }
            if (! AttachmentLink::query()->where('accounting_entity_id', $batch->accounting_entity_id)->where('target_type', 'opening_balance_line')->where('target_id', $line->id)->where('status', 'active')->exists()) {
                throw new FinancialPostingException('E-OPENING-EVIDENCE', 'Every opening balance line requires active attached evidence.');
            }
            $sourceNet = DecimalAmount::subtract($line->source_debit_amount, $line->source_credit_amount);
            $v2Net = DecimalAmount::subtract($line->debit_amount, $line->credit_amount);
            if (! DecimalAmount::equals($sourceNet, $v2Net) || $line->reconciliation_status !== 'reconciled' || ! DecimalAmount::equals($line->reconciliation_difference, '0.00')) {
                throw new FinancialPostingException('E-OPENING-RECONCILIATION', 'Opening balance source positions must reconcile exactly before posting.');
            }
            $dimensions = [
                'account' => $line->account_id,
                'financial_account' => $line->financial_account_id,
                'fund' => $line->fund_id,
                'program' => $line->program_id,
            ];
            foreach ($dimensions as $dimension => $targetId) {
                if ($targetId === null) {
                    continue;
                }
                if (! LegacyMapping::query()->where('mapping_set_id', $batch->mapping_set_id)->where('legacy_record_ref', $line->source_reference.'|'.$dimension)->where('target_entity_type', $dimension)->where('target_entity_id', $targetId)->whereIn('mapping_status', ['confirmed', 'frozen'])->exists()) {
                    throw new FinancialPostingException('E-OPENING-MAPPING', "Opening balance {$dimension} mapping is unresolved.");
                }
            }
        }
        if (! DecimalAmount::equals(DecimalAmount::sum($batch->lines->pluck('debit_amount')), DecimalAmount::sum($batch->lines->pluck('credit_amount')))) {
            throw new FinancialPostingException('E-JOURNAL-UNBALANCED', 'Opening balance batch must balance before posting.');
        }
    }

    /** @param array<int, array<string, mixed>> $lines */
    private function commitPosting(FinancialTransaction $transaction, IdempotencyKey $key, PostingRuleVersion $version, AccountingPeriod $period, array $lines, ?int $actorUserId, ?Journal $reversalOf = null): PostingResult
    {
        return FinancialFactWriteGuard::withinPosting(function () use ($transaction, $key, $version, $period, $lines, $actorUserId, $reversalOf): PostingResult {
            return FinancialTransactionStateGuard::withinLifecycle(function () use ($transaction, $key, $version, $period, $lines, $actorUserId, $reversalOf): PostingResult {
                $attempt = PostingAttempt::create([
                    'accounting_entity_id' => $transaction->accounting_entity_id,
                    'transaction_id' => $transaction->id,
                    'idempotency_record_id' => $key->id,
                    'status' => 'validated',
                    'attempt_no' => (int) PostingAttempt::query()->where('transaction_id', $transaction->id)->max('attempt_no') + 1,
                    'requested_at' => now(),
                    'correlation_id' => $transaction->correlation_id,
                    'created_by_user_id' => $actorUserId,
                    'updated_by_user_id' => $actorUserId,
                ]);
                // The transaction eagerly loads its source before it takes the
                // entity lock. Under MySQL REPEATABLE READ, a normal aggregate
                // read could therefore use that earlier snapshot after waiting
                // for a concurrent posting. Use a locking/current read while
                // the entity row remains locked, so this entity-local sequence
                // always advances from the latest committed Journal.
                $sequence = (int) (Journal::query()
                    ->forEntity($transaction->accounting_entity_id)
                    ->orderByDesc('posting_sequence')
                    ->lockForUpdate()
                    ->value('posting_sequence') ?? 0) + 1;
                $journal = Journal::create([
                    'accounting_entity_id' => $transaction->accounting_entity_id,
                    'transaction_id' => $transaction->id,
                    'posting_attempt_id' => $attempt->id,
                    'posting_rule_version_id' => $version->id,
                    'accounting_period_id' => $period->id,
                    'business_date' => $transaction->business_date,
                    'accounting_date' => $transaction->accounting_date,
                    'description' => $transaction->description,
                    'journal_status' => 'posting',
                    'posting_sequence' => $sequence,
                    'total_debit' => DecimalAmount::sum(array_column($lines, 'debit_amount')),
                    'total_credit' => DecimalAmount::sum(array_column($lines, 'credit_amount')),
                    'reversal_of_journal_id' => $reversalOf?->id,
                    'correlation_id' => $transaction->correlation_id,
                    'created_by_user_id' => $actorUserId,
                    'updated_by_user_id' => $actorUserId,
                ]);
                foreach ($lines as $index => $line) {
                    $line['journal_id'] = $journal->id;
                    $line['line_no'] = $index + 1;
                    $line['created_by_user_id'] = $actorUserId;
                    $line['updated_by_user_id'] = $actorUserId;
                    $journalLine = JournalLine::create($line);
                    $normal = Account::query()->findOrFail($journalLine->account_id)->normal_balance;
                    LedgerEntry::create([
                        'accounting_entity_id' => $transaction->accounting_entity_id,
                        'journal_line_id' => $journalLine->id,
                        'accounting_date' => $transaction->accounting_date,
                        'posting_sequence' => $sequence,
                        'line_no' => $index + 1,
                        'account_id' => $journalLine->account_id,
                        'fund_id' => $journalLine->fund_id,
                        'financial_account_id' => $journalLine->financial_account_id,
                        'program_id' => $journalLine->program_id,
                        'signed_amount' => $normal === 'debit'
                            ? DecimalAmount::subtract($journalLine->debit_amount, $journalLine->credit_amount)
                            : DecimalAmount::subtract($journalLine->credit_amount, $journalLine->debit_amount),
                        'created_at' => now(),
                    ]);
                }
                $voucher = $this->issueVoucher($transaction, $actorUserId);
                $journal->update(['journal_status' => 'posted', 'posted_at' => now(), 'posted_by_user_id' => $actorUserId]);
                $attempt->update(['status' => 'committed', 'journal_id' => $journal->id, 'completed_at' => now()]);
                $transaction->update(['status' => 'posted']);
                $key->update(['status' => 'completed', 'result_reference' => $journal->id]);
                AuditEvent::create([
                    'accounting_entity_id' => $transaction->accounting_entity_id,
                    'event_at' => now(),
                    'event_type' => 'posting_committed',
                    'target_type' => 'journal',
                    'target_id' => $journal->id,
                    'actor_user_id' => $actorUserId,
                    'correlation_id' => $transaction->correlation_id,
                    'after_summary' => json_encode(['voucher_id' => $voucher->id, 'posting_rule_version_id' => $version->id, 'reversal_of_journal_id' => $reversalOf?->id]),
                    'created_at' => now(),
                ]);

                if ($transaction->realization) {
                    $transaction->realization->update(['status' => 'recorded', 'recorded_at' => now(), 'updated_by_user_id' => $actorUserId]);
                    AuditEvent::create([
                        'accounting_entity_id' => $transaction->accounting_entity_id,
                        'event_at' => now(),
                        'event_type' => 'fund_realization_recorded',
                        'target_type' => 'fund_realization',
                        'target_id' => $transaction->realization->id,
                        'actor_user_id' => $actorUserId,
                        'correlation_id' => $transaction->correlation_id,
                        'after_summary' => json_encode(['transaction_id' => $transaction->id]),
                        'created_at' => now(),
                    ]);
                }
                if ($reversalOf) {
                    $originalTransaction = FinancialTransaction::query()->lockForUpdate()->findOrFail($reversalOf->transaction_id);
                    $originalTransaction->update(['status' => 'reversed', 'updated_by_user_id' => $actorUserId]);
                    $originalRealization = FundRealization::query()->where('transaction_id', $originalTransaction->id)->lockForUpdate()->first();
                    if ($originalRealization?->status === 'recorded') {
                        $originalRealization->update(['status' => 'reversed', 'reversed_at' => now(), 'updated_by_user_id' => $actorUserId]);
                    }
                    AuditEvent::create([
                        'accounting_entity_id' => $transaction->accounting_entity_id,
                        'event_at' => now(),
                        'event_type' => 'reversal_committed',
                        'target_type' => 'journal',
                        'target_id' => $journal->id,
                        'actor_user_id' => $actorUserId,
                        'correlation_id' => $transaction->correlation_id,
                        'after_summary' => json_encode(['reversal_of_journal_id' => $reversalOf->id, 'original_transaction_id' => $originalTransaction->id]),
                        'created_at' => now(),
                    ]);
                }

                return new PostingResult($transaction->id, $journal->id, $voucher->id);
            });
        });
    }

    private function issueVoucher(FinancialTransaction $transaction, ?int $actorUserId): Voucher
    {
        $sequence = DocumentSequence::query()->where('accounting_entity_id', $transaction->accounting_entity_id)->where('transaction_type_id', $transaction->transaction_type_id)->where('status', 'active')->lockForUpdate()->first();
        if (! $sequence) {
            throw new FinancialPostingException('E-VOUCHER-CONFLICT', 'No active document sequence exists for this transaction type.');
        }

        $number = $sequence->prefix.'-'.str_pad((string) $sequence->next_value, 8, '0', STR_PAD_LEFT);
        $sequence->increment('next_value');

        return Voucher::create([
            'accounting_entity_id' => $transaction->accounting_entity_id,
            'transaction_id' => $transaction->id,
            'document_sequence_id' => $sequence->id,
            'voucher_number' => $number,
            'status' => 'issued',
            'issued_at' => now(),
            'created_by_user_id' => $actorUserId,
            'updated_by_user_id' => $actorUserId,
        ]);
    }
}
