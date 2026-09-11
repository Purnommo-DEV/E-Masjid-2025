<?php

use App\Domain\FinancialV2\BudgetAllocationService;
use App\Domain\FinancialV2\DecimalAmount;
use App\Domain\FinancialV2\DistributionService;
use App\Domain\FinancialV2\FinancialTransactionLifecycleService;
use App\Domain\FinancialV2\Reporting\FinancialReportService;
use App\Domain\FinancialV2\Reporting\ZiswafReportingV2Service;
use App\Models\FinancialV2\Counterparty;
use App\Models\FinancialV2\Distribution;
use App\Models\FinancialV2\DistributionItem;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\JournalLine;
use App\Models\FinancialV2\LedgerEntry;
use App\Models\FinancialV2\Program;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\ZiswafDistributionPermissionSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\Support\UatFinancialFixture;

afterEach(fn () => Carbon::setTestNow());

test('Santunan Dhuafa January to February distribution posts once and reaches internal and public reports', function () {
    Carbon::setTestNow('2026-02-15 10:00:00');
    expect(app()->environment())->toBe('testing')
        ->and(DB::connection()->getDatabaseName())->toBe('mrj_test_db');

    $context = UatFinancialFixture::context();
    $context['fund']->update(['code' => 'DHUAFA-QA', 'name' => 'Dana Dhuafa QA']);
    $program = Program::create([
        'accounting_entity_id' => $context['entity']->id,
        'code' => 'SANTUNAN-DHUAFA-QA-2026',
        'name' => 'Santunan Dhuafa QA 2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'active',
    ]);
    $context['program'] = $program;

    $distributionService = app(DistributionService::class);
    $lifecycle = app(FinancialTransactionLifecycleService::class);
    $reporting = app(ZiswafReportingV2Service::class);
    $canonicalReporting = app(FinancialReportService::class);

    $beneficiaries = collect(range(1, 6))->map(function (int $number) use ($context, $distributionService): Counterparty {
        $region = $number <= 3 ? ['01', '01', 'Koordinator QA Utara'] : ['02', '01', 'Koordinator QA Selatan'];

        return $distributionService->saveBeneficiary($context['entity']->id, [
            'display_name' => sprintf('Beneficiary QA %02d', $number),
            'contact_reference' => '0812'.str_pad((string) $number, 8, '0', STR_PAD_LEFT),
            'address' => sprintf('Alamat Privat QA %02d', $number),
            'rt' => $region[0],
            'rw' => $region[1],
            'rt_coordinator_name' => $region[2],
            'beneficiary_notes' => 'Catatan privat skenario QA',
            'status' => 'active',
        ], null, null);
    });

    // Seed an opening receipt through the canonical lifecycle so the Fund has
    // a real posted balance before the February realization is posted.
    $receipt = UatFinancialFixture::receipt($context, '1000000.00', $context['fund']->id);
    UatFinancialFixture::advance($receipt);
    UatFinancialFixture::post($receipt, 'ziswaf-e2e-opening-receipt');

    $january = $distributionService->create($context['entity']->id, [
        'program_id' => $program->id,
        'title' => 'Santunan Dhuafa Januari 2026',
        'period_label' => 'Januari 2026',
        'starts_on' => '2026-01-01',
        'ends_on' => '2026-01-31',
        'notes' => 'Distribusi Januari untuk skenario QA.',
    ], null);
    foreach ($beneficiaries->take(5) as $beneficiary) {
        $january = $distributionService->item($context['entity']->id, $january->id, [
            'revision' => $january->fresh()->revision,
            'beneficiary_id' => $beneficiary->id,
            'amount' => '100000.00',
        ], null, false, null);
    }
    $januarySnapshot = $january->fresh()->load('items')->items
        ->sortBy('beneficiary_id')->map->only(['beneficiary_id', 'amount', 'identity_snapshot'])->values()->all();

    $financialCountsBeforeCopy = [
        FinancialTransaction::count(), Journal::count(), JournalLine::count(), LedgerEntry::count(),
    ];
    $february = $distributionService->copyPrevious($context['entity']->id, [
        'program_id' => $program->id,
        'title' => 'Santunan Dhuafa Februari 2026',
        'period_label' => 'Februari 2026',
        'starts_on' => '2026-02-01',
        'ends_on' => '2026-02-28',
        'notes' => 'Distribusi Februari hasil salin dan penyesuaian.',
    ], null);

    expect($january->items()->count())->toBe(5)
        ->and(DecimalAmount::sum($january->items()->pluck('amount')))->toBe('500000.00')
        ->and($february->status)->toBe('draft')
        ->and($february->copied_from_id)->toBe($january->id)
        ->and($february->realization_id)->toBeNull()
        ->and($february->items()->count())->toBe(5)
        ->and([FinancialTransaction::count(), Journal::count(), JournalLine::count(), LedgerEntry::count()])
        ->toBe($financialCountsBeforeCopy);

    // Replace Beneficiary QA 05 with QA 06 while keeping four copied people.
    $removed = $beneficiaries[4];
    $replacement = $beneficiaries[5];
    $removedItem = $february->items()->where('beneficiary_id', $removed->id)->sole();
    $february = $distributionService->item($context['entity']->id, $february->id, [
        'revision' => $february->fresh()->revision,
    ], $removedItem->id, true, null);
    $february = $distributionService->item($context['entity']->id, $february->id, [
        'revision' => $february->fresh()->revision,
        'beneficiary_id' => $replacement->id,
        'amount' => '100000.00',
    ], null, false, null);
    expect(fn () => $distributionService->item($context['entity']->id, $february->id, [
        'revision' => $february->fresh()->revision,
        'beneficiary_id' => $beneficiaries[0]->id,
        'amount' => '100000.00',
    ], null, false, null))->toThrow(ValidationException::class, 'sudah ada');

    $february = $february->fresh('items');
    expect($february->items)->toHaveCount(5)
        ->and(DecimalAmount::sum($february->items->pluck('amount')))->toBe('500000.00')
        ->and($february->items->pluck('beneficiary_id')->intersect($beneficiaries->take(4)->pluck('id')))->toHaveCount(4)
        ->and($february->items->pluck('beneficiary_id'))->toContain($replacement->id)
        ->not->toContain($removed->id)
        ->and($january->fresh()->items->sortBy('beneficiary_id')->map->only(['beneficiary_id', 'amount', 'identity_snapshot'])->values()->all())
        ->toBe($januarySnapshot)
        ->and([FinancialTransaction::count(), Journal::count(), JournalLine::count(), LedgerEntry::count()])
        ->toBe($financialCountsBeforeCopy);

    $budget = app(BudgetAllocationService::class);
    $allocation = $budget->create([
        'accounting_entity_id' => $context['entity']->id,
        'accounting_period_id' => $context['period']->id,
        'fundings' => [['fund_id' => $context['fund']->id, 'amount' => '500000.00']],
        'program_id' => $program->id,
        'account_id' => $context['expense']->id,
        'category_id' => $context['paymentCategory']->id,
        'allocation_reference' => 'SANTUNAN-DHUAFA-QA-2026',
        'idempotency_key' => 'ziswaf-e2e-allocation-'.Str::uuid(),
        'allocated_amount' => '500000.00',
        'effective_from' => $context['today'],
        'reason' => 'Alokasi skenario E2E Santunan Dhuafa QA 2026.',
    ]);
    $allocationVersion = $allocation->versions->sole();
    $budget->submit($allocation->id);
    $budget->approveVersion($allocation->id, $allocationVersion->id);

    $realization = $lifecycle->createRealization([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['paymentType']->id,
        'business_date' => $context['today'],
        'accounting_date' => $context['today'],
        'gross_amount' => '500000.00',
        'source_reference' => 'DIST-FEB-2026-'.Str::uuid(),
        'idempotency_key' => 'ziswaf-e2e-realization-'.Str::uuid(),
        'primary_financial_account_id' => $context['accountA']->id,
        'counterparty_id' => $context['supplier']->id,
        'category_id' => $context['paymentCategory']->id,
        'description' => 'Realisasi Santunan Dhuafa Februari 2026',
    ], [[
        'account_id' => $context['expense']->id,
        'split_amount' => '500000.00',
        'fund_id' => $context['fund']->id,
        'program_id' => $program->id,
    ]], $allocationVersion->id);

    $beforePosting = $reporting->report($context['entity'], '2026-02-01', '2026-02-28', [$context['fund']->id], ['program_id' => $program->id]);
    $beforeProgram = collect($beforePosting['programs'])->sole('program_id', $program->id);
    $beforeFund = collect($beforePosting['funds'])->sole('fund_id', $context['fund']->id);
    expect($beforeProgram['budget'])->toBe('500000.00')
        ->and($beforeProgram['allocation'])->toBe('500000.00')
        ->and($beforeProgram['realization'])->toBe('0.00')
        ->and($beforeProgram['actual_expense'])->toBe('0.00')
        ->and($beforePosting['summary']['expenses'])->toBe('0.00')
        ->and($beforeFund['fund_balance'])->toBe('1000000.00');

    $february = $distributionService->finalize(
        $context['entity']->id,
        $february->id,
        $realization->realization->id,
        $february->fresh()->revision,
        null,
    );
    expect($february->status)->toBe('finalized')
        ->and($february->realization_id)->toBe($realization->realization->id)
        ->and(fn () => $distributionService->item($context['entity']->id, $february->id, [
            'revision' => $february->revision,
            'beneficiary_id' => $beneficiaries[0]->id,
            'amount' => '90000.00',
        ], $february->items()->first()->id, false, null))->toThrow(ValidationException::class, 'tidak dapat diedit');

    $factsBeforeRealizationPosting = [Journal::count(), JournalLine::count(), LedgerEntry::count()];
    UatFinancialFixture::advance($realization);
    $postingKey = 'ziswaf-e2e-february-posting';
    $posting = UatFinancialFixture::post($realization, $postingKey);
    $factsAfterPosting = [Journal::count(), JournalLine::count(), LedgerEntry::count()];
    $repeatPosting = UatFinancialFixture::post($realization->fresh(), $postingKey);

    expect($realization->fresh()->status)->toBe('posted')
        ->and($realization->realization->fresh()->status)->toBe('recorded')
        ->and($factsAfterPosting)->toBe([
            $factsBeforeRealizationPosting[0] + 1,
            $factsBeforeRealizationPosting[1] + 2,
            $factsBeforeRealizationPosting[2] + 2,
        ])
        ->and($repeatPosting->journalId)->toBe($posting->journalId)
        ->and([Journal::count(), JournalLine::count(), LedgerEntry::count()])->toBe($factsAfterPosting)
        ->and(Journal::where('transaction_id', $realization->id)->count())->toBe(1)
        ->and(JournalLine::where('journal_id', $posting->journalId)->count())->toBe(2)
        ->and(LedgerEntry::whereIn('journal_line_id', JournalLine::where('journal_id', $posting->journalId)->select('id'))->count())->toBe(2);

    $afterPosting = $reporting->report($context['entity'], '2026-02-01', '2026-02-28', [$context['fund']->id], ['program_id' => $program->id]);
    $afterProgram = collect($afterPosting['programs'])->sole('program_id', $program->id);
    $afterFund = collect($afterPosting['funds'])->sole('fund_id', $context['fund']->id);
    $event = collect($afterPosting['distributions']['events'])->sole();
    $canonical = $canonicalReporting->report('fund-balance', $context['entity']->id, '2026-02-01', '2026-02-28', ['fund_id' => $context['fund']->id]);
    $canonicalFund = collect($canonical['data']['rows'])->sole('fund_id', $context['fund']->id);

    expect($afterProgram['budget'])->toBe('500000.00')
        ->and($afterProgram['allocation'])->toBe('500000.00')
        ->and($afterProgram['realization'])->toBe('500000.00')
        ->and($afterProgram['actual_expense'])->toBe('500000.00')
        ->and($afterPosting['summary']['expenses'])->toBe('500000.00')
        ->and($afterFund['fund_balance'])->toBe('500000.00')
        ->and(DecimalAmount::subtract($beforeFund['fund_balance'], $afterFund['fund_balance']))->toBe('500000.00')
        ->and($canonicalFund['fund_balance'])->toBe($afterFund['fund_balance'])
        ->and($afterPosting['diagnostics']['fund_balance_reconciled'])->toBeTrue()
        ->and($afterPosting['diagnostics']['actual_expense_reconciled'])->toBeTrue()
        ->and($afterPosting['diagnostics']['plan_excluded_from_actual'])->toBeTrue()
        ->and($event['recipient_count'])->toBe(5)
        ->and($event['operational_total'])->toBe('500000.00')
        ->and($event['actual_amount'])->toBe('500000.00')
        ->and($afterPosting['distributions']['distribution_events'])->toBe(1)
        ->and($afterPosting['distributions']['unique_beneficiaries'])->toBe(5)
        ->and(collect($afterPosting['transactions'])->contains(fn (array $row): bool => $row['transaction_id'] === $realization->id && $row['out'] === '500000.00'))->toBeTrue();

    $regions = collect($afterPosting['distributions']['regions'])->keyBy(fn (array $row): string => $row['rt'].'/'.$row['rw']);
    expect($regions['01/01']['recipient_count'])->toBe(3)
        ->and($regions['01/01']['coordinator'])->toBe('Koordinator QA Utara')
        ->and($regions['02/01']['recipient_count'])->toBe(2)
        ->and($regions['02/01']['coordinator'])->toBe('Koordinator QA Selatan');

    expect(DistributionItem::where('beneficiary_id', $beneficiaries[0]->id)->count())->toBe(2)
        ->and(DistributionItem::where('beneficiary_id', $removed->id)->count())->toBe(1)
        ->and(DistributionItem::where('beneficiary_id', $replacement->id)->count())->toBe(1)
        ->and($january->fresh()->items->sortBy('beneficiary_id')->map->only(['beneficiary_id', 'amount', 'identity_snapshot'])->values()->all())
        ->toBe($januarySnapshot);

    config()->set('financial_reporting.public_ziswaf.entity_code', $context['entity']->code);
    config()->set('financial_reporting.public_ziswaf.fund_codes', [$context['fund']->code]);
    config()->set('financial_reporting.public_ziswaf.financial_account_codes', [$context['accountA']->code]);
    $public = $reporting->publicReport('2026-01-01', '2026-02-28');
    $publicEvent = collect($public['distributions']['events'])->sole();
    $publicRegions = collect($public['distributions']['regions'])->keyBy(fn (array $row): string => $row['rt'].'/'.$row['rw']);
    expect($publicEvent['period'])->toBe('Februari 2026')
        ->and($publicEvent['recipient_count'])->toBe(5)
        ->and($publicEvent['actual_amount'])->toBe('500000.00')
        ->and($publicEvent)->not->toHaveKeys(['distribution_id', 'operational_status', 'financial_status', 'identity_snapshot', 'notes'])
        ->and($publicRegions['01/01']['recipient_count'])->toBe(3)
        ->and($publicRegions['02/01']['recipient_count'])->toBe(2)
        ->and($publicRegions['01/01'])->not->toHaveKeys(['beneficiary_id', 'coordinator'])
        ->and($publicRegions['02/01'])->not->toHaveKeys(['beneficiary_id', 'coordinator'])
        ->and(collect($public['programs'])->sole('program_id', $program->id)['actual_expense'])->toBe('500000.00')
        ->and($public)->not->toHaveKey('transactions')
        ->and($public['summary'])->not->toHaveKey('planned_usage');

    (new ZiswafDistributionPermissionSeeder)->run();
    $user = User::factory()->create();
    $user->givePermissionTo(ZiswafDistributionPermissionSeeder::PERMISSIONS);
    $this->actingAs($user);
    $this->get(route('financial-v2.beneficiaries.index', ['entity' => $context['entity']->id, 'q' => 'Beneficiary QA 01']))
        ->assertOk()->assertSee('Beneficiary QA 01');
    $this->get(route('financial-v2.beneficiaries.show', ['entity' => $context['entity']->id, 'beneficiary' => $beneficiaries[0]->id]))
        ->assertOk()->assertSee('Riwayat penyaluran')->assertSee('Januari 2026')->assertSee('Februari 2026');
    $this->get(route('financial-v2.distributions.index', ['entity' => $context['entity']->id]))
        ->assertOk()->assertSee('Salin dari Penyaluran Sebelumnya')->assertSee($program->name)->assertSee('Februari 2026');
    $this->get(route('financial-v2.distributions.show', ['entity' => $context['entity']->id, 'distribution' => $february->id]))
        ->assertOk()->assertSee('Lihat realisasi Financial V2')->assertSee('Dana Dhuafa QA')->assertSee('FINALIZED');
    $this->get(route('financial-v2.ziswaf-v2.index', ['entity' => $context['entity']->id, 'from' => '2026-02-01', 'through' => '2026-02-28']))
        ->assertOk()->assertSee($program->name)->assertSee('5 penerima')->assertSee('500.000');

    $publicResponse = $this->get(route('public.ziswaf-v2.index', ['from' => '2026-01-01', 'through' => '2026-02-28']))
        ->assertOk()->assertSee($program->name)->assertSee('5 penerima')->assertSee('500.000')
        ->assertDontSee('Januari 2026')->assertDontSee('DRAFT')->assertDontSee('SUBMITTED')
        ->assertDontSee('Alokasi skenario E2E')->assertDontSee('Realisasi Santunan Dhuafa Februari 2026');
    foreach ($beneficiaries as $beneficiary) {
        $publicResponse->assertDontSee($beneficiary->display_name)
            ->assertDontSee($beneficiary->address)
            ->assertDontSee($beneficiary->contact_reference)
            ->assertDontSee($beneficiary->rt_coordinator_name);
    }

    expect(DB::table('financial_v2_distribution_items as item')
        ->leftJoin('financial_v2_distributions as distribution', 'distribution.id', '=', 'item.distribution_id')
        ->whereNull('distribution.id')->count())->toBe(0)
        ->and(DB::table('financial_v2_distributions as distribution')
            ->leftJoin('financial_v2_fund_realizations as realization', 'realization.id', '=', 'distribution.realization_id')
            ->whereNotNull('distribution.realization_id')->whereNull('realization.id')->count())->toBe(0)
        ->and(DB::table('financial_v2_distribution_items')->select('distribution_id', 'beneficiary_id')
            ->groupBy('distribution_id', 'beneficiary_id')->havingRaw('COUNT(*) > 1')->count())->toBe(0)
        ->and(Distribution::where('realization_id', $realization->realization->id)->count())->toBe(1)
        ->and(Journal::where('transaction_id', $realization->id)->count())->toBe(1);
});

test('the end-to-end QA scenario leaves no records outside its isolated test transaction', function () {
    expect(app()->environment())->toBe('testing')
        ->and(DB::connection()->getDatabaseName())->toBe('mrj_test_db')
        ->and(Program::where('code', 'SANTUNAN-DHUAFA-QA-2026')->count())->toBe(0)
        ->and(Counterparty::where('display_name', 'like', 'Beneficiary QA %')->count())->toBe(0)
        ->and(Distribution::where('title', 'like', 'Santunan Dhuafa % 2026')->count())->toBe(0)
        ->and(FinancialTransaction::where('description', 'Realisasi Santunan Dhuafa Februari 2026')->count())->toBe(0);
});
