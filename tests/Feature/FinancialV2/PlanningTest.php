<?php

use App\Domain\FinancialV2\BudgetAllocationService;
use App\Domain\FinancialV2\FinancialDomainException;
use App\Domain\FinancialV2\FinancialTransactionLifecycleService;
use App\Domain\FinancialV2\PlanningService;
use App\Models\FinancialV2\AuditEvent;
use App\Models\FinancialV2\BudgetAllocation;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\FundRealization;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\JournalLine;
use App\Models\FinancialV2\LedgerEntry;
use App\Models\FinancialV2\Voucher;
use App\Models\User;
use Database\Seeders\FinancialV2PlanningPermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Support\UatFinancialFixture;

test('Planning draft validates master data amounts periods and exact multi-Fund totals without financial facts', function () {
    $context = planningFundedContext();
    $service = app(PlanningService::class);
    $facts = planningFacts();
    $planning = $service->createDraft($context['entity']->id, planningInput($context, '300.00', ['target_recipient_count' => 3, 'amount_per_recipient' => '100.00']), [
        ['fund_id' => $context['fund']->id, 'amount' => '175.00'],
        ['fund_id' => $context['destinationFund']->id, 'amount' => '125.00'],
    ]);

    expect($planning->status)->toBe('draft')
        ->and($planning->fundings)->toHaveCount(2)
        ->and($planning->fundings->pluck('amount')->all())->toBe(['175.00', '125.00'])
        ->and(planningFacts())->toBe($facts)
        ->and(AuditEvent::where('target_id', $planning->id)->where('event_type', 'planning_created')->exists())->toBeTrue();

    $updated = $service->updateDraft($planning->id, planningInput($context, '300.00', ['name' => 'Santunan Dhuafa Diperbarui']), [
        ['fund_id' => $context['fund']->id, 'amount' => '150.00'],
        ['fund_id' => $context['destinationFund']->id, 'amount' => '150.00'],
    ]);
    expect($updated->name)->toBe('Santunan Dhuafa Diperbarui')
        ->and($updated->fundings->pluck('amount')->all())->toBe(['150.00', '150.00'])
        ->and(planningFacts())->toBe($facts);

    expect(fn () => $service->createDraft($context['entity']->id, planningInput($context, '300.00'), [['fund_id' => $context['fund']->id, 'amount' => '299.00']]))
        ->toThrow(FinancialDomainException::class, 'harus sama')
        ->and(fn () => $service->createDraft($context['entity']->id, planningInput($context, '1.00'), [['fund_id' => (string) Str::uuid(), 'amount' => '1.00']]))
        ->toThrow(FinancialDomainException::class, 'Dana harus aktif');

    $other = UatFinancialFixture::context();
    expect(fn () => $service->createDraft($context['entity']->id, planningInput($context, '10.00', ['program_id' => $other['program']->id]), [['fund_id' => $context['fund']->id, 'amount' => '10.00']]))
        ->toThrow(FinancialDomainException::class, 'Program harus aktif')
        ->and(fn () => $service->createDraft($context['entity']->id, planningInput($context, '-1.00'), [['fund_id' => $context['fund']->id, 'amount' => '1.00']]))
        ->toThrow(FinancialDomainException::class)
        ->and(fn () => $service->createDraft($context['entity']->id, planningInput($context, '10.00', ['period_end' => now()->subDays(2)->toDateString()]), [['fund_id' => $context['fund']->id, 'amount' => '10.00']]))
        ->toThrow(FinancialDomainException::class, 'Tanggal akhir');
    expect(fn () => $service->createDraft($context['entity']->id, planningInput($context, '300.00', ['target_recipient_count' => 3, 'amount_per_recipient' => '99.00']), [['fund_id' => $context['fund']->id, 'amount' => '300.00']]))
        ->toThrow(FinancialDomainException::class, 'target penerima')
        ->and(fn () => $service->createDraft($context['entity']->id, planningInput($context, '20.00'), [
            ['fund_id' => $context['fund']->id, 'amount' => '10.00'],
            ['fund_id' => $context['fund']->id, 'amount' => '10.00'],
        ]))->toThrow(FinancialDomainException::class, 'hanya boleh muncul satu kali');
});

test('draft does not reserve capacity while approved and cancelled lifecycle updates capacity exactly', function () {
    $context = planningFundedContext('1000.00', '500.00');
    $service = app(PlanningService::class);
    $draft = $service->createDraft($context['entity']->id, planningInput($context, '400.00'), [['fund_id' => $context['fund']->id, 'amount' => '400.00']]);

    expect($service->calculateBalanceImpact($context['entity']->id, $context['fund']->id))->toMatchArray([
        'actual' => '1000.00', 'outstanding' => '0.00', 'approved' => '0.00', 'available' => '1000.00',
    ]);

    $service->approve($draft->id);
    expect($service->calculateBalanceImpact($context['entity']->id, $context['fund']->id))->toMatchArray([
        'actual' => '1000.00', 'outstanding' => '0.00', 'approved' => '400.00', 'available' => '600.00',
    ]);
    expect(fn () => $service->updateDraft($draft->id, planningInput($context, '400.00'), [['fund_id' => $context['fund']->id, 'amount' => '400.00']]))
        ->toThrow(FinancialDomainException::class)
        ->and(fn () => $draft->fresh()->update(['name' => 'Mutasi langsung tidak sah']))
        ->toThrow(DomainException::class, 'Approved Plannings are immutable');

    $service->cancel($draft->id, 'Prioritas program berubah.');
    expect($draft->fresh()->status)->toBe('cancelled')
        ->and($draft->fresh()->cancellation_reason)->toBe('Prioritas program berubah.')
        ->and($service->calculateBalanceImpact($context['entity']->id, $context['fund']->id)['available'])->toBe('1000.00');
});

test('fund capacity is enforced independently and revalidated before approval', function () {
    $context = planningFundedContext('100.00', '50.00');
    $service = app(PlanningService::class);

    expect(fn () => $service->createDraft($context['entity']->id, planningInput($context, '110.00'), [
        ['fund_id' => $context['fund']->id, 'amount' => '90.00'],
        ['fund_id' => $context['destinationFund']->id, 'amount' => '20.00'],
    ]))->not->toThrow(FinancialDomainException::class);

    expect(fn () => $service->createDraft($context['entity']->id, planningInput($context, '101.00'), [['fund_id' => $context['fund']->id, 'amount' => '101.00']]))
        ->toThrow(FinancialDomainException::class, 'kekurangan');

    $draft = $service->createDraft($context['entity']->id, planningInput($context, '50.00'), [['fund_id' => $context['fund']->id, 'amount' => '50.00']]);
    $competitor = $service->createDraft($context['entity']->id, planningInput($context, '60.00', ['name' => 'Komitmen paralel']), [['fund_id' => $context['fund']->id, 'amount' => '60.00']]);
    $service->approve($competitor->id);

    expect(fn () => $service->approve($draft->id))->toThrow(FinancialDomainException::class, 'kekurangan')
        ->and($draft->fresh()->status)->toBe('draft');
});

test('conversion is atomic traceable idempotent and moves commitment without double counting or accounting facts', function () {
    $context = planningFundedContext('1000.00', '500.00');
    $service = app(PlanningService::class);
    $planning = $service->createDraft($context['entity']->id, planningInput($context, '400.00'), [['fund_id' => $context['fund']->id, 'amount' => '400.00']]);
    $service->approve($planning->id);
    $facts = planningFacts();
    $allocation = $service->convertToAllocation($planning->id);
    $again = $service->convertToAllocation($planning->id);
    $impact = $service->calculateBalanceImpact($context['entity']->id, $context['fund']->id);

    expect($again->id)->toBe($allocation->id)
        ->and(BudgetAllocation::where('planning_id', $planning->id)->count())->toBe(1)
        ->and($allocation->planning_id)->toBe($planning->id)
        ->and($allocation->status)->toBe('draft')
        ->and($allocation->versions->sole()->fundings->sole()->amount)->toBe('400.00')
        ->and($planning->fresh()->status)->toBe('converted')
        ->and($impact)->toMatchArray(['actual' => '1000.00', 'approved' => '0.00', 'outstanding' => '400.00', 'available' => '600.00'])
        ->and(planningFacts())->toBe($facts);

    expect(fn () => BudgetAllocation::create(['accounting_entity_id' => $context['entity']->id, 'planning_id' => $planning->id]))
        ->toThrow(QueryException::class);
});

test('terminal Planning is immutable and invalid lifecycle transitions are rejected', function () {
    $context = planningFundedContext();
    $service = app(PlanningService::class);
    $cancelled = $service->createDraft($context['entity']->id, planningInput($context, '50.00'), [['fund_id' => $context['fund']->id, 'amount' => '50.00']]);
    $service->cancel($cancelled->id, 'Dibatalkan untuk pengujian.');

    expect(fn () => $service->approve($cancelled->id))->toThrow(FinancialDomainException::class)
        ->and(fn () => $cancelled->fresh()->update(['name' => 'Tidak boleh berubah']))->toThrow(DomainException::class, 'immutable')
        ->and(fn () => $cancelled->fresh()->delete())->toThrow(DomainException::class, 'retained');

    $converted = $service->createDraft($context['entity']->id, planningInput($context, '40.00', ['name' => 'Planning terminal']), [['fund_id' => $context['fund']->id, 'amount' => '40.00']]);
    $service->approve($converted->id);
    $service->convertToAllocation($converted->id);
    expect(fn () => $service->cancel($converted->id, 'Tidak sah'))->toThrow(FinancialDomainException::class)
        ->and(fn () => $service->updateDraft($converted->id, planningInput($context, '40.00'), [['fund_id' => $context['fund']->id, 'amount' => '40.00']]))->toThrow(FinancialDomainException::class);
});

test('Planning-created Allocation supports the existing Realization and canonical PostingEngine flow', function () {
    $context = planningFundedContext('500.00', '100.00');
    $planningService = app(PlanningService::class);
    $planning = $planningService->createDraft($context['entity']->id, planningInput($context, '200.00'), [['fund_id' => $context['fund']->id, 'amount' => '200.00']]);
    $planningService->approve($planning->id);
    $allocation = $planningService->convertToAllocation($planning->id);
    $version = $allocation->versions->sole();
    app(BudgetAllocationService::class)->submit($allocation->id);
    app(BudgetAllocationService::class)->approveVersion($allocation->id, $version->id);
    $baseline = planningFacts();

    $realization = app(FinancialTransactionLifecycleService::class)->createRealization([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['paymentType']->id,
        'business_date' => $context['today'],
        'accounting_date' => $context['today'],
        'gross_amount' => '150.00',
        'source_reference' => 'PLANNING-REAL-'.Str::uuid(),
        'idempotency_key' => 'planning-real-'.Str::uuid(),
        'primary_financial_account_id' => $context['accountA']->id,
        'counterparty_id' => $context['supplier']->id,
        'category_id' => $context['paymentCategory']->id,
        'description' => 'Realisasi dari Allocation Planning',
    ], [[
        'account_id' => $context['expense']->id,
        'split_amount' => '150.00',
        'fund_id' => $context['fund']->id,
        'program_id' => $context['program']->id,
        'category_id' => $context['paymentCategory']->id,
    ]], $version->id);

    expect(FinancialTransaction::count())->toBe($baseline[0] + 1)
        ->and([Journal::count(), JournalLine::count(), LedgerEntry::count(), Voucher::count()])->toBe([$baseline[1], $baseline[2], $baseline[3], $baseline[4]])
        ->and(FundRealization::count())->toBe($baseline[5] + 1)
        ->and(FundRealization::where('transaction_id', $realization->id)->exists())->toBeTrue();
    UatFinancialFixture::advance($realization);
    UatFinancialFixture::post($realization, 'planning-realization-post');

    expect($realization->fresh()->status)->toBe('posted')
        ->and(Journal::where('transaction_id', $realization->id)->where('journal_status', 'posted')->count())->toBe(1)
        ->and(app(BudgetAllocationService::class)->availability($version->id)['available'])->toBe('50.00')
        ->and($planningService->calculateBalanceImpact($context['entity']->id, $context['fund']->id))->toMatchArray(['actual' => '350.00', 'outstanding' => '50.00', 'available' => '300.00']);
});

test('Planning permissions routes UI state and validation are enforced', function () {
    $context = planningFundedContext();
    $superAdmin = Role::findOrCreate('SuperAdmin', 'web');
    $this->seed(FinancialV2PlanningPermissionSeeder::class);
    $viewer = User::factory()->create();
    $viewer->givePermissionTo('financial-v2.planning.view');
    $operator = User::factory()->create();
    $operator->givePermissionTo(FinancialV2PlanningPermissionSeeder::PERMISSIONS);
    $planning = app(PlanningService::class)->createDraft($context['entity']->id, planningInput($context, '50.00'), [['fund_id' => $context['fund']->id, 'amount' => '50.00']], $operator->id);

    $this->actingAs($viewer)->get(route('financial-v2.plannings.index', ['entity' => $context['entity']->id]))
        ->assertOk()->assertSee('Perencanaan Penggunaan Dana');
    $this->actingAs($viewer)->get(route('financial-v2.plannings.create', ['entity' => $context['entity']->id]))->assertForbidden();
    $this->actingAs($viewer)->put(route('financial-v2.plannings.update', ['entity' => $context['entity']->id, 'planning' => $planning->id]), [])->assertForbidden();
    $this->actingAs($viewer)->post(route('financial-v2.plannings.approve', ['entity' => $context['entity']->id, 'planning' => $planning->id]))->assertForbidden();
    $this->actingAs($viewer)->post(route('financial-v2.plannings.convert', ['entity' => $context['entity']->id, 'planning' => $planning->id]))->assertForbidden();
    $this->actingAs($viewer)->post(route('financial-v2.plannings.cancel', ['entity' => $context['entity']->id, 'planning' => $planning->id]), ['cancellation_reason' => 'Tidak berwenang'])->assertForbidden();
    $this->actingAs($viewer)->get(route('financial-v2.plannings.show', ['entity' => $context['entity']->id, 'planning' => $planning->id]))
        ->assertOk()->assertDontSee('Convert to Allocation')->assertDontSee('Batalkan Planning');

    $this->actingAs($operator)->get(route('financial-v2.plannings.create', ['entity' => $context['entity']->id]))
        ->assertOk()->assertSee('Fund Capacity Preview')->assertSee('Tambah Sumber Dana');
    $this->actingAs($operator)->get(route('financial-v2.plannings.show', ['entity' => $context['entity']->id, 'planning' => $planning->id]))
        ->assertOk()->assertSee('Edit')->assertSee('Approve')->assertDontSee('Convert to Allocation');
    $this->actingAs($operator)->post(route('financial-v2.plannings.store', ['entity' => $context['entity']->id]), planningInput($context, '100.00') + [
        'fundings' => [['fund_id' => $context['fund']->id, 'amount' => '99.00']],
    ])->assertSessionHasErrors('financial');
    $this->actingAs($operator)->postJson(route('financial-v2.plannings.preview', ['entity' => $context['entity']->id]), [
        'period_start' => $context['today'], 'fundings' => [['fund_id' => $context['fund']->id, 'amount' => '10.00']],
    ])->assertOk()->assertJsonPath('lines.0.actual', '1000.00')->assertJsonPath('lines.0.sufficient', true);

    app(PlanningService::class)->approve($planning->id, $operator->id);
    $this->actingAs($operator)->get(route('financial-v2.plannings.show', ['entity' => $context['entity']->id, 'planning' => $planning->id]))
        ->assertOk()->assertSee('Convert to Allocation')->assertDontSee('>Edit<', false);

    foreach (FinancialV2PlanningPermissionSeeder::PERMISSIONS as $permission) {
        expect(Permission::findByName($permission, 'web')->name)->toBe($permission);
    }
    expect($superAdmin->fresh()->hasAllPermissions(FinancialV2PlanningPermissionSeeder::PERMISSIONS))->toBeTrue();
});

test('Planning form keeps A B C workflow separate from the responsive sticky summary and localizes values', function () {
    $context = planningFundedContext('2000000.00', '500000.00');
    $this->seed(FinancialV2PlanningPermissionSeeder::class);
    $operator = User::factory()->create();
    $operator->givePermissionTo(FinancialV2PlanningPermissionSeeder::PERMISSIONS);

    $create = $this->actingAs($operator)->get(route('financial-v2.plannings.create', ['entity' => $context['entity']->id]));
    $create->assertOk()
        ->assertSeeInOrder([
            'data-planning-section="information"',
            'data-planning-section="funding"',
            'data-planning-section="capacity"',
            'id="planning-summary"',
        ], false)
        ->assertSee('lg:grid-cols-[1.35fr_.65fr]', false)
        ->assertSee('lg:sticky lg:self-start', false)
        ->assertSee('style="top: 7rem"', false)
        ->assertSee('id="funding-lines" class="mt-5 grid gap-4 md:grid-cols-2"', false)
        ->assertSee('Maks. Dapat Direncanakan')
        ->assertSee('Gunakan Maksimal')
        ->assertSee('Dana Mencukupi')
        ->assertSee('Dana Tidak Mencukupi')
        ->assertSee("form.addEventListener('submit'", false)
        ->assertSee('data-money-field', false)
        ->assertSee('data-money-value', false)
        ->assertSee("amount.dispatchEvent(new Event('input'", false);

    $planning = app(PlanningService::class)->createDraft($context['entity']->id, planningInput($context, '1000000.00', [
        'target_recipient_count' => 1000,
        'amount_per_recipient' => '1000.00',
    ]), [['fund_id' => $context['fund']->id, 'amount' => '1000000.00']], $operator->id);

    $this->actingAs($operator)->get(route('financial-v2.plannings.edit', ['entity' => $context['entity']->id, 'planning' => $planning->id]))
        ->assertOk()
        ->assertSee('name="target_recipient_count" value="1.000"', false)
        ->assertSee('id="per-recipient-display" data-money-input inputmode="decimal" class="input input-bordered" value="1.000"', false)
        ->assertSee('id="planning-total-display" data-money-input inputmode="decimal" class="input input-bordered font-semibold" value="1.000.000"', false)
        ->assertSee('class="input input-bordered fund-amount-display" data-money-input inputmode="decimal" required value="1.000.000"', false)
        ->assertSee('name="fundings[0][amount]" value="1000000.00"', false);

    $invalid = $this->actingAs($operator)->post(route('financial-v2.plannings.store', ['entity' => $context['entity']->id]), planningInput($context, '3000000.00') + [
        'fundings' => [['fund_id' => $context['fund']->id, 'amount' => '3000000.00']],
    ]);
    $invalid->assertSessionHasErrors('financial');
    expect(session('errors')->first('financial'))
        ->toContain('Rp2.000.000')
        ->toContain('Rp3.000.000')
        ->toContain('Rp1.000.000');
});

/** @return array<string,mixed> */
function planningFundedContext(string $first = '1000.00', string $second = '500.00'): array
{
    $context = UatFinancialFixture::context();
    $total = \App\Domain\FinancialV2\DecimalAmount::add($first, $second);
    $receipt = UatFinancialFixture::receipt($context, $total, null, [
        ['account_id' => $context['revenue']->id, 'split_amount' => $first, 'fund_id' => $context['fund']->id],
        ['account_id' => $context['revenue']->id, 'split_amount' => $second, 'fund_id' => $context['destinationFund']->id],
    ]);
    UatFinancialFixture::advance($receipt);
    UatFinancialFixture::post($receipt, 'planning-opening-'.Str::uuid());

    return $context;
}

/** @param array<string,mixed> $context @param array<string,mixed> $override @return array<string,mixed> */
function planningInput(array $context, string $amount, array $override = []): array
{
    return $override + [
        'name' => 'Santunan Dhuafa Bulanan',
        'period_start' => $context['today'],
        'period_end' => $context['today'],
        'program_id' => $context['program']->id,
        'target_recipient_count' => null,
        'amount_per_recipient' => null,
        'total_amount' => $amount,
        'notes' => 'Planning non-finansial.',
    ];
}

/** @return array<int,int> */
function planningFacts(): array
{
    return [FinancialTransaction::count(), Journal::count(), JournalLine::count(), LedgerEntry::count(), Voucher::count(), FundRealization::count()];
}
