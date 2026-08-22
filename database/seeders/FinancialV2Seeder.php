<?php

namespace Database\Seeders;

use App\Domain\FinancialV2\BudgetAllocationService;
use App\Domain\FinancialV2\EvidenceService;
use App\Domain\FinancialV2\FinancialTransactionLifecycleService;
use App\Domain\FinancialV2\OpeningBalanceService;
use App\Domain\FinancialV2\Reporting\FinancialReportService;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\BudgetAllocation;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\MappingSet;
use App\Models\FinancialV2\OpeningBalanceBatch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Replays the current mrj_prod_db baseline exported by financial-v2:export-seed.
 * Only configuration and source history are raw upserted. Opening balances and
 * posted facts are recreated through OpeningBalanceService, lifecycle, and the
 * canonical PostingEngine. No Journal/JournalLine/Ledger raw writer exists here.
 */
final class FinancialV2Seeder extends Seeder
{
    private const ENTITY = 'MRJ-ACTUAL';

    private const TABLES = [
        'financial_v2_accounting_entities', 'financial_v2_account_groups', 'financial_v2_accounting_calendars',
        'financial_v2_accounting_periods', 'financial_v2_accounts', 'financial_v2_fund_types',
        'financial_v2_fund_restrictions', 'financial_v2_funds', 'financial_v2_financial_accounts',
        'financial_v2_bank_account_details', 'financial_v2_cash_account_details', 'financial_v2_transaction_types',
        'financial_v2_programs', 'financial_v2_posting_rules', 'financial_v2_posting_rule_versions',
        'financial_v2_categories', 'financial_v2_fund_policy_versions', 'financial_v2_fund_policy_rules',
        'financial_v2_posting_rule_lines', 'financial_v2_document_sequences', 'financial_v2_evidence_requirements',
        'financial_v2_counterparties', 'financial_v2_closing_runs',
    ];

    /** @var array<string, mixed> */
    private array $data;

    /** @var array<string, string> */
    private array $versionMap = [];

    /** @var array<string, BudgetAllocation> */
    private array $allocationMap = [];

    /** @var array{created:int,updated:int,skipped:int,existing:int,replayed:int} */
    private array $metrics = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'existing' => 0, 'replayed' => 0];

    public function run(): void
    {
        $this->assertEnvironment();
        $this->data = $this->snapshot();
        $this->assertSnapshot();

        DB::transaction(function (): void {
            foreach (self::TABLES as $table) {
                foreach ($this->data['tables'][$table] ?? [] as $row) {
                    $this->upsert($table, $row);
                }
            }
            foreach ($this->data['historical_fund_histories'] as $row) {
                // Source lineage only: no transaction, Journal, or Ledger effect.
                $this->upsert('financial_v2_historical_fund_histories', $row);
            }

            $entity = AccountingEntity::query()->where('code', self::ENTITY)->sole();
            $this->prepareFactSequences($entity->id);
            $this->opening($entity->id);
            $this->postedInterfunds($entity->id);
            $this->allocations($entity->id);
            $this->realisations($entity->id);
            $this->cancelledAllocations();
            $this->alignSequences();
            $this->tieOut($entity->id);
        }, 3);

        $this->summary();
    }

    private function assertEnvironment(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('FinancialV2Seeder is prohibited in production.');
        }
        $database = (string) DB::connection()->getDatabaseName();
        if (! ((app()->environment(['local', 'development']) && $database === 'mrj_prod_db') || (app()->environment('testing') && $database === 'mrj_test_db'))) {
            throw new RuntimeException("FinancialV2Seeder may run only on local mrj_prod_db or testing mrj_test_db; current database: {$database}.");
        }
    }

    /** @return array<string, mixed> */
    private function snapshot(): array
    {
        $path = __DIR__.'/FinancialV2/current_mrj_financial_v2_snapshot.php';
        if (! is_file($path)) {
            throw new RuntimeException('Missing Financial V2 snapshot. Run php artisan financial-v2:export-seed on local mrj_prod_db.');
        }
        $snapshot = require $path;
        if (! is_array($snapshot)) {
            throw new RuntimeException('Financial V2 snapshot must return an array.');
        }

        return $snapshot;
    }

    private function assertSnapshot(): void
    {
        if (($this->data['schema_version'] ?? null) !== 1
            || ($this->data['source']['database'] ?? null) !== 'mrj_prod_db'
            || ($this->data['source']['accounting_entity_code'] ?? null) !== self::ENTITY) {
            throw new RuntimeException('The Financial V2 snapshot is not the current governed MRJ source.');
        }
    }

    private function opening(string $entityId): void
    {
        $source = $this->data['opening_balance'];
        $batchSource = $source['batch'];
        $existing = OpeningBalanceBatch::query()->where('accounting_entity_id', $entityId)->where('cutover_reference', $batchSource['cutover_reference'])->first();
        if ($existing) {
            if ($existing->status !== 'posted') {
                throw new RuntimeException('Seeder refuses to alter an in-progress Opening Balance batch.');
            }
            $this->metrics['existing']++;

            return;
        }

        $mappingSource = $source['mapping_set'];
        if (MappingSet::query()->where('accounting_entity_id', $entityId)->where('code', $mappingSource['code'])->exists()) {
            throw new RuntimeException('Seeder refuses to reinterpret an existing Opening Balance mapping set.');
        }
        $service = app(OpeningBalanceService::class);
        $this->activateHistoricalFundPolicies(
            collect($source['lines'])->pluck('fund_id')->filter()->unique()->all(),
            $batchSource['cutover_date'],
        );
        $mapping = $service->createMappingSet([
            'accounting_entity_id' => $entityId, 'code' => $mappingSource['code'], 'name' => $mappingSource['name'],
            'source_system_name' => $mappingSource['source_system_name'], 'position_date' => $mappingSource['cutover_date'],
        ]);
        foreach ($source['mappings'] as $row) {
            $dimension = $row['target_entity_type'];
            $suffix = '|'.$dimension;
            if (! str_ends_with($row['legacy_record_ref'], $suffix)) {
                throw new RuntimeException('Invalid Opening Balance mapping reference in snapshot.');
            }
            $service->recordMapping($mapping->id, substr($row['legacy_record_ref'], 0, -strlen($suffix)), $dimension, 'mapped', $row['target_entity_id'], $row['legacy_value'], $row['rationale']);
        }
        $service->reviewMappingSet($mapping->id);
        $service->approveMappingSet($mapping->id);
        $batch = $service->createDraft([
            'accounting_entity_id' => $entityId, 'accounting_period_id' => $batchSource['accounting_period_id'],
            'mapping_set_id' => $mapping->id, 'position_date' => $batchSource['cutover_date'],
            'rehearsal_reference' => $batchSource['cutover_reference'], 'evidence_package_ref' => $batchSource['evidence_package_ref'],
        ]);
        $lineMap = [];
        foreach ($source['lines'] as $row) {
            $line = $service->addLine($batch->id, Arr::only($row, [
                'account_id', 'fund_id', 'financial_account_id', 'program_id', 'debit_amount', 'credit_amount',
                'source_debit_amount', 'source_credit_amount', 'source_reference', 'evidence_ref', 'line_description',
            ]));
            $lineMap[$row['id']] = $line->id;
        }
        $this->openingEvidence($entityId, $source, $lineMap);
        $service->reconcile($batch->id);
        $service->review($batch->id);
        $service->approve($batch->id);
        $service->post($batch->id);
        $this->restoreFinalPolicyVersions();
        $this->metrics['replayed']++;
    }

    /** @param array<string, mixed> $source @param array<string, string> $lineMap */
    private function openingEvidence(string $entityId, array $source, array $lineMap): void
    {
        $attachments = collect($source['attachments'])->keyBy('id');
        $evidence = app(EvidenceService::class);
        foreach ($source['attachment_links'] as $link) {
            $attachment = $attachments->get($link['attachment_id']);
            $lineId = $lineMap[$link['target_id']] ?? null;
            if (! $attachment || ! $lineId) {
                throw new RuntimeException('Opening Balance evidence mapping is incomplete.');
            }
            $evidence->attachToOpeningBalanceLine($entityId, $lineId, $attachment['original_filename'], $attachment['media_type'], (int) $attachment['byte_size'], $attachment['content_hash'], $attachment['storage_reference'], $link['evidence_type']);
        }
    }

    private function postedInterfunds(string $entityId): void
    {
        $lifecycle = app(FinancialTransactionLifecycleService::class);
        foreach ($this->data['posted_interfund_transfers'] as $source) {
            $transactionSource = $source['transaction'];
            $existing = FinancialTransaction::query()->where('accounting_entity_id', $entityId)->where('source_reference', $transactionSource['source_reference'])->first();
            if ($existing) {
                if ($existing->status !== 'posted') {
                    throw new RuntimeException('Existing baseline Interfund Transfer is not posted.');
                }
                $this->metrics['existing']++;

                continue;
            }
            $detail = $source['detail'];
            $this->activateHistoricalInterfundPolicies($detail, $transactionSource['accounting_date']);
            $transaction = $lifecycle->createInterfundTransfer([
                'accounting_entity_id' => $entityId, 'transaction_type_id' => $transactionSource['transaction_type_id'],
                'source_reference' => $transactionSource['source_reference'], 'business_date' => $transactionSource['business_date'],
                'accounting_date' => $transactionSource['accounting_date'], 'description' => $transactionSource['description'],
                'currency_code' => $transactionSource['currency_code'], 'gross_amount' => $transactionSource['gross_amount'],
                'primary_financial_account_id' => $transactionSource['primary_financial_account_id'], 'idempotency_key' => $transactionSource['idempotency_key'],
                'policy_version_ref' => $transactionSource['policy_version_ref'], 'correlation_id' => $transactionSource['correlation_id'],
                'source_fund_id' => $detail['source_fund_id'], 'destination_fund_id' => $detail['destination_fund_id'],
                'policy_basis_ref' => $detail['policy_basis_ref'], 'reason' => $detail['reason'],
            ]);
            $this->transactionEvidence($entityId, $transaction->id, $source);
            $lifecycle->submit($transaction->id);
            $lifecycle->verify($transaction->id);
            $lifecycle->approve($transaction->id);
            $lifecycle->post($transaction->id, $source['posting']['idempotency_key'], $source['posting']['fingerprint']);
            $this->metrics['replayed']++;
        }
        $this->restoreFinalPolicyVersions();
    }

    /** @param array<string, mixed> $detail */
    private function activateHistoricalInterfundPolicies(array $detail, string $accountingDate): void
    {
        $this->activateHistoricalFundPolicies([$detail['source_fund_id'], $detail['destination_fund_id']], $accountingDate);
    }

    /** @param array<int, string> $fundIds */
    private function activateHistoricalFundPolicies(array $fundIds, string $accountingDate): void
    {
        foreach (array_unique(array_filter($fundIds)) as $fundId) {
            $historical = DB::table('financial_v2_fund_policy_versions')
                ->where('fund_id', $fundId)
                ->where('effective_from', '<=', $accountingDate)
                ->where(fn ($query) => $query->whereNull('effective_to')->orWhere('effective_to', '>=', $accountingDate))
                ->orderByDesc('effective_from')
                ->first();
            if (! $historical) {
                continue;
            }
            DB::table('financial_v2_fund_policy_versions')->where('id', $historical->id)->update(['status' => 'effective']);
        }
    }

    private function restoreFinalPolicyVersions(): void
    {
        foreach ($this->data['tables']['financial_v2_fund_policy_versions'] ?? [] as $row) {
            DB::table('financial_v2_fund_policy_versions')->where('id', $row['id'])->update(['status' => $row['status']]);
        }
    }

    private function allocations(string $entityId): void
    {
        $service = app(BudgetAllocationService::class);
        foreach ($this->data['operational_allocations'] as $source) {
            $allocationSource = $source['allocation'];
            if (count($source['versions']) !== 1) {
                throw new RuntimeException('Multiple allocation versions need an explicit replay extension.');
            }
            $versionSource = $source['versions'][0];
            $allocation = BudgetAllocation::query()->where('accounting_entity_id', $entityId)->where('allocation_reference', $allocationSource['allocation_reference'])->first();
            if (! $allocation) {
                $allocation = $service->create([
                    'accounting_entity_id' => $entityId, 'accounting_period_id' => $allocationSource['accounting_period_id'],
                    'fund_id' => $allocationSource['fund_id'], 'program_id' => $allocationSource['program_id'],
                    'account_id' => $allocationSource['account_id'], 'category_id' => $allocationSource['category_id'],
                    'allocation_reference' => $allocationSource['allocation_reference'], 'idempotency_key' => $allocationSource['idempotency_key'],
                    'correlation_id' => $allocationSource['correlation_id'], 'allocated_amount' => $versionSource['allocated_amount'],
                    'effective_from' => $versionSource['effective_from'], 'effective_to' => $versionSource['effective_to'], 'reason' => $allocationSource['reason'],
                ])->fresh('versions');
                $service->submit($allocation->id);
                $service->approveVersion($allocation->id, $allocation->versions->sole()->id);
                $this->metrics['created']++;
            } else {
                $this->metrics['existing']++;
            }
            $version = $allocation->fresh('versions')->versions->firstWhere('version_no', $versionSource['version_no']);
            if (! $version) {
                throw new RuntimeException('Allocation version replay mapping failed.');
            }
            $this->versionMap[$versionSource['id']] = $version->id;
            $this->allocationMap[$allocationSource['id']] = $allocation->fresh();
        }
    }

    private function realisations(string $entityId): void
    {
        $rows = $this->data['operational_realizations'];
        usort($rows, fn (array $a, array $b): int => ($a['transaction']['status'] === 'cancelled' ? 0 : 1) <=> ($b['transaction']['status'] === 'cancelled' ? 0 : 1));
        $lifecycle = app(FinancialTransactionLifecycleService::class);
        foreach ($rows as $source) {
            $transactionSource = $source['transaction'];
            if (FinancialTransaction::query()->where('accounting_entity_id', $entityId)->where('source_reference', $transactionSource['source_reference'])->exists()) {
                $this->metrics['existing']++;

                continue;
            }
            $versionId = $this->versionMap[$source['realization']['budget_allocation_version_id']] ?? null;
            if (! $versionId) {
                throw new RuntimeException('Fund Realization has no governed allocation version.');
            }
            $transaction = $lifecycle->createRealization($this->paymentInput($entityId, $transactionSource), array_map(fn (array $split): array => Arr::only($split, ['account_id', 'fund_id', 'financial_account_id', 'program_id', 'cost_center_id', 'counterparty_id', 'category_id', 'purpose_note', 'split_amount']), $source['splits']), $versionId);
            $this->transactionEvidence($entityId, $transaction->id, $source);
            $this->status($lifecycle, $transaction->id, $transactionSource['status'], $source['cancellation_reason'] ?? null);
            $this->metrics['created']++;
        }
    }

    /** @param array<string, mixed> $transaction */
    private function paymentInput(string $entityId, array $transaction): array
    {
        return [
            'accounting_entity_id' => $entityId, 'transaction_type_id' => $transaction['transaction_type_id'],
            'source_reference' => $transaction['source_reference'], 'business_date' => $transaction['business_date'],
            'accounting_date' => $transaction['accounting_date'], 'description' => $transaction['description'],
            'currency_code' => $transaction['currency_code'], 'gross_amount' => $transaction['gross_amount'],
            'primary_financial_account_id' => $transaction['primary_financial_account_id'], 'counterparty_id' => $transaction['counterparty_id'],
            'category_id' => $transaction['category_id'], 'reason_code_id' => $transaction['reason_code_id'],
            'related_transaction_id' => $transaction['related_transaction_id'], 'idempotency_key' => $transaction['idempotency_key'],
            'policy_version_ref' => $transaction['policy_version_ref'], 'correlation_id' => $transaction['correlation_id'],
        ];
    }

    private function status(FinancialTransactionLifecycleService $service, string $id, string $status, ?string $reason): void
    {
        if ($status === 'draft') {
            return;
        }
        if ($status === 'cancelled') {
            $service->cancel($id, $reason ?: 'Cancelled state retained from current baseline.');

            return;
        }
        $service->submit($id);
        if ($status === 'submitted') {
            return;
        }
        $service->verify($id);
        if ($status === 'verified') {
            return;
        }
        if ($status === 'approved') {
            $service->approve($id);

            return;
        }
        throw new RuntimeException("Unsupported non-posted transaction status {$status}.");
    }

    private function cancelledAllocations(): void
    {
        $service = app(BudgetAllocationService::class);
        foreach ($this->data['operational_allocations'] as $source) {
            if ($source['allocation']['status'] !== 'cancelled') {
                continue;
            }
            $allocation = $this->allocationMap[$source['allocation']['id']] ?? null;
            if (! $allocation) {
                throw new RuntimeException('Cancelled allocation replay mapping failed.');
            }
            if ($allocation->fresh()->status !== 'cancelled') {
                $service->cancel($allocation->id, $source['allocation']['cancellation_reason']);
            }
        }
    }

    /** @param array<string, mixed> $source */
    private function transactionEvidence(string $entityId, string $transactionId, array $source): void
    {
        $attachments = collect($source['attachments'] ?? [])->keyBy('id');
        $evidence = app(EvidenceService::class);
        foreach ($source['attachment_links'] ?? [] as $link) {
            $attachment = $attachments->get($link['attachment_id']);
            if (! $attachment) {
                throw new RuntimeException('Transaction evidence mapping is incomplete.');
            }
            $evidence->attachToTransaction($entityId, $transactionId, $attachment['original_filename'], $attachment['media_type'], (int) $attachment['byte_size'], $attachment['content_hash'], $attachment['storage_reference'], $link['evidence_type']);
        }
    }

    private function alignSequences(): void
    {
        foreach ($this->data['tables']['financial_v2_document_sequences'] ?? [] as $row) {
            DB::table('financial_v2_document_sequences')->where('id', $row['id'])->update(['next_value' => $row['next_value']]);
        }
    }

    private function prepareFactSequences(string $entityId): void
    {
        $types = collect($this->data['tables']['financial_v2_transaction_types'] ?? [])->keyBy('code');
        $uses = ['OPB' => 1, 'IFT' => count($this->data['posted_interfund_transfers'])];
        foreach ($uses as $code => $issued) {
            $type = $types->get($code);
            if (! $type || $issued === 0) {
                continue;
            }
            $sequence = DB::table('financial_v2_document_sequences')->where('accounting_entity_id', $entityId)->where('transaction_type_id', $type['id'])->first();
            if ($sequence) {
                DB::table('financial_v2_document_sequences')->where('id', $sequence->id)->update(['next_value' => max(1, (int) $sequence->next_value - $issued)]);
            }
        }
    }

    private function tieOut(string $entityId): void
    {
        foreach ($this->data['expected']['posted_fact_counts'] as $name => $expected) {
            $actual = DB::table('financial_v2_'.$name)->where('accounting_entity_id', $entityId)->count();
            if ($actual !== (int) $expected) {
                throw new RuntimeException("Financial fact count mismatch for {$name}: {$actual}.");
            }
        }
        $asOf = DB::table('financial_v2_ledger_entries')->where('accounting_entity_id', $entityId)->max('accounting_date');
        $reports = app(FinancialReportService::class);
        $funds = collect($reports->report('fund-balance', $entityId, '2026-01-01', $asOf)['data']['rows'])->mapWithKeys(fn (array $row): array => [$row['code'] => (string) $row['fund_balance']])->all();
        $accounts = collect($reports->report('account-balance', $entityId, '2026-01-01', $asOf)['data']['rows'])->mapWithKeys(fn (array $row): array => [$row['code'] => (string) $row['closing_balance']])->all();
        if ($funds !== $this->data['expected']['fund_balances'] || $accounts !== $this->data['expected']['financial_account_balances']) {
            throw new RuntimeException('Financial V2 Fund/Account semantic tie-out failed.');
        }
    }

    /** @param array<string, mixed> $row */
    private function upsert(string $table, array $row): void
    {
        if (! isset($row['id'])) {
            throw new RuntimeException("{$table} snapshot row lacks a stable UUID.");
        }
        foreach (array_keys($row) as $key) {
            if (str_ends_with($key, '_by_user_id')) {
                $row[$key] = null;
            }
        }
        $existing = DB::table($table)->where('id', $row['id'])->first();
        if (! $existing) {
            DB::table($table)->insert($row);
            $this->metrics['created']++;

            return;
        }
        foreach ($row as $key => $value) {
            if ((string) ($existing->{$key} ?? '') !== (string) ($value ?? '')) {
                DB::table($table)->where('id', $row['id'])->update(Arr::except($row, ['id']));
                $this->metrics['updated']++;

                return;
            }
        }
        $this->metrics['skipped']++;
    }

    private function summary(): void
    {
        $entity = AccountingEntity::query()->where('code', self::ENTITY)->first();
        $this->command?->table(['Control', 'Value'], [
            ['Source / target', 'mrj_prod_db / '.DB::connection()->getDatabaseName()],
            ['Static created / updated / unchanged', $this->metrics['created'].' / '.$this->metrics['updated'].' / '.$this->metrics['skipped']],
            ['Existing semantic records / duplicate creations', $this->metrics['existing'].' / 0'],
            ['Financial fact replayed', (string) $this->metrics['replayed']],
            ['Journal / JournalLine / Ledger', $entity ? DB::table('financial_v2_journals')->where('accounting_entity_id', $entity->id)->count().' / '.DB::table('financial_v2_journal_lines')->where('accounting_entity_id', $entity->id)->count().' / '.DB::table('financial_v2_ledger_entries')->where('accounting_entity_id', $entity->id)->count() : '0 / 0 / 0'],
            ['Allocation / Realization / History', $entity ? DB::table('financial_v2_budget_allocations')->where('accounting_entity_id', $entity->id)->count().' / '.DB::table('financial_v2_fund_realizations')->where('accounting_entity_id', $entity->id)->count().' / '.DB::table('financial_v2_historical_fund_histories')->where('accounting_entity_id', $entity->id)->count() : '0 / 0 / 0'],
        ]);
    }
}
