<?php

use App\Domain\FinancialV2\FinancialTransactionConfigurationResolver;
use App\Domain\FinancialV2\MasterDataGovernanceService;
use App\Models\FinancialV2\AuditEvent;
use App\Models\FinancialV2\BudgetAllocation;
use App\Models\FinancialV2\BudgetAllocationFunding;
use App\Models\FinancialV2\BudgetAllocationVersion;
use App\Models\FinancialV2\EvidenceRequirement;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\FundPolicyRule;
use App\Models\FinancialV2\FundPolicyVersion;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\JournalLine;
use App\Models\FinancialV2\LedgerEntry;
use App\Models\FinancialV2\PostingRule;
use App\Models\FinancialV2\PostingRuleVersion;
use App\Models\FinancialV2\Voucher;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\Support\UatFinancialFixture;

function inlineConfigurationAdmin(): User
{
    Role::findOrCreate('SuperAdmin', 'web');
    $user = User::factory()->create();
    $user->assignRole('SuperAdmin');

    return $user;
}

test('configuration action is permission aware and shared by operational and bank mutation forms', function () {
    $context = UatFinancialFixture::context();
    $ordinary = User::factory()->create();
    $admin = inlineConfigurationAdmin();

    $this->actingAs($ordinary)->get(route('financial-v2.transactions.create', ['operation' => 'receipt', 'entity' => $context['entity']->id]))
        ->assertOk()->assertDontSee('+ Buat Konfigurasi Penggunaan Dana')->assertSee('Hubungi administrator keuangan.');
    $this->actingAs($admin)->get(route('financial-v2.transactions.create', ['operation' => 'receipt', 'entity' => $context['entity']->id]))
        ->assertOk()->assertSee('+ Buat Konfigurasi Penggunaan Dana')->assertSee('Konfigurasi Pencatatan Baru');
    $this->actingAs($admin)->get(route('financial-v2.bank-mutations.create', ['entity' => $context['entity']->id]))
        ->assertOk()->assertSee('+ Buat Konfigurasi Penggunaan Dana')->assertSee('Konfigurasi Pencatatan Baru');
});

test('inline context endpoint uses the canonical RCV PAY TRF and IFT dimensions', function () {
    $context = UatFinancialFixture::context();
    $admin = inlineConfigurationAdmin();
    $base = ['entity' => $context['entity']->id, 'date' => $context['today']];
    $cases = [
        'receipt' => ['financial_account_id' => $context['accountA']->id, 'fund_id' => $context['fund']->id, 'category_id' => $context['receiptCategory']->id, 'program_id' => $context['program']->id],
        'payment' => ['financial_account_id' => $context['accountA']->id, 'fund_id' => $context['fund']->id, 'category_id' => $context['paymentCategory']->id],
        'transfer' => ['source_financial_account_id' => $context['accountA']->id, 'destination_financial_account_id' => $context['accountB']->id, 'fund_id' => $context['fund']->id],
        'interfund' => ['financial_account_id' => $context['accountA']->id, 'source_fund_id' => $context['fund']->id, 'destination_fund_id' => $context['destinationFund']->id],
    ];

    foreach ($cases as $operation => $dimensions) {
        $this->actingAs($admin)->getJson(route('financial-v2.configuration.inline.show', $base + ['operation' => $operation] + $dimensions))
            ->assertOk()->assertJsonPath('state', 'ready')
            ->assertJsonPath('context.entity', $context['entity']->name)
            ->assertJsonPath('context.date', $context['today']);
    }
});

test('inline receipt flow locks context, creates only an audited policy draft, blocks overlap, and becomes ready after governance activation', function () {
    $context = UatFinancialFixture::context();
    $fund = UatFinancialFixture::restrictedFund($context, 'INLINE', 'Dana Inline');
    $activePolicy = FundPolicyVersion::query()->where('fund_id', $fund->id)->sole();
    $activePolicy->rules()->delete();
    $admin = inlineConfigurationAdmin();
    $payload = [
        'entity' => $context['entity']->id, 'operation' => 'receipt', 'date' => $context['today'],
        'financial_account_id' => $context['accountA']->id, 'fund_id' => $fund->id,
        'category_id' => $context['receiptCategory']->id, 'program_id' => $context['program']->id,
    ];
    $facts = fn () => [FinancialTransaction::count(), Journal::count(), JournalLine::count(), LedgerEntry::count(), Voucher::count()];
    $before = $facts();

    $this->actingAs(User::factory()->create())->getJson(route('financial-v2.configuration.inline.show', $payload))->assertForbidden();
    $show = $this->actingAs($admin)->getJson(route('financial-v2.configuration.inline.show', $payload))
        ->assertOk()->assertJsonPath('state', 'missing')->assertJsonPath('context.entity', $context['entity']->name)
        ->assertJsonPath('context.financial_accounts.0', $context['accountA']->name)
        ->assertJsonPath('context.funds.0', $fund->name);
    $versionId = $show->json('selected_posting_rule_version_id');
    expect($versionId)->toBe($context['receiptVersion']->id);

    $create = $this->actingAs($admin)->postJson(route('financial-v2.configuration.inline.store'), $payload + [
        'posting_rule_version_id' => $versionId, 'effective_from' => $context['today'],
        'policy_document_ref' => 'SK-INLINE-2026', 'notes' => 'Dibuat dari form penerimaan.',
    ])->assertOk()->assertJsonPath('state', 'pending');
    $draft = FundPolicyVersion::query()->where('fund_id', $fund->id)->where('status', 'draft')->sole();
    expect($draft->policy_document_ref)->toBe('SK-INLINE-2026')
        ->and($draft->rules)->toHaveCount(2)
        ->and($draft->rules->every(fn (FundPolicyRule $rule) => $rule->decision === 'allowed'))->toBeTrue()
        ->and(AuditEvent::query()->where('event_type', 'inline_transaction_configuration_draft_created')->where('actor_user_id', $admin->id)->exists())->toBeTrue()
        ->and($facts())->toBe($before);

    $ruleIds = $draft->rules()->orderBy('id')->pluck('id')->all();
    $auditCount = AuditEvent::query()->where('event_type', 'inline_transaction_configuration_draft_created')->count();
    $this->actingAs($admin)->postJson(route('financial-v2.configuration.inline.store'), $payload + [
        'posting_rule_version_id' => $versionId, 'effective_from' => $context['today'], 'policy_document_ref' => 'DUPLICATE',
    ])->assertOk()->assertJsonPath('ok', true)->assertJsonPath('message', 'Aturan yang sama sudah tersedia.');
    expect(FundPolicyVersion::query()->where('fund_id', $fund->id)->where('status', 'draft')->count())->toBe(1)
        ->and($draft->rules()->orderBy('id')->pluck('id')->all())->toBe($ruleIds)
        ->and(AuditEvent::query()->where('event_type', 'inline_transaction_configuration_draft_created')->count())->toBe($auditCount)
        ->and($facts())->toBe($before);

    app(MasterDataGovernanceService::class)->makeFundPolicyVersionEffective($draft->id, $admin->id);
    expect(fn () => app(FinancialTransactionConfigurationResolver::class)->resolve([
        'accounting_entity_id' => $context['entity']->id, 'transaction_type_id' => $context['receiptType']->id,
        'date' => $context['today'], 'financial_account_id' => $context['accountA']->id, 'fund_id' => $fund->id,
        'category_id' => $context['receiptCategory']->id, 'program_id' => $context['program']->id,
    ]))->not->toThrow(Throwable::class);
    expect($facts())->toBe($before);
});

test('realization inline configuration locks the canonical PAY rule and its evidence instead of accepting an arbitrary rule', function () {
    $context = UatFinancialFixture::context();
    $admin = inlineConfigurationAdmin();
    $fund = UatFinancialFixture::restrictedFund($context, 'YATIM', 'Dana Santunan Anak Yatim');
    $context['paymentCategory']->update(['default_posting_rule_id' => $context['paymentVersion']->posting_rule_id]);
    $wrongRule = PostingRule::create([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['paymentType']->id,
        'code' => 'WRONG-PAY', 'name' => 'Rule PAY Tidak Relevan', 'rule_family' => 'payment-special', 'status' => 'active',
    ]);
    PostingRuleVersion::create([
        'accounting_entity_id' => $context['entity']->id, 'posting_rule_id' => $wrongRule->id,
        'version_no' => 1, 'effective_from' => $context['today'], 'status' => 'effective', 'approved_at' => now(),
        'input_contract_ref' => 'wrong', 'journal_template_ref' => 'wrong', 'business_rule_refs' => 'wrong',
    ]);
    $allocation = BudgetAllocation::create([
        'accounting_entity_id' => $context['entity']->id, 'accounting_period_id' => $context['period']->id,
        'fund_id' => $fund->id, 'program_id' => $context['program']->id, 'category_id' => $context['paymentCategory']->id,
        'allocation_reference' => 'YATIM-INLINE', 'idempotency_key' => (string) Str::uuid(), 'correlation_id' => (string) Str::uuid(),
        'status' => 'approved', 'reason' => 'Santunan yatim',
    ]);
    $version = BudgetAllocationVersion::create([
        'accounting_entity_id' => $context['entity']->id, 'budget_allocation_id' => $allocation->id,
        'version_no' => 1, 'allocated_amount' => '100.00', 'effective_from' => $context['today'],
        'status' => 'approved', 'approved_at' => now(), 'reason' => 'Santunan yatim',
    ]);
    BudgetAllocationFunding::create([
        'accounting_entity_id' => $context['entity']->id, 'budget_allocation_version_id' => $version->id,
        'line_no' => 1, 'fund_id' => $fund->id, 'amount' => '100.00',
    ]);
    $payload = [
        'entity' => $context['entity']->id, 'operation' => 'realization', 'date' => $context['today'],
        'financial_account_id' => $context['accountA']->id, 'budget_allocation_version_id' => $version->id,
    ];
    $facts = fn () => [FinancialTransaction::count(), Journal::count(), JournalLine::count(), LedgerEntry::count(), Voucher::count()];
    $before = $facts();

    $this->actingAs($admin)->getJson(route('financial-v2.configuration.inline.show', $payload))
        ->assertOk()
        ->assertJsonPath('status', 'EVIDENCE_CONFIGURATION_MISSING')
        ->assertJsonPath('can_create_configuration', false)
        ->assertJsonPath('selected_posting_rule_version_id', $context['paymentVersion']->id);
    EvidenceRequirement::create([
        'accounting_entity_id' => $context['entity']->id,
        'posting_rule_version_id' => $context['paymentVersion']->id,
        'evidence_type' => 'invoice',
        'minimum_count' => 1,
    ]);

    $show = $this->actingAs($admin)->getJson(route('financial-v2.configuration.inline.show', $payload))
        ->assertOk()->assertJsonPath('status', 'MISSING_CONFIGURATION')
        ->assertJsonPath('can_create_configuration', true)
        ->assertJsonPath('selected_posting_rule_version_id', $context['paymentVersion']->id)
        ->assertJsonPath('evidence_requirements.0.type', 'invoice')
        ->assertJsonMissing(['label' => 'Rule PAY Tidak Relevan — Versi 1']);

    $this->actingAs($admin)->postJson(route('financial-v2.configuration.inline.store'), $payload + [
        'posting_rule_version_id' => PostingRuleVersion::where('posting_rule_id', $wrongRule->id)->value('id'),
        'effective_from' => $context['today'], 'evidence_type' => 'statement',
        'policy_document_ref' => 'SK-YATIM-INLINE',
    ])->assertOk()->assertJsonPath('state', 'pending');
    $draftCount = FundPolicyVersion::where('fund_id', $fund->id)->where('status', 'draft')->count();
    $this->actingAs($admin)->postJson(route('financial-v2.configuration.inline.store'), $payload + [
        'effective_from' => $context['today'], 'policy_document_ref' => 'SK-YATIM-INLINE',
    ])->assertOk();

    expect($draftCount)->toBe(1)
        ->and(FundPolicyVersion::where('fund_id', $fund->id)->where('status', 'draft')->count())->toBe(1)
        ->and($facts())->toBe($before);
});
