<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\AccountingPeriod;
use App\Models\FinancialV2\AttachmentLink;
use App\Models\FinancialV2\FinancialAccount;
use App\Models\FinancialV2\Reconciliation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Controls reconciliation as an independently-audited comparison of an
 * external statement/cash count and immutable posted V2 Ledger facts.
 */
final class ReconciliationService
{
    public function __construct(
        private readonly AuditTrailService $auditTrail,
        private readonly BalanceInquiryService $balances,
    ) {}

    /** @param array{accounting_entity_id: string, financial_account_id: string, accounting_period_id: string, as_of_date: string, statement_balance: int|string, notes?: string|null} $input */
    public function createDraft(array $input, ?int $actorUserId = null): Reconciliation
    {
        foreach (['accounting_entity_id', 'financial_account_id', 'accounting_period_id', 'as_of_date', 'statement_balance'] as $field) {
            if (! array_key_exists($field, $input) || blank($input[$field])) {
                throw new FinancialDomainException('E-RECONCILIATION-INPUT', "{$field} is required.");
            }
        }

        return DB::transaction(function () use ($input, $actorUserId): Reconciliation {
            $period = AccountingPeriod::query()
                ->where('accounting_entity_id', $input['accounting_entity_id'])
                ->lockForUpdate()
                ->findOrFail($input['accounting_period_id']);
            $this->assertPeriodSupportsReconciliation($period);
            $this->assertAsOfDateInPeriod($period, $input['as_of_date']);

            $account = FinancialAccount::query()
                ->where('accounting_entity_id', $period->accounting_entity_id)
                ->lockForUpdate()
                ->findOrFail($input['financial_account_id']);
            $this->assertReconciliableAccount($account);
            if (Reconciliation::query()->where('financial_account_id', $account->id)->where('accounting_period_id', $period->id)->exists()) {
                throw new FinancialDomainException('E-RECONCILIATION-EXISTS', 'Only one reconciliation may exist for a Financial Account and Accounting Period.');
            }

            $snapshot = $this->bookSnapshot($period->accounting_entity_id, $account->id, $input['as_of_date']);
            $statementBalance = DecimalAmount::normalize($input['statement_balance']);
            $reconciliation = ReconciliationStateGuard::withinReconciliation(fn () => Reconciliation::create([
                'accounting_entity_id' => $period->accounting_entity_id,
                'financial_account_id' => $account->id,
                'accounting_period_id' => $period->id,
                // Existing Foundation names: both record the external as-of date.
                'business_date' => $input['as_of_date'],
                'accounting_date' => $input['as_of_date'],
                'statement_balance' => $statementBalance,
                'ledger_balance' => $snapshot['balance'],
                'difference' => DecimalAmount::subtract($statementBalance, $snapshot['balance']),
                'notes' => $input['notes'] ?? null,
                'status' => 'draft',
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]));
            $this->auditTrail->record($period->accounting_entity_id, 'reconciliation_drafted', 'reconciliation', $reconciliation->id, (string) Str::uuid(), $actorUserId, null, $this->auditSummary($reconciliation, $snapshot));

            return $reconciliation;
        }, 3);
    }

    /** @param array{as_of_date?: string, statement_balance?: int|string, notes?: string|null} $changes */
    public function updateDraft(string $reconciliationId, array $changes, ?int $actorUserId = null): Reconciliation
    {
        return DB::transaction(function () use ($reconciliationId, $changes, $actorUserId): Reconciliation {
            $reconciliation = Reconciliation::query()->with(['period', 'financialAccount'])->lockForUpdate()->findOrFail($reconciliationId);
            if ($reconciliation->status !== 'draft') {
                throw new FinancialDomainException('E-RECONCILIATION-STATE', 'Only Draft reconciliations may be edited.');
            }
            $this->assertPeriodSupportsReconciliation($reconciliation->period);
            $asOfDate = $changes['as_of_date'] ?? $reconciliation->business_date->toDateString();
            $this->assertAsOfDateInPeriod($reconciliation->period, $asOfDate);
            $statementBalance = DecimalAmount::normalize($changes['statement_balance'] ?? $reconciliation->statement_balance);
            $snapshot = $this->bookSnapshot($reconciliation->accounting_entity_id, $reconciliation->financial_account_id, $asOfDate);
            $before = $this->auditSummary($reconciliation, ['through_posting_sequence' => null]);
            ReconciliationStateGuard::withinReconciliation(fn () => $reconciliation->update([
                'business_date' => $asOfDate,
                'accounting_date' => $asOfDate,
                'statement_balance' => $statementBalance,
                'ledger_balance' => $snapshot['balance'],
                'difference' => DecimalAmount::subtract($statementBalance, $snapshot['balance']),
                'notes' => array_key_exists('notes', $changes) ? $changes['notes'] : $reconciliation->notes,
                'updated_by_user_id' => $actorUserId,
            ]));
            $this->auditTrail->record($reconciliation->accounting_entity_id, 'reconciliation_draft_updated', 'reconciliation', $reconciliation->id, (string) Str::uuid(), $actorUserId, $before, $this->auditSummary($reconciliation->fresh(), $snapshot));

            return $reconciliation->fresh();
        }, 3);
    }

    public function startReview(string $reconciliationId, ?int $actorUserId = null): Reconciliation
    {
        return $this->transition($reconciliationId, 'draft', 'in_progress', 'reconciliation_review_started', $actorUserId);
    }

    public function review(string $reconciliationId, ?int $actorUserId = null): Reconciliation
    {
        return DB::transaction(function () use ($reconciliationId, $actorUserId): Reconciliation {
            $reconciliation = Reconciliation::query()->with('period')->lockForUpdate()->findOrFail($reconciliationId);
            if ($reconciliation->status !== 'in_progress') {
                throw new FinancialDomainException('E-RECONCILIATION-STATE', 'Only In Progress reconciliations may be reviewed.');
            }
            $this->assertPeriodSupportsReconciliation($reconciliation->period);
            $snapshot = $this->bookSnapshot($reconciliation->accounting_entity_id, $reconciliation->financial_account_id, $reconciliation->business_date->toDateString());
            $before = $this->auditSummary($reconciliation, ['through_posting_sequence' => null]);
            ReconciliationStateGuard::withinReconciliation(fn () => $reconciliation->update([
                'ledger_balance' => $snapshot['balance'],
                'difference' => DecimalAmount::subtract($reconciliation->statement_balance, $snapshot['balance']),
                'status' => 'reviewed',
                'reviewed_at' => now(),
                'reviewed_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]));
            $this->auditTrail->record($reconciliation->accounting_entity_id, 'reconciliation_reviewed', 'reconciliation', $reconciliation->id, (string) Str::uuid(), $actorUserId, $before, $this->auditSummary($reconciliation->fresh(), $snapshot));

            return $reconciliation->fresh();
        }, 3);
    }

    public function complete(string $reconciliationId, ?int $actorUserId = null): Reconciliation
    {
        return DB::transaction(function () use ($reconciliationId, $actorUserId): Reconciliation {
            $reconciliation = Reconciliation::query()->with(['period', 'financialAccount'])->lockForUpdate()->findOrFail($reconciliationId);
            if ($reconciliation->status !== 'reviewed') {
                throw new FinancialDomainException('E-RECONCILIATION-STATE', 'Only Reviewed reconciliations may be completed.');
            }
            $this->assertPeriodSupportsReconciliation($reconciliation->period);
            $snapshot = $this->bookSnapshot($reconciliation->accounting_entity_id, $reconciliation->financial_account_id, $reconciliation->business_date->toDateString());
            $difference = DecimalAmount::subtract($reconciliation->statement_balance, $snapshot['balance']);
            if (! DecimalAmount::equals($difference, '0.00')) {
                throw new FinancialDomainException('E-RECONCILIATION-DIFFERENCE', 'A reconciliation with a non-zero difference cannot be completed; record an Exception for governed resolution.');
            }
            $this->assertRequiredEvidence($reconciliation);
            $before = $this->auditSummary($reconciliation, ['through_posting_sequence' => null]);
            ReconciliationStateGuard::withinReconciliation(fn () => $reconciliation->update([
                'ledger_balance' => $snapshot['balance'],
                'difference' => $difference,
                'status' => 'completed',
                'reconciled_at' => now(),
                'reconciled_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]));
            $this->auditTrail->record($reconciliation->accounting_entity_id, 'reconciliation_completed', 'reconciliation', $reconciliation->id, (string) Str::uuid(), $actorUserId, $before, $this->auditSummary($reconciliation->fresh(), $snapshot));

            return $reconciliation->fresh();
        }, 3);
    }

    public function markException(string $reconciliationId, string $reason, ?int $actorUserId = null): Reconciliation
    {
        if (blank($reason)) {
            throw new FinancialDomainException('E-RECONCILIATION-REASON', 'An Exception reconciliation requires a documented reason.');
        }

        return DB::transaction(function () use ($reconciliationId, $reason, $actorUserId): Reconciliation {
            $reconciliation = Reconciliation::query()->with('period')->lockForUpdate()->findOrFail($reconciliationId);
            if (! in_array($reconciliation->status, ['draft', 'in_progress', 'reviewed'], true)) {
                throw new FinancialDomainException('E-RECONCILIATION-STATE', 'Only unfinished reconciliations may be marked Exception.');
            }
            $this->assertPeriodSupportsReconciliation($reconciliation->period);
            $snapshot = $this->bookSnapshot($reconciliation->accounting_entity_id, $reconciliation->financial_account_id, $reconciliation->business_date->toDateString());
            $difference = DecimalAmount::subtract($reconciliation->statement_balance, $snapshot['balance']);
            if (DecimalAmount::equals($difference, '0.00')) {
                throw new FinancialDomainException('E-RECONCILIATION-EXCEPTION', 'A zero-difference reconciliation must be completed, not marked Exception.');
            }
            $before = $this->auditSummary($reconciliation, ['through_posting_sequence' => null]);
            $notes = trim((string) $reconciliation->notes);
            ReconciliationStateGuard::withinReconciliation(fn () => $reconciliation->update([
                'ledger_balance' => $snapshot['balance'],
                'difference' => $difference,
                'notes' => $notes === '' ? $reason : $notes."\n\nException: {$reason}",
                'status' => 'exception',
                'updated_by_user_id' => $actorUserId,
            ]));
            $this->auditTrail->record($reconciliation->accounting_entity_id, 'reconciliation_exception_recorded', 'reconciliation', $reconciliation->id, (string) Str::uuid(), $actorUserId, $before, $this->auditSummary($reconciliation->fresh(), $snapshot));

            return $reconciliation->fresh();
        }, 3);
    }

    private function transition(string $reconciliationId, string $from, string $to, string $eventType, ?int $actorUserId): Reconciliation
    {
        return DB::transaction(function () use ($reconciliationId, $from, $to, $eventType, $actorUserId): Reconciliation {
            $reconciliation = Reconciliation::query()->with('period')->lockForUpdate()->findOrFail($reconciliationId);
            if ($reconciliation->status !== $from) {
                throw new FinancialDomainException('E-RECONCILIATION-STATE', "Reconciliation must be {$from} before it can be {$to}.");
            }
            $this->assertPeriodSupportsReconciliation($reconciliation->period);
            $before = ['status' => $reconciliation->status];
            ReconciliationStateGuard::withinReconciliation(fn () => $reconciliation->update(['status' => $to, 'updated_by_user_id' => $actorUserId]));
            $this->auditTrail->record($reconciliation->accounting_entity_id, $eventType, 'reconciliation', $reconciliation->id, (string) Str::uuid(), $actorUserId, $before, ['status' => $to]);

            return $reconciliation->fresh();
        }, 3);
    }

    /** @return array{balance: string, through_posting_sequence: int} */
    private function bookSnapshot(string $entityId, string $financialAccountId, string $asOfDate): array
    {
        $balance = $this->balances->financialAccountBalance($entityId, $financialAccountId, $asOfDate);

        return ['balance' => $balance['balance'], 'through_posting_sequence' => $balance['through_posting_sequence']];
    }

    private function assertPeriodSupportsReconciliation(AccountingPeriod $period): void
    {
        if (! in_array($period->status, ['open', 'soft_closed'], true)) {
            throw new FinancialDomainException('E-RECONCILIATION-PERIOD', 'Reconciliation is available only while the period is Open or Soft Closed.');
        }
    }

    private function assertAsOfDateInPeriod(AccountingPeriod $period, string $asOfDate): void
    {
        if ($asOfDate < $period->start_date->toDateString() || $asOfDate > $period->end_date->toDateString()) {
            throw new FinancialDomainException('E-RECONCILIATION-DATE', 'The statement/cash-count date must be within the Accounting Period.');
        }
    }

    private function assertReconciliableAccount(FinancialAccount $account): void
    {
        if ($account->status !== 'active' || ! in_array($account->account_type, ['bank', 'cash', 'petty_cash'], true)) {
            throw new FinancialDomainException('E-RECONCILIATION-ACCOUNT', 'Only active Bank, Cash, and Petty Cash Financial Accounts are reconcilable.');
        }
    }

    private function assertRequiredEvidence(Reconciliation $reconciliation): void
    {
        $requiredType = $reconciliation->financialAccount->account_type === 'bank' ? 'statement' : 'cash_count';
        if (! AttachmentLink::query()
            ->where('accounting_entity_id', $reconciliation->accounting_entity_id)
            ->where('target_type', 'reconciliation')
            ->where('target_id', $reconciliation->id)
            ->where('evidence_type', $requiredType)
            ->where('status', 'active')
            ->exists()) {
            throw new FinancialDomainException('E-RECONCILIATION-EVIDENCE', "Completed reconciliation requires active {$requiredType} evidence.");
        }
    }

    /** @param array{through_posting_sequence: int|null} $snapshot @return array<string, mixed> */
    private function auditSummary(Reconciliation $reconciliation, array $snapshot): array
    {
        return [
            'status' => $reconciliation->status,
            'financial_account_id' => $reconciliation->financial_account_id,
            'accounting_period_id' => $reconciliation->accounting_period_id,
            'as_of_date' => $reconciliation->business_date->toDateString(),
            'statement_balance' => $reconciliation->statement_balance,
            'ledger_balance' => $reconciliation->ledger_balance,
            'difference' => $reconciliation->difference,
            'through_posting_sequence' => $snapshot['through_posting_sequence'],
        ];
    }
}
