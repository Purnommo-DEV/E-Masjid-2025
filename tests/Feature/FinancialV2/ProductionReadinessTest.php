<?php

use App\Domain\FinancialV2\ProductionReadiness\FinancialV2PreflightChecker;
use App\Models\FinancialV2\EvidenceRequirement;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\LedgerEntry;
use Illuminate\Support\Facades\DB;
use Tests\Support\UatFinancialFixture;

test('production preflight refuses a non-target environment before querying its configured database', function () {
    DB::enableQueryLog();
    DB::flushQueryLog();
    $result = app(FinancialV2PreflightChecker::class)->inspect('production');
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    expect($result['mode'])->toBe('refused')
        ->and($result['read_only'])->toBeTrue()
        ->and($result['checks'][0]['id'])->toBe('environment_gate')
        ->and($result['checks'][0]['detail'])->toContain('No database query was executed.')
        ->and($queries)->toBe([]);
});

test('production preflight simulation is read-only and reports actual schema/services plus missing production evidence honestly', function () {
    $context = UatFinancialFixture::context();
    EvidenceRequirement::create([
        'accounting_entity_id' => $context['entity']->id,
        'posting_rule_version_id' => $context['receiptVersion']->id,
        'evidence_type' => 'receipt',
        'minimum_count' => 1,
    ]);
    $before = [
        'journals' => Journal::count(),
        'ledger' => LedgerEntry::count(),
    ];

    DB::enableQueryLog();
    DB::flushQueryLog();
    $result = app(FinancialV2PreflightChecker::class)->inspect('production', true);
    $queries = collect(DB::getQueryLog())->pluck('query')->map(fn (string $query) => strtolower(ltrim($query)));
    DB::disableQueryLog();
    $checks = collect($result['checks'])->keyBy('id');
    $masters = collect($result['master_data'])->keyBy('id');

    expect($result['mode'])->toBe('simulation')
        ->and($result['read_only'])->toBeTrue()
        ->and($result['technical_readiness'])->toBe('pass')
        ->and($result['overall'])->toBe('not_ready')
        ->and($checks->only(['financial_v2_migrations', 'financial_v2_tables', 'financial_v2_indexes', 'financial_v2_constraints', 'no_boot_migration', 'test_isolation', 'legacy_isolation'])->pluck('status')->unique()->all())->toBe(['pass'])
        ->and($masters->get('accounting_entity')['status'])->toBe('ready')
        ->and($masters->get('evidence_configuration')['status'])->toBe('ready')
        ->and($masters->get('fund_policy')['status'])->toBe('ready')
        ->and(['journals' => Journal::count(), 'ledger' => LedgerEntry::count()])->toBe($before)
        ->and($queries->contains(fn (string $query) => str_starts_with($query, 'insert ') || str_starts_with($query, 'update ') || str_starts_with($query, 'delete ') || str_starts_with($query, 'alter ') || str_starts_with($query, 'create ') || str_starts_with($query, 'drop ')))->toBeFalse();
});
