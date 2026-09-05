<?php

use App\Domain\FinancialV2\AllocationHistoryReadService;
use App\Domain\FinancialV2\BudgetAllocationService;
use App\Domain\FinancialV2\FinancialDomainException;
use App\Domain\FinancialV2\FinancialPostingException;
use App\Domain\FinancialV2\FinancialTransactionLifecycleService;
use App\Domain\FinancialV2\FundPolicyCompatibilityService;
use App\Domain\FinancialV2\Reporting\FinancialReportService;
use App\Models\FinancialV2\BudgetAllocationFunding;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\JournalLine;
use App\Models\FinancialV2\LedgerEntry;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\Support\UatFinancialFixture;

test('single-Fund compatibility and editable multi-Fund allocation remain non-financial', function () {
    $context = UatFinancialFixture::context();
    $service = app(BudgetAllocationService::class);

    $single = multiFundAllocation($context, '40.00', null, 'SINGLE');
    expect($single->versions->sole()->fundings)->toHaveCount(1)
        ->and($single->versions->sole()->fundings->sole()->fund_id)->toBe($context['fund']->id);

    $factsBefore = [Journal::count(), JournalLine::count(), LedgerEntry::count()];
    $allocation = multiFundAllocation($context, '120.00', [
        ['fund_id' => $context['fund']->id, 'amount' => '70.00', 'note' => 'Bagian pertama'],
        ['fund_id' => $context['destinationFund']->id, 'amount' => '50.00', 'source_reference' => 'PROGRAM-107'],
    ], 'MULTI');
    $version = $allocation->versions->sole();

    expect($version->fundings->pluck('amount')->all())->toBe(['70.00', '50.00'])
        ->and($version->fundings->pluck('fund_id')->unique())->toHaveCount(2)
        ->and([Journal::count(), JournalLine::count(), LedgerEntry::count()])->toBe($factsBefore);

    $updated = $service->updateDraft($allocation->id, [
        'allocated_amount' => '120.00',
        'effective_from' => $context['today'],
        'program_id' => $context['program']->id,
        'account_id' => $context['expense']->id,
        'category_id' => $context['paymentCategory']->id,
        'reason' => 'Updated draft funding mix',
    ], [
        ['fund_id' => $context['fund']->id, 'amount' => '65.00'],
        ['fund_id' => $context['destinationFund']->id, 'amount' => '55.00'],
    ]);
    $updatedVersion = $updated->versions->sole();
    expect($updatedVersion->fundings->pluck('amount')->all())->toBe(['65.00', '55.00']);

    expect(fn () => multiFundAllocation($context, '120.00', [
        ['fund_id' => $context['fund']->id, 'amount' => '70.00'],
        ['fund_id' => $context['destinationFund']->id, 'amount' => '40.00'],
    ], 'MISMATCH'))->toThrow(FinancialDomainException::class, 'harus sama');

    $service->submit($updated->id);
    $service->approveVersion($updated->id, $updatedVersion->id);
    expect(fn () => BudgetAllocationFunding::findOrFail($updatedVersion->fundings->first()->id)->update(['amount' => '64.00']))
        ->toThrow(DomainException::class, 'immutable');
});

test('multi-Fund realization posts one balanced PAY through the canonical engine and reports each Fund correctly', function () {
    $context = UatFinancialFixture::context();
    $lifecycle = app(FinancialTransactionLifecycleService::class);
    $budget = app(BudgetAllocationService::class);

    $receipt = UatFinancialFixture::receipt($context, '200.00', null, [
        ['account_id' => $context['revenue']->id, 'split_amount' => '100.00', 'fund_id' => $context['fund']->id],
        ['account_id' => $context['revenue']->id, 'split_amount' => '100.00', 'fund_id' => $context['destinationFund']->id],
    ]);
    UatFinancialFixture::advance($receipt);
    UatFinancialFixture::post($receipt, 'multi-fund-receipt');

    $allocation = multiFundAllocation($context, '120.00', [
        ['fund_id' => $context['fund']->id, 'amount' => '70.00'],
        ['fund_id' => $context['destinationFund']->id, 'amount' => '50.00'],
    ], 'POST');
    $version = $allocation->versions->sole();
    $budget->submit($allocation->id);
    $budget->approveVersion($allocation->id, $version->id);

    $realization = multiFundRealization($context, $version->id, '100.00', [
        ['fund_id' => $context['fund']->id, 'amount' => '60.00', 'note' => 'Fidyah'],
        ['fund_id' => $context['destinationFund']->id, 'amount' => '40.00', 'note' => 'Zakat Maal'],
    ]);
    UatFinancialFixture::advance($realization);
    $result = UatFinancialFixture::post($realization, 'multi-fund-realization');
    $counts = [Journal::count(), JournalLine::count(), LedgerEntry::count()];
    $sameResult = $lifecycle->post($realization->id, 'multi-fund-realization', hash('sha256', 'multi-fund-realization'));

    $funding = collect($budget->fundingAvailability($version->id))->keyBy('fund_id');
    $firstAllocationSummary = app(AllocationHistoryReadService::class)->summary($context['entity']->id, $context['fund']->id);
    $secondAllocationSummary = app(AllocationHistoryReadService::class)->summary($context['entity']->id, $context['destinationFund']->id);
    $fundRows = collect(app(FinancialReportService::class)->report('fund-balance', $context['entity']->id, $context['today'], $context['today'])['data']['rows'])->keyBy('fund_id');
    $trialBalance = app(FinancialReportService::class)->report('trial-balance', $context['entity']->id, $context['today'], $context['today']);

    expect($sameResult->journalId)->toBe($result->journalId)
        ->and([Journal::count(), JournalLine::count(), LedgerEntry::count()])->toBe($counts)
        ->and(JournalLine::where('journal_id', $result->journalId)->count())->toBe(4)
        ->and(LedgerEntry::whereIn('journal_line_id', JournalLine::where('journal_id', $result->journalId)->pluck('id'))->count())->toBe(4)
        ->and(Journal::findOrFail($result->journalId)->total_debit)->toBe('100.00')
        ->and(Journal::findOrFail($result->journalId)->total_credit)->toBe('100.00')
        ->and($funding->get($context['fund']->id)['actual'])->toBe('60.00')
        ->and($funding->get($context['fund']->id)['available'])->toBe('10.00')
        ->and($funding->get($context['destinationFund']->id)['actual'])->toBe('40.00')
        ->and($funding->get($context['destinationFund']->id)['available'])->toBe('10.00')
        ->and($firstAllocationSummary)->toBe(['allocated' => '70.00', 'realized' => '60.00', 'remaining' => '10.00'])
        ->and($secondAllocationSummary)->toBe(['allocated' => '50.00', 'realized' => '40.00', 'remaining' => '10.00'])
        ->and($fundRows->get($context['fund']->id)['fund_balance'])->toBe('40.00')
        ->and($fundRows->get($context['destinationFund']->id)['fund_balance'])->toBe('60.00')
        ->and($trialBalance['data']['is_balanced'])->toBeTrue();
});

test('multi-Fund realization rejects mismatches, restricted sources, and per-Fund overspend without financial facts', function () {
    $context = UatFinancialFixture::context();
    $budget = app(BudgetAllocationService::class);

    $receipt = UatFinancialFixture::receipt($context, '200.00', null, [
        ['account_id' => $context['revenue']->id, 'split_amount' => '100.00', 'fund_id' => $context['fund']->id],
        ['account_id' => $context['revenue']->id, 'split_amount' => '100.00', 'fund_id' => $context['destinationFund']->id],
    ]);
    UatFinancialFixture::advance($receipt);
    UatFinancialFixture::post($receipt, 'multi-fund-guard-receipt');

    $allocation = multiFundAllocation($context, '120.00', [
        ['fund_id' => $context['fund']->id, 'amount' => '70.00'],
        ['fund_id' => $context['destinationFund']->id, 'amount' => '50.00'],
    ], 'GUARD');
    $version = $allocation->versions->sole();
    $budget->submit($allocation->id);
    $budget->approveVersion($allocation->id, $version->id);

    expect(fn () => multiFundRealization($context, $version->id, '100.00', [
        ['fund_id' => $context['fund']->id, 'amount' => '60.00'],
        ['fund_id' => $context['destinationFund']->id, 'amount' => '30.00'],
    ]))->toThrow(FinancialDomainException::class, 'splits must equal gross amount');

    $restricted = UatFinancialFixture::restrictedFund($context, 'BLOCKED-PAY', 'Dana Restricted Tanpa Izin', false);
    expect(fn () => multiFundAllocation($context, '10.00', [['fund_id' => $restricted->id, 'amount' => '10.00']], 'RESTRICTED'))
        ->toThrow(FinancialDomainException::class, 'tidak dapat digunakan');

    $overspend = multiFundRealization($context, $version->id, '120.00', [
        ['fund_id' => $context['fund']->id, 'amount' => '71.00'],
        ['fund_id' => $context['destinationFund']->id, 'amount' => '49.00'],
    ]);
    UatFinancialFixture::advance($overspend);
    $before = [Journal::count(), JournalLine::count(), LedgerEntry::count()];
    expect(fn () => UatFinancialFixture::post($overspend, 'multi-fund-overspend'))
        ->toThrow(FinancialPostingException::class, 'salah satu Sumber Dana');
    expect([Journal::count(), JournalLine::count(), LedgerEntry::count()])->toBe($before);
});

test('authenticated Allocation and Realization UI expose multiple funding lines while retaining single-Fund input compatibility', function () {
    $context = UatFinancialFixture::context();
    $user = User::factory()->create();
    $allocation = multiFundAllocation($context, '120.00', [
        ['fund_id' => $context['fund']->id, 'amount' => '70.00'],
        ['fund_id' => $context['destinationFund']->id, 'amount' => '50.00'],
    ], 'UI');
    $version = $allocation->versions->sole();
    app(BudgetAllocationService::class)->submit($allocation->id);
    app(BudgetAllocationService::class)->approveVersion($allocation->id, $version->id);

    $this->actingAs($user)->get(route('financial-v2.allocations.create', ['entity' => $context['entity']->id]))
        ->assertOk()->assertSee('Sumber Dana')->assertSee('funding_sources[0][fund_id]', false)->assertSee('Tambah Sumber Dana');
    $this->actingAs($user)->get(route('financial-v2.transactions.create', ['operation' => 'realization', 'entity' => $context['entity']->id, 'allocation_version_id' => $version->id]))
        ->assertOk()->assertSee('data-fundings=', false)->assertSee('data-realization-funding', false)->assertSee('Total Sumber');
});

test('restricted multi-Fund policy permits only the exact PAY program and category matrix', function () {
    $context = UatFinancialFixture::context();
    $fidyah = UatFinancialFixture::restrictedFund($context, 'FIDYAH-107', 'Dana Fidyah', false);
    $zakat = UatFinancialFixture::restrictedFund($context, 'ZAKAT-107', 'Dana Zakat Maal', false);

    foreach ([$fidyah, $zakat] as $fund) {
        $version = \App\Models\FinancialV2\FundPolicyVersion::query()->where('fund_id', $fund->id)->sole();
        \App\Models\FinancialV2\FundPolicyRule::create([
            'accounting_entity_id' => $context['entity']->id,
            'fund_policy_version_id' => $version->id,
            'transaction_type_id' => $context['paymentType']->id,
            'category_id' => $context['paymentCategory']->id,
            'program_id' => $context['program']->id,
            'decision' => 'allowed',
            'rationale' => 'Distribusi Sembako 107 Paket',
        ]);
    }

    $allocation = multiFundAllocation($context, '128.40', [
        ['fund_id' => $fidyah->id, 'amount' => '75.00'],
        ['fund_id' => $zakat->id, 'amount' => '53.40'],
    ], 'POLICY-107');
    expect($allocation->versions->sole()->fundings)->toHaveCount(2);

    $otherProgram = \App\Models\FinancialV2\Program::create([
        'accounting_entity_id' => $context['entity']->id,
        'code' => 'OTHER-'.Str::upper(Str::random(6)),
        'name' => 'Program lain',
        'start_date' => now()->subDay(),
        'end_date' => now()->addDay(),
        'status' => 'active',
    ]);
    $otherCategory = \App\Models\FinancialV2\Category::create([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['paymentType']->id,
        'code' => 'OTHER-'.Str::upper(Str::random(6)),
        'name' => 'Kategori lain',
        'status' => 'active',
    ]);
    $policies = app(FundPolicyCompatibilityService::class);

    expect(fn () => $policies->assertAllocationCompatible($context['entity']->id, [$fidyah->id, $zakat->id], $context['today'], $context['expense']->id, $context['paymentCategory']->id, $otherProgram->id))
        ->toThrow(FinancialDomainException::class, 'tidak dapat digunakan')
        ->and(fn () => $policies->assertAllocationCompatible($context['entity']->id, [$fidyah->id, $zakat->id], $context['today'], $context['expense']->id, $otherCategory->id, $context['program']->id))
        ->toThrow(FinancialDomainException::class, 'tidak dapat digunakan');
});

test('approved allocation amendment raises the governed limit and retires the superseded realization draft without financial facts', function () {
    $context = UatFinancialFixture::context();
    $user = User::factory()->create();
    $budget = app(BudgetAllocationService::class);
    $allocation = multiFundAllocation($context, '128.40', [
        ['fund_id' => $context['fund']->id, 'amount' => '75.00'],
        ['fund_id' => $context['destinationFund']->id, 'amount' => '53.40'],
    ], 'AMENDMENT');
    $versionOne = $allocation->versions->sole();
    $budget->submit($allocation->id);
    $budget->approveVersion($allocation->id, $versionOne->id, $user->id);

    $oldDraft = multiFundRealization($context, $versionOne->id, '128.40', [
        ['fund_id' => $context['fund']->id, 'amount' => '75.00'],
        ['fund_id' => $context['destinationFund']->id, 'amount' => '53.40'],
    ]);
    $factsBefore = [Journal::count(), JournalLine::count(), LedgerEntry::count()];
    $effectiveFrom = \Carbon\Carbon::parse($context['today'])->addDay()->toDateString();

    $created = $this->actingAs($user)->postJson(route('financial-v2.allocations.amendments.store', $allocation), [
        'entity' => $context['entity']->id,
        'effective_from' => $effectiveFrom,
        'amendment_amount' => '0.85',
        'funding_adjustments' => [[
            'fund_id' => $context['destinationFund']->id,
            'amount' => '0.85',
            'note' => 'Tambahan biaya aktual distribusi sembako',
        ]],
        'reason' => 'Biaya aktual melebihi rencana awal sebesar 0.85.',
    ])->assertOk()->assertJsonPath('status', 'draft');
    $versionTwo = \App\Models\FinancialV2\BudgetAllocationVersion::query()->with('fundings')->findOrFail($created->json('allocation_version_id'));

    expect($versionTwo->allocated_amount)->toBe('129.25')
        ->and($versionTwo->fundings->pluck('amount', 'fund_id')->all())->toBe([
            $context['fund']->id => '75.00',
            $context['destinationFund']->id => '54.25',
        ])
        ->and(app(AllocationHistoryReadService::class)->summary($context['entity']->id, $context['fund']->id)['allocated'])->toBe('75.00')
        ->and(app(AllocationHistoryReadService::class)->summary($context['entity']->id, $context['destinationFund']->id)['allocated'])->toBe('53.40')
        ->and($oldDraft->fresh()->status)->toBe('draft')
        ->and([Journal::count(), JournalLine::count(), LedgerEntry::count()])->toBe($factsBefore);

    $this->actingAs($user)->postJson(route('financial-v2.allocations.amendments.store', $allocation), [
        'entity' => $context['entity']->id,
        'effective_from' => $effectiveFrom,
        'amendment_amount' => '0.85',
        'funding_adjustments' => [['fund_id' => $context['destinationFund']->id, 'amount' => '0.85']],
        'reason' => 'Tidak boleh membuat perubahan kedua yang paralel.',
    ])->assertStatus(422)->assertJsonPath('code', 'E-BUDGET-REVISION-PENDING');

    $this->actingAs($user)->get(route('financial-v2.allocations.create', ['entity' => $context['entity']->id]))
        ->assertOk()
        ->assertSee('Perubahan alokasi menunggu persetujuan')
        ->assertSee('Setujui perubahan alokasi');
    $this->actingAs($user)->postJson(route('financial-v2.allocations.amendments.approve', ['allocation' => $allocation, 'version' => $versionTwo]), [
        'entity' => $context['entity']->id,
    ])->assertOk()->assertJsonPath('status', 'approved');

    expect($versionOne->fresh()->status)->toBe('superseded')
        ->and($versionTwo->fresh()->status)->toBe('approved')
        ->and($oldDraft->fresh()->status)->toBe('cancelled')
        ->and($oldDraft->realization()->value('status'))->toBe('cancelled')
        ->and(app(AllocationHistoryReadService::class)->summary($context['entity']->id, $context['fund']->id)['allocated'])->toBe('75.00')
        ->and(app(AllocationHistoryReadService::class)->summary($context['entity']->id, $context['destinationFund']->id)['allocated'])->toBe('54.25')
        ->and([Journal::count(), JournalLine::count(), LedgerEntry::count()])->toBe($factsBefore);

    $newDraft = multiFundRealization($context, $versionTwo->id, '129.25', [
        ['fund_id' => $context['fund']->id, 'amount' => '75.00'],
        ['fund_id' => $context['destinationFund']->id, 'amount' => '54.25'],
    ]);
    expect($newDraft->status)->toBe('draft')
        ->and(FinancialTransaction::query()->whereHas('realization', fn ($query) => $query->where('budget_allocation_version_id', $versionTwo->id))->count())->toBe(1)
        ->and([Journal::count(), JournalLine::count(), LedgerEntry::count()])->toBe($factsBefore);
});

/** @param array<string,mixed> $context @param array<int,array<string,mixed>>|null $fundings */
function multiFundAllocation(array $context, string $amount, ?array $fundings, string $suffix): \App\Models\FinancialV2\BudgetAllocation
{
    return app(BudgetAllocationService::class)->create([
        'accounting_entity_id' => $context['entity']->id,
        'accounting_period_id' => $context['period']->id,
        'fund_id' => $fundings === null ? $context['fund']->id : null,
        'fundings' => $fundings,
        'program_id' => $context['program']->id,
        'account_id' => $context['expense']->id,
        'category_id' => $context['paymentCategory']->id,
        'allocation_reference' => 'MF-'.$suffix.'-'.Str::uuid(),
        'idempotency_key' => 'mf-allocation-'.Str::uuid(),
        'allocated_amount' => $amount,
        'effective_from' => $context['today'],
        'reason' => 'Multi-Fund allocation test '.$suffix,
    ]);
}

/** @param array<string,mixed> $context @param array<int,array<string,mixed>> $fundings */
function multiFundRealization(array $context, string $versionId, string $amount, array $fundings): FinancialTransaction
{
    return app(FinancialTransactionLifecycleService::class)->createRealization([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['paymentType']->id,
        'business_date' => $context['today'],
        'accounting_date' => $context['today'],
        'gross_amount' => $amount,
        'source_reference' => 'MF-REAL-'.Str::uuid(),
        'idempotency_key' => 'mf-realization-'.Str::uuid(),
        'primary_financial_account_id' => $context['accountA']->id,
        'counterparty_id' => $context['supplier']->id,
        'category_id' => $context['paymentCategory']->id,
        'description' => 'Distribusi Sembako 107 Paket',
    ], collect($fundings)->map(fn (array $funding): array => [
        'account_id' => $context['expense']->id,
        'split_amount' => $funding['amount'],
        'fund_id' => $funding['fund_id'],
        'program_id' => $context['program']->id,
        'category_id' => $context['paymentCategory']->id,
        'purpose_note' => $funding['note'] ?? null,
        'source_reference' => $funding['source_reference'] ?? null,
    ])->all(), $versionId);
}
