<?php

use App\Domain\FinancialV2\FinancialPostingException;
use App\Domain\FinancialV2\FinancialTransactionConfigurationResolver;
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
use Illuminate\Support\Str;
use Tests\Support\UatFinancialFixture;

test('canonical resolver automatically resolves valid RCV PAY TRF and IFT configuration', function () {
    $context = UatFinancialFixture::context();
    $resolver = app(FinancialTransactionConfigurationResolver::class);

    $receipt = $resolver->resolve([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['receiptType']->id,
        'date' => $context['today'],
        'financial_account_id' => $context['accountA']->id,
        'fund_id' => $context['fund']->id,
        'category_id' => $context['receiptCategory']->id,
        'program_id' => $context['program']->id,
    ]);
    $payment = $resolver->resolve([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['paymentType']->id,
        'date' => $context['today'],
        'financial_account_id' => $context['accountA']->id,
        'fund_id' => $context['fund']->id,
        'category_id' => $context['paymentCategory']->id,
    ]);
    $treasury = $resolver->resolve([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['treasuryType']->id,
        'date' => $context['today'],
        'source_financial_account_id' => $context['accountA']->id,
        'destination_financial_account_id' => $context['accountB']->id,
        'fund_id' => $context['fund']->id,
    ]);
    $interfund = $resolver->resolve([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['interfundType']->id,
        'date' => $context['today'],
        'financial_account_id' => $context['accountA']->id,
        'fund_ids' => [$context['fund']->id, $context['destinationFund']->id],
    ]);

    expect($receipt->postingRuleVersion->id)->toBe($context['receiptVersion']->id)
        ->and($payment->postingRuleVersion->id)->toBe($context['paymentVersion']->id)
        ->and($treasury->postingRuleVersion->id)->toBe($context['treasuryVersion']->id)
        ->and($interfund->postingRuleVersion->id)->toBe($context['interfundVersion']->id);
});

test('resolver selects a superseded approved version when its date range covers the transaction', function () {
    $context = UatFinancialFixture::context();
    $historicalDate = now()->subDays(2)->toDateString();
    $context['receiptVersion']->update([
        'effective_from' => now()->subYear()->toDateString(),
        'effective_to' => now()->subDay()->toDateString(),
        'status' => 'superseded',
        'approved_at' => now(),
    ]);
    PostingRuleVersion::create([
        'accounting_entity_id' => $context['entity']->id,
        'posting_rule_id' => $context['receiptVersion']->posting_rule_id,
        'version_no' => 2,
        'effective_from' => now()->toDateString(),
        'input_contract_ref' => 'future-version',
        'journal_template_ref' => 'future-version',
        'business_rule_refs' => 'BR-FUTURE',
        'status' => 'effective',
        'approved_at' => now(),
    ]);

    $resolved = app(FinancialTransactionConfigurationResolver::class)->resolve([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['receiptType']->id,
        'date' => $historicalDate,
        'financial_account_id' => $context['accountA']->id,
        'fund_id' => $context['fund']->id,
        'category_id' => $context['receiptCategory']->id,
    ]);

    expect($resolved->postingRuleVersion->id)->toBe($context['receiptVersion']->id)
        ->and($resolved->postingRuleVersion->status)->toBe('superseded');
});

test('resolver validates every fund-bearing posting line against the policy matrix', function () {
    $context = UatFinancialFixture::context();
    $fund = UatFinancialFixture::restrictedFund($context, 'LINE-MATRIX', 'Dana Matriks Baris');
    $policy = FundPolicyVersion::query()->where('fund_id', $fund->id)->firstOrFail();
    FundPolicyRule::query()->where('fund_policy_version_id', $policy->id)->delete();
    FundPolicyRule::create([
        'accounting_entity_id' => $context['entity']->id,
        'fund_policy_version_id' => $policy->id,
        'transaction_type_id' => $context['receiptType']->id,
        'account_id' => $context['revenue']->id,
        'decision' => 'allowed',
    ]);

    expect(fn () => app(FinancialTransactionConfigurationResolver::class)->resolve([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['receiptType']->id,
        'date' => $context['today'],
        'financial_account_id' => $context['accountA']->id,
        'fund_id' => $fund->id,
        'category_id' => $context['receiptCategory']->id,
    ]))->toThrow(FinancialPostingException::class, 'Aturan penggunaan Dana tidak mengizinkan kombinasi tersebut.');
});

test('missing configuration is controlled and neither preview nor draft submission creates policy or financial facts', function () {
    $context = UatFinancialFixture::context();
    $context['receiptVersion']->update(['status' => 'draft']);
    $user = User::factory()->create();
    $before = [
        'rules' => PostingRule::count(),
        'versions' => PostingRuleVersion::count(),
        'transactions' => FinancialTransaction::count(),
        'journals' => Journal::count(),
        'journal_lines' => JournalLine::count(),
        'ledger' => LedgerEntry::count(),
        'vouchers' => Voucher::count(),
    ];
    $payload = [
        'entity' => $context['entity']->id,
        'operation' => 'receipt',
        'date' => $context['today'],
        'financial_account_id' => $context['accountA']->id,
        'fund_id' => $context['fund']->id,
        'category_id' => $context['receiptCategory']->id,
    ];

    $this->actingAs($user)->postJson(route('financial-v2.preview'), $payload)
        ->assertStatus(422)
        ->assertJsonPath('ok', false)
        ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'Konfigurasi pencatatan belum tersedia'));
    $this->actingAs($user)->postJson(route('financial-v2.transactions.store', 'receipt'), $payload + [
        'submission_key' => (string) Str::uuid(),
        'amount' => '100.00',
        'source' => 'Resolver test',
    ])->assertStatus(422)
        ->assertJsonPath('ok', false)
        ->assertJsonPath('code', 'E-CONFIGURATION-MISSING');

    expect([
        'rules' => PostingRule::count(),
        'versions' => PostingRuleVersion::count(),
        'transactions' => FinancialTransaction::count(),
        'journals' => Journal::count(),
        'journal_lines' => JournalLine::count(),
        'ledger' => LedgerEntry::count(),
        'vouchers' => Voucher::count(),
    ])->toBe($before);
});
