<?php

namespace App\Console\Commands;

use App\Domain\FinancialV2\BalanceInquiryService;
use App\Domain\FinancialV2\BudgetAllocationService;
use App\Domain\FinancialV2\Reporting\FinancialReportService;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\JournalLine;
use App\Models\FinancialV2\LedgerEntry;
use App\Models\FinancialV2\Voucher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

/**
 * Runs an actual ten-session rehearsal only on the disposable safeguarded
 * testing database. This command never runs migrations and cannot execute on
 * a production target.
 */
final class FinancialV2ConcurrencyRehearsalCommand extends Command
{
    protected $signature = 'financial-v2:concurrency-rehearsal
        {--workers=10 : Exact concurrent workers per scenario; the approved rehearsal baseline is ten}
        {--format=table : Output format: table or json}';

    protected $description = 'Run the disposable Financial V2 multi-session cutover rehearsal; refused outside testing/mrj_test_db.';

    public function handle(): int
    {
        try {
            $this->assertDisposableTestTarget();
            $workers = (int) $this->option('workers');
            if ($workers !== 10) {
                throw new \InvalidArgumentException('The governed rehearsal baseline requires exactly 10 concurrent workers per scenario.');
            }
            if (! class_exists(\Tests\Support\UatFinancialFixture::class)) {
                throw new \RuntimeException('The disposable UAT fixture is unavailable. Run this command only from the development/test dependency set.');
            }

            /** @var array<string, mixed> $context */
            $context = \Tests\Support\UatFinancialFixture::context();
            $result = $this->runRehearsal($context, $workers);
            $this->render($result);

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $result = ['status' => 'failed', 'message' => $exception->getMessage()];
            $this->render($result);

            return self::FAILURE;
        }
    }

    /** @param array<string, mixed> $context @return array<string, mixed> */
    private function runRehearsal(array $context, int $workers): array
    {
        $receiptPayload = $this->payload($context, 'receipt', '1000.00');
        $receipts = $this->runConcurrent('receipt', $receiptPayload, $workers);
        $this->assertGroup($receipts, $workers, 0, [], 'simultaneous receipt');

        $duplicate = \Tests\Support\UatFinancialFixture::receipt($context, '1000.00');
        \Tests\Support\UatFinancialFixture::advance($duplicate);
        $duplicateKey = 'concurrency-duplicate-'.$duplicate->id;
        $duplicates = $this->runConcurrent('duplicate_post', [
            'scenario' => 'duplicate_post',
            'transaction_id' => $duplicate->id,
            'post_key' => $duplicateKey,
        ], $workers);
        $this->assertGroup($duplicates, $workers, 0, [], 'duplicate post/retry');
        $duplicateJournalIds = collect($duplicates)->pluck('journal_id')->unique();
        $duplicateVoucherIds = collect($duplicates)->pluck('voucher_id')->unique();
        if ($duplicateJournalIds->count() !== 1 || $duplicateVoucherIds->count() !== 1) {
            throw new \RuntimeException('Duplicate post race created more than one official Journal or Voucher.');
        }

        $payments = $this->runConcurrent('payment', $this->payload($context, 'payment', '1500.00'), $workers);
        $this->assertGroup($payments, 7, 3, ['E-FUND-INSUFFICIENT'], 'same Fund/account balance race');

        $transfers = $this->runConcurrent('transfer', $this->payload($context, 'transfer', '25.00'), $workers);
        $this->assertGroup($transfers, $workers, 0, [], 'simultaneous treasury transfer');

        $budget = app(BudgetAllocationService::class);
        $allocation = $budget->create([
            'accounting_entity_id' => $context['entity']->id,
            'accounting_period_id' => $context['period']->id,
            'fund_id' => $context['fund']->id,
            'program_id' => $context['program']->id,
            'account_id' => $context['expense']->id,
            'category_id' => $context['paymentCategory']->id,
            'allocation_reference' => 'CONC-BGT-'.Str::uuid(),
            'idempotency_key' => 'concurrency-budget-'.Str::uuid(),
            'allocated_amount' => '100.00',
            'effective_from' => $context['today'],
            'reason' => 'Disposable multi-session realization rehearsal',
        ]);
        $budget->submit($allocation->id);
        $version = $budget->approveVersion($allocation->id, $allocation->versions->sole()->id);
        $realizationPayload = $this->payload($context, 'realization', '15.00') + ['budget_allocation_version_id' => $version->id, 'program_id' => $context['program']->id];
        $realizations = $this->runConcurrent('realization', $realizationPayload, $workers);
        $this->assertGroup($realizations, 6, 4, ['E-BUDGET-INSUFFICIENT'], 'simultaneous realization');

        $availability = $budget->availability($version->id);
        if ($availability !== ['allocated' => '100.00', 'actual' => '90.00', 'available' => '10.00']) {
            throw new \RuntimeException('Concurrent realization availability did not preserve the approved allocation boundary.');
        }

        $journalCount = Journal::where('accounting_entity_id', $context['entity']->id)->count();
        $journalLineCount = JournalLine::where('accounting_entity_id', $context['entity']->id)->count();
        $ledgerCount = LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count();
        $voucherCount = Voucher::where('accounting_entity_id', $context['entity']->id)->count();
        $distinctVoucherCount = Voucher::where('accounting_entity_id', $context['entity']->id)->distinct('voucher_number')->count('voucher_number');
        $missingLedger = DB::table('financial_v2_journal_lines as journal_line')
            ->leftJoin('financial_v2_ledger_entries as ledger', 'ledger.journal_line_id', '=', 'journal_line.id')
            ->where('journal_line.accounting_entity_id', $context['entity']->id)
            ->whereNull('ledger.id')
            ->count();
        $orphanLedger = DB::table('financial_v2_ledger_entries as ledger')
            ->leftJoin('financial_v2_journal_lines as journal_line', 'journal_line.id', '=', 'ledger.journal_line_id')
            ->where('ledger.accounting_entity_id', $context['entity']->id)
            ->whereNull('journal_line.id')
            ->count();
        $postingJournal = Journal::where('accounting_entity_id', $context['entity']->id)->where('journal_status', '!=', 'posted')->count();
        $trialBalance = app(FinancialReportService::class)->report('trial-balance', $context['entity']->id, $context['today'], $context['today']);
        $balances = app(BalanceInquiryService::class);
        $sourceBalance = $balances->financialAccountBalance($context['entity']->id, $context['accountA']->id, $context['today'])['balance'];
        $destinationBalance = $balances->financialAccountBalance($context['entity']->id, $context['accountB']->id, $context['today'])['balance'];

        if ($journalCount !== 34 || $journalLineCount !== 68 || $ledgerCount !== 68 || $voucherCount !== 34 || $distinctVoucherCount !== 34 || $missingLedger !== 0 || $orphanLedger !== 0 || $postingJournal !== 0 || ! $trialBalance['data']['is_balanced'] || $sourceBalance !== '160.00' || $destinationBalance !== '250.00') {
            throw new \RuntimeException('Multi-session facts, voucher uniqueness, Ledger integrity, Trial Balance, or Financial Account balance did not tie out.');
        }

        return [
            'status' => 'passed',
            'workers_per_scenario' => $workers,
            'scenarios' => [
                'receipt' => $this->summary($receipts),
                'duplicate_post_retry' => $this->summary($duplicates),
                'payment_fund_account_race' => $this->summary($payments),
                'treasury_transfer' => $this->summary($transfers),
                'realization' => $this->summary($realizations),
            ],
            'facts' => [
                'journals' => $journalCount,
                'journal_lines' => $journalLineCount,
                'ledger_entries' => $ledgerCount,
                'vouchers' => $voucherCount,
                'distinct_vouchers' => $distinctVoucherCount,
                'missing_ledger' => $missingLedger,
                'orphan_ledger' => $orphanLedger,
                'non_posted_journals' => $postingJournal,
                'trial_balance_balanced' => $trialBalance['data']['is_balanced'],
                'source_financial_account_balance' => $sourceBalance,
                'destination_financial_account_balance' => $destinationBalance,
                'allocation_availability' => $availability,
            ],
        ];
    }

    /** @param array<string, mixed> $context @return array<string, mixed> */
    private function payload(array $context, string $scenario, string $amount): array
    {
        return [
            'scenario' => $scenario,
            'entity_id' => $context['entity']->id,
            'transaction_type_id' => match ($scenario) {
                'receipt' => $context['receiptType']->id,
                'payment', 'realization' => $context['paymentType']->id,
                'transfer' => $context['treasuryType']->id,
            },
            'date' => $context['today'],
            'amount' => $amount,
            'split_account_id' => match ($scenario) {
                'receipt' => $context['revenue']->id,
                'payment', 'realization' => $context['expense']->id,
                'transfer' => $context['cashA']->id,
            },
            'fund_id' => $context['fund']->id,
            'source_financial_account_id' => $context['accountA']->id,
            'destination_financial_account_id' => $context['accountB']->id,
            'counterparty_id' => $context['supplier']->id,
            'category_id' => $scenario === 'receipt' ? $context['receiptCategory']->id : $context['paymentCategory']->id,
        ];
    }

    /** @param array<string, mixed> $payload @return list<array<string, mixed>> */
    private function runConcurrent(string $scenario, array $payload, int $workers): array
    {
        $startAt = microtime(true) + 2;
        $processes = [];
        for ($worker = 1; $worker <= $workers; $worker++) {
            $process = new Process([
                PHP_BINARY,
                base_path('artisan'),
                'financial-v2:concurrency-worker',
                '--payload='.base64_encode(json_encode($payload, JSON_THROW_ON_ERROR)),
                '--start-at='.$startAt,
            ], base_path(), $this->childEnvironment());
            $process->setTimeout(180);
            $process->start();
            $processes[] = $process;
        }

        return collect($processes)->map(function (Process $process) use ($scenario): array {
            $process->wait();
            $output = trim($process->getOutput());
            $result = json_decode($output, true);
            if (! $process->isSuccessful() || ! is_array($result)) {
                return ['status' => 'error', 'scenario' => $scenario, 'message' => trim($process->getErrorOutput()."\n".$output)];
            }

            return $result + ['scenario' => $scenario];
        })->all();
    }

    /** @param list<array<string, mixed>> $results @param list<string> $failureCodes */
    private function assertGroup(array $results, int $expectedPosted, int $expectedRejected, array $failureCodes, string $label): void
    {
        $posted = collect($results)->where('status', 'posted');
        $rejected = collect($results)->where('status', 'rejected');
        $errors = collect($results)->where('status', 'error');
        if ($posted->count() !== $expectedPosted || $rejected->count() !== $expectedRejected || $errors->isNotEmpty() || ($failureCodes !== [] && $rejected->pluck('failure_code')->unique()->sort()->values()->all() !== $failureCodes)) {
            throw new \RuntimeException("Unexpected {$label} result: ".json_encode([
                'summary' => $this->summary($results),
                'errors' => $errors->values()->all(),
            ], JSON_THROW_ON_ERROR));
        }
    }

    /** @param list<array<string, mixed>> $results @return array<string, mixed> */
    private function summary(array $results): array
    {
        return [
            'posted' => collect($results)->where('status', 'posted')->count(),
            'rejected' => collect($results)->where('status', 'rejected')->count(),
            'errors' => collect($results)->where('status', 'error')->count(),
            'failure_codes' => collect($results)->where('status', 'rejected')->pluck('failure_code')->countBy()->all(),
        ];
    }

    /** @return array<string, string> */
    private function childEnvironment(): array
    {
        return [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => (string) config('database.connections.mysql.host'),
            'DB_PORT' => (string) config('database.connections.mysql.port'),
            'DB_DATABASE' => 'mrj_test_db',
        ];
    }

    /** @param array<string, mixed> $result */
    private function render(array $result): void
    {
        if ($this->option('format') === 'json') {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            return;
        }

        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    private function assertDisposableTestTarget(): void
    {
        if (! app()->environment('testing') || DB::connection()->getDatabaseName() !== 'mrj_test_db') {
            throw new \RuntimeException('Financial V2 multi-session rehearsal may run only with APP_ENV=testing on mrj_test_db.');
        }
    }
}
