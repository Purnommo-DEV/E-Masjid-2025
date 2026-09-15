<?php

use App\Domain\FinancialV2\FinancialDomainException;
use App\Domain\FinancialV2\FundPolicyRuleDeduplicationService;
use App\Models\FinancialV2\AuditEvent;
use App\Models\FinancialV2\FundPolicyRule;
use App\Models\FinancialV2\FundPolicyVersion;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\JournalLine;
use App\Models\FinancialV2\LedgerEntry;
use App\Models\FinancialV2\Voucher;
use Illuminate\Support\Facades\DB;
use Tests\Support\UatFinancialFixture;

function duplicatePolicyRuleFixture(array $context): array
{
    $policy = FundPolicyVersion::create([
        'accounting_entity_id' => $context['entity']->id,
        'fund_id' => $context['fund']->id,
        'version_no' => 99,
        'effective_from' => $context['today'],
        'policy_document_ref' => 'duplicate-cleanup-test',
        'exception_approval_level' => 'financial-governance',
        'status' => 'draft',
    ]);
    $attributes = [
        'accounting_entity_id' => $context['entity']->id,
        'fund_policy_version_id' => $policy->id,
        'transaction_type_id' => $context['paymentType']->id,
        'account_id' => null,
        'category_id' => $context['paymentCategory']->id,
        'program_id' => null,
        'cost_center_id' => null,
        'decision' => 'allowed',
    ];

    return [$policy, FundPolicyRule::create($attributes + ['rationale' => 'canonical']), FundPolicyRule::create($attributes + ['rationale' => 'duplicate'])];
}

test('unused semantic duplicate policy rules are reduced to one without changing financial facts', function () {
    $context = UatFinancialFixture::context();
    [$policy, $canonical, $duplicate] = duplicatePolicyRuleFixture($context);
    $facts = fn (): array => [
        'transactions' => DB::table('financial_v2_transactions')->count(),
        'journals' => Journal::count(),
        'journal_lines' => JournalLine::count(),
        'ledger_entries' => LedgerEntry::count(),
        'vouchers' => Voucher::count(),
    ];
    $before = $facts();

    $result = app(FundPolicyRuleDeduplicationService::class)->clean($context['entity']->id);

    expect($result['removed_count'])->toBe(1)
        ->and($result['facts_before'])->toBe($before)
        ->and($result['facts_after'])->toBe($before)
        ->and(FundPolicyRule::where('fund_policy_version_id', $policy->id)->pluck('id')->all())->toBe([$canonical->id])
        ->and(FundPolicyRule::whereKey($duplicate->id)->exists())->toBeFalse()
        ->and(AuditEvent::where('target_id', $duplicate->id)->where('event_type', 'duplicate_fund_policy_rule_removed')->exists())->toBeTrue();
});

test('cleanup fails closed when a duplicate policy version is referenced by a financial transaction', function () {
    $context = UatFinancialFixture::context();
    [$policy] = duplicatePolicyRuleFixture($context);
    $transaction = UatFinancialFixture::payment($context, '10.00');
    DB::table('financial_v2_transactions')->where('id', $transaction->id)->update(['policy_version_ref' => $policy->id]);

    expect(fn () => app(FundPolicyRuleDeduplicationService::class)->clean($context['entity']->id))
        ->toThrow(FinancialDomainException::class, 'sudah direferensikan')
        ->and(FundPolicyRule::where('fund_policy_version_id', $policy->id)->count())->toBe(2);
});
