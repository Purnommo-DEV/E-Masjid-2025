<?php

use App\Domain\FinancialV2\Reporting\FinancialReportService;
use App\Models\FinancialV2\AccountingEntity;
use Database\Seeders\FinancialV2Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/** @return array<string, int> */
function financialV2BaselineCounts(string $entityId): array
{
    return [
        'funds' => DB::table('financial_v2_funds')->where('accounting_entity_id', $entityId)->count(),
        'accounts' => DB::table('financial_v2_financial_accounts')->where('accounting_entity_id', $entityId)->count(),
        'programs' => DB::table('financial_v2_programs')->where('accounting_entity_id', $entityId)->count(),
        'categories' => DB::table('financial_v2_categories')->where('accounting_entity_id', $entityId)->count(),
        'history' => DB::table('financial_v2_historical_fund_histories')->where('accounting_entity_id', $entityId)->count(),
        'allocations' => DB::table('financial_v2_budget_allocations')->where('accounting_entity_id', $entityId)->count(),
        'realisations' => DB::table('financial_v2_fund_realizations')->where('accounting_entity_id', $entityId)->count(),
        'transactions' => DB::table('financial_v2_transactions')->where('accounting_entity_id', $entityId)->count(),
        'journals' => DB::table('financial_v2_journals')->where('accounting_entity_id', $entityId)->count(),
        'journal_lines' => DB::table('financial_v2_journal_lines')->where('accounting_entity_id', $entityId)->count(),
        'ledger' => DB::table('financial_v2_ledger_entries')->where('accounting_entity_id', $entityId)->count(),
        'vouchers' => DB::table('financial_v2_vouchers')->where('accounting_entity_id', $entityId)->count(),
    ];
}

/** @param array<string, mixed> $row @return array<string, mixed> */
function financialV2ComparableSnapshotRow(array $row): array
{
    $ignored = collect(array_keys($row))
        ->filter(fn (string $key): bool => str_ends_with($key, '_by_user_id') || in_array($key, ['created_at', 'updated_at', 'approved_at', 'reviewed_at', 'cancelled_at'], true))
        ->all();

    return Arr::except($row, $ignored);
}

test('Financial V2 snapshot seeder replays the current MRJ baseline idempotently through canonical writers', function () {
    /** @var array<string, mixed> $snapshot */
    $snapshot = require database_path('seeders/FinancialV2/current_mrj_financial_v2_snapshot.php');
    app(FinancialV2Seeder::class)->setContainer(app())->run();
    $entity = AccountingEntity::query()->where('code', 'MRJ-ACTUAL')->sole();
    $first = financialV2BaselineCounts($entity->id);

    expect($first)->toMatchArray([
        'funds' => 11, 'accounts' => 10, 'programs' => 12, 'categories' => 31,
        'history' => 33, 'allocations' => 2, 'realisations' => 4, 'transactions' => 7,
        'journals' => 3, 'journal_lines' => 17, 'ledger' => 17, 'vouchers' => 3,
    ]);

    foreach ($snapshot['tables'] as $table => $rows) {
        foreach ($rows as $row) {
            $actual = (array) DB::table($table)->where('id', $row['id'])->first();
            expect(financialV2ComparableSnapshotRow($actual))->toMatchArray(financialV2ComparableSnapshotRow($row));
        }
    }
    foreach ($snapshot['historical_fund_histories'] as $row) {
        $actual = (array) DB::table('financial_v2_historical_fund_histories')->where('id', $row['id'])->first();
        expect(financialV2ComparableSnapshotRow($actual))->toMatchArray(financialV2ComparableSnapshotRow($row));
    }

    $asOf = '2026-08-16';
    $funds = collect(app(FinancialReportService::class)->report('fund-balance', $entity->id, '2026-01-01', $asOf)['data']['rows'])
        ->mapWithKeys(fn (array $row): array => [$row['code'] => (string) $row['fund_balance']])
        ->all();
    $accounts = collect(app(FinancialReportService::class)->report('account-balance', $entity->id, '2026-01-01', $asOf)['data']['rows'])
        ->mapWithKeys(fn (array $row): array => [$row['code'] => (string) $row['closing_balance']])
        ->all();
    expect($funds)->toBe($snapshot['expected']['fund_balances'])
        ->and($accounts)->toBe($snapshot['expected']['financial_account_balances'])
        ->and(DB::table('financial_v2_opening_balance_batches')->where('accounting_entity_id', $entity->id)->where('cutover_reference', $snapshot['opening_balance']['batch']['cutover_reference'])->where('status', 'posted')->count())->toBe(1)
        ->and(DB::table('financial_v2_budget_allocations')->where('accounting_entity_id', $entity->id)->pluck('allocation_reference')->sort()->values()->all())->toBe(collect($snapshot['operational_allocations'])->pluck('allocation.allocation_reference')->sort()->values()->all());
    foreach ($snapshot['operational_allocations'] as $source) {
        $sourceAllocation = $source['allocation'];
        $actualAllocation = (array) DB::table('financial_v2_budget_allocations')->where('allocation_reference', $sourceAllocation['allocation_reference'])->first();
        expect(Arr::only($actualAllocation, ['fund_id', 'program_id', 'account_id', 'category_id', 'allocation_reference', 'status', 'reason', 'cancellation_reason']))
            ->toMatchArray(Arr::only($sourceAllocation, ['fund_id', 'program_id', 'account_id', 'category_id', 'allocation_reference', 'status', 'reason', 'cancellation_reason']));
        $sourceVersion = $source['versions'][0];
        $actualVersion = (array) DB::table('financial_v2_budget_allocation_versions')->where('budget_allocation_id', $actualAllocation['id'])->where('version_no', $sourceVersion['version_no'])->first();
        expect(Arr::only($actualVersion, ['version_no', 'allocated_amount', 'effective_from', 'effective_to', 'status', 'reason']))
            ->toMatchArray(Arr::only($sourceVersion, ['version_no', 'allocated_amount', 'effective_from', 'effective_to', 'status', 'reason']));
    }
    foreach ($snapshot['operational_realizations'] as $source) {
        $sourceTransaction = $source['transaction'];
        $actualTransaction = (array) DB::table('financial_v2_transactions')->where('source_reference', $sourceTransaction['source_reference'])->first();
        expect(Arr::only($actualTransaction, ['transaction_type_id', 'status', 'source_reference', 'business_date', 'accounting_date', 'gross_amount', 'primary_financial_account_id', 'counterparty_id', 'category_id']))
            ->toMatchArray(Arr::only($sourceTransaction, ['transaction_type_id', 'status', 'source_reference', 'business_date', 'accounting_date', 'gross_amount', 'primary_financial_account_id', 'counterparty_id', 'category_id']));
        $actualRealization = (array) DB::table('financial_v2_fund_realizations')->where('transaction_id', $actualTransaction['id'])->first();
        expect($actualRealization['status'])->toBe($source['realization']['status']);
    }

    app(FinancialV2Seeder::class)->setContainer(app())->run();
    expect(financialV2BaselineCounts($entity->id))->toBe($first)
        ->and(DB::table('financial_v2_vouchers')->where('accounting_entity_id', $entity->id)->distinct('voucher_number')->count('voucher_number'))->toBe(3)
        ->and(DB::table('financial_v2_ledger_entries as ledger')->leftJoin('financial_v2_journal_lines as line', 'line.id', '=', 'ledger.journal_line_id')->where('ledger.accounting_entity_id', $entity->id)->whereNull('line.id')->count())->toBe(0);
});
