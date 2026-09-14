<?php

namespace Database\Seeders;

use App\Domain\FinancialV2\BudgetAllocationService;
use App\Domain\FinancialV2\EvidenceService;
use App\Domain\FinancialV2\FinancialTransactionLifecycleService;
use App\Domain\FinancialV2\MrjZiswafOpeningPosition;
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
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Replays the current raudhotu_mrj_db baseline exported by financial-v2:export-seed.
 * Configuration, source history, and retained non-financial Allocation source
 * state are replayed directly. Opening balances and posted facts are recreated
 * through OpeningBalanceService, lifecycle, and the canonical PostingEngine.
 * No Journal/JournalLine/Ledger raw writer exists here.
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
        $this->data = self::governedSnapshot();
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
        if (! ((app()->environment(['local', 'development']) && $database === 'raudhotu_mrj_db') || (app()->environment('testing') && $database === 'mrj_test_db'))) {
            throw new RuntimeException("FinancialV2Seeder may run only on local raudhotu_mrj_db or testing mrj_test_db; current database: {$database}.");
        }
    }

    /** @return array<string, mixed> */
    public static function governedSnapshot(): array
    {
        $path = __DIR__.'/FinancialV2/current_mrj_financial_v2_snapshot.php';
        if (! is_file($path)) {
            throw new RuntimeException('Missing Financial V2 snapshot. Run php artisan financial-v2:export-seed on local raudhotu_mrj_db.');
        }
        $snapshot = require $path;
        if (! is_array($snapshot)) {
            throw new RuntimeException('Financial V2 snapshot must return an array.');
        }

        $snapshot = self::applyPhase126CashTromolSourceSemantics($snapshot);

        return self::applyLegacyProgramLifecycleSemantics($snapshot);
    }

    /**
     * The Phase 12 capture used the Financial V2 configuration cutover as the
     * business start for every Program. Santunan Anak Yatim Bulanan is an
     * established legacy Program, so null is the accurate unbounded/unknown
     * historical start. Rule and policy versions retain their own dates.
     *
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    private static function applyLegacyProgramLifecycleSemantics(array $snapshot): array
    {
        foreach ($snapshot['tables']['financial_v2_programs'] ?? [] as $index => $program) {
            if (($program['code'] ?? null) === 'SANTUNAN-YATIM-BULANAN') {
                $snapshot['tables']['financial_v2_programs'][$index]['start_date'] = null;
            }
        }

        return $snapshot;
    }

    /**
     * The exported Phase 12.5 capture is retained as a historical archive.
     * Its Cash Tromol mapping and its matching IFT were superseded by the
     * owner-confirmed Phase 12.6 source semantics: Cash Tromol is an existing
     * Dhuafa & Anak Yatim opening position.  Seed replay must therefore begin
     * with the corrected opening dimensions and replay only the genuine
     * Rp1.200.000 Inter-Fund reclassification.
     *
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    private static function applyPhase126CashTromolSourceSemantics(array $snapshot): array
    {
        $funds = collect($snapshot['tables']['financial_v2_funds'] ?? [])->keyBy('code');
        $infaq = $funds->get('INFAQ-TROMOL');
        $dhuafa = $funds->get('DHUAFA');
        if (! is_array($infaq) || ! is_array($dhuafa)) {
            throw new RuntimeException('Phase 12.6 seed normalization requires INFAQ-TROMOL and DHUAFA Funds.');
        }

        $cashSourceReference = 'Sisa Alokasi Dana!D66:E66';
        $cashAmount = '2653000.00';
        $cashFundId = (string) $dhuafa['id'];
        $infaqFundId = (string) $infaq['id'];
        $openingFunds = collect(MrjZiswafOpeningPosition::funds())->keyBy('code');
        $infaqOpening = $openingFunds->get('INFAQ-TROMOL');
        $dhuafaOpening = $openingFunds->get('DHUAFA');
        if (! is_array($infaqOpening) || ! is_array($dhuafaOpening)) {
            throw new RuntimeException('Phase 12.6 seed normalization requires governed Cash Tromol opening positions.');
        }
        foreach ($snapshot['tables']['financial_v2_funds'] as $index => $fund) {
            if (($fund['id'] ?? null) === $cashFundId) {
                $snapshot['tables']['financial_v2_funds'][$index]['name'] = $dhuafaOpening['name'];
            }
        }

        foreach ($snapshot['opening_balance']['lines'] ?? [] as $index => $line) {
            if (($line['fund_id'] ?? null) !== $infaqFundId
                || ! str_contains((string) ($line['source_reference'] ?? ''), 'Cash Tromol Yatim')) {
                continue;
            }

            $snapshot['opening_balance']['lines'][$index]['fund_id'] = $cashFundId;
            $snapshot['opening_balance']['lines'][$index]['mapping_ref'] = self::cashOpeningReference($cashSourceReference);
            $snapshot['opening_balance']['lines'][$index]['source_reference'] = self::cashOpeningReference($cashSourceReference);
            $snapshot['opening_balance']['lines'][$index]['line_description'] = 'Saldo awal Dana Dhuafa & Anak Yatim pada Cash Tromol Yatim; sumber '.$cashSourceReference;
        }

        foreach ($snapshot['opening_balance']['lines'] ?? [] as $index => $line) {
            if (($line['financial_account_id'] ?? null) !== null || ! str_contains((string) ($line['source_reference'] ?? ''), 'FUND-NET-ASSET')) {
                continue;
            }

            $fundCode = ($line['fund_id'] ?? null) === $infaqFundId
                ? 'INFAQ-TROMOL'
                : (($line['fund_id'] ?? null) === $cashFundId ? 'DHUAFA' : null);
            if (! $fundCode) {
                continue;
            }
            $opening = $fundCode === 'INFAQ-TROMOL' ? $infaqOpening : $dhuafaOpening;
            $reference = 'ZISWAF UPDATE 3.xlsx|'.$opening['source_range'].'|FUND-NET-ASSET';
            $snapshot['opening_balance']['lines'][$index]['debit_amount'] = '0.00';
            $snapshot['opening_balance']['lines'][$index]['credit_amount'] = $opening['total'];
            $snapshot['opening_balance']['lines'][$index]['source_debit_amount'] = '0.00';
            $snapshot['opening_balance']['lines'][$index]['source_credit_amount'] = $opening['total'];
            $snapshot['opening_balance']['lines'][$index]['mapping_ref'] = $reference;
            $snapshot['opening_balance']['lines'][$index]['source_reference'] = $reference;
            $snapshot['opening_balance']['lines'][$index]['line_description'] = "Saldo dana awal {$opening['name']}; sumber {$opening['source_range']}";
        }

        foreach ($snapshot['opening_balance']['mappings'] ?? [] as $index => $mapping) {
            if (! str_contains((string) ($mapping['legacy_record_ref'] ?? ''), 'Cash Tromol Yatim')) {
                continue;
            }

            $targetType = (string) ($mapping['target_entity_type'] ?? '');
            $snapshot['opening_balance']['mappings'][$index]['legacy_record_ref'] = self::cashOpeningReference($cashSourceReference).'|'.$targetType;
            if ($targetType !== 'fund') {
                continue;
            }
            $snapshot['opening_balance']['mappings'][$index]['legacy_value'] = 'DHUAFA';
            $snapshot['opening_balance']['mappings'][$index]['target_entity_id'] = $cashFundId;
            $snapshot['opening_balance']['mappings'][$index]['rationale'] = 'Phase 12.6 source mapping: Cash Tromol is the original Dhuafa & Anak Yatim cash composition, not an Inter-Fund Transfer.';
        }

        foreach ($snapshot['opening_balance']['mappings'] ?? [] as $index => $mapping) {
            $reference = (string) ($mapping['legacy_record_ref'] ?? '');
            $fundCode = str_contains($reference, 'Sisa Alokasi Dana!A6:D7|FUND-NET-ASSET')
                ? 'INFAQ-TROMOL'
                : (str_contains($reference, 'Sisa Alokasi Dana!A11:D11|FUND-NET-ASSET') ? 'DHUAFA' : null);
            if (! $fundCode) {
                continue;
            }
            $opening = $fundCode === 'INFAQ-TROMOL' ? $infaqOpening : $dhuafaOpening;
            $targetType = (string) ($mapping['target_entity_type'] ?? '');
            $snapshot['opening_balance']['mappings'][$index]['legacy_record_ref'] = 'ZISWAF UPDATE 3.xlsx|'.$opening['source_range'].'|FUND-NET-ASSET|'.$targetType;
            if ($targetType === 'fund') {
                $snapshot['opening_balance']['mappings'][$index]['target_entity_id'] = $fundCode === 'INFAQ-TROMOL' ? $infaqFundId : $cashFundId;
                $snapshot['opening_balance']['mappings'][$index]['legacy_value'] = $fundCode;
            }
            $snapshot['opening_balance']['mappings'][$index]['rationale'] = 'Phase 12.6 source mapping: '.($opening['name']).' opening Fund position.';
        }

        foreach ($snapshot['historical_fund_histories'] ?? [] as $index => $history) {
            if (($history['source_reference'] ?? null) !== $cashSourceReference) {
                continue;
            }

            $payload = json_decode((string) ($history['source_payload'] ?? '{}'), true);
            if (! is_array($payload)) {
                $payload = [];
            }
            $payload['kind'] = 'account_position';
            $payload['financial_account_code'] = 'CASH-ZISWAF';
            $payload['position_treatment'] = 'original_fund_account_composition';
            $payload['notes'] = 'Komposisi rekening/kas Dana Dhuafa & Anak Yatim sejak posisi awal; bukan penerimaan, pengeluaran, atau pemindahan Dana.';

            $snapshot['historical_fund_histories'][$index]['fund_id'] = $cashFundId;
            $snapshot['historical_fund_histories'][$index]['source_fund_code'] = 'DHUAFA';
            $snapshot['historical_fund_histories'][$index]['source_key'] = hash('sha256', implode('|', [
                (string) ($history['source_hash'] ?? ''), 'DHUAFA', 'account_position', $cashSourceReference, 'Cash Tromol Yatim', $cashAmount,
            ]));
            $snapshot['historical_fund_histories'][$index]['source_payload'] = json_encode($payload, JSON_THROW_ON_ERROR);
            $snapshot['historical_fund_histories'][$index]['notes'] = $payload['notes'];
            $snapshot['historical_fund_histories'][$index]['status'] = 'active';
            $snapshot['historical_fund_histories'][$index]['correction_reason'] = null;
            $snapshot['historical_fund_histories'][$index]['corrected_at'] = null;
        }

        foreach ($snapshot['historical_fund_histories'] ?? [] as $index => $history) {
            if (($history['description'] ?? null) !== 'Pemindahan Dana dari alokasi Infaq & Tromol'
                || ($history['source_reference'] ?? null) !== 'Sisa Alokasi Dana Ziswaf DKM MRJ TCE (per 16 agustus 2026).pdf'
                || ! in_array((string) ($history['entry_kind'] ?? ''), ['receipt', 'usage'], true)
                || (string) ($history['amount'] ?? '') !== '1200000.00') {
                continue;
            }

            // The same reclassification is now an immutable, canonical IFT.
            // Retain these Phase 12.5 source-only explanation records as
            // audit history, but prevent a second public/admin presentation.
            $snapshot['historical_fund_histories'][$index]['status'] = 'void';
            $snapshot['historical_fund_histories'][$index]['correction_reason'] = 'Phase 12.6: superseded source-only duplicate. The single official Rp1.200.000 reclassification is presented from Posted V2 Ledger.';
        }

        $snapshot['posted_interfund_transfers'] = array_values(array_filter(
            $snapshot['posted_interfund_transfers'] ?? [],
            fn (array $item): bool => ($item['transaction']['source_reference'] ?? null) !== 'MRJ-P12.5-CASH-ATTRIBUTION-2026-08-16',
        ));

        // The historic capture contained one additional, now-superseded Cash
        // IFT. The governed source starts with Cash already attributed to
        // DHUAFA, so only the genuine Rp1.200.000 IFT is replayed.
        $snapshot['expected']['posted_fact_counts'] = [
            'journals' => 2,
            'journal_lines' => 15,
            'ledger_entries' => 15,
            'vouchers' => 2,
        ];

        return $snapshot;
    }

    private static function cashOpeningReference(string $cashSourceReference): string
    {
        return 'ZISWAF UPDATE 3.xlsx|'.$cashSourceReference.'|Cash Tromol Yatim';
    }

    private function assertSnapshot(): void
    {
        if (($this->data['schema_version'] ?? null) !== 1
            || ($this->data['source']['database'] ?? null) !== 'raudhotu_mrj_db'
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
        foreach ($this->data['operational_allocations'] as $source) {
            $allocationSource = $source['allocation'];
            if (count($source['versions']) !== 1) {
                throw new RuntimeException('Multiple allocation versions need an explicit replay extension.');
            }
            $versionSource = $source['versions'][0];
            $allocation = BudgetAllocation::query()->where('accounting_entity_id', $entityId)->where('allocation_reference', $allocationSource['allocation_reference'])->first();
            if (! $allocation) {
                // These are governed source snapshots, including an allocation
                // whose historical effective date predates the current PAY
                // policy. They are non-financial operational facts; replaying
                // them must not reinterpret policy or create Journal/Ledger.
                // A cancelled source is temporarily restored as approved so
                // its retained realization records can be linked, then the
                // governed cancellation is replayed below.
                $pendingCancellation = $allocationSource['status'] === 'cancelled';
                DB::table('financial_v2_budget_allocations')->insert(array_replace($this->withoutSourceUsers($allocationSource), $pendingCancellation ? [
                    'status' => 'approved',
                    'cancelled_at' => null,
                    'cancelled_by_user_id' => null,
                    'cancellation_reason' => null,
                ] : []));
                DB::table('financial_v2_budget_allocation_versions')->insert(array_replace($this->withoutSourceUsers($versionSource), $pendingCancellation ? ['status' => 'approved'] : []));
                DB::table('financial_v2_budget_allocation_fundings')->insert([
                    'id' => (string) Str::uuid(),
                    'accounting_entity_id' => $entityId,
                    'budget_allocation_version_id' => $versionSource['id'],
                    'fund_id' => $allocationSource['fund_id'],
                    'line_no' => 1,
                    'amount' => $versionSource['allocated_amount'],
                    'note' => 'Backward-compatible governed snapshot funding source.',
                    'source_reference' => $allocationSource['allocation_reference'],
                    'created_at' => $versionSource['created_at'],
                    'updated_at' => $versionSource['updated_at'],
                    'created_by_user_id' => null,
                    'updated_by_user_id' => null,
                ]);
                $allocation = BudgetAllocation::query()->findOrFail($allocationSource['id']);
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
            throw new RuntimeException('Financial V2 Fund/Account semantic tie-out failed. Expected/actual Funds: '
                .json_encode([$this->data['expected']['fund_balances'], $funds], JSON_THROW_ON_ERROR)
                .'; expected/actual Financial Accounts: '
                .json_encode([$this->data['expected']['financial_account_balances'], $accounts], JSON_THROW_ON_ERROR));
        }
    }

    /** @param array<string, mixed> $row */
    private function upsert(string $table, array $row): void
    {
        if (! isset($row['id'])) {
            throw new RuntimeException("{$table} snapshot row lacks a stable UUID.");
        }
        $row = $this->withoutSourceUsers($row);
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

    /** @param array<string, mixed> $row */
    private function withoutSourceUsers(array $row): array
    {
        foreach (array_keys($row) as $key) {
            if (str_ends_with($key, '_by_user_id')) {
                $row[$key] = null;
            }
        }

        return $row;
    }

    private function summary(): void
    {
        $entity = AccountingEntity::query()->where('code', self::ENTITY)->first();
        $this->command?->table(['Control', 'Value'], [
            ['Source / target', 'raudhotu_mrj_db / '.DB::connection()->getDatabaseName()],
            ['Static created / updated / unchanged', $this->metrics['created'].' / '.$this->metrics['updated'].' / '.$this->metrics['skipped']],
            ['Existing semantic records / duplicate creations', $this->metrics['existing'].' / 0'],
            ['Financial fact replayed', (string) $this->metrics['replayed']],
            ['Journal / JournalLine / Ledger', $entity ? DB::table('financial_v2_journals')->where('accounting_entity_id', $entity->id)->count().' / '.DB::table('financial_v2_journal_lines')->where('accounting_entity_id', $entity->id)->count().' / '.DB::table('financial_v2_ledger_entries')->where('accounting_entity_id', $entity->id)->count() : '0 / 0 / 0'],
            ['Allocation / Realization / History', $entity ? DB::table('financial_v2_budget_allocations')->where('accounting_entity_id', $entity->id)->count().' / '.DB::table('financial_v2_fund_realizations')->where('accounting_entity_id', $entity->id)->count().' / '.DB::table('financial_v2_historical_fund_histories')->where('accounting_entity_id', $entity->id)->count() : '0 / 0 / 0'],
        ]);
    }
}
