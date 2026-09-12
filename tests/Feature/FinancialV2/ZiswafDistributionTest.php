<?php

use App\Domain\FinancialV2\BudgetAllocationService;
use App\Domain\FinancialV2\DistributionService;
use App\Domain\FinancialV2\FinancialTransactionLifecycleService;
use App\Domain\FinancialV2\Reporting\DistributionReportingService;
use App\Domain\FinancialV2\Reporting\ZiswafReportingV2Service;
use App\Models\FinancialV2\Counterparty;
use App\Models\FinancialV2\Distribution;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\JournalLine;
use App\Models\FinancialV2\LedgerEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\Support\UatFinancialFixture;

function distributionPerson(array $c, array $overrides = []): Counterparty
{
    return app(DistributionService::class)->saveBeneficiary($c['entity']->id, $overrides + [
        'display_name' => 'Penerima Privat Budi', 'address' => 'Alamat Privat A', 'contact_reference' => '081234599999',
        'rt' => '05', 'rw' => '04', 'rt_coordinator_name' => 'Koordinator Privat', 'beneficiary_notes' => 'Catatan Privat', 'status' => 'active',
    ], null, null);
}

function distributionInput(array $c, int $monthOffset = 0): array
{
    $date = now()->startOfMonth()->addMonths($monthOffset);

    return ['program_id' => $c['program']->id, 'title' => 'Judul Internal', 'period_label' => $date->format('F Y'), 'starts_on' => $date->toDateString(), 'ends_on' => $date->endOfMonth()->toDateString(), 'notes' => 'Catatan Penyaluran Privat'];
}

function distributionAttach(Distribution $d, Counterparty $p, string $amount = '120.00'): Distribution
{
    return app(DistributionService::class)->item($d->accounting_entity_id, $d->id, ['revision' => $d->fresh()->revision, 'beneficiary_id' => $p->id, 'amount' => $amount], null, false, null);
}

function distributionFinancialCounts(): array
{
    return [FinancialTransaction::count(), Journal::count(), JournalLine::count(), LedgerEntry::count()];
}

/** Snapshot every financial fact family that distribution draft deletion must not mutate. */
function distributionProtectedFinancialState(): array
{
    $tables = [
        'financial_v2_transactions', 'financial_v2_transaction_splits', 'financial_v2_journals',
        'financial_v2_journal_lines', 'financial_v2_ledger_entries', 'financial_v2_vouchers',
        'financial_v2_fund_realizations', 'financial_v2_budget_allocations', 'financial_v2_funds',
        'financial_v2_opening_balance_batches', 'financial_v2_opening_balance_lines',
    ];

    return collect($tables)->mapWithKeys(fn (string $table) => [
        $table => DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all(),
    ])->all();
}

function distributionRealization(array $c): FinancialTransaction
{
    $budget = app(BudgetAllocationService::class);
    $allocation = $budget->create([
        'accounting_entity_id' => $c['entity']->id, 'accounting_period_id' => $c['period']->id,
        'fundings' => [['fund_id' => $c['fund']->id, 'amount' => '70.00'], ['fund_id' => $c['destinationFund']->id, 'amount' => '50.00']],
        'program_id' => $c['program']->id, 'account_id' => $c['expense']->id, 'category_id' => $c['paymentCategory']->id,
        'allocation_reference' => 'DIST-'.Str::uuid(), 'idempotency_key' => 'dist-'.Str::uuid(), 'allocated_amount' => '120.00', 'effective_from' => $c['today'], 'reason' => 'Synthetic distribution test',
    ]);
    $version = $allocation->versions->sole();
    $budget->submit($allocation->id);
    $budget->approveVersion($allocation->id, $version->id);

    return app(FinancialTransactionLifecycleService::class)->createRealization([
        'accounting_entity_id' => $c['entity']->id, 'transaction_type_id' => $c['paymentType']->id,
        'business_date' => $c['today'], 'accounting_date' => $c['today'], 'gross_amount' => '120.00',
        'source_reference' => 'DIST-'.Str::uuid(), 'idempotency_key' => 'dist-realization-'.Str::uuid(),
        'primary_financial_account_id' => $c['accountA']->id, 'counterparty_id' => $c['supplier']->id,
        'category_id' => $c['paymentCategory']->id, 'description' => 'Synthetic existing realization',
    ], [
        ['account_id' => $c['expense']->id, 'fund_id' => $c['fund']->id, 'program_id' => $c['program']->id, 'split_amount' => '70.00'],
        ['account_id' => $c['expense']->id, 'fund_id' => $c['destinationFund']->id, 'program_id' => $c['program']->id, 'split_amount' => '50.00'],
    ], $version->id);
}

function distributionUser(): User
{
    return User::factory()->create();
}

test('cancelled realization cannot finalize or publish a distribution', function () {
    $c = UatFinancialFixture::context();
    $s = app(DistributionService::class);
    $d = $s->create($c['entity']->id, distributionInput($c), null);
    distributionAttach($d, distributionPerson($c));
    $tx = distributionRealization($c);
    app(FinancialTransactionLifecycleService::class)->cancel($tx->id, 'Synthetic cancellation');
    $before = distributionFinancialCounts();
    expect(fn () => $s->finalize($c['entity']->id, $d->id, $tx->realization->id, $d->fresh()->revision, null))->toThrow(ValidationException::class, 'dibatalkan');
    expect(distributionFinancialCounts())->toBe($before);
});

test('beneficiary master stays paginated while distribution selection shows all matching recipients', function () {
    $c = UatFinancialFixture::context();
    for ($i = 1; $i <= 25; $i++) {
        distributionPerson($c, ['display_name' => 'Recipient '.str_pad((string) $i, 2, '0', STR_PAD_LEFT)]);
    }
    $d = app(DistributionService::class)->create($c['entity']->id, distributionInput($c), null);
    $this->actingAs(distributionUser());
    $this->get(route('financial-v2.beneficiaries.index', ['entity' => $c['entity']->id]))->assertOk()->assertSee('Recipient 20')->assertDontSee('Recipient 21');
    $this->get(route('financial-v2.beneficiaries.index', ['entity' => $c['entity']->id, 'page' => 2]))->assertOk()->assertSee('Recipient 25')->assertDontSee('Recipient 01');
    $this->get(route('financial-v2.distributions.show', ['entity' => $c['entity']->id, 'distribution' => $d->id]))
        ->assertOk()->assertSee('Recipient 01')->assertSee('Recipient 25')->assertDontSee('people_page');
});

test('distribution selection groups by rw then rt and sorts names within the group', function () {
    $c = UatFinancialFixture::context();
    $zulu = distributionPerson($c, ['display_name' => 'Zulu RW Four', 'rw' => '04', 'rt' => '03']);
    $alpha = distributionPerson($c, ['display_name' => 'Alpha RW Four', 'rw' => '04', 'rt' => '03']);
    $beta = distributionPerson($c, ['display_name' => 'Beta RW Four', 'rw' => '04', 'rt' => '05']);
    $six = distributionPerson($c, ['display_name' => 'Alpha RW Six', 'rw' => '06', 'rt' => '03']);
    $tce = distributionPerson($c, ['display_name' => 'Alpha TCE', 'rw' => '08', 'rt' => 'TCE', 'rt_coordinator_name' => 'Pak Indra (TCE)']);
    $d = app(DistributionService::class)->create($c['entity']->id, distributionInput($c), null);
    $this->actingAs(distributionUser());

    $response = $this->get(route('financial-v2.distributions.show', ['entity' => $c['entity']->id, 'distribution' => $d->id]))
        ->assertOk()->assertSee('RW: 04')->assertSee('RT: 03 · RW: 04 · Total Penerima: 2')
        ->assertSee('RT: 05 · RW: 04 · Total Penerima: 1')->assertSee('RW: 06')
        ->assertSee('RT: 03 · RW: 06 · Total Penerima: 1')
        ->assertSee('RT: TCE · RW: 08 · Total Penerima: 1')->assertSee('Pak Indra (TCE)');
    $html = $response->getContent();

    expect(strpos($html, 'RW: 04'))->toBeLessThan(strpos($html, 'RW: 06'))
        ->and(strpos($html, 'RT: 03 · RW: 04'))->toBeLessThan(strpos($html, 'RT: 05 · RW: 04'))
        ->and(strpos($html, 'Alpha RW Four'))->toBeLessThan(strpos($html, 'Zulu RW Four'));

    foreach ([$zulu, $alpha, $beta, $six, $tce] as $person) {
        distributionAttach($d, $person, '100.00');
    }
    $managedResponse = $this->get(route('financial-v2.distributions.show', ['entity' => $c['entity']->id, 'distribution' => $d->id]))
        ->assertOk()->assertSee('Kelola Penerima Draft')->assertSee('Penerima: 5')->assertSee('Total: Rp500')
        ->assertSee('data-draft-rw-group', false)->assertSee('data-draft-rt-group', false)
        ->assertSee('RT: 03 · RW: 04 · Total Penerima: 2')
        ->assertSee('RT: 03 · RW: 06 · Total Penerima: 1')
        ->assertSee('RT: TCE · RW: 08 · Total Penerima: 1');
    $managedHtml = substr($managedResponse->getContent(), strpos($managedResponse->getContent(), 'data-draft-items'));

    expect(strpos($managedHtml, 'RW: 04'))->toBeLessThan(strpos($managedHtml, 'RW: 06'))
        ->and(strpos($managedHtml, 'Alpha RW Four'))->toBeLessThan(strpos($managedHtml, 'Zulu RW Four'))
        ->and(substr_count($managedHtml, 'RT: 03 · RW: 04 · Total Penerima: 2'))->toBe(1)
        ->and(substr_count($managedHtml, 'RT: 03 · RW: 06 · Total Penerima: 1'))->toBe(1);
});

test('internal aggregate report does not expose recipient identity', function () {
    $c = UatFinancialFixture::context();
    $d = app(DistributionService::class)->create($c['entity']->id, distributionInput($c), null);
    $p = distributionPerson($c);
    distributionAttach($d, $p);
    $this->actingAs(distributionUser());
    $this->get(route('financial-v2.ziswaf-v2.program', ['entity' => $c['entity']->id, 'program' => $c['program']->id, 'from' => $c['today'], 'through' => $c['today']]))
        ->assertOk()->assertSee('Penyaluran dan penerima manfaat')->assertSee('1 penyaluran')->assertDontSee($p->display_name)->assertDontSee($p->address)->assertDontSee($p->contact_reference);
});

test('entity selection works without granular permission records', function () {
    $this->actingAs(distributionUser());
    expect(\Spatie\Permission\Models\Permission::query()->count())->toBe(0);
    $this->get(route('financial-v2.beneficiaries.index'))->assertOk()->assertSee('Pilih entitas Financial V2');
    $this->get(route('financial-v2.distributions.index'))->assertOk()->assertSee('Pilih entitas Financial V2');
    expect(\Spatie\Permission\Models\Permission::query()->count())->toBe(0);
});

test('beneficiary master reuses Counterparty and updates cannot rewrite historical identity', function () {
    $c = UatFinancialFixture::context();
    $before = distributionFinancialCounts();
    $s = app(DistributionService::class);
    $p = distributionPerson($c);
    $d = $s->create($c['entity']->id, distributionInput($c), null);
    distributionAttach($d, $p);
    $original = $d->items()->sole()->identity_snapshot;
    $s->saveBeneficiary($c['entity']->id, ['display_name' => 'Budi Baru', 'address' => 'Alamat B', 'status' => 'active'], $p->id, null);
    expect($p->fresh()->display_name)->toBe('Budi Baru')->and($p->party_type)->toBe('beneficiary')
        ->and($d->items()->sole()->identity_snapshot)->toBe($original)
        ->and($p->distributionItems()->count())->toBe(1)
        ->and(distributionFinancialCounts())->toBe($before);
});

test('authorized beneficiary UI creates updates searches filters and displays detail', function () {
    $c = UatFinancialFixture::context();
    $this->actingAs(distributionUser());
    $this->post(route('financial-v2.beneficiaries.store'), ['entity' => $c['entity']->id, 'display_name' => 'UI Penerima', 'contact_reference' => '089000123', 'status' => 'active', 'rt' => '05', 'rw' => '04', 'rt_coordinator_name' => 'Koordinator UI'])->assertRedirect()->assertSessionHasNoErrors();
    $p = Counterparty::where('display_name', 'UI Penerima')->sole();
    $this->patch(route('financial-v2.beneficiaries.update', $p->id), ['entity' => $c['entity']->id, 'display_name' => 'UI Nama Baru', 'status' => 'active'])->assertRedirect()->assertSessionHasNoErrors();
    foreach (['UI Nama', '089000', '05', '04'] as $q) {
        $this->get(route('financial-v2.beneficiaries.index', ['entity' => $c['entity']->id, 'q' => $q, 'rt' => '05', 'rw' => '04', 'coordinator' => 'Koordinator UI', 'status' => 'active']))->assertOk()->assertSee('UI Nama Baru');
    }
    $this->get(route('financial-v2.beneficiaries.index', ['entity' => $c['entity']->id, 'rt' => '99']))->assertOk()->assertDontSee('UI Nama Baru');
    $this->get(route('financial-v2.beneficiaries.show', ['entity' => $c['entity']->id, 'beneficiary' => $p->id]))->assertOk()->assertSee('Riwayat penyaluran');
});

test('duplicate recipients and overlapping periods are blocked without changing the original', function () {
    $c = UatFinancialFixture::context();
    $s = app(DistributionService::class);
    $p = distributionPerson($c);
    $d = $s->create($c['entity']->id, distributionInput($c), null);
    distributionAttach($d, $p);
    expect(fn () => distributionAttach($d, $p))->toThrow(ValidationException::class, 'sudah ada');
    expect(fn () => $s->create($c['entity']->id, distributionInput($c), null))->toThrow(ValidationException::class, 'bertumpang tindih');
    expect($d->items()->count())->toBe(1)->and(Distribution::count())->toBe(1);
});

test('item rejects invalid amount', function ($amount) {
    $c = UatFinancialFixture::context();
    $s = app(DistributionService::class);
    $p = distributionPerson($c);
    $d = $s->create($c['entity']->id, distributionInput($c), null);
    expect(fn () => distributionAttach($d, $p, $amount))->toThrow(ValidationException::class);
    expect($d->items()->count())->toBe(0);
})->with(['negative' => '-1.00', 'too precise' => '1.001', 'exponent' => '1e3', 'overflow' => '10000000000000000.00']);

test('batch adds multiple recipients with independent exact amounts without financial posting', function () {
    $c = UatFinancialFixture::context();
    $people = [
        distributionPerson($c, ['display_name' => 'Batch Satu']),
        distributionPerson($c, ['display_name' => 'Batch Dua']),
        distributionPerson($c, ['display_name' => 'Batch Tiga']),
    ];
    $d = app(DistributionService::class)->create($c['entity']->id, distributionInput($c), null);
    $before = distributionFinancialCounts();

    app(DistributionService::class)->addItemsToDraft($c['entity']->id, $d->id, [
        'revision' => 0,
        'items' => [
            ['beneficiary_id' => $people[0]->id, 'amount' => '100000', 'notes' => 'Satu'],
            ['beneficiary_id' => $people[1]->id, 'amount' => '125000', 'notes' => 'Dua'],
            ['beneficiary_id' => $people[2]->id, 'amount' => '150000', 'notes' => 'Tiga'],
        ],
    ], null);

    expect($d->items()->count())->toBe(3)
        ->and($d->fresh()->revision)->toBe(1)
        ->and($d->items()->orderBy('amount')->pluck('amount')->all())->toBe(['100000.00', '125000.00', '150000.00'])
        ->and(\App\Domain\FinancialV2\DecimalAmount::sum($d->items()->pluck('amount')))->toBe('375000.00')
        ->and(distributionFinancialCounts())->toBe($before);
});

test('batch endpoint supports one or many recipients and stale double submit creates no duplicate', function () {
    $c = UatFinancialFixture::context();
    $first = distributionPerson($c, ['display_name' => 'HTTP Batch Satu']);
    $second = distributionPerson($c, ['display_name' => 'HTTP Batch Dua']);
    $d = app(DistributionService::class)->create($c['entity']->id, distributionInput($c), null);
    $this->actingAs(distributionUser());
    $payload = [
        'entity' => $c['entity']->id,
        'revision' => 0,
        'items' => [
            ['beneficiary_id' => $first->id, 'amount' => '1000', 'notes' => null],
            ['beneficiary_id' => $second->id, 'amount' => '1250', 'notes' => null],
        ],
    ];

    $this->post(route('financial-v2.distributions.items.store', $d->id), $payload)
        ->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('distribution_batch_added', true);
    expect($d->items()->count())->toBe(2)->and($d->fresh()->revision)->toBe(1);

    $this->post(route('financial-v2.distributions.items.store', $d->id), $payload)
        ->assertRedirect()->assertSessionHasErrors();
    expect($d->items()->count())->toBe(2)->and($d->fresh()->revision)->toBe(1);
});

test('batch duplicate or invalid row rolls back every recipient', function ($scenario) {
    $c = UatFinancialFixture::context();
    $first = distributionPerson($c, ['display_name' => 'Atomic Satu']);
    $second = distributionPerson($c, ['display_name' => 'Atomic Dua']);
    $d = app(DistributionService::class)->create($c['entity']->id, distributionInput($c), null);

    $items = $scenario === 'duplicate payload'
        ? [
            ['beneficiary_id' => $first->id, 'amount' => '100.00'],
            ['beneficiary_id' => $first->id, 'amount' => '200.00'],
        ]
        : [
            ['beneficiary_id' => $first->id, 'amount' => '100.00'],
            ['beneficiary_id' => $second->id, 'amount' => '1.250.000'],
        ];

    expect(fn () => app(DistributionService::class)->addItemsToDraft($c['entity']->id, $d->id, ['revision' => 0, 'items' => $items], null))
        ->toThrow(ValidationException::class);
    expect($d->items()->count())->toBe(0)->and($d->fresh()->revision)->toBe(0);
})->with(['duplicate payload', 'invalid amount']);

test('batch rejects existing inactive and cross entity recipients atomically', function ($scenario) {
    $c = UatFinancialFixture::context();
    $valid = distributionPerson($c, ['display_name' => 'Batch Valid']);
    $invalid = distributionPerson($c, ['display_name' => 'Batch Invalid']);
    if ($scenario === 'existing') {
        $d = app(DistributionService::class)->create($c['entity']->id, distributionInput($c), null);
        distributionAttach($d, $invalid);
        $revision = 1;
    } else {
        $d = app(DistributionService::class)->create($c['entity']->id, distributionInput($c), null);
        $revision = 0;
        if ($scenario === 'inactive') {
            $invalid->update(['status' => 'inactive']);
        } else {
            $other = UatFinancialFixture::context();
            $invalid = distributionPerson($other, ['display_name' => 'Other Entity Recipient']);
        }
    }
    $beforeCount = $d->items()->count();

    expect(fn () => app(DistributionService::class)->addItemsToDraft($c['entity']->id, $d->id, [
        'revision' => $revision,
        'items' => [
            ['beneficiary_id' => $valid->id, 'amount' => '100.00'],
            ['beneficiary_id' => $invalid->id, 'amount' => '100.00'],
        ],
    ], null))->toThrow(ValidationException::class);
    expect($d->items()->count())->toBe($beforeCount)->and($d->fresh()->revision)->toBe($revision);
})->with(['existing', 'inactive', 'cross entity']);

test('batch respects draft immutability and optimistic revision', function ($scenario) {
    $c = UatFinancialFixture::context();
    $d = app(DistributionService::class)->create($c['entity']->id, distributionInput($c), null);
    $existing = distributionPerson($c, ['display_name' => 'Existing Recipient']);
    distributionAttach($d, $existing, '120.00');
    $candidate = distributionPerson($c, ['display_name' => 'Batch Candidate']);
    $revision = $d->fresh()->revision;
    if ($scenario === 'finalized') {
        $tx = distributionRealization($c);
        app(DistributionService::class)->finalize($c['entity']->id, $d->id, $tx->realization->id, $revision, null);
        $revision = $d->fresh()->revision;
    }

    expect(fn () => app(DistributionService::class)->addItemsToDraft($c['entity']->id, $d->id, [
        'revision' => $scenario === 'stale revision' ? 0 : $revision,
        'items' => [['beneficiary_id' => $candidate->id, 'amount' => '100.00']],
    ], null))->toThrow(ValidationException::class);
    expect($d->items()->count())->toBe(1);
})->with(['stale revision', 'finalized']);

test('distribution selection UI exposes persistent bulk staging and honest status filters', function () {
    $c = UatFinancialFixture::context();
    $active = distributionPerson($c, ['display_name' => 'Selectable Active']);
    $inactive = distributionPerson($c, ['display_name' => 'Visible Inactive', 'status' => 'inactive']);
    $d = app(DistributionService::class)->create($c['entity']->id, distributionInput($c), null);
    $this->actingAs(distributionUser());

    $this->get(route('financial-v2.distributions.show', ['entity' => $c['entity']->id, 'distribution' => $d->id]))
        ->assertOk()->assertSee($active->display_name)->assertDontSee($inactive->display_name)
        ->assertSee('data-beneficiary-select', false)->assertSee('data-select-all-page', false)
        ->assertSee('data-staging-template', false)->assertSee('sessionStorage', false)
        ->assertSee('data-money-input', false)->assertSee('data-money-value', false)
        ->assertSee('Pilih semua yang tersedia')->assertSee('sm:grid-cols-2', false)
        ->assertSee('lg:grid-cols-3', false)->assertSee('xl:grid-cols-4', false)
        ->assertSee('Kategori')->assertSee('Koordinator')->assertSee('Status')
        ->assertSee('Tambah Penerima ke Draft')->assertSee('Kelola Penerima Draft')
        ->assertDontSee('data-staging-notes', false);
    $this->get(route('financial-v2.distributions.show', ['entity' => $c['entity']->id, 'distribution' => $d->id, 'status' => 'inactive']))
        ->assertOk()->assertSee($inactive->display_name)->assertSee('Tidak dapat dipilih');
    $this->get(route('financial-v2.distributions.show', ['entity' => $c['entity']->id, 'distribution' => $d->id, 'status' => '']))
        ->assertOk()->assertSee($active->display_name)->assertSee($inactive->display_name);
});

test('copy is a new draft with fresh snapshots and independently editable recipients', function () {
    $c = UatFinancialFixture::context();
    $s = app(DistributionService::class);
    $p = distributionPerson($c);
    $old = $s->create($c['entity']->id, distributionInput($c), null);
    distributionAttach($old, $p);
    $tx = distributionRealization($c);
    $s->finalize($c['entity']->id, $old->id, $tx->realization->id, $old->fresh()->revision, null);
    $before = distributionFinancialCounts();
    $oldSnapshot = $old->fresh()->toArray();
    $oldItem = $old->items()->sole()->toArray();
    $s->saveBeneficiary($c['entity']->id, ['display_name' => 'Nama Periode Baru', 'status' => 'active'], $p->id, null);
    $copy = $s->copyPrevious($c['entity']->id, distributionInput($c, 1), null);
    expect($copy->id)->not->toBe($old->id)->and($copy->status)->toBe('draft')->and($copy->realization_id)->toBeNull()
        ->and($copy->finalized_at)->toBeNull()->and($copy->copied_from_id)->toBe($old->id)
        ->and($copy->items()->sole()->id)->not->toBe($oldItem['id'])
        ->and($copy->items()->sole()->identity_snapshot['display_name'])->toBe('Nama Periode Baru');
    $item = $copy->items()->sole();
    $s->item($c['entity']->id, $copy->id, ['revision' => 0, 'beneficiary_id' => $p->id, 'amount' => '0.00', 'notes' => 'Diperbarui'], $item->id, false, null);
    expect($item->fresh()->amount)->toBe('0.00');
    $newPerson = distributionPerson($c, ['display_name' => 'Penerima Tambahan']);
    distributionAttach($copy, $newPerson, '120.00');
    $s->item($c['entity']->id, $copy->id, ['revision' => $copy->fresh()->revision], $item->id, true, null);
    expect($copy->items()->sole()->beneficiary_id)->toBe($newPerson->id)
        ->and($old->fresh()->toArray())->toBe($oldSnapshot)->and($old->items()->sole()->toArray())->toBe($oldItem)
        ->and($c['program']->distributions()->count())->toBe(2)->and(distributionFinancialCounts())->toBe($before);
});

test('copy without previous period rolls back the entire new distribution', function () {
    $c = UatFinancialFixture::context();
    expect(fn () => app(DistributionService::class)->copyPrevious($c['entity']->id, distributionInput($c), null))->toThrow(ValidationException::class, 'Belum ada');
    expect(Distribution::count())->toBe(0);
});

test('finalization rejects empty or mismatching totals', function ($amount) {
    $c = UatFinancialFixture::context();
    $s = app(DistributionService::class);
    $d = $s->create($c['entity']->id, distributionInput($c), null);
    if ($amount !== null) {
        distributionAttach($d, distributionPerson($c), $amount);
    }
    $tx = distributionRealization($c);
    $before = distributionFinancialCounts();
    expect(fn () => $s->finalize($c['entity']->id, $d->id, $tx->realization->id, $d->fresh()->revision, null))->toThrow(ValidationException::class);
    expect($d->fresh()->status)->toBe('draft')->and($d->fresh()->realization_id)->toBeNull()->and(distributionFinancialCounts())->toBe($before);
})->with(['empty' => [null], 'under' => ['119.99'], 'over' => ['120.01']]);

test('finalized distribution protects items and realizes multi-Fund total only once without posting', function () {
    $c = UatFinancialFixture::context();
    $s = app(DistributionService::class);
    $d = $s->create($c['entity']->id, distributionInput($c), null);
    $p = distributionPerson($c);
    distributionAttach($d, $p, '70.00');
    distributionAttach($d, distributionPerson($c, ['display_name' => 'Penerima Kedua']), '50.00');
    $tx = distributionRealization($c);
    $before = distributionFinancialCounts();
    $s->finalize($c['entity']->id, $d->id, $tx->realization->id, $d->fresh()->revision, null);
    expect($d->fresh()->status)->toBe('finalized')->and($tx->fresh()->status)->toBe('draft')->and($tx->splits()->count())->toBe(2);
    expect(fn () => distributionAttach($d, $p))->toThrow(ValidationException::class);
    expect(fn () => $d->items()->first()->update(['amount' => '1.00']))->toThrow(DomainException::class);
    expect(fn () => $d->items()->first()->delete())->toThrow(DomainException::class);
    expect(fn () => $d->fresh()->update(['status' => 'draft']))->toThrow(DomainException::class);
    $this->actingAs(distributionUser())->get(route('financial-v2.distributions.show', ['entity' => $c['entity']->id, 'distribution' => $d->id]))
        ->assertOk()->assertSee('Kelola Penerima Draft')->assertSee('Terkunci')
        ->assertSee('RT: 05 · RW: 04 · Total Penerima: 2')
        ->assertDontSee('<section data-distribution-selection', false)->assertDontSee('>Edit</summary>', false);
    $next = $s->copyPrevious($c['entity']->id, distributionInput($c, 1), null);
    expect(fn () => $s->finalize($c['entity']->id, $next->id, $tx->realization->id, 0, null))->toThrow(ValidationException::class, 'sudah dikaitkan');
    expect(distributionFinancialCounts())->toBe($before);
});

test('stale revision and inactive beneficiary are blocked', function () {
    $c = UatFinancialFixture::context();
    $s = app(DistributionService::class);
    $d = $s->create($c['entity']->id, distributionInput($c), null);
    $p = distributionPerson($c);
    distributionAttach($d, $p);
    $item = $d->items()->sole();
    expect(fn () => $s->item($c['entity']->id, $d->id, ['revision' => 0], $item->id, true, null))->toThrow(ValidationException::class, 'Muat ulang');
    $p->update(['status' => 'inactive']);
    $tx = distributionRealization($c);
    expect(fn () => $s->finalize($c['entity']->id, $d->id, $tx->realization->id, $d->fresh()->revision, null))->toThrow(ValidationException::class, 'tidak aktif');
});

test('public distribution report excludes drafts PII and partial Fund scope and exposes only posted aggregates', function () {
    $c = UatFinancialFixture::context();
    $s = app(DistributionService::class);
    $receipt = UatFinancialFixture::receipt($c, '200.00', null, [
        ['account_id' => $c['revenue']->id, 'fund_id' => $c['fund']->id, 'split_amount' => '100.00'],
        ['account_id' => $c['revenue']->id, 'fund_id' => $c['destinationFund']->id, 'split_amount' => '100.00'],
    ]);
    UatFinancialFixture::advance($receipt);
    UatFinancialFixture::post($receipt, 'distribution-receipt');
    $p = distributionPerson($c);
    $d = $s->create($c['entity']->id, distributionInput($c), null);
    distributionAttach($d, $p);
    $tx = distributionRealization($c);
    config()->set('financial_reporting.public_ziswaf.entity_code', $c['entity']->code);
    config()->set('financial_reporting.public_ziswaf.fund_codes', [$c['fund']->code, $c['destinationFund']->code]);
    $reporter = app(ZiswafReportingV2Service::class);
    expect($reporter->publicReport($c['today'], $c['today'])['distributions']['events'])->toBe([]);
    $s->finalize($c['entity']->id, $d->id, $tx->realization->id, $d->fresh()->revision, null);
    expect($reporter->publicReport($c['today'], $c['today'])['distributions']['events'])->toBe([]);
    UatFinancialFixture::advance($tx);
    UatFinancialFixture::post($tx, 'distribution-realization');
    $before = distributionFinancialCounts();
    $report = $reporter->publicReport($c['today'], $c['today']);
    $event = collect($report['distributions']['events'])->sole();
    expect($event['recipient_count'])->toBe(1)->and($event['actual_amount'])->toBe('120.00')
        ->and($report['distributions']['unique_beneficiaries'])->toBe(1)->and($report['summary']['expenses'])->toBe('120.00')
        ->and($report['distributions']['regions'])->toBe([['rt' => '05', 'rw' => '04', 'recipient_count' => 1]])
        ->and($event)->not->toHaveKeys(['distribution_id', 'operational_status', 'financial_status', 'identity_snapshot', 'notes']);
    $this->get(route('public.ziswaf-v2.index', ['from' => $c['today'], 'through' => $c['today']]))->assertOk()->assertSee('1 penerima')
        ->assertSee('RT 05 / RW 04')
        ->assertDontSee($p->display_name)->assertDontSee($p->address)->assertDontSee($p->contact_reference)->assertDontSee($p->rt_coordinator_name)
        ->assertDontSee('Catatan Penyaluran Privat')->assertDontSee('Judul Internal')->assertDontSee('DRAFT')->assertDontSee('SUBMITTED');
    config()->set('financial_reporting.public_ziswaf.fund_codes', [$c['fund']->code]);
    expect($reporter->publicReport($c['today'], $c['today'])['distributions']['events'])->toBe([]);
    expect(distributionFinancialCounts())->toBe($before);
});

test('internal reporting counts unique people across multiple periods without counting amounts as actuals', function () {
    $c = UatFinancialFixture::context();
    $s = app(DistributionService::class);
    $p = distributionPerson($c);
    $d = $s->create($c['entity']->id, distributionInput($c), null);
    distributionAttach($d, $p);
    $s->copyPrevious($c['entity']->id, distributionInput($c, 1), null);
    $r = app(DistributionReportingService::class)->report($c['entity']->id, now()->startOfMonth()->toDateString(), now()->addMonthsNoOverflow(1)->endOfMonth()->toDateString(), [$c['fund']->id, $c['destinationFund']->id]);
    expect($r['unique_beneficiaries'])->toBe(1)->and($r['distribution_events'])->toBe(2)
        ->and($r['programs'][0]['operational_total'])->toBe('240.00')->and($r['programs'][0]['actual_amount'])->toBe('0.00');
});

test('distribution HTTP workflow supports creation item edit delete copying and finalization', function () {
    $c = UatFinancialFixture::context();
    $p = distributionPerson($c);
    $this->actingAs(distributionUser());
    $this->post(route('financial-v2.distributions.store'), ['entity' => $c['entity']->id] + distributionInput($c))->assertRedirect()->assertSessionHasNoErrors();
    $d = Distribution::sole();
    $this->get(route('financial-v2.distributions.index', ['entity' => $c['entity']->id]))->assertOk()->assertSee('Salin dari Penyaluran Sebelumnya');
    $this->post(route('financial-v2.distributions.items.store', $d->id), ['entity' => $c['entity']->id, 'revision' => 0, 'beneficiary_id' => $p->id, 'amount' => '100.00'])->assertRedirect()->assertSessionHasNoErrors();
    $item = $d->items()->sole();
    $this->patch(route('financial-v2.distributions.items.update', [$d->id, $item->id]), ['entity' => $c['entity']->id, 'revision' => 1, 'beneficiary_id' => $p->id, 'amount' => '120.00'])->assertRedirect()->assertSessionHasNoErrors();
    $tx = distributionRealization($c);
    $this->get(route('financial-v2.distributions.show', ['entity' => $c['entity']->id, 'distribution' => $d->id, 'q' => 'Budi']))->assertOk()->assertSee('Total cocok');
    $this->post(route('financial-v2.distributions.finalize', $d->id), ['entity' => $c['entity']->id, 'revision' => 2, 'realization_id' => $tx->realization->id])->assertRedirect()->assertSessionHasNoErrors();
    $this->post(route('financial-v2.distributions.store'), ['entity' => $c['entity']->id, 'copy_previous' => 1] + distributionInput($c, 1))->assertRedirect()->assertSessionHasNoErrors();
    $copy = Distribution::where('copied_from_id', $d->id)->sole();
    $this->delete(route('financial-v2.distributions.items.destroy', [$copy->id, $copy->items()->sole()->id]), ['entity' => $c['entity']->id, 'revision' => 0])->assertRedirect()->assertSessionHasNoErrors();
    expect($copy->items()->count())->toBe(0)->and($d->items()->count())->toBe(1);
});

test('authorized delete removes only an unrealized draft and its items with an immutable audit record', function () {
    $c = UatFinancialFixture::context();
    $service = app(DistributionService::class);
    $actor = distributionUser();
    $this->actingAs($actor);

    // Keep non-empty allocation, transaction, realization, and Fund records beside the draft.
    distributionRealization($c);
    $draft = $service->create($c['entity']->id, distributionInput($c), $actor->id);
    distributionAttach($draft, distributionPerson($c, ['display_name' => 'Alpha Delete']));
    distributionAttach($draft, distributionPerson($c, ['display_name' => 'Beta Delete']), '80.00');
    $itemIds = $draft->items()->pluck('id');
    $financialBefore = distributionProtectedFinancialState();

    $list = $this->get(route('financial-v2.distributions.index', ['entity' => $c['entity']->id]))
        ->assertOk()->assertSee('Detail / edit')->assertSee('Salin ke periode berikutnya')
        ->assertSee('data-distribution-action-group', false)->assertSee('flex flex-wrap items-center gap-3 text-sm', false)
        ->assertSee('link whitespace-nowrap', false)
        ->assertSee('data-delete-distribution-trigger', false)->assertSee('Hapus Draft Penyaluran?')
        ->assertSee('data-delete-distribution-dialog', false)
        ->assertSee('Data transaksi keuangan tidak akan dihapus.');

    $this->delete(route('financial-v2.distributions.destroy', $draft->id), ['entity' => $c['entity']->id])
        ->assertRedirect(route('financial-v2.distributions.index', ['entity' => $c['entity']->id]))
        ->assertSessionHas('success', 'Draft penyaluran berhasil dihapus.');

    $this->assertDatabaseMissing('financial_v2_distributions', ['id' => $draft->id]);
    foreach ($itemIds as $itemId) {
        $this->assertDatabaseMissing('financial_v2_distribution_items', ['id' => $itemId]);
    }
    expect(distributionProtectedFinancialState())->toBe($financialBefore)
        ->and(DB::table('financial_v2_distribution_items')->where('distribution_id', $draft->id)->count())->toBe(0);

    $audit = DB::table('financial_v2_audit_events')->where('event_type', 'distribution.deleted')->where('target_id', $draft->id)->sole();
    $before = json_decode($audit->before_summary, true, 512, JSON_THROW_ON_ERROR);
    expect((int) $audit->actor_user_id)->toBe($actor->id)
        ->and($audit->accounting_entity_id)->toBe($c['entity']->id)
        ->and($before['status'])->toBe('draft')->and($before['recipient_count'])->toBe(2)
        ->and($before['operational_total'])->toBe('200.00');

    $this->get(route('financial-v2.distributions.show', ['entity' => $c['entity']->id, 'distribution' => $draft->id]))->assertNotFound();
});

test('finalized and newly linked stale distributions cannot be deleted through the endpoint', function () {
    $c = UatFinancialFixture::context();
    $service = app(DistributionService::class);
    $this->actingAs(distributionUser());

    $finalized = $service->create($c['entity']->id, distributionInput($c), null);
    distributionAttach($finalized, distributionPerson($c), '120.00');
    $firstRealization = distributionRealization($c);
    $service->finalize($c['entity']->id, $finalized->id, $firstRealization->realization->id, $finalized->fresh()->revision, null);
    $financialBefore = distributionProtectedFinancialState();

    $indexUrl = route('financial-v2.distributions.index', ['entity' => $c['entity']->id]);
    $this->from($indexUrl)->delete(route('financial-v2.distributions.destroy', $finalized->id), ['entity' => $c['entity']->id])
        ->assertRedirect($indexUrl)->assertSessionHasErrors([
            'distribution' => 'Penyaluran tidak dapat dihapus karena sudah direalisasikan atau tidak lagi berstatus draft.',
        ]);
    expect($finalized->fresh())->not->toBeNull()->and(distributionProtectedFinancialState())->toBe($financialBefore);

    $stale = $service->create($c['entity']->id, distributionInput($c, 1), null);
    $secondRealization = distributionRealization($c);
    // Simulate another admin linking a realization after this admin loaded the list.
    DB::table('financial_v2_distributions')->where('id', $stale->id)->update(['realization_id' => $secondRealization->realization->id]);
    $staleFinancialBefore = distributionProtectedFinancialState();
    $this->from($indexUrl)->delete(route('financial-v2.distributions.destroy', $stale->id), ['entity' => $c['entity']->id])
        ->assertRedirect($indexUrl)->assertSessionHasErrors('distribution');
    expect($stale->fresh())->not->toBeNull()->and(distributionProtectedFinancialState())->toBe($staleFinancialBefore);

    $html = $this->get($indexUrl)->assertOk()->getContent();
    expect(substr_count($html, 'data-delete-distribution-trigger'))->toBe(0)
        ->and(substr_count($html, '>Detail<'))->toBe(4);
});

test('distribution deletion needs no granular permission and uses exact entity scope', function () {
    $a = UatFinancialFixture::context();
    $b = UatFinancialFixture::context();
    $draft = app(DistributionService::class)->create($a['entity']->id, distributionInput($a), null);
    distributionAttach($draft, distributionPerson($a));

    $this->actingAs(distributionUser());
    $modulePage = $this->get(route('financial-v2.distributions.index', ['entity' => $a['entity']->id]))->assertOk();
    expect(substr_count($modulePage->getContent(), 'data-delete-distribution-trigger'))->toBe(2);
    $this->delete(route('financial-v2.distributions.destroy', $draft->id), ['entity' => $b['entity']->id])->assertNotFound();
    expect($draft->fresh())->not->toBeNull()->and($draft->items()->count())->toBe(1);
});

test('distribution deletion is service-only and rolls back when copy lineage blocks removal', function () {
    $c = UatFinancialFixture::context();
    $service = app(DistributionService::class);
    $source = $service->create($c['entity']->id, distributionInput($c), null);
    distributionAttach($source, distributionPerson($c));

    expect(fn () => $source->delete())->toThrow(DomainException::class, 'audited service workflow');
    $copy = $service->copyPrevious($c['entity']->id, distributionInput($c, 1), null);
    $before = [Distribution::count(), DB::table('financial_v2_distribution_items')->count(), DB::table('financial_v2_audit_events')->count()];
    expect(fn () => $service->deleteDraft($c['entity']->id, $source->id, null))
        ->toThrow(ValidationException::class, 'sumber salinan');
    expect([Distribution::count(), DB::table('financial_v2_distribution_items')->count(), DB::table('financial_v2_audit_events')->count()])->toBe($before)
        ->and($source->fresh())->not->toBeNull()->and($copy->fresh())->not->toBeNull();
});

test('authentication is required and missing granular permissions do not block Financial V2 operations', function () {
    $c = UatFinancialFixture::context();
    $s = app(DistributionService::class);
    $p = distributionPerson($c);
    $d = $s->create($c['entity']->id, distributionInput($c), null);
    distributionAttach($d, $p);
    $this->get(route('financial-v2.beneficiaries.index', ['entity' => $c['entity']->id]))->assertRedirect(route('login'));
    expect(\Spatie\Permission\Models\Permission::query()->count())->toBe(0);
    $this->actingAs(distributionUser());
    foreach (['beneficiaries.index' => [], 'beneficiaries.show' => ['beneficiary' => $p->id], 'distributions.index' => [], 'distributions.show' => ['distribution' => $d->id]] as $name => $args) {
        $this->get(route('financial-v2.'.$name, $args + ['entity' => $c['entity']->id]))->assertOk();
    }
    $this->post(route('financial-v2.beneficiaries.store'), ['entity' => $c['entity']->id])->assertRedirect()->assertSessionHasErrors();
    $this->patch(route('financial-v2.beneficiaries.update', $p->id), ['entity' => $c['entity']->id])->assertRedirect()->assertSessionHasErrors();
    $this->post(route('financial-v2.distributions.store'), ['entity' => $c['entity']->id])->assertRedirect()->assertSessionHasErrors();
    $this->post(route('financial-v2.distributions.items.store', $d->id), ['entity' => $c['entity']->id])->assertRedirect()->assertSessionHasErrors();
    $this->patch(route('financial-v2.distributions.items.update', [$d->id, $d->items()->sole()->id]), ['entity' => $c['entity']->id])->assertRedirect()->assertSessionHasErrors();
    $this->post(route('financial-v2.distributions.finalize', $d->id), ['entity' => $c['entity']->id])->assertRedirect()->assertSessionHasErrors();
    expect(\Spatie\Permission\Models\Permission::query()->count())->toBe(0);
});

test('entity scoping rejects cross-entity people programs distributions and realizations', function () {
    $a = UatFinancialFixture::context();
    $b = UatFinancialFixture::context();
    $s = app(DistributionService::class);
    $p = distributionPerson($b);
    $d = $s->create($a['entity']->id, distributionInput($a), null);
    expect(fn () => distributionAttach($d, $p))->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    expect(fn () => $s->create($a['entity']->id, distributionInput($b), null))->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    $tx = distributionRealization($b);
    expect(fn () => $s->finalize($a['entity']->id, $d->id, $tx->realization->id, 0, null))->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    $this->actingAs(distributionUser());
    $this->get(route('financial-v2.beneficiaries.show', ['entity' => $a['entity']->id, 'beneficiary' => $p->id]))->assertNotFound();
    $this->get(route('financial-v2.distributions.show', ['entity' => $b['entity']->id, 'distribution' => $d->id]))->assertNotFound();
});
