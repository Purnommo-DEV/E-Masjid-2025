<?php

use App\Domain\FinancialV2\BudgetAllocationService;
use App\Domain\FinancialV2\FinancialTransactionLifecycleService;
use App\Domain\FinancialV2\Reporting\ZiswafReportingV2Service;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\LedgerEntry;
use App\Models\FinancialV2\Program;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\Support\UatFinancialFixture;

test('ZISWAF Reporting V2 keeps plans separate and reads actuals from posted Financial V2 facts', function () {
    $context = UatFinancialFixture::context();
    $budget = app(BudgetAllocationService::class);
    $lifecycle = app(FinancialTransactionLifecycleService::class);

    $receipt = UatFinancialFixture::receipt($context, '200.00', null, [
        ['account_id' => $context['revenue']->id, 'split_amount' => '100.00', 'fund_id' => $context['fund']->id],
        ['account_id' => $context['revenue']->id, 'split_amount' => '100.00', 'fund_id' => $context['destinationFund']->id],
    ]);
    UatFinancialFixture::advance($receipt);
    UatFinancialFixture::post($receipt, 'ziswaf-v2-receipt');

    $allocation = ziswafV2Allocation($context, '120.00');
    $version = $allocation->versions->sole();
    $budget->submit($allocation->id);
    $budget->approveVersion($allocation->id, $version->id);

    $cancelledAllocation = ziswafV2Allocation($context, '120.00');
    $budget->cancel($cancelledAllocation->id, 'Rencana dibatalkan sebelum realisasi.');

    // A realization draft is a plan/workflow record, not an actual expense.
    $draft = $lifecycle->createRealization([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['paymentType']->id,
        'business_date' => $context['today'],
        'accounting_date' => $context['today'],
        'gross_amount' => '120.00',
        'source_reference' => 'ZISWAF-V2-DRAFT-'.Str::uuid(),
        'idempotency_key' => 'ziswaf-v2-draft-'.Str::uuid(),
        'primary_financial_account_id' => $context['accountA']->id,
        'counterparty_id' => $context['supplier']->id,
        'category_id' => $context['paymentCategory']->id,
        'description' => 'Draft Distribusi Sembako',
    ], [
        ['account_id' => $context['expense']->id, 'split_amount' => '70.00', 'fund_id' => $context['fund']->id, 'program_id' => $context['program']->id],
        ['account_id' => $context['expense']->id, 'split_amount' => '50.00', 'fund_id' => $context['destinationFund']->id, 'program_id' => $context['program']->id],
    ], $version->id);

    $payment = UatFinancialFixture::payment($context, '30.00', $context['fund']->id, $context['program']->id);
    UatFinancialFixture::advance($payment);
    UatFinancialFixture::post($payment, 'ziswaf-v2-payment');
    $factsAfterPosting = [Journal::count(), LedgerEntry::count()];

    $report = app(ZiswafReportingV2Service::class)->report($context['entity'], $context['today'], $context['today']);
    $program = collect($report['programs'])->sole('program_id', $context['program']->id);
    $sources = collect($program['funding_sources'])->keyBy('fund_id');

    expect($draft->fresh()->status)->toBe('draft')
        ->and($cancelledAllocation->fresh()->status)->toBe('cancelled')
        ->and($report['summary']['receipts'])->toBe('200.00')
        ->and($report['summary']['expenses'])->toBe('30.00')
        ->and($report['summary']['planned_usage'])->toBe('120.00')
        ->and($program['allocation'])->toBe('120.00')
        ->and($program['realization'])->toBe('0.00')
        ->and($program['actual_expense'])->toBe('30.00')
        ->and($program['status'])->toBe('BERJALAN')
        ->and($sources->get($context['fund']->id)['allocated'])->toBe('70.00')
        ->and($sources->get($context['fund']->id)['realized'])->toBe('30.00')
        ->and($sources->get($context['destinationFund']->id)['allocated'])->toBe('50.00')
        ->and($sources->get($context['destinationFund']->id)['realized'])->toBe('0.00')
        ->and($report['diagnostics']['fund_balance_reconciled'])->toBeTrue()
        ->and($report['diagnostics']['actual_expense_reconciled'])->toBeTrue()
        ->and($report['diagnostics']['plan_excluded_from_actual'])->toBeTrue()
        ->and([Journal::count(), LedgerEntry::count()])->toBe($factsAfterPosting);
});

test('ZISWAF Reporting V2 provides internal and public web reports without changing the legacy route', function () {
    $context = UatFinancialFixture::context();
    $receipt = UatFinancialFixture::receipt($context, '50.00', $context['fund']->id);
    UatFinancialFixture::advance($receipt);
    UatFinancialFixture::post($receipt, 'ziswaf-v2-web-receipt');

    config()->set('financial_reporting.public_ziswaf.entity_code', $context['entity']->code);
    config()->set('financial_reporting.public_ziswaf.fund_codes', [$context['fund']->code, $context['destinationFund']->code]);
    config()->set('financial_reporting.public_ziswaf.financial_account_codes', [$context['accountA']->code, $context['accountB']->code]);

    $user = User::factory()->create();
    $this->actingAs($user)->get(route('financial-v2.ziswaf-v2.index', ['entity' => $context['entity']->id, 'from' => $context['today'], 'through' => $context['today']]))
        ->assertOk()
        ->assertSee('Laporan Pengelolaan Dana ZISWAF')
        ->assertSee('PLAN')
        ->assertSee('POSTED')
        ->assertSee($context['fund']->name);
    $this->actingAs($user)->get(route('financial-v2.ziswaf-v2.program', ['program' => $context['program']->id, 'entity' => $context['entity']->id, 'from' => $context['today'], 'through' => $context['today']]))
        ->assertOk()
        ->assertSee('PROGRAM DETAIL')
        ->assertSee($context['program']->name);

    $this->get(route('public.ziswaf-v2.index', ['from' => $context['today'], 'through' => $context['today']]))
        ->assertOk()
        ->assertSee('Laporan Pengelolaan Dana ZISWAF')
        ->assertSee('ACTUAL')
        ->assertSee('Catatan transparansi')
        ->assertDontSee('Cetak laporan PDF');
    $this->get(route('public.ziswaf.index', ['from' => $context['today'], 'to' => $context['today']]))
        ->assertOk()
        ->assertSee('Laporan Dana ZISWAF');
});

test('public ZISWAF V2 exposes only posted program actuals without internal workflow fields', function () {
    $context = UatFinancialFixture::context();
    $context['program']->update(['name' => 'Program Rencana Rahasia']);
    ziswafV2Allocation($context, '120.00');
    $actualProgram = ziswafV2Program($context, 'PUB-ACTUAL', 'Program Actual Publik');

    $receipt = UatFinancialFixture::receipt($context, '50.00', $context['fund']->id);
    UatFinancialFixture::advance($receipt);
    UatFinancialFixture::post($receipt, 'ziswaf-v2-public-receipt');
    $payment = UatFinancialFixture::payment($context, '30.00', $context['fund']->id, $actualProgram->id);
    UatFinancialFixture::advance($payment);
    UatFinancialFixture::post($payment, 'ziswaf-v2-public-actual');

    config()->set('financial_reporting.public_ziswaf.entity_code', $context['entity']->code);
    config()->set('financial_reporting.public_ziswaf.fund_codes', [$context['fund']->code, $context['destinationFund']->code]);
    $report = app(ZiswafReportingV2Service::class)->publicReport($context['today'], $context['today']);
    $publicProgram = collect($report['programs'])->sole('program_id', $actualProgram->id);

    expect($report['summary']['receipts'])->toBe('50.00')
        ->and($report['summary']['expenses'])->toBe('30.00')
        ->and($report['summary'])->not->toHaveKey('planned_usage')
        ->and(collect($report['programs'])->pluck('program_id')->all())->toBe([$actualProgram->id])
        ->and($publicProgram['actual_expense'])->toBe('30.00')
        ->and(array_key_exists('status', $publicProgram))->toBeFalse()
        ->and(array_key_exists('allocation', $publicProgram))->toBeFalse()
        ->and(array_key_exists('realization', $publicProgram))->toBeFalse();

    $this->get(route('public.ziswaf-v2.index', ['from' => $context['today'], 'through' => $context['today']]))
        ->assertOk()
        ->assertSee($actualProgram->name)
        ->assertDontSee('Program Rencana Rahasia')
        ->assertDontSee('DRAFT')
        ->assertDontSee('SUBMITTED')
        ->assertDontSee('BELUM DIREALISASIKAN');
});

test('ZISWAF V2 keeps non-program expenses scoped to null program when filtering a program', function () {
    $context = UatFinancialFixture::context();
    $programB = ziswafV2Program($context, 'PROGRAM-B', 'Program B');

    $receipt = UatFinancialFixture::receipt($context, '100.00', null, [
        ['account_id' => $context['revenue']->id, 'split_amount' => '85.00', 'fund_id' => $context['fund']->id],
        ['account_id' => $context['revenue']->id, 'split_amount' => '15.00', 'fund_id' => $context['destinationFund']->id],
    ]);
    UatFinancialFixture::advance($receipt);
    UatFinancialFixture::post($receipt, 'ziswaf-v2-filter-receipt');

    foreach ([
        ['20.00', $context['fund']->id, $context['program']->id, 'program-a-fund-a'],
        ['15.00', $context['destinationFund']->id, $context['program']->id, 'program-a-fund-b'],
        ['30.00', $context['fund']->id, $programB->id, 'program-b'],
        ['10.00', $context['fund']->id, null, 'non-program'],
    ] as [$amount, $fundId, $programId, $key]) {
        $payment = UatFinancialFixture::payment($context, $amount, $fundId, $programId);
        UatFinancialFixture::advance($payment);
        UatFinancialFixture::post($payment, 'ziswaf-v2-filter-'.$key);
    }

    $service = app(ZiswafReportingV2Service::class);
    $all = $service->report($context['entity'], $context['today'], $context['today']);
    $allPrograms = collect($all['programs'])->keyBy('program_id');
    $programASources = collect($allPrograms->get($context['program']->id)['funding_sources'])->keyBy('fund_id');
    $allNonProgram = collect($all['non_program_expenses'])->sole();

    expect($all['summary']['expenses'])->toBe('75.00')
        ->and($allPrograms->get($context['program']->id)['actual_expense'])->toBe('35.00')
        ->and($allPrograms->get($programB->id)['actual_expense'])->toBe('30.00')
        ->and($programASources->get($context['fund']->id)['realized'])->toBe('20.00')
        ->and($programASources->get($context['destinationFund']->id)['realized'])->toBe('15.00')
        ->and($allNonProgram['amount'])->toBe('10.00')
        ->and($all['diagnostics']['actual_expense_reconciled'])->toBeTrue();

    $onlyA = $service->report($context['entity'], $context['today'], $context['today'], null, ['program_id' => $context['program']->id]);
    $onlyB = $service->report($context['entity'], $context['today'], $context['today'], null, ['program_id' => $programB->id]);
    $onlyANonProgram = collect($onlyA['non_program_expenses'])->sole();
    $onlyBNonProgram = collect($onlyB['non_program_expenses'])->sole();

    expect(collect($onlyA['programs'])->pluck('program_id')->all())->toBe([$context['program']->id])
        ->and($onlyA['programs'][0]['actual_expense'])->toBe('35.00')
        ->and($onlyANonProgram['amount'])->toBe('10.00')
        ->and($onlyA['diagnostics']['actual_expense_reconciled'])->toBeTrue()
        ->and(collect($onlyB['programs'])->pluck('program_id')->all())->toBe([$programB->id])
        ->and($onlyB['programs'][0]['actual_expense'])->toBe('30.00')
        ->and($onlyBNonProgram['amount'])->toBe('10.00')
        ->and($onlyB['diagnostics']['actual_expense_reconciled'])->toBeTrue();
});

/** @param array<string, mixed> $context */
function ziswafV2Allocation(array $context, string $amount): \App\Models\FinancialV2\BudgetAllocation
{
    return app(BudgetAllocationService::class)->create([
        'accounting_entity_id' => $context['entity']->id,
        'accounting_period_id' => $context['period']->id,
        'fundings' => [
            ['fund_id' => $context['fund']->id, 'amount' => '70.00'],
            ['fund_id' => $context['destinationFund']->id, 'amount' => '50.00'],
        ],
        'program_id' => $context['program']->id,
        'account_id' => $context['expense']->id,
        'category_id' => $context['paymentCategory']->id,
        'allocation_reference' => 'ZISWAF-V2-'.Str::uuid(),
        'idempotency_key' => 'ziswaf-v2-allocation-'.Str::uuid(),
        'allocated_amount' => $amount,
        'effective_from' => $context['today'],
        'reason' => 'Rencana program ZISWAF Reporting V2.',
    ]);
}

/** @param array<string, mixed> $context */
function ziswafV2Program(array $context, string $code, string $name): Program
{
    return Program::create([
        'accounting_entity_id' => $context['entity']->id,
        'code' => $code,
        'name' => $name,
        'start_date' => $context['today'],
        'end_date' => $context['today'],
        'status' => 'active',
    ]);
}
