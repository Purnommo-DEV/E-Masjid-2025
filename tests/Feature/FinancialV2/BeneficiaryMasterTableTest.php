<?php

use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\AuditEvent;
use App\Models\FinancialV2\Counterparty;
use App\Models\FinancialV2\Distribution;
use App\Models\FinancialV2\DistributionItem;
use App\Models\FinancialV2\Program;
use App\Models\User;
use Database\Seeders\ZiswafDistributionPermissionSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function beneficiaryMasterEntity(string $name = 'Entity Master Penerima'): AccountingEntity
{
    return AccountingEntity::create([
        'code' => 'BEN-'.Str::upper(Str::random(8)),
        'name' => $name,
        'legal_name' => $name,
        'status' => 'active',
    ]);
}

function beneficiaryMasterPerson(AccountingEntity $entity, string $name, array $overrides = []): Counterparty
{
    return Counterparty::create($overrides + [
        'accounting_entity_id' => $entity->id,
        'code' => 'BEN-'.Str::upper(Str::random(20)),
        'party_type' => 'beneficiary',
        'beneficiary_type' => 'BELUM_DITENTUKAN',
        'display_name' => $name,
        'status' => 'active',
    ]);
}

function beneficiaryMasterUser(array $permissions = ZiswafDistributionPermissionSeeder::PERMISSIONS): User
{
    (new ZiswafDistributionPermissionSeeder)->run();
    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    return $user;
}

function beneficiaryIntegrityCounts(): array
{
    return collect([
        'beneficiaries' => ['financial_v2_counterparties', fn ($q) => $q->where('party_type', 'beneficiary')],
        'distributions' => ['financial_v2_distributions', null],
        'distribution_items' => ['financial_v2_distribution_items', null],
        'realizations' => ['financial_v2_fund_realizations', null],
        'transactions' => ['financial_v2_transactions', null],
        'journals' => ['financial_v2_journals', null],
        'journal_lines' => ['financial_v2_journal_lines', null],
        'ledgers' => ['financial_v2_ledger_entries', null],
        'vouchers' => ['financial_v2_vouchers', null],
    ])->mapWithKeys(function (array $definition, string $key): array {
        [$table, $scope] = $definition;
        $query = DB::table($table);
        if ($scope) {
            $scope($query);
        }

        return [$key => $query->count()];
    })->all();
}

test('Master Penerima renders RW then RT then case-insensitive name groups with safe missing values', function () {
    $entity = beneficiaryMasterEntity();
    $other = beneficiaryMasterEntity('Entity Lain');
    beneficiaryMasterPerson($entity, 'zeta RW04 RT03', ['rw' => '04', 'rt' => '03', 'rt_coordinator_name' => 'Koordinator Barat']);
    beneficiaryMasterPerson($entity, 'Alpha RW04 RT03', ['rw' => '04', 'rt' => '03', 'beneficiary_type' => 'YATIM', 'rt_coordinator_name' => 'Koordinator Barat']);
    beneficiaryMasterPerson($entity, 'Beta RW04 RT05', ['rw' => '04', 'rt' => '05']);
    beneficiaryMasterPerson($entity, 'Gamma RW08 RT03', ['rw' => '08', 'rt' => '03', 'status' => 'inactive']);
    beneficiaryMasterPerson($entity, 'Missing RT', ['rw' => '08', 'rt' => null]);
    beneficiaryMasterPerson($entity, 'Missing RW', ['rw' => null, 'rt' => '01']);
    beneficiaryMasterPerson($other, 'Penerima Entitas Lain', ['rw' => '01', 'rt' => '01']);
    $this->actingAs(beneficiaryMasterUser());

    $response = $this->get(route('financial-v2.beneficiaries.index', ['entity' => $entity->id, 'per_page' => '100']));
    $response->assertOk()
        ->assertSeeInOrder(['RW: 04', 'RT: 03', 'Alpha RW04 RT03', 'zeta RW04 RT03', 'RT: 05', 'Beta RW04 RT05', 'RW: 08', 'Gamma RW08 RT03', 'RT: Belum Ditentukan', 'Missing RT', 'RW: Belum Ditentukan', 'Missing RW'])
        ->assertSee('data-rt-group="04|03"', false)
        ->assertSee('data-rt-group="08|03"', false)
        ->assertSee('RT: 03')
        ->assertSee('Total Penerima: 2')
        ->assertSee('RW: Belum Ditentukan')
        ->assertSee('RT: Belum Ditentukan')
        ->assertDontSee('Penerima Entitas Lain')
        ->assertSee('beneficiary-select-all')
        ->assertSee('beneficiary-selected-count')
        ->assertSee('Hapus Terpilih')
        ->assertSee('overflow-x-auto')
        ->assertSee('min-w-[70rem]');

    $this->get(route('financial-v2.beneficiaries.index', ['entity' => $entity->id, 'q' => 'Alpha']))
        ->assertOk()->assertSeeInOrder(['RW: 04', 'RT: 03', 'Alpha RW04 RT03'])->assertDontSee('zeta RW04 RT03');
    $this->get(route('financial-v2.beneficiaries.index', ['entity' => $entity->id, 'rw' => '04']))
        ->assertOk()->assertSee('Alpha RW04 RT03')->assertDontSee('Gamma RW08 RT03');
    $this->get(route('financial-v2.beneficiaries.index', ['entity' => $entity->id, 'rt' => '03']))
        ->assertOk()->assertSee('Alpha RW04 RT03')->assertSee('Gamma RW08 RT03')->assertDontSee('Beta RW04 RT05');
    $this->get(route('financial-v2.beneficiaries.index', ['entity' => $entity->id, 'beneficiary_type' => 'YATIM']))
        ->assertOk()->assertSee('Alpha RW04 RT03')->assertDontSee('Beta RW04 RT05');
    $this->get(route('financial-v2.beneficiaries.index', ['entity' => $entity->id, 'status' => 'inactive']))
        ->assertOk()->assertSee('Gamma RW08 RT03')->assertDontSee('Alpha RW04 RT03');
    $this->get(route('financial-v2.beneficiaries.index', ['entity' => $entity->id, 'coordinator' => 'Barat']))
        ->assertOk()->assertSee('Alpha RW04 RT03')->assertSee('zeta RW04 RT03')->assertDontSee('Beta RW04 RT05');
    $this->get(route('financial-v2.beneficiaries.index', ['entity' => $entity->id, 'q' => 'tidak-ada']))
        ->assertOk()->assertSee('Tidak ada penerima yang sesuai.')->assertDontSee('data-rw-group=', false)->assertDontSee('data-rt-group=', false);
});

test('page sizes and Semua preserve filters while RT headers show the global matching total', function () {
    $entity = beneficiaryMasterEntity();
    foreach (range(1, 25) as $number) {
        beneficiaryMasterPerson($entity, 'Daftar Penerima '.str_pad((string) $number, 2, '0', STR_PAD_LEFT), ['rw' => '04', 'rt' => '03']);
    }
    beneficiaryMasterPerson($entity, 'Di Luar Filter', ['rw' => '08', 'rt' => '01']);
    $this->actingAs(beneficiaryMasterUser());

    $pageOne = $this->get(route('financial-v2.beneficiaries.index', ['entity' => $entity->id, 'q' => 'Daftar Penerima', 'per_page' => '10']));
    $pageOne->assertOk()->assertSee('Daftar Penerima 10')->assertDontSee('Daftar Penerima 11')->assertSee('Total Penerima: 25')->assertSee('value="10" selected', false);
    $this->get(route('financial-v2.beneficiaries.index', ['entity' => $entity->id, 'q' => 'Daftar Penerima', 'per_page' => '10', 'page' => 2]))
        ->assertOk()->assertSee('Daftar Penerima 11')->assertDontSee('Daftar Penerima 01')->assertSee('Total Penerima: 25');
    $this->get(route('financial-v2.beneficiaries.index', ['entity' => $entity->id, 'q' => 'Daftar Penerima', 'per_page' => '20']))
        ->assertOk()->assertSee('Daftar Penerima 20')->assertDontSee('Daftar Penerima 21')->assertSee('value="20" selected', false);
    $this->get(route('financial-v2.beneficiaries.index', ['entity' => $entity->id, 'q' => 'Daftar Penerima', 'per_page' => '100']))
        ->assertOk()->assertSee('Daftar Penerima 25')->assertDontSee('Di Luar Filter')->assertSee('value="100" selected', false);
    $this->get(route('financial-v2.beneficiaries.index', ['entity' => $entity->id, 'q' => 'Daftar Penerima', 'per_page' => 'all', 'page' => 99]))
        ->assertOk()->assertSeeInOrder(['Daftar Penerima 01', 'Daftar Penerima 25'])->assertDontSee('Di Luar Filter')->assertSee('value="all" selected', false);
});

test('recipient and RW RT group counts use Indonesian thousands separators', function () {
    $entity = beneficiaryMasterEntity();
    $now = now();
    collect(range(1, 1000))->map(fn (int $number) => [
        'id' => (string) Str::uuid(),
        'accounting_entity_id' => $entity->id,
        'code' => 'FMT-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
        'party_type' => 'beneficiary',
        'beneficiary_type' => 'BELUM_DITENTUKAN',
        'display_name' => 'Format Count '.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
        'rt' => '01',
        'rw' => '08',
        'status' => 'active',
        'created_at' => $now,
        'updated_at' => $now,
    ])->chunk(200)->each(fn ($rows) => DB::table('financial_v2_counterparties')->insert($rows->all()));
    $this->actingAs(beneficiaryMasterUser());

    $this->get(route('financial-v2.beneficiaries.index', ['entity' => $entity->id, 'per_page' => '10']))
        ->assertOk()->assertSee('1.000 penerima sesuai filter')->assertSee('Total Penerima: 1.000');
});

test('bulk delete enforces permission entity scope history protection audit and financial fact integrity', function () {
    $entity = beneficiaryMasterEntity();
    $otherEntity = beneficiaryMasterEntity('Entity Lain');
    $eligible = beneficiaryMasterPerson($entity, 'Aman Dihapus', ['rw' => '04', 'rt' => '03']);
    $protected = beneficiaryMasterPerson($entity, 'Memiliki Histori', ['rw' => '04', 'rt' => '03']);
    $crossEntity = beneficiaryMasterPerson($otherEntity, 'Lintas Entitas', ['rw' => '04', 'rt' => '03']);
    $program = Program::create([
        'accounting_entity_id' => $entity->id,
        'code' => 'DIST-'.Str::upper(Str::random(8)),
        'name' => 'Program Histori',
        'status' => 'active',
    ]);
    $distribution = Distribution::create([
        'accounting_entity_id' => $entity->id,
        'program_id' => $program->id,
        'title' => 'Histori Penerima',
        'period_label' => 'September 2026',
        'starts_on' => '2026-09-01',
        'ends_on' => '2026-09-30',
        'status' => 'draft',
    ]);
    DistributionItem::create([
        'distribution_id' => $distribution->id,
        'beneficiary_id' => $protected->id,
        'amount' => '100000.00',
        'identity_snapshot' => ['display_name' => $protected->display_name, 'rt' => '03', 'rw' => '04'],
    ]);
    $before = beneficiaryIntegrityCounts();

    $this->actingAs(beneficiaryMasterUser(['view penerima ziswaf']));
    $this->delete(route('financial-v2.beneficiaries.destroy-bulk'), [
        'entity' => $entity->id,
        'beneficiary_ids' => [$eligible->id],
    ])->assertForbidden();
    expect($eligible->fresh())->not->toBeNull();

    $this->actingAs(beneficiaryMasterUser());
    $this->delete(route('financial-v2.beneficiaries.destroy-bulk'), [
        'entity' => $entity->id,
        'beneficiary_ids' => [$eligible->id, $protected->id],
    ])->assertRedirect()->assertSessionHas('success', '1 penerima berhasil dihapus.')
        ->assertSessionHas('warning', '1 penerima tidak dapat dihapus karena sudah memiliki riwayat atau referensi Financial V2.');

    expect($eligible->fresh())->toBeNull()
        ->and($protected->fresh())->not->toBeNull()
        ->and(Distribution::whereKey($distribution->id)->exists())->toBeTrue()
        ->and(DistributionItem::where('beneficiary_id', $protected->id)->exists())->toBeTrue()
        ->and(AuditEvent::where('event_type', 'beneficiary.deleted')->where('target_id', $eligible->id)->exists())->toBeTrue();
    $afterMixed = beneficiaryIntegrityCounts();
    foreach (['distributions', 'distribution_items', 'realizations', 'transactions', 'journals', 'journal_lines', 'ledgers', 'vouchers'] as $key) {
        expect($afterMixed[$key])->toBe($before[$key]);
    }
    expect($afterMixed['beneficiaries'])->toBe($before['beneficiaries'] - 1);

    $sameEntityCandidate = beneficiaryMasterPerson($entity, 'Harus Tetap Ada');
    $beforeCrossEntity = beneficiaryIntegrityCounts();
    $this->delete(route('financial-v2.beneficiaries.destroy-bulk'), [
        'entity' => $entity->id,
        'beneficiary_ids' => [$sameEntityCandidate->id, $crossEntity->id],
    ])->assertRedirect()->assertSessionHasErrors('beneficiary_ids');
    expect($sameEntityCandidate->fresh())->not->toBeNull()
        ->and($crossEntity->fresh())->not->toBeNull()
        ->and(beneficiaryIntegrityCounts())->toBe($beforeCrossEntity);
});
