<?php

use App\Domain\FinancialV2\BalanceInquiryService;
use App\Domain\FinancialV2\FinancialPostingException;
use App\Domain\FinancialV2\OpeningBalanceStateGuard;
use App\Domain\FinancialV2\PostingEngine;
use App\Models\FinancialV2\Account;
use App\Models\FinancialV2\AccountGroup;
use App\Models\FinancialV2\AccountingCalendar;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\AccountingPeriod;
use App\Models\FinancialV2\ApprovalDecision;
use App\Models\FinancialV2\ApprovalRequirement;
use App\Models\FinancialV2\Attachment;
use App\Models\FinancialV2\AttachmentLink;
use App\Models\FinancialV2\AuditEvent;
use App\Models\FinancialV2\BankAccountDetail;
use App\Models\FinancialV2\DocumentSequence;
use App\Models\FinancialV2\EvidenceRequirement;
use App\Models\FinancialV2\FinancialAccount;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\FundPolicyRule;
use App\Models\FinancialV2\FundPolicyVersion;
use App\Models\FinancialV2\FundRestriction;
use App\Models\FinancialV2\FundType;
use App\Models\FinancialV2\IdempotencyKey;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\JournalLine;
use App\Models\FinancialV2\LedgerEntry;
use App\Models\FinancialV2\LegacyMapping;
use App\Models\FinancialV2\MappingSet;
use App\Models\FinancialV2\OpeningBalanceBatch;
use App\Models\FinancialV2\OpeningBalanceLine;
use App\Models\FinancialV2\PostingAttempt;
use App\Models\FinancialV2\PostingRule;
use App\Models\FinancialV2\PostingRuleLine;
use App\Models\FinancialV2\PostingRuleVersion;
use App\Models\FinancialV2\ReasonCode;
use App\Models\FinancialV2\TransactionSplit;
use App\Models\FinancialV2\TransactionType;
use Illuminate\Support\Str;

function financialV2Context(array $overrides = []): array
{
    $today = now()->toDateString();
    $amount = $overrides['amount'] ?? 100;
    $entity = AccountingEntity::create(['code' => 'TST-'.Str::upper(Str::random(6)), 'name' => 'Test Entity', 'legal_name' => 'Test Entity', 'status' => 'active']);
    $calendar = AccountingCalendar::create(['accounting_entity_id' => $entity->id, 'code' => 'FY-'.Str::random(6), 'name' => 'FY', 'fiscal_year_label' => '2099'.Str::random(4), 'start_date' => '2099-01-01', 'end_date' => '2099-12-31', 'status' => 'active']);
    $period = AccountingPeriod::create(['accounting_entity_id' => $entity->id, 'accounting_calendar_id' => $calendar->id, 'period_no' => 1, 'period_name' => 'Test Period', 'start_date' => now()->subDay()->toDateString(), 'end_date' => now()->addDay()->toDateString(), 'status' => $overrides['period_status'] ?? 'open']);
    $group = AccountGroup::create(['accounting_entity_id' => $entity->id, 'code' => 'ASSET', 'name' => 'Assets', 'group_class' => 'asset', 'status' => 'active']);
    $cash = Account::create(['accounting_entity_id' => $entity->id, 'account_group_id' => $group->id, 'code' => 'CASH', 'name' => 'Cash', 'account_class' => 'asset', 'normal_balance' => 'debit', 'is_posting_account' => true, 'is_liquidity_account' => true, 'status' => 'active']);
    $revenue = Account::create(['accounting_entity_id' => $entity->id, 'account_group_id' => $group->id, 'code' => 'REV', 'name' => 'Revenue', 'account_class' => 'revenue', 'normal_balance' => 'credit', 'is_posting_account' => true, 'status' => 'active']);
    $financialAccount = FinancialAccount::create(['accounting_entity_id' => $entity->id, 'account_id' => $cash->id, 'code' => 'BANK', 'name' => 'Bank', 'account_type' => 'bank', 'opening_date' => now()->subYear()->toDateString(), 'status' => 'active']);
    BankAccountDetail::create(['financial_account_id' => $financialAccount->id, 'bank_name' => 'Test Bank', 'account_number_masked' => '****0001']);
    $wrongFinancialAccount = FinancialAccount::create(['accounting_entity_id' => $entity->id, 'account_id' => $revenue->id, 'code' => 'WRONG', 'name' => 'Wrong Account', 'account_type' => 'e_wallet', 'opening_date' => now()->subYear()->toDateString(), 'status' => 'active']);
    $type = TransactionType::create(['accounting_entity_id' => $entity->id, 'code' => 'RCV', 'name' => 'Receipt', 'voucher_prefix' => 'RCV', 'status' => 'active']);
    $fundType = FundType::create(['accounting_entity_id' => $entity->id, 'code' => 'FUND', 'name' => 'Fund', 'classification' => $overrides['fund_classification'] ?? 'unrestricted']);
    $restriction = FundRestriction::create(['accounting_entity_id' => $entity->id, 'fund_type_id' => $fundType->id, 'code' => 'GEN', 'name' => 'General', 'severity' => 'low', 'policy_basis' => 'test', 'status' => 'active']);
    $fund = Fund::create(['accounting_entity_id' => $entity->id, 'fund_type_id' => $fundType->id, 'fund_restriction_id' => $restriction->id, 'code' => 'FUND', 'name' => 'Fund', 'purpose_statement' => 'Test', 'status' => 'active']);
    $policy = FundPolicyVersion::create(['accounting_entity_id' => $entity->id, 'fund_id' => $fund->id, 'version_no' => 1, 'effective_from' => now()->subDay()->toDateString(), 'policy_document_ref' => 'policy', 'exception_approval_level' => 'test', 'status' => 'effective']);
    if (($overrides['fund_classification'] ?? 'unrestricted') !== 'unrestricted' && ! ($overrides['deny_fund'] ?? false)) {
        FundPolicyRule::create(['accounting_entity_id' => $entity->id, 'fund_policy_version_id' => $policy->id, 'transaction_type_id' => $type->id, 'decision' => 'allowed']);
    }
    $rule = PostingRule::create(['accounting_entity_id' => $entity->id, 'transaction_type_id' => $type->id, 'code' => 'RCV', 'name' => 'Receipt', 'rule_family' => 'receipt', 'status' => 'active']);
    $version = PostingRuleVersion::create(['accounting_entity_id' => $entity->id, 'posting_rule_id' => $rule->id, 'version_no' => 1, 'effective_from' => now()->subDay()->toDateString(), 'input_contract_ref' => 'test', 'journal_template_ref' => 'test', 'business_rule_refs' => 'BR-066', 'status' => 'effective']);
    PostingRuleLine::create(['accounting_entity_id' => $entity->id, 'posting_rule_version_id' => $version->id, 'line_no' => 1, 'account_id' => $cash->id, 'entry_side' => 'debit', 'amount_source' => 'split_amount', 'financial_account_source' => 'transaction_primary', 'fund_source' => 'split']);
    if (! ($overrides['one_line'] ?? false)) {
        PostingRuleLine::create(['accounting_entity_id' => $entity->id, 'posting_rule_version_id' => $version->id, 'line_no' => 2, 'account_id' => $revenue->id, 'entry_side' => 'credit', 'amount_source' => 'split_amount', 'fund_source' => 'split']);
    }
    if (! ($overrides['no_sequence'] ?? false)) {
        DocumentSequence::create(['accounting_entity_id' => $entity->id, 'transaction_type_id' => $type->id, 'code' => 'RCV', 'name' => 'Receipt', 'prefix' => 'RCV', 'scope_key' => 'test', 'status' => 'active']);
    }
    $transaction = FinancialTransaction::create(['accounting_entity_id' => $entity->id, 'transaction_type_id' => $type->id, 'status' => 'approved', 'source_reference' => 'SRC-'.Str::uuid(), 'business_date' => $today, 'accounting_date' => $today, 'gross_amount' => $amount, 'primary_financial_account_id' => ($overrides['invalid_financial_account'] ?? false) ? $wrongFinancialAccount->id : $financialAccount->id, 'idempotency_key' => 'source-'.Str::uuid(), 'correlation_id' => (string) Str::uuid()]);
    TransactionSplit::create(['accounting_entity_id' => $entity->id, 'transaction_id' => $transaction->id, 'line_no' => 1, 'split_amount' => $amount, 'account_id' => $revenue->id, 'fund_id' => $fund->id]);

    return compact('entity', 'period', 'cash', 'revenue', 'financialAccount', 'fund', 'policy', 'version', 'transaction');
}

function financialV2TypeWithRule(array $context, string $code, string $family, bool $withRuleLines = true): array
{
    $type = TransactionType::create(['accounting_entity_id' => $context['entity']->id, 'code' => $code, 'name' => $code, 'voucher_prefix' => $code, 'status' => 'active']);
    $rule = PostingRule::create(['accounting_entity_id' => $context['entity']->id, 'transaction_type_id' => $type->id, 'code' => $code, 'name' => $code, 'rule_family' => $family, 'status' => 'active']);
    $version = PostingRuleVersion::create(['accounting_entity_id' => $context['entity']->id, 'posting_rule_id' => $rule->id, 'version_no' => 1, 'effective_from' => now()->subDay()->toDateString(), 'input_contract_ref' => 'test', 'journal_template_ref' => 'test', 'business_rule_refs' => 'BR-TEST', 'status' => 'effective']);
    if ($withRuleLines) {
        PostingRuleLine::create(['accounting_entity_id' => $context['entity']->id, 'posting_rule_version_id' => $version->id, 'line_no' => 1, 'account_id' => $context['cash']->id, 'entry_side' => 'debit', 'amount_source' => 'split_amount', 'financial_account_source' => 'transaction_primary', 'fund_source' => 'split']);
        PostingRuleLine::create(['accounting_entity_id' => $context['entity']->id, 'posting_rule_version_id' => $version->id, 'line_no' => 2, 'account_id' => $context['revenue']->id, 'entry_side' => 'credit', 'amount_source' => 'split_amount', 'fund_source' => 'split']);
    }
    DocumentSequence::create(['accounting_entity_id' => $context['entity']->id, 'transaction_type_id' => $type->id, 'code' => $code, 'name' => $code, 'prefix' => $code, 'scope_key' => 'test', 'status' => 'active']);

    return compact('type', 'rule', 'version');
}

function financialV2Reason(array $context, string $code, string $class): ReasonCode
{
    return ReasonCode::create(['accounting_entity_id' => $context['entity']->id, 'code' => $code, 'name' => $code, 'reason_class' => $class, 'status' => 'active']);
}

function financialV2CorrectionApprovalAndEvidence(array $context, FinancialTransaction $transaction): void
{
    ApprovalDecision::create(['accounting_entity_id' => $context['entity']->id, 'transaction_id' => $transaction->id, 'step_no' => 1, 'decision' => 'approved', 'decision_at' => now(), 'comment' => 'Test approval']);
    $attachment = Attachment::create(['accounting_entity_id' => $context['entity']->id, 'original_filename' => 'evidence.pdf', 'media_type' => 'application/pdf', 'byte_size' => 1, 'content_hash' => hash('sha256', (string) Str::uuid()), 'storage_reference' => 'test://'.Str::uuid(), 'status' => 'active', 'received_at' => now()]);
    AttachmentLink::create(['accounting_entity_id' => $context['entity']->id, 'attachment_id' => $attachment->id, 'target_type' => 'transaction', 'target_id' => $transaction->id, 'evidence_type' => 'other', 'status' => 'active']);
}

test('posts a balanced journal, immutable ledger, voucher, and source traceability', function () {
    $context = financialV2Context();
    $result = app(PostingEngine::class)->post($context['transaction']->id, 'request-1', hash('sha256', 'request-1'));
    $journal = Journal::findOrFail($result->journalId);
    expect($journal->journal_status)->toBe('posted')->and($journal->total_debit)->toBe('100.00')->and($journal->total_credit)->toBe('100.00');
    expect($journal->transaction_id)->toBe($context['transaction']->id)->and($journal->posting_rule_version_id)->toBe($context['version']->id);
    expect(LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count())->toBe(2);
    expect(fn () => $journal->update(['description' => 'changed']))->toThrow(DomainException::class);
    expect(fn () => LedgerEntry::firstOrFail()->update(['signed_amount' => 99]))->toThrow(DomainException::class);
});

test('prevents direct Eloquent creation of official financial facts outside the Posting Engine', function () {
    expect(fn () => Journal::create())->toThrow(DomainException::class, 'Posting Engine');
    expect(fn () => JournalLine::create())->toThrow(DomainException::class, 'Posting Engine');
    expect(fn () => LedgerEntry::create())->toThrow(DomainException::class, 'Posting Engine');
});

test('preserves high-value decimal amounts without PHP float precision loss', function () {
    $amount = '90071992547409.91';
    $context = financialV2Context(['amount' => $amount]);
    $result = app(PostingEngine::class)->post($context['transaction']->id, 'request-exact-decimal', hash('sha256', 'request-exact-decimal'));

    expect(Journal::findOrFail($result->journalId)->total_debit)->toBe($amount)
        ->and(Journal::findOrFail($result->journalId)->total_credit)->toBe($amount)
        ->and(LedgerEntry::where('account_id', $context['cash']->id)->value('signed_amount'))->toBe($amount)
        ->and(app(BalanceInquiryService::class)->accountBalance($context['entity']->id, $context['cash']->id, now()->toDateString())['balance'])->toBe($amount);
});

test('derives account balances and rebuildable projections from posted V2 ledger facts only', function () {
    $context = financialV2Context();
    app(PostingEngine::class)->post($context['transaction']->id, 'request-balance', hash('sha256', 'request-balance'));
    $inquiry = app(BalanceInquiryService::class);

    expect($inquiry->accountBalance($context['entity']->id, $context['cash']->id, now()->toDateString()))->toMatchArray(['debit_total' => '100.00', 'credit_total' => '0.00', 'balance' => '100.00', 'through_posting_sequence' => 1]);
    expect($inquiry->financialAccountBalance($context['entity']->id, $context['financialAccount']->id, now()->toDateString())['balance'])->toBe('100.00');
    expect($inquiry->fundLiquidityDistribution($context['entity']->id, $context['fund']->id, now()->toDateString())->sole()->balance)->toBe('100.00');
    expect($inquiry->refreshAccountProjections($context['entity']->id, now()->toDateString()))->toBe(2);
});

test('rejects a liquidity journal line without both Financial Account and Fund dimensions', function () {
    $context = financialV2Context();
    PostingRuleLine::query()->where('posting_rule_version_id', $context['version']->id)->where('line_no', 1)->update(['fund_source' => 'none']);

    expect(fn () => app(PostingEngine::class)->post($context['transaction']->id, 'request-liquidity-dimension', hash('sha256', 'request-liquidity-dimension')))->toThrow(FinancialPostingException::class, 'liquidity account requires Financial Account and Fund');
    expect(LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count())->toBe(0);
});

test('blocks a payment that would make a Fund balance in its Financial Account negative', function () {
    $context = financialV2Context();
    $engine = app(PostingEngine::class);
    $engine->post($context['transaction']->id, 'request-fund-receipt', hash('sha256', 'request-fund-receipt'));
    $expense = Account::create(['accounting_entity_id' => $context['entity']->id, 'account_group_id' => AccountGroup::where('accounting_entity_id', $context['entity']->id)->firstOrFail()->id, 'code' => 'EXP', 'name' => 'Expense', 'account_class' => 'expense', 'normal_balance' => 'debit', 'is_posting_account' => true, 'status' => 'active']);
    $type = TransactionType::create(['accounting_entity_id' => $context['entity']->id, 'code' => 'PAY', 'name' => 'Payment', 'voucher_prefix' => 'PAY', 'status' => 'active']);
    $rule = PostingRule::create(['accounting_entity_id' => $context['entity']->id, 'transaction_type_id' => $type->id, 'code' => 'PAY', 'name' => 'Payment', 'rule_family' => 'payment', 'status' => 'active']);
    $version = PostingRuleVersion::create(['accounting_entity_id' => $context['entity']->id, 'posting_rule_id' => $rule->id, 'version_no' => 1, 'effective_from' => now()->subDay()->toDateString(), 'input_contract_ref' => 'test', 'journal_template_ref' => 'test', 'business_rule_refs' => 'BR-025', 'status' => 'effective']);
    PostingRuleLine::create(['accounting_entity_id' => $context['entity']->id, 'posting_rule_version_id' => $version->id, 'line_no' => 1, 'account_id' => $expense->id, 'entry_side' => 'debit', 'amount_source' => 'split_amount', 'fund_source' => 'split']);
    PostingRuleLine::create(['accounting_entity_id' => $context['entity']->id, 'posting_rule_version_id' => $version->id, 'line_no' => 2, 'account_id' => $context['cash']->id, 'entry_side' => 'credit', 'amount_source' => 'split_amount', 'financial_account_source' => 'transaction_primary', 'fund_source' => 'split']);
    DocumentSequence::create(['accounting_entity_id' => $context['entity']->id, 'transaction_type_id' => $type->id, 'code' => 'PAY', 'name' => 'Payment', 'prefix' => 'PAY', 'scope_key' => 'test', 'status' => 'active']);
    $payment = FinancialTransaction::create(['accounting_entity_id' => $context['entity']->id, 'transaction_type_id' => $type->id, 'status' => 'approved', 'source_reference' => 'PAY-'.Str::uuid(), 'business_date' => now()->toDateString(), 'accounting_date' => now()->toDateString(), 'description' => 'Overspend test', 'gross_amount' => 150, 'primary_financial_account_id' => $context['financialAccount']->id, 'idempotency_key' => 'source-'.Str::uuid(), 'correlation_id' => (string) Str::uuid()]);
    TransactionSplit::create(['accounting_entity_id' => $context['entity']->id, 'transaction_id' => $payment->id, 'line_no' => 1, 'split_amount' => 150, 'account_id' => $expense->id, 'fund_id' => $context['fund']->id]);

    expect(fn () => $engine->post($payment->id, 'request-fund-overspend', hash('sha256', 'request-fund-overspend')))->toThrow(FinancialPostingException::class, 'Fund liquidity balance policy');
    expect(Journal::where('accounting_entity_id', $context['entity']->id)->count())->toBe(1);
});

test('rejects an unbalanced rule before any accounting fact is committed', function () {
    $context = financialV2Context(['one_line' => true]);
    expect(fn () => app(PostingEngine::class)->post($context['transaction']->id, 'request-2', hash('sha256', 'request-2')))->toThrow(FinancialPostingException::class);
    expect(Journal::where('accounting_entity_id', $context['entity']->id)->count())->toBe(0)->and(LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count())->toBe(0);
});

test('retains failed posting diagnostics and allows a same-key retry after correction', function () {
    $context = financialV2Context(['one_line' => true]);
    $key = 'request-retained-failure';
    $fingerprint = hash('sha256', $key);

    expect(fn () => app(PostingEngine::class)->post($context['transaction']->id, $key, $fingerprint))->toThrow(FinancialPostingException::class);
    expect(PostingAttempt::where('transaction_id', $context['transaction']->id)->sole())
        ->status->toBe('failed')
        ->and(PostingAttempt::where('transaction_id', $context['transaction']->id)->sole()->failure_code)->toBe('E-JOURNAL-UNBALANCED')
        ->and(IdempotencyKey::where('key_value', $key)->sole()->status)->toBe('failed')
        ->and(AuditEvent::where('target_id', $context['transaction']->id)->where('event_type', 'posting_failed')->count())->toBe(1)
        ->and(Journal::where('accounting_entity_id', $context['entity']->id)->count())->toBe(0)
        ->and(LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count())->toBe(0);

    PostingRuleLine::create(['accounting_entity_id' => $context['entity']->id, 'posting_rule_version_id' => $context['version']->id, 'line_no' => 2, 'account_id' => $context['revenue']->id, 'entry_side' => 'credit', 'amount_source' => 'split_amount', 'fund_source' => 'split']);
    $result = app(PostingEngine::class)->post($context['transaction']->id, $key, $fingerprint);

    expect($result->journalId)->not->toBeEmpty()
        ->and(PostingAttempt::where('transaction_id', $context['transaction']->id)->count())->toBe(2)
        ->and(IdempotencyKey::where('key_value', $key)->sole()->status)->toBe('completed');
});

test('is idempotent and serializes duplicate posting requests to one official journal', function () {
    $context = financialV2Context();
    $engine = app(PostingEngine::class);
    $first = $engine->post($context['transaction']->id, 'request-3', hash('sha256', 'request-3'));
    $second = $engine->post($context['transaction']->id, 'request-3', hash('sha256', 'request-3'));
    expect($second->journalId)->toBe($first->journalId)->and(Journal::where('accounting_entity_id', $context['entity']->id)->count())->toBe(1);
});

test('rejects an idempotency key reused with a different request fingerprint', function () {
    $context = financialV2Context();
    $engine = app(PostingEngine::class);
    $engine->post($context['transaction']->id, 'request-conflict', hash('sha256', 'original'));

    expect(fn () => $engine->post($context['transaction']->id, 'request-conflict', hash('sha256', 'different')))->toThrow(FinancialPostingException::class, 'Idempotency key payload differs');
    expect(Journal::where('accounting_entity_id', $context['entity']->id)->count())->toBe(1);
});

test('rejects closed periods, restricted funds without an allowed rule, and invalid financial accounts', function (array $overrides) {
    $context = financialV2Context($overrides);
    expect(fn () => app(PostingEngine::class)->post($context['transaction']->id, 'request-'.Str::uuid(), hash('sha256', (string) Str::uuid())))->toThrow(FinancialPostingException::class);
})->with([
    'closed period' => [['period_status' => 'hard_closed']],
    'reopened period without an approved scope' => [['period_status' => 'reopened']],
    'restricted fund fail closed' => [['fund_classification' => 'restricted', 'deny_fund' => true]],
    'invalid financial account' => [['invalid_financial_account' => true]],
]);

test('requires configured evidence before committing accounting facts', function () {
    $context = financialV2Context();
    EvidenceRequirement::create(['accounting_entity_id' => $context['entity']->id, 'posting_rule_version_id' => $context['version']->id, 'evidence_type' => 'receipt', 'minimum_count' => 1]);

    expect(fn () => app(PostingEngine::class)->post($context['transaction']->id, 'request-evidence', hash('sha256', 'request-evidence')))->toThrow(FinancialPostingException::class);
    expect(Journal::where('accounting_entity_id', $context['entity']->id)->count())->toBe(0)->and(LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count())->toBe(0);
});

test('requires configured approval steps before committing accounting facts', function () {
    $context = financialV2Context();
    ApprovalRequirement::create(['accounting_entity_id' => $context['entity']->id, 'transaction_type_id' => $context['transaction']->transaction_type_id, 'required_steps' => 1, 'status' => 'active', 'effective_from' => now()->subDay()->toDateString()]);

    expect(fn () => app(PostingEngine::class)->post($context['transaction']->id, 'request-approval', hash('sha256', 'request-approval')))->toThrow(FinancialPostingException::class);
    expect(Journal::where('accounting_entity_id', $context['entity']->id)->count())->toBe(0)->and(LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count())->toBe(0);
});

test('gives an explicit prohibited restricted-fund matrix rule precedence over allowed rules', function () {
    $context = financialV2Context(['fund_classification' => 'restricted']);
    FundPolicyRule::create(['accounting_entity_id' => $context['entity']->id, 'fund_policy_version_id' => $context['policy']->id, 'transaction_type_id' => $context['transaction']->transaction_type_id, 'decision' => 'prohibited']);

    expect(fn () => app(PostingEngine::class)->post($context['transaction']->id, 'request-prohibited', hash('sha256', 'request-prohibited')))->toThrow(FinancialPostingException::class);
    expect(Journal::where('accounting_entity_id', $context['entity']->id)->count())->toBe(0);
});

test('posts a reversal as a new journal with immutable lineage and offsetting ledger facts', function () {
    $context = financialV2Context();
    $engine = app(PostingEngine::class);
    $original = $engine->post($context['transaction']->id, 'request-original', hash('sha256', 'request-original'));
    $reversalDefinition = financialV2TypeWithRule($context, 'REV', 'reversal', false);
    $reason = financialV2Reason($context, 'REV-TEST', 'reversal');
    $reversal = FinancialTransaction::create(['accounting_entity_id' => $context['entity']->id, 'transaction_type_id' => $reversalDefinition['type']->id, 'status' => 'approved', 'source_reference' => 'REV-'.Str::uuid(), 'business_date' => now()->toDateString(), 'accounting_date' => now()->toDateString(), 'description' => 'Reverse posting error', 'gross_amount' => 100, 'primary_financial_account_id' => $context['financialAccount']->id, 'reason_code_id' => $reason->id, 'related_transaction_id' => $context['transaction']->id, 'idempotency_key' => 'source-'.Str::uuid(), 'correlation_id' => (string) Str::uuid()]);
    TransactionSplit::create(['accounting_entity_id' => $context['entity']->id, 'transaction_id' => $reversal->id, 'line_no' => 1, 'split_amount' => 100, 'account_id' => $context['revenue']->id, 'fund_id' => $context['fund']->id]);
    financialV2CorrectionApprovalAndEvidence($context, $reversal);

    $result = $engine->post($reversal->id, 'request-reversal', hash('sha256', 'request-reversal'));
    $journal = Journal::findOrFail($result->journalId);
    expect($journal->reversal_of_journal_id)->toBe($original->journalId)->and(LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count())->toBe(4);
    expect(AuditEvent::where('event_type', 'reversal_committed')->where('target_id', $journal->id)->count())->toBe(1);
    expect(app(BalanceInquiryService::class)->accountBalance($context['entity']->id, $context['cash']->id, now()->toDateString())['balance'])->toBe('0.00');
    expect(fn () => $journal->update(['description' => 'mutate reversal']))->toThrow(DomainException::class);

    $reversalOfReversal = FinancialTransaction::create(['accounting_entity_id' => $context['entity']->id, 'transaction_type_id' => $reversalDefinition['type']->id, 'status' => 'approved', 'source_reference' => 'REV-OF-REV-'.Str::uuid(), 'business_date' => now()->toDateString(), 'accounting_date' => now()->toDateString(), 'description' => 'Invalid reversal of a reversal', 'gross_amount' => 100, 'primary_financial_account_id' => $context['financialAccount']->id, 'reason_code_id' => $reason->id, 'related_transaction_id' => $reversal->id, 'idempotency_key' => 'source-'.Str::uuid(), 'correlation_id' => (string) Str::uuid()]);
    TransactionSplit::create(['accounting_entity_id' => $context['entity']->id, 'transaction_id' => $reversalOfReversal->id, 'line_no' => 1, 'split_amount' => 100, 'account_id' => $context['revenue']->id, 'fund_id' => $context['fund']->id]);
    financialV2CorrectionApprovalAndEvidence($context, $reversalOfReversal);

    expect(fn () => $engine->post($reversalOfReversal->id, 'request-reversal-of-reversal', hash('sha256', 'request-reversal-of-reversal')))
        ->toThrow(FinancialPostingException::class, 'cannot target another reversal')
        ->and(Journal::where('accounting_entity_id', $context['entity']->id)->count())->toBe(2);
});

test('posts adjustment only with traceable correction rationale and its effective rule version', function () {
    $context = financialV2Context();
    $adjustmentDefinition = financialV2TypeWithRule($context, 'ADJ', 'adjustment');
    $reason = financialV2Reason($context, 'ADJ-TEST', 'adjustment');
    $adjustment = FinancialTransaction::create(['accounting_entity_id' => $context['entity']->id, 'transaction_type_id' => $adjustmentDefinition['type']->id, 'status' => 'approved', 'source_reference' => 'ADJ-'.Str::uuid(), 'business_date' => now()->toDateString(), 'accounting_date' => now()->toDateString(), 'description' => 'Approved reconciliation adjustment', 'gross_amount' => 25, 'primary_financial_account_id' => $context['financialAccount']->id, 'reason_code_id' => $reason->id, 'idempotency_key' => 'source-'.Str::uuid(), 'correlation_id' => (string) Str::uuid()]);
    TransactionSplit::create(['accounting_entity_id' => $context['entity']->id, 'transaction_id' => $adjustment->id, 'line_no' => 1, 'split_amount' => 25, 'account_id' => $context['revenue']->id, 'fund_id' => $context['fund']->id]);
    financialV2CorrectionApprovalAndEvidence($context, $adjustment);

    $result = app(PostingEngine::class)->post($adjustment->id, 'request-adjustment', hash('sha256', 'request-adjustment'));
    expect(Journal::findOrFail($result->journalId)->posting_rule_version_id)->toBe($adjustmentDefinition['version']->id);
});

test('allows only a governed adjustment in a soft-closed period', function () {
    $context = financialV2Context(['period_status' => 'soft_closed']);
    $adjustmentDefinition = financialV2TypeWithRule($context, 'ADJ', 'adjustment');
    $reason = financialV2Reason($context, 'ADJ-SOFT', 'adjustment');
    $adjustment = FinancialTransaction::create(['accounting_entity_id' => $context['entity']->id, 'transaction_type_id' => $adjustmentDefinition['type']->id, 'status' => 'approved', 'source_reference' => 'ADJ-SOFT-'.Str::uuid(), 'business_date' => now()->toDateString(), 'accounting_date' => now()->toDateString(), 'description' => 'Governed soft-close adjustment', 'gross_amount' => 25, 'primary_financial_account_id' => $context['financialAccount']->id, 'reason_code_id' => $reason->id, 'idempotency_key' => 'source-'.Str::uuid(), 'correlation_id' => (string) Str::uuid()]);
    TransactionSplit::create(['accounting_entity_id' => $context['entity']->id, 'transaction_id' => $adjustment->id, 'line_no' => 1, 'split_amount' => 25, 'account_id' => $context['revenue']->id, 'fund_id' => $context['fund']->id]);
    financialV2CorrectionApprovalAndEvidence($context, $adjustment);

    expect(app(PostingEngine::class)->post($adjustment->id, 'request-soft-adjustment', hash('sha256', 'request-soft-adjustment'))->journalId)->not->toBeEmpty();
});

test('posts an approved and reconciled opening-balance batch through the canonical engine', function () {
    $context = financialV2Context();
    $openingDefinition = financialV2TypeWithRule($context, 'OPB', 'opening-balance', false);
    $mappingSet = MappingSet::create(['accounting_entity_id' => $context['entity']->id, 'code' => 'MAP-OPEN', 'name' => 'Opening Mapping', 'source_system_name' => 'legacy_archive', 'mapping_status' => 'frozen', 'approved_at' => now()]);
    foreach ([
        ['legacy:cash', 'account', $context['cash']->id], ['legacy:cash', 'fund', $context['fund']->id], ['legacy:cash', 'financial_account', $context['financialAccount']->id],
        ['legacy:equity', 'account', $context['revenue']->id], ['legacy:equity', 'fund', $context['fund']->id],
    ] as [$source, $dimension, $target]) {
        LegacyMapping::create(['accounting_entity_id' => $context['entity']->id, 'mapping_set_id' => $mappingSet->id, 'legacy_record_ref' => $source.'|'.$dimension, 'target_entity_type' => $dimension, 'target_entity_id' => $target, 'mapping_status' => 'confirmed', 'rationale' => 'approved test mapping']);
    }
    $batch = OpeningBalanceStateGuard::withinOpeningBalance(fn () => OpeningBalanceBatch::create(['accounting_entity_id' => $context['entity']->id, 'accounting_period_id' => $context['period']->id, 'mapping_set_id' => $mappingSet->id, 'cutover_date' => now()->toDateString(), 'cutover_reference' => 'SIMULATED-TEST-ONLY', 'status' => 'approved', 'evidence_package_ref' => 'reconciliation/test-only', 'reviewed_at' => now(), 'approved_at' => now()]));
    $lines = OpeningBalanceStateGuard::withinOpeningBalance(fn () => collect([
        OpeningBalanceLine::create(['accounting_entity_id' => $context['entity']->id, 'opening_balance_batch_id' => $batch->id, 'line_no' => 1, 'account_id' => $context['cash']->id, 'fund_id' => $context['fund']->id, 'financial_account_id' => $context['financialAccount']->id, 'debit_amount' => 100, 'credit_amount' => 0, 'source_debit_amount' => 100, 'source_credit_amount' => 0, 'source_reference' => 'legacy:cash', 'reconciliation_difference' => 0, 'reconciliation_status' => 'reconciled', 'evidence_ref' => 'statement/test', 'mapping_ref' => 'legacy:cash', 'line_description' => 'Opening cash']),
        OpeningBalanceLine::create(['accounting_entity_id' => $context['entity']->id, 'opening_balance_batch_id' => $batch->id, 'line_no' => 2, 'account_id' => $context['revenue']->id, 'fund_id' => $context['fund']->id, 'debit_amount' => 0, 'credit_amount' => 100, 'source_debit_amount' => 0, 'source_credit_amount' => 100, 'source_reference' => 'legacy:equity', 'reconciliation_difference' => 0, 'reconciliation_status' => 'reconciled', 'evidence_ref' => 'statement/test', 'mapping_ref' => 'legacy:equity', 'line_description' => 'Opening net assets']),
    ]));
    foreach ($lines as $line) {
        $attachment = Attachment::create(['accounting_entity_id' => $context['entity']->id, 'original_filename' => 'opening.pdf', 'media_type' => 'application/pdf', 'byte_size' => 10, 'content_hash' => hash('sha256', $line->id), 'storage_reference' => 'tests/opening/'.$line->id.'.pdf', 'status' => 'active', 'received_at' => now()]);
        AttachmentLink::create(['accounting_entity_id' => $context['entity']->id, 'attachment_id' => $attachment->id, 'target_type' => 'opening_balance_line', 'target_id' => $line->id, 'evidence_type' => 'statement', 'status' => 'active']);
    }

    $result = app(PostingEngine::class)->postOpeningBalance($batch->id, $openingDefinition['type']->id, 'request-opening', hash('sha256', 'request-opening'));
    expect(OpeningBalanceBatch::findOrFail($batch->id)->status)->toBe('posted')->and(OpeningBalanceBatch::findOrFail($batch->id)->journal_id)->toBe($result->journalId);
    expect(Journal::findOrFail($result->journalId)->posting_rule_version_id)->toBe($openingDefinition['version']->id)->and(LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count())->toBe(2);
});
