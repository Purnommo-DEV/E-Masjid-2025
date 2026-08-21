<?php

use App\Domain\FinancialV2\FinancialMasterDataService;
use App\Models\FinancialV2\AuditEvent;
use App\Models\FinancialV2\Category;
use App\Models\FinancialV2\FinancialAccount;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\LedgerEntry;
use App\Models\FinancialV2\Program;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\Support\UatFinancialFixture;

test('Financial V2 master sidebar contains business navigation and does not expose accounting internals', function () {
    $sidebar = view('masjid.mrj.admin.layouts._sidebar')->render();
    $navbar = view('masjid.mrj.admin.layouts._navbar')->render();

    expect($sidebar)
        ->toContain('Keuangan V2')
        ->toContain('Master Keuangan')
        ->toContain('Rekening / Kas')
        ->toContain('Kategori Transaksi')
        ->toContain('Aturan Dana')
        ->toContain('id="admin-sidebar"')
        ->toContain('aria-label="Tutup sidebar"')
        ->not->toContain('sidebarOpen: window.innerWidth')
        ->toContain('/admin/keuangan-v2')
        ->toContain('/admin/keuangan-v2/master/rekening-kas')
        ->not->toContain('/admin/keuangan-v2/journal')
        ->not->toContain('/admin/keuangan-v2/ledger');

    expect($navbar)
        ->toContain('aria-label="Buka atau tutup sidebar"')
        ->toContain('aria-controls="admin-sidebar"');
});

test('master account CRUD is audited, can be deactivated, and has no hard-delete endpoint', function () {
    $context = UatFinancialFixture::context();
    $user = User::factory()->create();
    $payload = [
        'entity' => $context['entity']->id,
        'account_id' => $context['cashA']->id,
        'code' => 'MR-'.Str::upper(Str::random(8)),
        'name' => 'Synthetic Master Cash',
        'account_type' => 'cash',
        'currency_code' => 'IDR',
        'opening_date' => $context['today'],
        'cash_location' => 'Synthetic test cash box',
        'cash_count_frequency' => 'daily',
    ];

    $created = $this->actingAs($user)->postJson(route('financial-v2.masters.accounts.store'), $payload)
        ->assertOk()->assertJsonPath('ok', true);
    $id = $created->json('financial_account_id');
    expect(FinancialAccount::findOrFail($id)->status)->toBe('draft')
        ->and(AuditEvent::where('target_id', $id)->where('event_type', 'financial_account_created')->exists())->toBeTrue()
        ->and(Journal::where('accounting_entity_id', $context['entity']->id)->count())->toBe(0)
        ->and(LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count())->toBe(0);

    $this->actingAs($user)->putJson(route('financial-v2.masters.accounts.update', $id), array_merge($payload, ['name' => 'Synthetic Master Cash Updated']))
        ->assertOk()->assertJsonPath('ok', true);
    expect(FinancialAccount::findOrFail($id)->name)->toBe('Synthetic Master Cash Updated');

    $this->actingAs($user)->postJson(route('financial-v2.masters.accounts.activate', $id), ['entity' => $context['entity']->id, 'effective_date' => $context['today']])
        ->assertOk()->assertJsonPath('ok', true);
    $this->actingAs($user)->postJson(route('financial-v2.masters.accounts.deactivate', $id), ['entity' => $context['entity']->id, 'effective_date' => $context['today']])
        ->assertOk()->assertJsonPath('ok', true);
    expect(FinancialAccount::findOrFail($id)->status)->toBe('suspended');

    $this->actingAs($user)->deleteJson(route('financial-v2.masters.accounts.update', $id), ['entity' => $context['entity']->id])
        ->assertStatus(405);
});

test('fund program and category masters remain separate, audited, and do not create financial facts', function () {
    $context = UatFinancialFixture::context();
    $user = User::factory()->create();
    $fundCode = 'FND-'.Str::upper(Str::random(7));
    $fund = $this->actingAs($user)->postJson(route('financial-v2.masters.funds.store'), [
        'entity' => $context['entity']->id,
        'fund_type_id' => $context['fundType']->id,
        'fund_restriction_id' => $context['restriction']->id,
        'code' => $fundCode,
        'name' => 'Synthetic Fund',
        'purpose_statement' => 'Synthetic permitted purpose',
    ])->assertOk()->json('fund_id');
    $program = $this->actingAs($user)->postJson(route('financial-v2.masters.programs.store'), [
        'entity' => $context['entity']->id,
        'code' => 'PRG-'.Str::upper(Str::random(7)),
        'name' => 'Synthetic Program',
        'start_date' => $context['today'],
    ])->assertOk()->json('program_id');
    $category = $this->actingAs($user)->postJson(route('financial-v2.masters.categories.store'), [
        'entity' => $context['entity']->id,
        'transaction_type_id' => $context['receiptType']->id,
        'code' => 'CAT-'.Str::upper(Str::random(7)),
        'name' => 'Synthetic Category',
        'status' => 'active',
    ])->assertOk()->json('category_id');

    $this->actingAs($user)->postJson(route('financial-v2.masters.funds.activate', $fund), ['entity' => $context['entity']->id, 'effective_date' => $context['today']])->assertOk();
    $this->actingAs($user)->postJson(route('financial-v2.masters.programs.activate', $program), ['entity' => $context['entity']->id])->assertOk();
    $this->actingAs($user)->postJson(route('financial-v2.masters.categories.deactivate', $category), ['entity' => $context['entity']->id])->assertOk();

    expect(Fund::findOrFail($fund)->status)->toBe('active')
        ->and(Program::findOrFail($program)->status)->toBe('active')
        ->and(Category::findOrFail($category)->status)->toBe('inactive')
        ->and($fund)->not->toBe($program)
        ->and($fund)->not->toBe($context['accountA']->id)
        ->and(AuditEvent::where('accounting_entity_id', $context['entity']->id)->whereIn('target_id', [$fund, $program, $category])->count())->toBeGreaterThanOrEqual(6)
        ->and(Journal::where('accounting_entity_id', $context['entity']->id)->count())->toBe(0)
        ->and(LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count())->toBe(0);
});

test('draft fund policy rules are configurable without changing financial facts and effective policy remains immutable', function () {
    $context = UatFinancialFixture::context();
    $user = User::factory()->create();
    $policy = $this->actingAs($user)->postJson(route('financial-v2.masters.policies.store'), [
        'entity' => $context['entity']->id,
        'fund_id' => $context['fund']->id,
        'effective_from' => $context['today'],
        'policy_document_ref' => 'Synthetic policy '.Str::uuid(),
        'exception_approval_level' => 'Synthetic approver',
    ])->assertOk()->json('fund_policy_version_id');

    $this->actingAs($user)->postJson(route('financial-v2.masters.policy-rules.store', $policy), [
        'entity' => $context['entity']->id,
        'transaction_type_id' => $context['receiptType']->id,
        'decision' => 'allowed',
        'rationale' => 'Synthetic allowed use',
    ])->assertOk()->assertJsonPath('ok', true);
    $this->actingAs($user)->postJson(route('financial-v2.masters.policies.effective', $policy), ['entity' => $context['entity']->id])
        ->assertOk()->assertJsonPath('ok', true);
    $this->actingAs($user)->putJson(route('financial-v2.masters.policies.update', $policy), [
        'entity' => $context['entity']->id,
        'effective_from' => $context['today'],
        'policy_document_ref' => 'Changed policy',
        'exception_approval_level' => 'Synthetic approver',
    ])->assertStatus(422)->assertJsonPath('ok', false);

    expect(app(FinancialMasterDataService::class))->toBeInstanceOf(FinancialMasterDataService::class)
        ->and(Journal::where('accounting_entity_id', $context['entity']->id)->count())->toBe(0)
        ->and(LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count())->toBe(0);
});
