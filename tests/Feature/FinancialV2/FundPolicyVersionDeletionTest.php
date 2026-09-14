<?php

use App\Domain\FinancialV2\FinancialDomainException;
use App\Domain\FinancialV2\FundPolicyVersionDeletionService;
use App\Models\FinancialV2\AuditEvent;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\FundPolicyRule;
use App\Models\FinancialV2\FundPolicyVersion;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\JournalLine;
use App\Models\FinancialV2\LedgerEntry;
use App\Models\FinancialV2\Voucher;
use App\Models\User;
use Tests\Support\UatFinancialFixture;

function coveredUnusedPolicy(array $context): FundPolicyVersion
{
    FundPolicyVersion::create([
        'accounting_entity_id' => $context['entity']->id,
        'fund_id' => $context['fund']->id,
        'version_no' => 2,
        'effective_from' => now()->subDays(3)->toDateString(),
        'effective_to' => now()->addDays(3)->toDateString(),
        'policy_document_ref' => 'replacement-policy',
        'allowed_matrix_ref' => 'replacement-matrix',
        'exception_approval_level' => 'test',
        'status' => 'effective',
        'approved_at' => now(),
    ]);

    return FundPolicyVersion::create([
        'accounting_entity_id' => $context['entity']->id,
        'fund_id' => $context['fund']->id,
        'version_no' => 1,
        'effective_from' => now()->subDay()->toDateString(),
        'effective_to' => now()->subDay()->toDateString(),
        'policy_document_ref' => 'unused-policy',
        'allowed_matrix_ref' => 'unused-matrix',
        'exception_approval_level' => 'test',
        'status' => 'superseded',
        'approved_at' => now(),
    ]);
}

test('unused covered Fund policy version can be deleted with its configuration rules and no financial facts', function () {
    $context = UatFinancialFixture::context();
    $user = User::factory()->create();
    $version = coveredUnusedPolicy($context);
    FundPolicyRule::create([
        'accounting_entity_id' => $context['entity']->id,
        'fund_policy_version_id' => $version->id,
        'transaction_type_id' => $context['receiptType']->id,
        'decision' => 'allowed',
    ]);
    $facts = [FinancialTransaction::count(), Journal::count(), JournalLine::count(), LedgerEntry::count(), Voucher::count()];

    $this->actingAs($user)->deleteJson(route('financial-v2.masters.policies.destroy', $version), ['entity' => $context['entity']->id])
        ->assertOk()
        ->assertJsonPath('ok', true);

    expect(FundPolicyVersion::find($version->id))->toBeNull()
        ->and(FundPolicyRule::where('fund_policy_version_id', $version->id)->exists())->toBeFalse()
        ->and(AuditEvent::where('event_type', 'fund_policy_version_deleted')->where('target_id', $version->id)->exists())->toBeTrue()
        ->and([FinancialTransaction::count(), Journal::count(), JournalLine::count(), LedgerEntry::count(), Voucher::count()])->toBe($facts);
});

test('draft workflow usage blocks deletion and the server guard preserves dependencies', function () {
    $context = UatFinancialFixture::context();
    $user = User::factory()->create();
    $version = coveredUnusedPolicy($context);
    $transaction = UatFinancialFixture::receipt($context, '25.00');
    FinancialTransaction::withoutEvents(fn () => $transaction->update(['policy_version_ref' => $version->id]));

    $usage = app(FundPolicyVersionDeletionService::class)->usage($version);
    expect($usage['status'])->toBe('USED_IN_DRAFT_ONLY')->and($usage['can_delete'])->toBeFalse();

    $this->actingAs($user)->deleteJson(route('financial-v2.masters.policies.destroy', $version), ['entity' => $context['entity']->id])
        ->assertStatus(422)
        ->assertJsonPath('code', 'E-FUND-POLICY-VERSION-USED');
    expect(FundPolicyVersion::find($version->id))->not->toBeNull()
        ->and(FinancialTransaction::find($transaction->id))->not->toBeNull();
});

test('posted usage is immutable and blocks Fund policy deletion', function () {
    $context = UatFinancialFixture::context();
    $fund = UatFinancialFixture::restrictedFund($context, 'POSTED-POLICY', 'Posted policy');
    $policy = FundPolicyVersion::where('fund_id', $fund->id)->sole();
    $transaction = UatFinancialFixture::receipt($context, '35.00', $fund->id);
    UatFinancialFixture::advance($transaction);
    UatFinancialFixture::post($transaction, 'posted-policy-delete-guard');

    $usage = app(FundPolicyVersionDeletionService::class)->usage($policy);
    expect($usage['status'])->toBe('USED_IN_POSTED')
        ->and($usage['can_delete'])->toBeFalse()
        ->and(JournalLine::where('policy_version_ref', $policy->id)->exists())->toBeTrue();
    expect(fn () => app(FundPolicyVersionDeletionService::class)->delete($context['entity']->id, $policy->id))
        ->toThrow(FinancialDomainException::class, 'tidak dapat dihapus');
});

test('effective policy stays protected even when it has no recorded financial usage', function () {
    $context = UatFinancialFixture::context();
    $policy = FundPolicyVersion::create([
        'accounting_entity_id' => $context['entity']->id,
        'fund_id' => $context['fund']->id,
        'version_no' => 1,
        'effective_from' => now()->subDay()->toDateString(),
        'policy_document_ref' => 'active-policy',
        'allowed_matrix_ref' => 'active-matrix',
        'exception_approval_level' => 'test',
        'status' => 'effective',
        'approved_at' => now(),
    ]);

    expect(app(FundPolicyVersionDeletionService::class)->usage($policy))
        ->status->toBe('ACTIVE_POLICY')
        ->can_delete->toBeFalse();
});
