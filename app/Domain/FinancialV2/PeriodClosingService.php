<?php

namespace App\Domain\FinancialV2;

use App\Domain\FinancialV2\Reporting\FinancialReportService;
use App\Models\FinancialV2\AccountingPeriod;
use App\Models\FinancialV2\ClosingRun;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Controlled period-closing boundary.
 *
 * Closing changes only AccountingPeriod and ClosingRun control facts. It never
 * creates, updates, deletes, or rebalances Journal/JournalLine/Ledger facts.
 */
final class PeriodClosingService
{
    private const CHECKLIST_VERSION = 'financial-v2-closing-foundation-v1';

    public function __construct(
        private readonly AuditTrailService $auditTrail,
        private readonly FinancialReportService $reports,
    ) {}

    /**
     * @return array{run: ClosingRun, closed: bool, checks: array<int, array<string, mixed>>}
     */
    public function close(string $periodId, string $runType, ?int $actorUserId = null, ?string $reference = null): array
    {
        if (! in_array($runType, ['soft_close', 'hard_close'], true)) {
            throw new InvalidArgumentException('Closing run type must be soft_close or hard_close.');
        }

        return DB::transaction(function () use ($periodId, $runType, $actorUserId, $reference): array {
            $period = AccountingPeriod::query()->lockForUpdate()->findOrFail($periodId);
            $this->assertTransitionAllowed($period, $runType);

            $run = ClosingRun::query()
                ->where('accounting_period_id', $period->id)
                ->whereIn('status', ['planned', 'in_progress', 'blocked'])
                ->lockForUpdate()
                ->first();
            if (! $run) {
                $run = PeriodClosingStateGuard::withinClosing(fn () => ClosingRun::create([
                    'accounting_entity_id' => $period->accounting_entity_id,
                    'accounting_period_id' => $period->id,
                    'business_date' => $period->end_date->toDateString(),
                    'accounting_date' => $period->end_date->toDateString(),
                    'run_type' => $runType,
                    'status' => 'planned',
                    'checklist_version' => self::CHECKLIST_VERSION,
                    'created_by_user_id' => $actorUserId,
                    'updated_by_user_id' => $actorUserId,
                ]));
            }
            if ($run->run_type !== $runType) {
                throw new FinancialDomainException('E-CLOSING-RUN-ACTIVE', 'An active closing run of another type already exists for this period.');
            }

            PeriodClosingStateGuard::withinClosing(fn () => $run->update(['status' => 'in_progress', 'updated_by_user_id' => $actorUserId]));
            $checks = $this->integrityChecks($period, $runType);
            $summary = ['run_type' => $runType, 'reference' => $reference, 'checks' => $checks];
            $correlationId = (string) Str::uuid();

            if (collect($checks)->contains(fn (array $check) => ! $check['passed'])) {
                PeriodClosingStateGuard::withinClosing(fn () => $run->update([
                    'status' => 'blocked',
                    'result_summary' => json_encode($summary, JSON_THROW_ON_ERROR),
                    'updated_by_user_id' => $actorUserId,
                ]));
                $this->auditTrail->record($period->accounting_entity_id, 'period_closing_blocked', 'closing_run', $run->id, $correlationId, $actorUserId, null, $summary);

                return ['run' => $run->fresh(), 'closed' => false, 'checks' => $checks];
            }

            $before = ['status' => $period->status, 'closed_at' => $period->closed_at?->toIso8601String(), 'closed_by_user_id' => $period->closed_by_user_id];
            PeriodClosingStateGuard::withinClosing(fn () => $period->update([
                'status' => $runType === 'soft_close' ? 'soft_closed' : 'hard_closed',
                'closed_at' => now(),
                'closed_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]));
            PeriodClosingStateGuard::withinClosing(fn () => $run->update([
                'status' => 'completed',
                'result_summary' => json_encode($summary, JSON_THROW_ON_ERROR),
                'completed_at' => now(),
                'completed_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]));
            $this->auditTrail->record($period->accounting_entity_id, 'period_closed', 'accounting_period', $period->id, $correlationId, $actorUserId, $before, ['status' => $period->fresh()->status, 'closing_run_id' => $run->id, 'run_type' => $runType, 'reference' => $reference]);
            $this->auditTrail->record($period->accounting_entity_id, 'closing_completed', 'closing_run', $run->id, $correlationId, $actorUserId, null, $summary);

            return ['run' => $run->fresh(), 'closed' => true, 'checks' => $checks];
        }, 3);
    }

    /** @return array<int, array{code: string, label: string, passed: bool, detail: string}> */
    public function integrityChecks(AccountingPeriod $period, string $runType): array
    {
        $entityId = $period->accounting_entity_id;
        $journalViolations = $this->journalIntegrityViolations($period);
        $missingLedger = $this->missingLedgerCount($period);
        $misalignedLedger = $this->misalignedLedgerCount($period);
        $orphanLedger = $this->orphanLedgerCount($period);
        $unresolvedTransactions = DB::table('financial_v2_transactions')
            ->where('accounting_entity_id', $entityId)
            ->whereBetween('accounting_date', [$period->start_date->toDateString(), $period->end_date->toDateString()])
            ->whereIn('status', ['draft', 'submitted', 'verified', 'approved', 'posting'])
            ->count();
        $missingPostedJournal = DB::table('financial_v2_transactions as financial_transaction')
            ->leftJoin('financial_v2_journals as journal', function (\Illuminate\Database\Query\JoinClause $join): void {
                $join->on('journal.transaction_id', '=', 'financial_transaction.id')->where('journal.journal_status', '=', 'posted');
            })
            ->where('financial_transaction.accounting_entity_id', $entityId)
            ->whereBetween('financial_transaction.accounting_date', [$period->start_date->toDateString(), $period->end_date->toDateString()])
            ->whereIn('financial_transaction.status', ['posted', 'reversed'])
            ->whereNull('journal.id')
            ->count();
        $failedAttempts = DB::table('financial_v2_posting_attempts as attempt')
            ->join('financial_v2_transactions as financial_transaction', 'financial_transaction.id', '=', 'attempt.transaction_id')
            ->where('attempt.accounting_entity_id', $entityId)
            ->whereBetween('financial_transaction.accounting_date', [$period->start_date->toDateString(), $period->end_date->toDateString()])
            ->whereIn('attempt.status', ['failed', 'recovery_required'])
            ->count();
        $trialBalance = $this->reports->report('trial-balance', $entityId, $period->start_date->toDateString(), $period->end_date->toDateString());

        $checks = [
            $this->check('journal_integrity', 'Semua jurnal posted seimbang dan memiliki minimal dua baris.', $journalViolations === 0, $journalViolations.' jurnal tidak memenuhi kontrol balance/line.'),
            $this->check('ledger_integrity', 'Setiap baris jurnal posted memiliki Ledger yang tepat dan tidak ada Ledger orphan.', $missingLedger === 0 && $misalignedLedger === 0 && $orphanLedger === 0, "missing={$missingLedger}; misaligned={$misalignedLedger}; orphan={$orphanLedger}."),
            $this->check('transaction_state', 'Tidak ada transaksi periode yang masih berada pada status posting/pending.', $unresolvedTransactions === 0 && $missingPostedJournal === 0, "pending={$unresolvedTransactions}; posted_without_journal={$missingPostedJournal}."),
            $this->check('posting_recovery', 'Tidak ada posting failure atau recovery yang belum terselesaikan.', $failedAttempts === 0, $failedAttempts.' posting attempt gagal/recovery masih terbuka.'),
            $this->check('trial_balance', 'Trial Balance posted V2 seimbang pada watermark laporan.', $trialBalance['data']['is_balanced'], 'watermark='.$trialBalance['as_of_posting_sequence'].'; debit='.$trialBalance['data']['total_debit'].'; kredit='.$trialBalance['data']['total_credit'].'.'),
        ];

        if ($runType === 'hard_close') {
            $unreconciled = $this->unreconciledExternalAccounts($period);
            $checks[] = $this->check('reconciliation', 'Semua Rekening bank/kas aktif telah direkonsiliasi tanpa selisih.', $unreconciled === 0, $unreconciled.' Rekening bank/kas belum final direkonsiliasi.');
        }

        return $checks;
    }

    private function assertTransitionAllowed(AccountingPeriod $period, string $runType): void
    {
        $expected = $runType === 'soft_close' ? 'open' : 'soft_closed';
        if ($period->status !== $expected) {
            throw new FinancialDomainException('E-CLOSING-PERIOD-STATE', $runType === 'soft_close'
                ? 'Only an Open period can be soft closed.'
                : 'Only a Soft Closed period can be hard closed.');
        }
    }

    private function journalIntegrityViolations(AccountingPeriod $period): int
    {
        return DB::table('financial_v2_journals as journal')
            ->leftJoin('financial_v2_journal_lines as journal_line', 'journal_line.journal_id', '=', 'journal.id')
            ->where('journal.accounting_entity_id', $period->accounting_entity_id)
            ->where('journal.accounting_period_id', $period->id)
            ->where('journal.journal_status', 'posted')
            ->select('journal.id')
            ->groupBy('journal.id', 'journal.total_debit', 'journal.total_credit')
            ->havingRaw('journal.total_debit <> journal.total_credit OR COUNT(journal_line.id) < 2 OR COALESCE(SUM(journal_line.debit_amount), 0) <> journal.total_debit OR COALESCE(SUM(journal_line.credit_amount), 0) <> journal.total_credit')
            ->get()
            ->count();
    }

    private function missingLedgerCount(AccountingPeriod $period): int
    {
        return DB::table('financial_v2_journal_lines as journal_line')
            ->join('financial_v2_journals as journal', 'journal.id', '=', 'journal_line.journal_id')
            ->leftJoin('financial_v2_ledger_entries as ledger', 'ledger.journal_line_id', '=', 'journal_line.id')
            ->where('journal.accounting_entity_id', $period->accounting_entity_id)
            ->where('journal.accounting_period_id', $period->id)
            ->where('journal.journal_status', 'posted')
            ->whereNull('ledger.id')
            ->count();
    }

    private function misalignedLedgerCount(AccountingPeriod $period): int
    {
        return DB::table('financial_v2_ledger_entries as ledger')
            ->join('financial_v2_journal_lines as journal_line', 'journal_line.id', '=', 'ledger.journal_line_id')
            ->join('financial_v2_journals as journal', 'journal.id', '=', 'journal_line.journal_id')
            ->where('journal.accounting_entity_id', $period->accounting_entity_id)
            ->where('journal.accounting_period_id', $period->id)
            ->where('journal.journal_status', 'posted')
            ->where(function (Builder $query): void {
                $query->whereColumn('ledger.accounting_entity_id', '<>', 'journal_line.accounting_entity_id')
                    ->orWhereColumn('ledger.account_id', '<>', 'journal_line.account_id')
                    ->orWhereColumn('ledger.accounting_date', '<>', 'journal.accounting_date')
                    ->orWhereColumn('ledger.posting_sequence', '<>', 'journal.posting_sequence')
                    ->orWhereColumn('ledger.line_no', '<>', 'journal_line.line_no')
                    ->orWhereRaw('NOT (ledger.fund_id <=> journal_line.fund_id)')
                    ->orWhereRaw('NOT (ledger.financial_account_id <=> journal_line.financial_account_id)')
                    ->orWhereRaw('NOT (ledger.program_id <=> journal_line.program_id)');
            })
            ->count();
    }

    private function orphanLedgerCount(AccountingPeriod $period): int
    {
        return DB::table('financial_v2_ledger_entries as ledger')
            ->leftJoin('financial_v2_journal_lines as journal_line', 'journal_line.id', '=', 'ledger.journal_line_id')
            ->leftJoin('financial_v2_journals as journal', 'journal.id', '=', 'journal_line.journal_id')
            ->where('ledger.accounting_entity_id', $period->accounting_entity_id)
            ->whereBetween('ledger.accounting_date', [$period->start_date->toDateString(), $period->end_date->toDateString()])
            ->where(function (Builder $query): void {
                $query->whereNull('journal_line.id')->orWhereNull('journal.id')->orWhere('journal.journal_status', '!=', 'posted');
            })
            ->count();
    }

    private function unreconciledExternalAccounts(AccountingPeriod $period): int
    {
        return DB::table('financial_v2_financial_accounts as financial_account')
            ->leftJoin('financial_v2_reconciliations as reconciliation', function (\Illuminate\Database\Query\JoinClause $join) use ($period): void {
                $join->on('reconciliation.financial_account_id', '=', 'financial_account.id')
                    ->where('reconciliation.accounting_period_id', '=', $period->id)
                    ->where('reconciliation.status', '=', 'completed')
                    ->where('reconciliation.difference', '=', '0.00');
            })
            ->where('financial_account.accounting_entity_id', $period->accounting_entity_id)
            ->where('financial_account.status', 'active')
            ->whereIn('financial_account.account_type', ['bank', 'cash', 'petty_cash'])
            ->whereNull('reconciliation.id')
            ->count();
    }

    /** @return array{code: string, label: string, passed: bool, detail: string} */
    private function check(string $code, string $label, bool $passed, string $detail): array
    {
        return compact('code', 'label', 'passed', 'detail');
    }
}
