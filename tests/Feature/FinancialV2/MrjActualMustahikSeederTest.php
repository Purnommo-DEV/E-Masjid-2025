<?php

use App\Models\FinancialV2\Counterparty;
use Database\Seeders\MrjActualMustahikSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Support\UatFinancialFixture;

test('MRJ-ACTUAL mustahik seeder reconciles exactly 107 and preserves historical financial references idempotently', function () {
    $context = UatFinancialFixture::context();
    $context['entity']->update(['code' => 'MRJ-ACTUAL']);

    $kokom = Counterparty::create([
        'accounting_entity_id' => $context['entity']->id,
        'code' => 'LEGACY-KOKOM',
        'party_type' => 'beneficiary',
        'beneficiary_type' => 'DHUAFA',
        'display_name' => 'Kokom lama',
        'contact_reference' => 'Nomor existing dipertahankan',
        'address' => 'Alamat existing dipertahankan',
        'rt' => '03',
        'rw' => '06',
        'rt_coordinator_name' => 'Koordinator lama',
        'beneficiary_notes' => '[Source: Data Mustahik ZISWAF MRJ; No ALL: 001; Tanda Terima: 1]',
        'status' => 'inactive',
    ]);
    $sukartiRw06 = Counterparty::create([
        'accounting_entity_id' => $context['entity']->id,
        'code' => 'LEGACY-SUKARTI-06',
        'party_type' => 'beneficiary',
        'beneficiary_type' => 'BELUM_DITENTUKAN',
        'display_name' => 'Sukarti',
        'beneficiary_notes' => '[Source: Data Mustahik ZISWAF MRJ; No ALL: 039; Tanda Terima: 1]',
        'status' => 'active',
    ]);
    $sukartiRw04 = Counterparty::create([
        'accounting_entity_id' => $context['entity']->id,
        'code' => 'LEGACY-SUKARTI-04',
        'party_type' => 'beneficiary',
        'beneficiary_type' => 'BELUM_DITENTUKAN',
        'display_name' => 'Sukarti',
        'beneficiary_notes' => '[Source: Data Mustahik ZISWAF MRJ; No ALL: 093; Tanda Terima: 1]',
        'status' => 'active',
    ]);
    $historicalOnly = Counterparty::create([
        'accounting_entity_id' => $context['entity']->id,
        'code' => 'LEGACY-HISTORICAL',
        'party_type' => 'beneficiary',
        'display_name' => 'Master Lama Dengan History',
        'status' => 'active',
    ]);
    $unreferenced = Counterparty::create([
        'accounting_entity_id' => $context['entity']->id,
        'code' => 'LEGACY-DELETE',
        'party_type' => 'beneficiary',
        'display_name' => 'Master Lama Tanpa History',
        'status' => 'active',
    ]);

    $context['supplier'] = $kokom;
    UatFinancialFixture::payment($context, '1000.00');
    $context['supplier'] = $historicalOnly;
    UatFinancialFixture::payment($context, '2000.00');

    $factTables = [
        'financial_v2_transactions', 'financial_v2_journals', 'financial_v2_journal_lines',
        'financial_v2_ledger_entries', 'financial_v2_vouchers', 'financial_v2_budget_allocations',
        'financial_v2_fund_realizations', 'financial_v2_distributions', 'financial_v2_distribution_items',
        'financial_v2_opening_balance_batches', 'financial_v2_opening_balance_lines',
    ];
    $facts = fn (): array => collect($factTables)->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->count()])->all();
    $links = fn (): array => DB::table('financial_v2_transactions')->orderBy('id')->pluck('counterparty_id', 'id')->all();
    $factsBefore = $facts();
    $linksBefore = $links();

    $first = new MrjActualMustahikSeeder;
    $first->run();

    $active = Counterparty::forEntity($context['entity']->id)
        ->where('party_type', 'beneficiary')
        ->where('status', 'active')
        ->get();
    $groups = $active->countBy(fn (Counterparty $person): string => $person->rt.'|'.$person->rw)->all();
    ksort($groups);
    $sourceNames = collect($first->records())->pluck('name')->sort()->values()->all();

    expect($first->summary)->toMatchArray([
        'source' => 107,
        'reused' => 3,
        'created' => 104,
        'retained_non_source_inactive' => 2,
        'historical_references_retained' => 1,
        'deactivated_non_source' => 2,
        'active' => 107,
    ])->and($active)->toHaveCount(107)
        ->and($groups)->toBe(['01|06' => 9, '02|06' => 22, '03|04' => 12, '03|06' => 10, '04|06' => 9, '05|04' => 9, '06|07' => 7, 'TCE|08' => 29])
        ->and($active->pluck('display_name')->sort()->values()->all())->toBe($sourceNames)
        ->and($active->where('beneficiary_type', 'BELUM_DITENTUKAN'))->toHaveCount(107)
        ->and($active->where('rt', 'TCE')->where('rw', '08'))->toHaveCount(29)
        ->and($active->where('rt', 'TCE')->where('rt_coordinator_name', 'Pak Indra (TCE)'))->toHaveCount(29)
        ->and($active->where('display_name', 'Sukarti'))->toHaveCount(2)
        ->and($active->where('display_name', 'Sukarti')->pluck('rw')->sort()->values()->all())->toBe(['04', '06'])
        ->and($active->pluck('external_reference')->unique())->toHaveCount(107)
        ->and($kokom->fresh()->id)->toBe($kokom->id)
        ->and($kokom->fresh()->display_name)->toBe('Kokom')
        ->and($kokom->fresh()->beneficiary_type)->toBe('BELUM_DITENTUKAN')
        ->and($kokom->fresh()->contact_reference)->toBe('Nomor existing dipertahankan')
        ->and($kokom->fresh()->address)->toBe('Alamat existing dipertahankan')
        ->and($sukartiRw06->fresh()->rt)->toBe('02')
        ->and($sukartiRw06->fresh()->rw)->toBe('06')
        ->and($sukartiRw04->fresh()->rt)->toBe('05')
        ->and($sukartiRw04->fresh()->rw)->toBe('04')
        ->and($historicalOnly->fresh()->status)->toBe('inactive')
        ->and($unreferenced->fresh()->status)->toBe('inactive')
        ->and($facts())->toBe($factsBefore)
        ->and($links())->toBe($linksBefore);

    $second = new MrjActualMustahikSeeder;
    $second->run();

    expect($second->summary)->toMatchArray([
        'source' => 107,
        'reused' => 107,
        'created' => 0,
        'updated' => 0,
        'retained_non_source_inactive' => 2,
        'historical_references_retained' => 1,
        'deactivated_non_source' => 0,
        'active' => 107,
    ])->and(Counterparty::forEntity($context['entity']->id)->where('party_type', 'beneficiary')->where('status', 'active')->count())->toBe(107)
        ->and($historicalOnly->fresh()->id)->toBe($historicalOnly->id)
        ->and($historicalOnly->fresh()->status)->toBe('inactive')
        ->and($facts())->toBe($factsBefore)
        ->and($links())->toBe($linksBefore);
});

test('MRJ-ACTUAL mustahik seeder creates all 107 when the beneficiary master is empty', function () {
    $entity = \App\Models\FinancialV2\AccountingEntity::create([
        'code' => 'MRJ-ACTUAL',
        'name' => 'Masjid Raudhotul Jannah',
        'legal_name' => 'Masjid Raudhotul Jannah',
        'status' => 'active',
    ]);

    $first = new MrjActualMustahikSeeder;
    $first->run();

    expect($first->summary)->toMatchArray([
        'source' => 107,
        'reused' => 0,
        'created' => 107,
        'updated' => 0,
        'retained_non_source_inactive' => 0,
        'historical_references_retained' => 0,
        'deactivated_non_source' => 0,
        'active' => 107,
    ])->and(Counterparty::forEntity($entity->id)->where('party_type', 'beneficiary')->where('status', 'active')->count())->toBe(107);

    $second = new MrjActualMustahikSeeder;
    $second->run();

    expect($second->summary)->toMatchArray([
        'source' => 107,
        'reused' => 107,
        'created' => 0,
        'updated' => 0,
        'retained_non_source_inactive' => 0,
        'historical_references_retained' => 0,
        'deactivated_non_source' => 0,
        'active' => 107,
    ])->and(Counterparty::forEntity($entity->id)->where('party_type', 'beneficiary')->where('status', 'active')->count())->toBe(107);
});
