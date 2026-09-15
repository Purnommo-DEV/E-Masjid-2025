<?php

use App\Domain\FinancialV2\ConfigureMrjHistoricalDhuafaReceiptService;
use App\Domain\FinancialV2\CorrectMrjLegacyProgramLifecycleService;
use App\Domain\FinancialV2\FinancialPostingException;
use App\Domain\FinancialV2\FinancialTransactionConfigurationResolver;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\Category;
use App\Models\FinancialV2\FinancialAccount;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\Program;
use App\Models\FinancialV2\TransactionType;
use App\Models\User;
use Database\Seeders\CorrectMrjLegacyProgramLifecycleSeeder;
use Database\Seeders\FinancialV2Seeder;
use Illuminate\Support\Facades\DB;

/** @return array<string, int> */
function programLifecycleFactCounts(): array
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

test('legacy Program lifecycle is corrected without changing facts and resolves for the July receipt', function () {
    app(FinancialV2Seeder::class)->setContainer(app())->run();

    $entity = AccountingEntity::query()->where('code', 'MRJ-ACTUAL')->sole();
    $program = Program::query()->where('accounting_entity_id', $entity->id)->where('code', 'SANTUNAN-YATIM-BULANAN')->sole();
    expect($program->start_date)->toBeNull()
        ->and($program->isBusinessActiveOn('2026-07-11'))->toBeTrue();

    // Recreate the erroneous Phase 12 value to exercise the deployable,
    // guarded data correction rather than relying only on seed normalization.
    $program->update(['start_date' => '2026-08-15']);
    $factsBefore = programLifecycleFactCounts();
    $correction = app(CorrectMrjLegacyProgramLifecycleService::class);
    $user = User::factory()->create();
    $route = app('router')->getRoutes()->getByName('financial-v2.configuration.correct-legacy-program-lifecycle');
    expect($route->methods())->toBe(['POST'])
        ->and($route->gatherMiddleware())->toContain('web', 'auth');
    $this->post(route('financial-v2.configuration.correct-legacy-program-lifecycle'), ['entity' => $entity->id])
        ->assertRedirect(route('login'));
    $this->actingAs($user)
        ->get(route('financial-v2.configuration.index', ['entity' => $entity->id]))
        ->assertOk()
        ->assertSee('Koreksi Lifecycle Program');
    $result = $this->actingAs($user)
        ->postJson(route('financial-v2.configuration.correct-legacy-program-lifecycle'), ['entity' => $entity->id])
        ->assertOk()
        ->assertJsonPath('ok', true)
        ->json('result');

    expect($result['changed'])->toBeTrue()
        ->and($result['program_start_date'])->toBeNull()
        ->and($result['program_business_active'])->toBeTrue()
        ->and(programLifecycleFactCounts())->toBe($factsBefore)
        ->and($correction->correct()['changed'])->toBeFalse()
        ->and(programLifecycleFactCounts())->toBe($factsBefore)
        ->and(\App\Models\FinancialV2\AuditEvent::query()
            ->where('event_type', 'legacy_program_lifecycle_corrected')
            ->where('actor_user_id', $user->id)
            ->where('after_summary', 'like', '%ADMIN_CONFIGURATION_PROVISION%')
            ->exists())->toBeTrue();

    app(ConfigureMrjHistoricalDhuafaReceiptService::class)->configure();
    $status = $correction->status();
    expect($status['resolver'])->toMatchArray([
        'status' => 'READY',
        'posting_rule_version' => 2,
        'fund_policy_version' => 5,
    ])->and(programLifecycleFactCounts())->toBe($factsBefore);

    $this->seed(CorrectMrjLegacyProgramLifecycleSeeder::class);
    expect(programLifecycleFactCounts())->toBe($factsBefore);
});

test('Program options use business lifecycle and the canonical configuration resolver', function () {
    app(FinancialV2Seeder::class)->setContainer(app())->run();
    app(ConfigureMrjHistoricalDhuafaReceiptService::class)->configure();

    $entity = AccountingEntity::query()->where('code', 'MRJ-ACTUAL')->sole();
    $type = TransactionType::query()->where('accounting_entity_id', $entity->id)->where('code', 'RCV')->sole();
    $account = FinancialAccount::query()->where('accounting_entity_id', $entity->id)->where('code', 'BNI-ZISWAF')->sole();
    $fund = Fund::query()->where('accounting_entity_id', $entity->id)->where('code', 'DHUAFA')->sole();
    $category = Category::query()->where('accounting_entity_id', $entity->id)->where('code', 'RCV-DONASI')->sole();
    $legacy = Program::query()->where('accounting_entity_id', $entity->id)->where('code', 'SANTUNAN-YATIM-BULANAN')->sole();
    $future = Program::query()->create([
        'accounting_entity_id' => $entity->id,
        'code' => 'PROGRAM-MULAI-AGUSTUS',
        'name' => 'Program Mulai Agustus',
        'start_date' => '2026-08-01',
        'end_date' => null,
        'status' => 'active',
    ]);
    $input = [
        'accounting_entity_id' => $entity->id,
        'transaction_type_id' => $type->id,
        'date' => '2026-07-11',
        'financial_account_id' => $account->id,
        'fund_id' => $fund->id,
        'category_id' => $category->id,
    ];
    $factsBefore = programLifecycleFactCounts();

    $resolved = app(FinancialTransactionConfigurationResolver::class)->resolve($input + ['program_id' => $legacy->id]);
    expect($resolved->postingRuleVersion->version_no)->toBe(2)
        ->and(fn () => app(FinancialTransactionConfigurationResolver::class)->resolve($input + ['program_id' => $future->id]))
        ->toThrow(FinancialPostingException::class);

    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('financial-v2.transactions.create', ['operation' => 'receipt', 'entity' => $entity->id]))
        ->assertOk()
        ->assertSee('data-program-options-url=', false)
        ->assertSee('data-type-code="RCV"', false);
    $response = $this->actingAs($user)->getJson(route('financial-v2.options', [
        'entity' => $entity->id,
        'type' => 'RCV',
        'date' => '2026-07-11',
        'financial_account_id' => $account->id,
        'fund_id' => $fund->id,
        'category_id' => $category->id,
    ]))->assertOk()->assertJsonPath('configuration_filtered', true);
    $programIds = collect($response->json('programs'))->pluck('id');

    expect($programIds)->toContain($legacy->id)
        ->not->toContain($future->id)
        ->and(programLifecycleFactCounts())->toBe($factsBefore);

    $this->actingAs($user)->postJson(route('financial-v2.preview'), [
        'entity' => $entity->id,
        'operation' => 'receipt',
        'date' => '2026-07-11',
        'financial_account_id' => $account->id,
        'fund_id' => $fund->id,
        'category_id' => $category->id,
        'program_id' => $legacy->id,
    ])->assertOk()->assertJsonPath('message', '● Siap digunakan');

    expect(programLifecycleFactCounts())->toBe($factsBefore);
});
