<?php

namespace App\Console\Commands;

use App\Domain\FinancialV2\Reporting\FinancialReportService;
use App\Models\FinancialV2\AccountingEntity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Exports the current governed MRJ Financial V2 baseline into a source-controlled
 * payload. The command is deliberately read-only with respect to mrj_prod_db.
 *
 * Posted facts are exported as replay instructions only. The resulting seeder
 * uses OpeningBalanceService, the transaction lifecycle, and PostingEngine; it
 * never serializes Journal, JournalLine, Ledger, Voucher, or posting attempts as
 * raw seed rows.
 */
final class ExportFinancialV2SeedSnapshotCommand extends Command
{
    private const ENTITY_CODE = 'MRJ-ACTUAL';

    /** @var array<int, string> */
    private const STATIC_TABLES = [
        'financial_v2_accounting_entities',
        'financial_v2_account_groups',
        'financial_v2_accounting_calendars',
        'financial_v2_accounting_periods',
        'financial_v2_accounts',
        'financial_v2_fund_types',
        'financial_v2_fund_restrictions',
        'financial_v2_funds',
        'financial_v2_financial_accounts',
        'financial_v2_bank_account_details',
        'financial_v2_cash_account_details',
        'financial_v2_programs',
        'financial_v2_categories',
        'financial_v2_transaction_types',
        'financial_v2_fund_policy_versions',
        'financial_v2_fund_policy_rules',
        'financial_v2_posting_rules',
        'financial_v2_posting_rule_versions',
        'financial_v2_posting_rule_lines',
        'financial_v2_document_sequences',
        'financial_v2_evidence_requirements',
        'financial_v2_counterparties',
        'financial_v2_closing_runs',
    ];

    protected $signature = 'financial-v2:export-seed
        {--path=database/seeders/FinancialV2/current_mrj_financial_v2_snapshot.php : Source-controlled PHP snapshot path}';

    protected $description = 'Read current mrj_prod_db Financial V2 state and export a replay-safe local/testing seed snapshot.';

    public function handle(FinancialReportService $reports): int
    {
        $this->assertSourceEnvironment();

        $entity = AccountingEntity::query()->where('code', self::ENTITY_CODE)->firstOrFail();
        $snapshot = $this->snapshot($entity, $reports);
        $this->assertNoSampleOrQaBaseline($snapshot);
        $path = base_path((string) $this->option('path'));

        File::ensureDirectoryExists(dirname($path));
        $payload = preg_replace('/[\t ]+(?=\R)/', '', var_export($snapshot, true));
        File::put($path, "<?php\n\n/** Generated from mrj_prod_db by financial-v2:export-seed. */\nreturn {$payload};\n");

        $this->table(['Source', 'Value'], [
            ['Database', (string) DB::connection()->getDatabaseName()],
            ['Accounting entity', $entity->code],
            ['Static/configuration rows', (string) collect($snapshot['tables'])->sum(fn (array $rows): int => count($rows))],
            ['Historical Fund History', (string) count($snapshot['historical_fund_histories'])],
            ['Opening balance lines', (string) count($snapshot['opening_balance']['lines'])],
            ['Posted fact replays', (string) (1 + count($snapshot['posted_interfund_transfers']))],
            ['Allocation baselines', (string) count($snapshot['operational_allocations'])],
            ['Operational realization baselines', (string) count($snapshot['operational_realizations'])],
            ['Output', str_replace(base_path().DIRECTORY_SEPARATOR, '', $path)],
        ]);

        return self::SUCCESS;
    }

    private function assertSourceEnvironment(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Financial V2 seed export is prohibited in production.');
        }

        if (! app()->environment('local') || DB::connection()->getDatabaseName() !== 'mrj_prod_db') {
            throw new RuntimeException('Financial V2 seed export is permitted only on local mrj_prod_db.');
        }
    }

    /** @param array<string, mixed> $snapshot */
    private function assertNoSampleOrQaBaseline(array $snapshot): void
    {
        $matches = [];
        $scan = function (mixed $value, string $path) use (&$scan, &$matches): void {
            if (is_array($value)) {
                foreach ($value as $key => $child) {
                    $scan($child, $path.'.'.$key);
                }

                return;
            }
            if (is_string($value) && preg_match('/(?:^|[-_\s\/])(sample|qa)(?:$|[-_\s\/])/i', $value)) {
                $matches[] = $path;
            }
        };

        $scan([
            'tables' => $snapshot['tables'],
            'historical_fund_histories' => $snapshot['historical_fund_histories'],
            'opening_balance' => $snapshot['opening_balance'],
            'posted_interfund_transfers' => $snapshot['posted_interfund_transfers'],
            'operational_allocations' => $snapshot['operational_allocations'],
            'operational_realizations' => $snapshot['operational_realizations'],
        ], 'snapshot');

        if ($matches !== []) {
            throw new RuntimeException('Seeder export refused: SAMPLE/QA marker found in current Financial V2 baseline at '.implode(', ', array_slice($matches, 0, 10)).'.');
        }
    }

    /** @return array<string, mixed> */
    private function snapshot(AccountingEntity $entity, FinancialReportService $reports): array
    {
        $tables = [];
        foreach (self::STATIC_TABLES as $table) {
            $tables[$table] = $this->tableRows($table, $entity->id);
        }

        $transactions = $this->rows('financial_v2_transactions', $entity->id)->keyBy('id');
        $types = collect($tables['financial_v2_transaction_types'])->keyBy('id');
        $journals = $this->rows('financial_v2_journals', $entity->id)->keyBy('transaction_id');
        $openingBatches = $this->rows('financial_v2_opening_balance_batches', $entity->id);

        if ($openingBatches->count() !== 1 || $openingBatches->first()['status'] !== 'posted') {
            throw new RuntimeException('The current baseline must contain exactly one posted Opening Balance batch before export.');
        }

        $opening = $this->openingBalanceSnapshot($entity->id, $openingBatches->first());
        $postedInterfunds = $this->postedInterfundSnapshots($entity->id, $transactions, $types, $journals);
        $this->assertAllPostedFactsAreReplayable($transactions, $types, $opening, $postedInterfunds);

        $operationalAllocations = $this->operationalAllocationSnapshots($entity->id);
        $operationalRealisations = $this->operationalRealizationSnapshots($entity->id, $transactions, $types);

        $latestLedgerDate = DB::table('financial_v2_ledger_entries')
            ->where('accounting_entity_id', $entity->id)
            ->max('accounting_date');
        $asOf = $latestLedgerDate ?: now()->toDateString();
        $fundReport = $reports->report('fund-balance', $entity->id, '2026-01-01', $asOf)['data'];
        $accountReport = $reports->report('account-balance', $entity->id, '2026-01-01', $asOf)['data'];

        return [
            'schema_version' => 1,
            'source' => [
                'database' => 'mrj_prod_db',
                'accounting_entity_code' => $entity->code,
                'exported_at' => now()->toIso8601String(),
                'notes' => 'Current local Financial V2 state. Export excludes audit-event noise, failed posting attempts, sessions, caches, and queues.',
            ],
            'inventory' => $this->financialV2Inventory(),
            'tables' => $tables,
            'historical_fund_histories' => $this->rows('financial_v2_historical_fund_histories', $entity->id)->all(),
            'opening_balance' => $opening,
            'posted_interfund_transfers' => $postedInterfunds,
            'operational_allocations' => $operationalAllocations,
            'operational_realizations' => $operationalRealisations,
            'expected' => [
                'posted_fact_counts' => [
                    'journals' => DB::table('financial_v2_journals')->where('accounting_entity_id', $entity->id)->where('journal_status', 'posted')->count(),
                    'journal_lines' => DB::table('financial_v2_journal_lines')->where('accounting_entity_id', $entity->id)->count(),
                    'ledger_entries' => DB::table('financial_v2_ledger_entries')->where('accounting_entity_id', $entity->id)->count(),
                    'vouchers' => DB::table('financial_v2_vouchers')->where('accounting_entity_id', $entity->id)->where('status', 'issued')->count(),
                ],
                'fund_balances' => collect($fundReport['rows'])->mapWithKeys(fn (array $row): array => [$row['code'] => (string) $row['fund_balance']])->all(),
                'financial_account_balances' => collect($accountReport['rows'])->mapWithKeys(fn (array $row): array => [$row['code'] => (string) $row['closing_balance']])->all(),
            ],
            'excluded_runtime' => [
                'financial_v2_audit_events' => DB::table('financial_v2_audit_events')->where('accounting_entity_id', $entity->id)->count(),
                'financial_v2_idempotency_keys' => DB::table('financial_v2_idempotency_keys')->where('accounting_entity_id', $entity->id)->where('status', 'failed')->count(),
                'financial_v2_posting_attempts' => DB::table('financial_v2_posting_attempts')->where('accounting_entity_id', $entity->id)->where('status', 'failed')->count(),
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function tableRows(string $table, string $entityId): array
    {
        if ($table === 'financial_v2_accounting_entities') {
            return DB::table($table)
                ->where('id', $entityId)
                ->orderBy('id')
                ->get()
                ->map(fn (object $row): array => (array) $row)
                ->all();
        }

        if (in_array($table, ['financial_v2_bank_account_details', 'financial_v2_cash_account_details'], true)) {
            return DB::table($table)
                ->whereIn('financial_account_id', DB::table('financial_v2_financial_accounts')->where('accounting_entity_id', $entityId)->select('id'))
                ->orderBy('id')
                ->get()
                ->map(fn (object $row): array => (array) $row)
                ->all();
        }

        return $this->rows($table, $entityId)->all();
    }

    /** @return \Illuminate\Support\Collection<int, array<string, mixed>> */
    private function rows(string $table, string $entityId): \Illuminate\Support\Collection
    {
        return DB::table($table)
            ->where('accounting_entity_id', $entityId)
            ->orderBy('id')
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->values();
    }

    /** @param array<string, mixed> $batch @return array<string, mixed> */
    private function openingBalanceSnapshot(string $entityId, array $batch): array
    {
        $mapping = DB::table('financial_v2_mapping_sets')->where('id', $batch['mapping_set_id'])->first();
        if (! $mapping) {
            throw new RuntimeException('The posted opening balance batch has no mapping set.');
        }

        $lines = DB::table('financial_v2_opening_balance_lines')
            ->where('opening_balance_batch_id', $batch['id'])
            ->orderBy('line_no')
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->all();
        $lineIds = collect($lines)->pluck('id');
        $links = DB::table('financial_v2_attachment_links')
            ->where('accounting_entity_id', $entityId)
            ->where('target_type', 'opening_balance_line')
            ->whereIn('target_id', $lineIds)
            ->orderBy('id')
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->all();
        $attachments = DB::table('financial_v2_attachments')
            ->whereIn('id', collect($links)->pluck('attachment_id'))
            ->orderBy('id')
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->all();

        return [
            'batch' => $batch,
            'mapping_set' => (array) $mapping,
            'mappings' => DB::table('financial_v2_legacy_mappings')->where('mapping_set_id', $mapping->id)->orderBy('legacy_record_ref')->get()->map(fn (object $row): array => (array) $row)->all(),
            'lines' => $lines,
            'attachments' => $attachments,
            'attachment_links' => $links,
        ];
    }

    /** @param \Illuminate\Support\Collection<string, array<string, mixed>> $transactions @param \Illuminate\Support\Collection<string, array<string, mixed>> $types @param \Illuminate\Support\Collection<string, array<string, mixed>> $journals @return array<int, array<string, mixed>> */
    private function postedInterfundSnapshots(string $entityId, \Illuminate\Support\Collection $transactions, \Illuminate\Support\Collection $types, \Illuminate\Support\Collection $journals): array
    {
        return $transactions
            ->filter(fn (array $transaction): bool => $transaction['status'] === 'posted' && ($types->get($transaction['transaction_type_id'])['code'] ?? null) === 'IFT')
            ->sortBy(fn (array $transaction): int => (int) ($journals->get($transaction['id'])['posting_sequence'] ?? PHP_INT_MAX))
            ->map(function (array $transaction) use ($entityId, $journals): array {
                $detail = DB::table('financial_v2_interfund_transfers')->where('transaction_id', $transaction['id'])->first();
                $journal = $journals->get($transaction['id']);
                $idempotency = $journal
                    ? DB::table('financial_v2_idempotency_keys')->where('scope_name', 'transaction-posting')->where('result_reference', $journal['id'])->where('status', 'completed')->first()
                    : null;
                if (! $detail || ! $journal || ! $idempotency) {
                    throw new RuntimeException("Posted Interfund Transfer {$transaction['source_reference']} is missing governed replay provenance.");
                }

                $links = DB::table('financial_v2_attachment_links')->where('accounting_entity_id', $entityId)->where('target_type', 'transaction')->where('target_id', $transaction['id'])->orderBy('id')->get()->map(fn (object $row): array => (array) $row)->all();
                $attachments = DB::table('financial_v2_attachments')->whereIn('id', collect($links)->pluck('attachment_id'))->orderBy('id')->get()->map(fn (object $row): array => (array) $row)->all();

                return [
                    'transaction' => $transaction,
                    'detail' => (array) $detail,
                    'posting' => [
                        'idempotency_key' => $idempotency->key_value,
                        'fingerprint' => $idempotency->request_fingerprint,
                        'voucher_number' => DB::table('financial_v2_vouchers')->where('transaction_id', $transaction['id'])->value('voucher_number'),
                    ],
                    'attachments' => $attachments,
                    'attachment_links' => $links,
                ];
            })
            ->values()
            ->all();
    }

    /** @param \Illuminate\Support\Collection<string, array<string, mixed>> $transactions @param \Illuminate\Support\Collection<string, array<string, mixed>> $types @param array<string, mixed> $opening @param array<int, array<string, mixed>> $interfunds */
    private function assertAllPostedFactsAreReplayable(\Illuminate\Support\Collection $transactions, \Illuminate\Support\Collection $types, array $opening, array $interfunds): void
    {
        $posted = $transactions->filter(fn (array $transaction): bool => $transaction['status'] === 'posted');
        $openingCount = $posted->filter(fn (array $transaction): bool => ($types->get($transaction['transaction_type_id'])['code'] ?? null) === 'OPB')->count();
        $interfundCount = $posted->filter(fn (array $transaction): bool => ($types->get($transaction['transaction_type_id'])['code'] ?? null) === 'IFT')->count();
        if ($openingCount !== 1 || $interfundCount !== count($interfunds) || $posted->count() !== (1 + count($interfunds)) || $opening['batch']['status'] !== 'posted') {
            throw new RuntimeException('Current posted Financial V2 facts contain a type without an approved canonical replay path. Export stopped without producing a seed payload.');
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function operationalAllocationSnapshots(string $entityId): array
    {
        return $this->rows('financial_v2_budget_allocations', $entityId)
            ->map(function (array $allocation) use ($entityId): array {
                return [
                    'allocation' => $allocation,
                    'versions' => $this->rows('financial_v2_budget_allocation_versions', $entityId)
                        ->where('budget_allocation_id', $allocation['id'])
                        ->sortBy('version_no')
                        ->values()
                        ->all(),
                ];
            })
            ->all();
    }

    /** @param \Illuminate\Support\Collection<string, array<string, mixed>> $transactions @param \Illuminate\Support\Collection<string, array<string, mixed>> $types @return array<int, array<string, mixed>> */
    private function operationalRealizationSnapshots(string $entityId, \Illuminate\Support\Collection $transactions, \Illuminate\Support\Collection $types): array
    {
        $realizations = $this->rows('financial_v2_fund_realizations', $entityId)->keyBy('transaction_id');
        $rows = $transactions
            ->filter(fn (array $transaction): bool => $transaction['status'] !== 'posted')
            ->map(function (array $transaction) use ($entityId, $types, $realizations): array {
                $realization = $realizations->get($transaction['id']);
                if (! $realization || ($types->get($transaction['transaction_type_id'])['code'] ?? null) !== 'PAY') {
                    throw new RuntimeException("Operational transaction {$transaction['source_reference']} does not have a safe lifecycle replay path.");
                }

                $links = DB::table('financial_v2_attachment_links')->where('accounting_entity_id', $entityId)->where('target_type', 'transaction')->where('target_id', $transaction['id'])->orderBy('id')->get()->map(fn (object $row): array => (array) $row)->all();
                $attachments = DB::table('financial_v2_attachments')->whereIn('id', collect($links)->pluck('attachment_id'))->orderBy('id')->get()->map(fn (object $row): array => (array) $row)->all();
                $cancellation = DB::table('financial_v2_audit_events')->where('accounting_entity_id', $entityId)->where('event_type', 'transaction_cancelled')->where('target_id', $transaction['id'])->latest('event_at')->value('after_summary');
                $cancellationPayload = is_string($cancellation) ? json_decode($cancellation, true) : [];

                return [
                    'transaction' => $transaction,
                    'splits' => DB::table('financial_v2_transaction_splits')->where('transaction_id', $transaction['id'])->orderBy('line_no')->get()->map(fn (object $row): array => (array) $row)->all(),
                    'realization' => $realization,
                    'attachments' => $attachments,
                    'attachment_links' => $links,
                    'cancellation_reason' => $cancellationPayload['reason'] ?? null,
                ];
            });

        if ($realizations->count() !== $rows->count()) {
            throw new RuntimeException('Current Fund Realization rows have an orphan or unsupported transaction. Export stopped without a seed payload.');
        }

        return $rows->values()->all();
    }

    /** @return array<string, int> */
    private function financialV2Inventory(): array
    {
        return collect(DB::select('SHOW TABLES'))
            ->map(fn (object $row): string => array_values((array) $row)[0])
            ->filter(fn (string $table): bool => str_starts_with($table, 'financial_v2_'))
            ->sort()
            ->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->count()])
            ->all();
    }
}
