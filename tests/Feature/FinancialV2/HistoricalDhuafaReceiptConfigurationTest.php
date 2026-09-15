<?php

use App\Domain\FinancialV2\ConfigureMrjHistoricalDhuafaReceiptService;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\AuditEvent;
use App\Models\FinancialV2\Category;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\FundPolicyRule;
use App\Models\FinancialV2\FundPolicyVersion;
use App\Models\FinancialV2\Program;
use App\Models\FinancialV2\TransactionType;
use App\Models\User;
use Database\Seeders\ConfigureMrjHistoricalDhuafaReceiptSeeder;
use Database\Seeders\FinancialV2Seeder;
use Illuminate\Support\Facades\DB;

/** @return array<string, int> */
function historicalDhuafaFactCounts(): array
{
    return collect([
        'transactions' => 'financial_v2_transactions',
        'journals' => 'financial_v2_journals',
        'journal_lines' => 'financial_v2_journal_lines',
        'ledger_entries' => 'financial_v2_ledger_entries',
        'vouchers' => 'financial_v2_vouchers',
        'allocations' => 'financial_v2_budget_allocations',
    ])->mapWithKeys(fn (string $table, string $name): array => [$name => DB::table($table)->count()])->all();
}

test('historical DHUAFA receipt resolves through version 6 after unused version 5 is safely deleted', function () {
    app(FinancialV2Seeder::class)->setContainer(app())->run();

    $entity = AccountingEntity::query()->where('code', 'MRJ-ACTUAL')->sole();
    $fund = Fund::query()->where('accounting_entity_id', $entity->id)->where('code', 'DHUAFA')->sole();
    $type = TransactionType::query()->where('accounting_entity_id', $entity->id)->where('code', 'RCV')->sole();
    $category = Category::query()->where('accounting_entity_id', $entity->id)->where('code', 'RCV-DONASI')->sole();
    $program = Program::query()->where('accounting_entity_id', $entity->id)->where('code', 'SANTUNAN-YATIM-BULANAN')->sole();

    $version5 = FundPolicyVersion::query()->create([
        'accounting_entity_id' => $entity->id,
        'fund_id' => $fund->id,
        'version_no' => 5,
        'effective_from' => '2026-07-10',
        'effective_to' => '2026-07-10',
        'policy_document_ref' => 'JULI-HISTORI_TRANSAKSI_1789270210045',
        'allowed_matrix_ref' => '-',
        'exception_approval_level' => 'financial-governance',
        'status' => 'superseded',
        'approved_at' => now(),
    ]);
    FundPolicyRule::query()->create([
        'accounting_entity_id' => $entity->id,
        'fund_policy_version_id' => $version5->id,
        'transaction_type_id' => $type->id,
        'category_id' => $category->id,
        'program_id' => $program->id,
        'decision' => 'allowed',
    ]);

    $version6 = FundPolicyVersion::query()->create([
        'accounting_entity_id' => $entity->id,
        'fund_id' => $fund->id,
        'version_no' => 6,
        'effective_from' => '2026-07-11',
        'effective_to' => '2026-07-30',
        'policy_document_ref' => 'HISTORICAL-RCV-DHUAFA-V6',
        'allowed_matrix_ref' => 'RCV-DONASI; wildcard Program',
        'exception_approval_level' => 'financial-governance',
        'status' => 'effective',
        'approved_at' => now(),
    ]);
    FundPolicyRule::query()->create([
        'accounting_entity_id' => $entity->id,
        'fund_policy_version_id' => $version6->id,
        'transaction_type_id' => $type->id,
        'category_id' => $category->id,
        'program_id' => null,
        'decision' => 'allowed',
    ]);

    $factsBefore = historicalDhuafaFactCounts();
    $configuration = app(ConfigureMrjHistoricalDhuafaReceiptService::class);
    $result = $configuration->configure();

    expect($result['ready'])->toBeTrue()
        ->and($result['dates']['2026-07-11'])->toMatchArray(['status' => 'READY', 'posting_rule_version' => 2, 'fund_policy_version' => 6])
        ->and($result['dates']['2026-07-15']['status'])->toBe('READY')
        ->and($result['dates']['2026-07-30']['status'])->toBe('READY')
        ->and($result['dates']['2026-07-31']['status'])->toBe('MISSING')
        ->and($result['dates']['2026-08-14']['status'])->toBe('MISSING')
        ->and($result['dates']['2026-08-15'])->toMatchArray(['status' => 'READY', 'posting_rule_version' => 1, 'fund_policy_version' => 2])
        ->and($result['policy_overlaps'])->toBe([])
        ->and(historicalDhuafaFactCounts())->toBe($factsBefore);

    foreach (['2026-07-11', '2026-07-15', '2026-07-30', '2026-07-31', '2026-08-14', '2026-08-15'] as $date) {
        $policyCount = FundPolicyVersion::query()
            ->where('fund_id', $fund->id)
            ->whereDate('effective_from', '<=', $date)
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date))
            ->count();
        expect($policyCount)->toBe(1);
    }

    expect(FundPolicyVersion::find($version5->id))->toBeNull()
        ->and(FundPolicyVersion::find($version6->id)?->status)->toBe('effective')
        ->and(historicalDhuafaFactCounts())->toBe($factsBefore)
        ->and($configuration->configure()['changed'])->toBeFalse()
        ->and(historicalDhuafaFactCounts())->toBe($factsBefore);

    expect(AuditEvent::query()
        ->where('event_type', 'fund_policy_version_deleted')
        ->where('target_id', $version5->id)
        ->exists())->toBeTrue();
});

test('production UI and seeder provision missing historical DHUAFA configuration by business codes idempotently', function () {
    app(FinancialV2Seeder::class)->setContainer(app())->run();

    $entity = AccountingEntity::query()->where('code', 'MRJ-ACTUAL')->sole();
    $user = User::factory()->create();
    $factsBefore = historicalDhuafaFactCounts();
    $route = app('router')->getRoutes()->getByName('financial-v2.configuration.provision-historical-dhuafa');

    expect($route->methods())->toBe(['POST'])
        ->and($route->gatherMiddleware())->toContain('web', 'auth');
    $this->post(route('financial-v2.configuration.provision-historical-dhuafa'), ['entity' => $entity->id])
        ->assertRedirect(route('login'));

    $this->actingAs($user)
        ->get(route('financial-v2.configuration.index', ['entity' => $entity->id]))
        ->assertOk()
        ->assertSee('Provision Konfigurasi Historis DHUAFA');

    $this->actingAs($user)
        ->post(route('financial-v2.configuration.provision-historical-dhuafa'), ['entity' => $entity->id])
        ->assertRedirect(route('financial-v2.configuration.index', ['entity' => $entity->id]));

    $status = app(ConfigureMrjHistoricalDhuafaReceiptService::class)->status();
    $fund = Fund::query()->where('accounting_entity_id', $entity->id)->where('code', 'DHUAFA')->sole();
    $type = TransactionType::query()->where('accounting_entity_id', $entity->id)->where('code', 'RCV')->sole();
    $category = Category::query()->where('accounting_entity_id', $entity->id)->where('code', 'RCV-DONASI')->sole();
    $historicalPolicy = FundPolicyVersion::query()
        ->where('fund_id', $fund->id)
        ->whereDate('effective_from', '2026-07-11')
        ->whereDate('effective_to', '2026-07-30')
        ->sole();

    expect($status['ready'])->toBeTrue()
        ->and($status['configuration'])->toBe([
            'entity' => 'MRJ-ACTUAL',
            'financial_account' => 'BNI-ZISWAF',
            'fund' => 'DHUAFA',
            'transaction_type' => 'RCV',
            'category' => 'RCV-DONASI',
            'program' => null,
            'date' => '2026-07-11',
        ])
        ->and($status['dates']['2026-07-11']['status'])->toBe('READY')
        ->and(FundPolicyRule::query()
            ->where('fund_policy_version_id', $historicalPolicy->id)
            ->where('transaction_type_id', $type->id)
            ->where('category_id', $category->id)
            ->whereNull('program_id')
            ->where('decision', 'allowed')
            ->exists())->toBeTrue()
        ->and(AuditEvent::query()
            ->where('event_type', 'historical_dhuafa_configuration_provisioned')
            ->where('actor_user_id', $user->id)
            ->exists())->toBeTrue()
        ->and(historicalDhuafaFactCounts())->toBe($factsBefore);

    $configurationCounts = [
        FundPolicyVersion::query()->where('fund_id', $fund->id)->count(),
        FundPolicyRule::query()->where('fund_policy_version_id', $historicalPolicy->id)->count(),
        AuditEvent::query()->where('event_type', 'historical_dhuafa_configuration_provisioned')->count(),
    ];
    $this->seed(ConfigureMrjHistoricalDhuafaReceiptSeeder::class);
    $this->seed(ConfigureMrjHistoricalDhuafaReceiptSeeder::class);

    expect([
        FundPolicyVersion::query()->where('fund_id', $fund->id)->count(),
        FundPolicyRule::query()->where('fund_policy_version_id', $historicalPolicy->id)->count(),
        AuditEvent::query()->where('event_type', 'historical_dhuafa_configuration_provisioned')->count(),
    ])->toBe($configurationCounts)
        ->and(historicalDhuafaFactCounts())->toBe($factsBefore);

    $this->actingAs($user)
        ->get(route('financial-v2.configuration.index', ['entity' => $entity->id]))
        ->assertOk()
        ->assertSee('Siap digunakan')
        ->assertDontSee('>Provision Konfigurasi Historis DHUAFA<', false);
});
