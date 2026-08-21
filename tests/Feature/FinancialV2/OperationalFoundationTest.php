<?php

use App\Domain\FinancialV2\BudgetAllocationService;
use App\Domain\FinancialV2\EvidenceService;
use App\Domain\FinancialV2\FinancialDomainException;
use App\Domain\FinancialV2\FinancialTransactionLifecycleService;
use App\Domain\FinancialV2\MasterDataGovernanceService;
use App\Domain\FinancialV2\PeriodClosingService;
use App\Domain\FinancialV2\PeriodClosingStateGuard;
use App\Domain\FinancialV2\ReconciliationService;
use App\Domain\FinancialV2\Reporting\FinancialReportService;
use App\Models\FinancialV2\Account;
use App\Models\FinancialV2\AccountGroup;
use App\Models\FinancialV2\AccountingCalendar;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\AccountingPeriod;
use App\Models\FinancialV2\AuditEvent;
use App\Models\FinancialV2\BankAccountDetail;
use App\Models\FinancialV2\Category;
use App\Models\FinancialV2\ClosingRun;
use App\Models\FinancialV2\Counterparty;
use App\Models\FinancialV2\DocumentSequence;
use App\Models\FinancialV2\FinancialAccount;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\FundPolicyVersion;
use App\Models\FinancialV2\FundRealization;
use App\Models\FinancialV2\FundRestriction;
use App\Models\FinancialV2\FundType;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\JournalLine;
use App\Models\FinancialV2\LedgerEntry;
use App\Models\FinancialV2\PostingRule;
use App\Models\FinancialV2\PostingRuleLine;
use App\Models\FinancialV2\PostingRuleVersion;
use App\Models\FinancialV2\Reconciliation;
use App\Models\FinancialV2\TransactionType;
use App\Models\FinancialV2\Voucher;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function operationalV2Context(): array
{
    $suffix = Str::upper(Str::random(7));
    $today = now()->toDateString();
    $entity = AccountingEntity::create(['code' => "OPS-{$suffix}", 'name' => 'Operational Test', 'legal_name' => 'Operational Test', 'status' => 'active']);
    $calendar = AccountingCalendar::create(['accounting_entity_id' => $entity->id, 'code' => "CAL-{$suffix}", 'name' => 'Operational Calendar', 'fiscal_year_label' => "2099-{$suffix}", 'start_date' => '2099-01-01', 'end_date' => '2099-12-31', 'status' => 'active']);
    $period = AccountingPeriod::create(['accounting_entity_id' => $entity->id, 'accounting_calendar_id' => $calendar->id, 'period_no' => 1, 'period_name' => 'Operational Period', 'start_date' => now()->subDay(), 'end_date' => now()->addDay(), 'status' => 'open']);

    $assetGroup = AccountGroup::create(['accounting_entity_id' => $entity->id, 'code' => 'AST', 'name' => 'Assets', 'group_class' => 'asset', 'status' => 'active']);
    $revenueGroup = AccountGroup::create(['accounting_entity_id' => $entity->id, 'code' => 'REV', 'name' => 'Revenue', 'group_class' => 'revenue', 'status' => 'active']);
    $expenseGroup = AccountGroup::create(['accounting_entity_id' => $entity->id, 'code' => 'EXP', 'name' => 'Expenses', 'group_class' => 'expense', 'status' => 'active']);
    $transferGroup = AccountGroup::create(['accounting_entity_id' => $entity->id, 'code' => 'TRN', 'name' => 'Transfers', 'group_class' => 'transfer', 'status' => 'active']);
    $cashSource = Account::create(['accounting_entity_id' => $entity->id, 'account_group_id' => $assetGroup->id, 'code' => 'CASH-SRC', 'name' => 'Cash Source', 'account_class' => 'asset', 'normal_balance' => 'debit', 'is_posting_account' => true, 'is_liquidity_account' => true, 'status' => 'active']);
    $cashDestination = Account::create(['accounting_entity_id' => $entity->id, 'account_group_id' => $assetGroup->id, 'code' => 'CASH-DST', 'name' => 'Cash Destination', 'account_class' => 'asset', 'normal_balance' => 'debit', 'is_posting_account' => true, 'is_liquidity_account' => true, 'status' => 'active']);
    $revenue = Account::create(['accounting_entity_id' => $entity->id, 'account_group_id' => $revenueGroup->id, 'code' => 'DON-REV', 'name' => 'Donation Revenue', 'account_class' => 'revenue', 'normal_balance' => 'credit', 'is_posting_account' => true, 'status' => 'active']);
    $expense = Account::create(['accounting_entity_id' => $entity->id, 'account_group_id' => $expenseGroup->id, 'code' => 'OPS-EXP', 'name' => 'Operational Expense', 'account_class' => 'expense', 'normal_balance' => 'debit', 'is_posting_account' => true, 'status' => 'active']);
    $transferIn = Account::create(['accounting_entity_id' => $entity->id, 'account_group_id' => $transferGroup->id, 'code' => 'IFT-IN', 'name' => 'Interfund Transfer In', 'account_class' => 'transfer', 'normal_balance' => 'debit', 'is_posting_account' => true, 'status' => 'active']);
    $transferOut = Account::create(['accounting_entity_id' => $entity->id, 'account_group_id' => $transferGroup->id, 'code' => 'IFT-OUT', 'name' => 'Interfund Transfer Out', 'account_class' => 'transfer', 'normal_balance' => 'credit', 'is_posting_account' => true, 'status' => 'active']);

    $sourceFinancialAccount = FinancialAccount::create(['accounting_entity_id' => $entity->id, 'account_id' => $cashSource->id, 'code' => 'BANK-A', 'name' => 'Bank A', 'account_type' => 'bank', 'opening_date' => now()->subYear(), 'status' => 'active']);
    $destinationFinancialAccount = FinancialAccount::create(['accounting_entity_id' => $entity->id, 'account_id' => $cashDestination->id, 'code' => 'BANK-B', 'name' => 'Bank B', 'account_type' => 'bank', 'opening_date' => now()->subYear(), 'status' => 'active']);
    BankAccountDetail::create(['financial_account_id' => $sourceFinancialAccount->id, 'bank_name' => 'Bank A', 'account_number_masked' => '****0001']);
    BankAccountDetail::create(['financial_account_id' => $destinationFinancialAccount->id, 'bank_name' => 'Bank B', 'account_number_masked' => '****0002']);

    $fundType = FundType::create(['accounting_entity_id' => $entity->id, 'code' => 'UNR', 'name' => 'Unrestricted', 'classification' => 'unrestricted', 'status' => 'active']);
    $restriction = FundRestriction::create(['accounting_entity_id' => $entity->id, 'fund_type_id' => $fundType->id, 'code' => 'GEN', 'name' => 'General', 'severity' => 'low', 'policy_basis' => 'Approved test policy', 'status' => 'active']);
    $fund = Fund::create(['accounting_entity_id' => $entity->id, 'fund_type_id' => $fundType->id, 'fund_restriction_id' => $restriction->id, 'code' => 'OPS', 'name' => 'Operational Fund', 'purpose_statement' => 'Operations', 'status' => 'active']);
    $destinationFund = Fund::create(['accounting_entity_id' => $entity->id, 'fund_type_id' => $fundType->id, 'fund_restriction_id' => $restriction->id, 'code' => 'DST', 'name' => 'Destination Fund', 'purpose_statement' => 'Destination', 'status' => 'active']);

    $receiptType = TransactionType::create(['accounting_entity_id' => $entity->id, 'code' => 'RCV', 'name' => 'Receipt', 'voucher_prefix' => 'RCV', 'status' => 'active']);
    $paymentType = TransactionType::create(['accounting_entity_id' => $entity->id, 'code' => 'PAY', 'name' => 'Payment', 'voucher_prefix' => 'PAY', 'status' => 'active']);
    $transferType = TransactionType::create(['accounting_entity_id' => $entity->id, 'code' => 'TRF', 'name' => 'Treasury Transfer', 'voucher_prefix' => 'TRF', 'status' => 'active']);
    $interfundType = TransactionType::create(['accounting_entity_id' => $entity->id, 'code' => 'IFT', 'name' => 'Interfund Transfer', 'voucher_prefix' => 'IFT', 'status' => 'active']);
    $receiptCategory = Category::create(['accounting_entity_id' => $entity->id, 'transaction_type_id' => $receiptType->id, 'code' => 'DON', 'name' => 'Donation', 'status' => 'active']);
    $paymentCategory = Category::create(['accounting_entity_id' => $entity->id, 'transaction_type_id' => $paymentType->id, 'code' => 'UTIL', 'name' => 'Utility', 'status' => 'active']);
    $supplier = Counterparty::create(['accounting_entity_id' => $entity->id, 'code' => 'SUP', 'party_type' => 'supplier', 'display_name' => 'Test Supplier', 'status' => 'active']);

    $receiptVersion = operationalPostingRule($entity, $receiptType, 'receipt', [
        ['account_id' => $cashSource->id, 'entry_side' => 'debit', 'amount_source' => 'split_amount', 'financial_account_source' => 'transaction_primary', 'fund_source' => 'split'],
        ['account_id' => $revenue->id, 'entry_side' => 'credit', 'amount_source' => 'split_amount', 'fund_source' => 'split', 'category_source' => 'transaction'],
    ]);
    $paymentVersion = operationalPostingRule($entity, $paymentType, 'payment', [
        ['account_id' => $expense->id, 'entry_side' => 'debit', 'amount_source' => 'split_amount', 'fund_source' => 'split', 'category_source' => 'transaction', 'counterparty_source' => 'transaction'],
        ['account_id' => $cashSource->id, 'entry_side' => 'credit', 'amount_source' => 'split_amount', 'financial_account_source' => 'transaction_primary', 'fund_source' => 'split'],
    ]);
    $transferVersion = operationalPostingRule($entity, $transferType, 'treasury-transfer', [
        ['account_id' => $cashDestination->id, 'entry_side' => 'debit', 'amount_source' => 'split_amount', 'financial_account_source' => 'transfer_destination', 'fund_source' => 'split'],
        ['account_id' => $cashSource->id, 'entry_side' => 'credit', 'amount_source' => 'split_amount', 'financial_account_source' => 'transfer_source', 'fund_source' => 'split'],
    ]);
    $interfundVersion = operationalPostingRule($entity, $interfundType, 'interfund-transfer', [
        ['account_id' => $transferIn->id, 'entry_side' => 'debit', 'amount_source' => 'transaction_gross_amount', 'fund_source' => 'interfund_destination'],
        ['account_id' => $transferOut->id, 'entry_side' => 'credit', 'amount_source' => 'transaction_gross_amount', 'fund_source' => 'interfund_source'],
    ]);

    foreach ([$receiptType, $paymentType, $transferType, $interfundType] as $type) {
        DocumentSequence::create(['accounting_entity_id' => $entity->id, 'transaction_type_id' => $type->id, 'code' => $type->code, 'name' => $type->name, 'prefix' => $type->voucher_prefix, 'scope_key' => "test-{$type->code}", 'status' => 'active']);
    }

    return compact('entity', 'period', 'cashSource', 'cashDestination', 'revenue', 'expense', 'transferIn', 'transferOut', 'sourceFinancialAccount', 'destinationFinancialAccount', 'fundType', 'restriction', 'fund', 'destinationFund', 'receiptType', 'paymentType', 'transferType', 'interfundType', 'receiptCategory', 'paymentCategory', 'supplier', 'receiptVersion', 'paymentVersion', 'transferVersion', 'interfundVersion', 'today');
}

/** @param array<int, array<string, mixed>> $lines */
function operationalPostingRule(AccountingEntity $entity, TransactionType $type, string $family, array $lines): PostingRuleVersion
{
    $rule = PostingRule::create(['accounting_entity_id' => $entity->id, 'transaction_type_id' => $type->id, 'code' => "{$type->code}-RULE", 'name' => "{$type->name} Rule", 'rule_family' => $family, 'status' => 'active']);
    $version = PostingRuleVersion::create(['accounting_entity_id' => $entity->id, 'posting_rule_id' => $rule->id, 'version_no' => 1, 'effective_from' => now()->subDay(), 'input_contract_ref' => 'operational-test', 'journal_template_ref' => 'operational-test', 'business_rule_refs' => 'BR-066', 'status' => 'effective']);
    foreach ($lines as $index => $line) {
        PostingRuleLine::create(['accounting_entity_id' => $entity->id, 'posting_rule_version_id' => $version->id, 'line_no' => $index + 1] + $line);
    }

    return $version;
}

function operationalAdvance(FinancialTransaction $transaction): FinancialTransaction
{
    $lifecycle = app(FinancialTransactionLifecycleService::class);
    $lifecycle->submit($transaction->id);
    $lifecycle->verify($transaction->id);

    return $lifecycle->approve($transaction->id);
}

function operationalReceipt(array $context, string|int $amount = '100.00', ?string $sourceReference = null, ?string $idempotencyKey = null): FinancialTransaction
{
    return app(FinancialTransactionLifecycleService::class)->createReceipt([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['receiptType']->id,
        'business_date' => $context['today'],
        'accounting_date' => $context['today'],
        'gross_amount' => $amount,
        'source_reference' => $sourceReference ?? 'RCV-'.Str::uuid(),
        'idempotency_key' => $idempotencyKey ?? 'source-'.Str::uuid(),
        'primary_financial_account_id' => $context['sourceFinancialAccount']->id,
        'category_id' => $context['receiptCategory']->id,
        'description' => 'Receipt test',
    ], [[
        'account_id' => $context['revenue']->id,
        'split_amount' => $amount,
        'fund_id' => $context['fund']->id,
    ]]);
}

function operationalPayment(array $context, string|int $amount = '25.00'): FinancialTransaction
{
    return app(FinancialTransactionLifecycleService::class)->createPayment([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['paymentType']->id,
        'business_date' => $context['today'],
        'accounting_date' => $context['today'],
        'gross_amount' => $amount,
        'source_reference' => 'PAY-'.Str::uuid(),
        'idempotency_key' => 'source-'.Str::uuid(),
        'primary_financial_account_id' => $context['sourceFinancialAccount']->id,
        'counterparty_id' => $context['supplier']->id,
        'category_id' => $context['paymentCategory']->id,
        'description' => 'Payment test',
    ], [[
        'account_id' => $context['expense']->id,
        'split_amount' => $amount,
        'fund_id' => $context['fund']->id,
    ]]);
}

function phaseFiveCompleteReconciliation(array $context, FinancialAccount $financialAccount, string $statementBalance): Reconciliation
{
    $reconciliations = app(ReconciliationService::class);
    $reconciliation = $reconciliations->createDraft([
        'accounting_entity_id' => $context['entity']->id,
        'financial_account_id' => $financialAccount->id,
        'accounting_period_id' => $context['period']->id,
        'as_of_date' => $context['today'],
        'statement_balance' => $statementBalance,
        'notes' => 'Phase 5 test reconciliation',
    ]);
    app(EvidenceService::class)->attachToReconciliation(
        $context['entity']->id,
        $reconciliation->id,
        "statement-{$financialAccount->code}.pdf",
        'application/pdf',
        100,
        hash('sha256', 'phase-5-statement-'.$financialAccount->id),
        'test://reconciliation/'.$financialAccount->id,
        'statement',
    );
    $reconciliations->startReview($reconciliation->id);
    $reconciliations->review($reconciliation->id);

    return $reconciliations->complete($reconciliation->id);
}

test('governs master uniqueness and rejects an invalid Financial Account relationship', function () {
    $context = operationalV2Context();
    expect(fn () => Account::create(['accounting_entity_id' => $context['entity']->id, 'account_group_id' => $context['revenue']->account_group_id, 'code' => $context['revenue']->code, 'name' => 'Duplicate', 'account_class' => 'revenue', 'normal_balance' => 'credit', 'status' => 'active']))->toThrow(QueryException::class);
    expect(fn () => Fund::create(['accounting_entity_id' => $context['entity']->id, 'fund_type_id' => $context['fundType']->id, 'fund_restriction_id' => $context['restriction']->id, 'code' => $context['fund']->code, 'name' => 'Duplicate Fund', 'purpose_statement' => 'Duplicate', 'status' => 'active']))->toThrow(QueryException::class);

    $invalidFinancialAccount = FinancialAccount::create(['accounting_entity_id' => $context['entity']->id, 'account_id' => $context['revenue']->id, 'code' => 'INVALID', 'name' => 'Invalid Relationship', 'account_type' => 'e_wallet', 'opening_date' => now()->subDay(), 'status' => 'draft']);
    expect(fn () => app(MasterDataGovernanceService::class)->activateFinancialAccount($invalidFinancialAccount->id, now()->toDateString()))->toThrow(FinancialDomainException::class, 'liquidity account');
});

test('receipt lifecycle posts once, preserves audit history, and rejects duplicate source identity', function () {
    $context = operationalV2Context();
    $receipt = operationalReceipt($context, '100.00', 'BANK-REF-1', 'receipt-source-key');
    app(FinancialTransactionLifecycleService::class)->updateDraft($receipt->id, ['description' => 'Updated draft receipt']);
    operationalAdvance($receipt);
    $result = app(FinancialTransactionLifecycleService::class)->post($receipt->id, 'receipt-post-key', hash('sha256', 'receipt-post-key'));

    expect(Journal::findOrFail($result->journalId)->journal_status)->toBe('posted')
        ->and(AuditEvent::where('target_id', $receipt->id)->whereIn('event_type', ['transaction_created', 'transaction_draft_updated', 'posting_requested'])->count())->toBe(3)
        ->and(AuditEvent::where('event_type', 'posting_committed')->count())->toBe(1);
    expect(fn () => operationalReceipt($context, '100.00', 'BANK-REF-1', 'receipt-second-key'))->toThrow(QueryException::class);
    expect(fn () => operationalReceipt($context, '100.00', 'BANK-REF-2', 'receipt-source-key'))->toThrow(QueryException::class);
    $duplicateVoucherTransaction = operationalReceipt($context, '100.00', 'BANK-REF-3', 'receipt-third-key');
    $issuedVoucher = Voucher::where('transaction_id', $receipt->id)->sole();
    $sequence = DocumentSequence::where('transaction_type_id', $context['receiptType']->id)->sole();
    expect(fn () => Voucher::create([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_id' => $duplicateVoucherTransaction->id,
        'document_sequence_id' => $sequence->id,
        'voucher_number' => $issuedVoucher->voucher_number,
        'status' => 'reserved',
    ]))->toThrow(QueryException::class);
});

test('receipt rejects inactive Fund and a non-posting Account before an official effect', function () {
    $context = operationalV2Context();
    $context['fund']->update(['status' => 'suspended']);
    $receipt = operationalReceipt($context);
    operationalAdvance($receipt);
    expect(fn () => app(FinancialTransactionLifecycleService::class)->post($receipt->id, 'inactive-fund', hash('sha256', 'inactive-fund')))->toThrow(\App\Domain\FinancialV2\FinancialPostingException::class, 'Fund is inactive');

    $context = operationalV2Context();
    $context['revenue']->update(['is_posting_account' => false]);
    $receipt = operationalReceipt($context);
    operationalAdvance($receipt);
    expect(fn () => app(FinancialTransactionLifecycleService::class)->post($receipt->id, 'inactive-account', hash('sha256', 'inactive-account')))->toThrow(\App\Domain\FinancialV2\FinancialPostingException::class, 'inactive or non-posting');
});

test('payment applies Fund restriction, fund liquidity, and period controls', function () {
    $context = operationalV2Context();
    $receipt = operationalReceipt($context, '100.00');
    operationalAdvance($receipt);
    app(FinancialTransactionLifecycleService::class)->post($receipt->id, 'payment-receipt', hash('sha256', 'payment-receipt'));
    $payment = operationalPayment($context, '25.00');
    operationalAdvance($payment);
    expect(app(FinancialTransactionLifecycleService::class)->post($payment->id, 'payment-valid', hash('sha256', 'payment-valid'))->journalId)->not->toBeEmpty();

    $restrictedType = FundType::create(['accounting_entity_id' => $context['entity']->id, 'code' => 'PAY-RES', 'name' => 'Restricted', 'classification' => 'restricted', 'status' => 'active']);
    $restrictedRestriction = FundRestriction::create(['accounting_entity_id' => $context['entity']->id, 'fund_type_id' => $restrictedType->id, 'code' => 'PAY-RES', 'name' => 'Restricted', 'severity' => 'high', 'policy_basis' => 'Approved policy', 'status' => 'active']);
    $restrictedFund = Fund::create(['accounting_entity_id' => $context['entity']->id, 'fund_type_id' => $restrictedType->id, 'fund_restriction_id' => $restrictedRestriction->id, 'code' => 'PAY-RES', 'name' => 'Restricted', 'purpose_statement' => 'Restricted purpose', 'status' => 'active']);
    FundPolicyVersion::create(['accounting_entity_id' => $context['entity']->id, 'fund_id' => $restrictedFund->id, 'version_no' => 1, 'effective_from' => now()->subDay(), 'policy_document_ref' => 'policy', 'allowed_matrix_ref' => 'matrix', 'exception_approval_level' => 'test', 'status' => 'effective']);
    $restrictedPayment = operationalPayment($context, '1.00');
    $restrictedPayment->splits()->update(['fund_id' => $restrictedFund->id]);
    operationalAdvance($restrictedPayment);
    expect(fn () => app(FinancialTransactionLifecycleService::class)->post($restrictedPayment->id, 'payment-restricted', hash('sha256', 'payment-restricted')))->toThrow(\App\Domain\FinancialV2\FinancialPostingException::class, 'fail-closed');

    $overspend = operationalPayment($context, '80.00');
    operationalAdvance($overspend);
    expect(fn () => app(FinancialTransactionLifecycleService::class)->post($overspend->id, 'payment-over', hash('sha256', 'payment-over')))->toThrow(\App\Domain\FinancialV2\FinancialPostingException::class, 'Fund liquidity balance policy');

    // Fixture-only state setup; production state changes are guarded by PeriodClosingService.
    PeriodClosingStateGuard::withinClosing(fn () => $context['period']->update(['status' => 'hard_closed']));
    expect(fn () => operationalPayment($context, '1.00'))->toThrow(FinancialDomainException::class, 'not eligible for financial transaction work');
});

test('treasury transfer is balanced, preserves Fund identity, and creates no revenue or expense', function () {
    $context = operationalV2Context();
    $receipt = operationalReceipt($context, '100.00');
    operationalAdvance($receipt);
    app(FinancialTransactionLifecycleService::class)->post($receipt->id, 'transfer-receipt', hash('sha256', 'transfer-receipt'));
    $transfer = app(FinancialTransactionLifecycleService::class)->createTreasuryTransfer([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['transferType']->id,
        'business_date' => $context['today'],
        'accounting_date' => $context['today'],
        'gross_amount' => '40.00',
        'source_reference' => 'TRF-'.Str::uuid(),
        'idempotency_key' => 'source-'.Str::uuid(),
        'source_financial_account_id' => $context['sourceFinancialAccount']->id,
        'destination_financial_account_id' => $context['destinationFinancialAccount']->id,
        'description' => 'Move cash location',
    ], [[
        'account_id' => $context['cashSource']->id,
        'split_amount' => '40.00',
        'fund_id' => $context['fund']->id,
    ]]);
    operationalAdvance($transfer);
    $result = app(FinancialTransactionLifecycleService::class)->post($transfer->id, 'transfer-post', hash('sha256', 'transfer-post'));
    $lines = JournalLine::where('journal_id', $result->journalId)->get();

    expect($lines)->toHaveCount(2)
        ->and($lines->pluck('fund_id')->unique()->all())->toBe([$context['fund']->id])
        ->and($lines->pluck('account_id')->sort()->values()->all())->toBe(collect([$context['cashDestination']->id, $context['cashSource']->id])->sort()->values()->all())
        ->and(JournalLine::where('journal_id', $result->journalId)->whereIn('account_id', [$context['revenue']->id, $context['expense']->id])->count())->toBe(0);
    expect(app(FinancialTransactionLifecycleService::class)->post($transfer->id, 'transfer-post', hash('sha256', 'transfer-post'))->journalId)->toBe($result->journalId);
});

test('interfund transfer is separate, policy-checked, and never moves a Financial Account', function () {
    $context = operationalV2Context();
    $receipt = operationalReceipt($context, '100.00');
    operationalAdvance($receipt);
    app(FinancialTransactionLifecycleService::class)->post($receipt->id, 'ift-liquidity-source', hash('sha256', 'ift-liquidity-source'));
    $transfer = app(FinancialTransactionLifecycleService::class)->createInterfundTransfer([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['interfundType']->id,
        'business_date' => $context['today'],
        'accounting_date' => $context['today'],
        'gross_amount' => '15.00',
        'source_reference' => 'IFT-'.Str::uuid(),
        'idempotency_key' => 'source-'.Str::uuid(),
        'primary_financial_account_id' => $context['sourceFinancialAccount']->id,
        'source_fund_id' => $context['fund']->id,
        'destination_fund_id' => $context['destinationFund']->id,
        'policy_basis_ref' => 'GOV-IFT-TEST',
        'reason' => 'Approved reclassification test',
        'description' => 'Interfund transfer',
    ]);
    operationalAdvance($transfer);
    $result = app(FinancialTransactionLifecycleService::class)->post($transfer->id, 'ift-post', hash('sha256', 'ift-post'));
    $lines = JournalLine::where('journal_id', $result->journalId)->get();
    expect($lines)->toHaveCount(2)
        ->and($lines->whereNotNull('financial_account_id'))->toHaveCount(0)
        ->and($lines->pluck('fund_id')->sort()->values()->all())->toBe(collect([$context['fund']->id, $context['destinationFund']->id])->sort()->values()->all());

    $restrictedType = FundType::create(['accounting_entity_id' => $context['entity']->id, 'code' => 'RES', 'name' => 'Restricted', 'classification' => 'restricted', 'status' => 'active']);
    $restrictedRestriction = FundRestriction::create(['accounting_entity_id' => $context['entity']->id, 'fund_type_id' => $restrictedType->id, 'code' => 'RES-R', 'name' => 'Restricted', 'severity' => 'high', 'policy_basis' => 'Approved policy', 'status' => 'active']);
    $restrictedFund = Fund::create(['accounting_entity_id' => $context['entity']->id, 'fund_type_id' => $restrictedType->id, 'fund_restriction_id' => $restrictedRestriction->id, 'code' => 'RES-F', 'name' => 'Restricted Fund', 'purpose_statement' => 'Restricted', 'status' => 'active']);
    FundPolicyVersion::create(['accounting_entity_id' => $context['entity']->id, 'fund_id' => $restrictedFund->id, 'version_no' => 1, 'effective_from' => now()->subDay(), 'policy_document_ref' => 'policy', 'allowed_matrix_ref' => 'matrix', 'exception_approval_level' => 'test', 'status' => 'effective']);
    $blocked = app(FinancialTransactionLifecycleService::class)->createInterfundTransfer([
        'accounting_entity_id' => $context['entity']->id, 'transaction_type_id' => $context['interfundType']->id, 'business_date' => $context['today'], 'accounting_date' => $context['today'], 'gross_amount' => '1.00', 'source_reference' => 'IFT-'.Str::uuid(), 'idempotency_key' => 'source-'.Str::uuid(), 'primary_financial_account_id' => $context['sourceFinancialAccount']->id, 'source_fund_id' => $restrictedFund->id, 'destination_fund_id' => $context['destinationFund']->id, 'policy_basis_ref' => 'GOV-IFT-RESTRICTED', 'reason' => 'Must fail closed', 'description' => 'Blocked transfer',
    ]);
    operationalAdvance($blocked);
    expect(fn () => app(FinancialTransactionLifecycleService::class)->post($blocked->id, 'ift-blocked', hash('sha256', 'ift-blocked')))->toThrow(\App\Domain\FinancialV2\FinancialPostingException::class, 'fail-closed');
});

test('budget allocation has no Journal and realization derives actual only from the linked posted payment', function () {
    $context = operationalV2Context();
    $budgetService = app(BudgetAllocationService::class);
    $allocation = $budgetService->create(['accounting_entity_id' => $context['entity']->id, 'accounting_period_id' => $context['period']->id, 'fund_id' => $context['fund']->id, 'account_id' => $context['expense']->id, 'allocation_reference' => 'BGT-'.Str::uuid(), 'idempotency_key' => 'budget-'.Str::uuid(), 'allocated_amount' => '40.00', 'effective_from' => $context['today'], 'reason' => 'Approved utility plan']);
    $budgetService->submit($allocation->id);
    $version = $budgetService->approveVersion($allocation->id, $allocation->versions->sole()->id);
    expect(Journal::where('accounting_entity_id', $context['entity']->id)->count())->toBe(0);

    $receipt = operationalReceipt($context, '100.00');
    operationalAdvance($receipt);
    app(FinancialTransactionLifecycleService::class)->post($receipt->id, 'realization-receipt', hash('sha256', 'realization-receipt'));
    $realization = app(FinancialTransactionLifecycleService::class)->createRealization([
        'accounting_entity_id' => $context['entity']->id, 'transaction_type_id' => $context['paymentType']->id, 'business_date' => $context['today'], 'accounting_date' => $context['today'], 'gross_amount' => '20.00', 'source_reference' => 'PAY-REAL-'.Str::uuid(), 'idempotency_key' => 'realization-'.Str::uuid(), 'primary_financial_account_id' => $context['sourceFinancialAccount']->id, 'counterparty_id' => $context['supplier']->id, 'category_id' => $context['paymentCategory']->id, 'description' => 'Realized utility expense',
    ], [[
        'account_id' => $context['expense']->id, 'split_amount' => '20.00', 'fund_id' => $context['fund']->id,
    ]], $version->id);
    operationalAdvance($realization);
    app(FinancialTransactionLifecycleService::class)->post($realization->id, 'realization-post', hash('sha256', 'realization-post'));
    expect(FundRealization::where('transaction_id', $realization->id)->sole()->status)->toBe('recorded')
        ->and($budgetService->availability($version->id))->toBe(['allocated' => '40.00', 'actual' => '20.00', 'available' => '20.00']);

    $excess = app(FinancialTransactionLifecycleService::class)->createRealization([
        'accounting_entity_id' => $context['entity']->id, 'transaction_type_id' => $context['paymentType']->id, 'business_date' => $context['today'], 'accounting_date' => $context['today'], 'gross_amount' => '25.00', 'source_reference' => 'PAY-REAL-'.Str::uuid(), 'idempotency_key' => 'realization-'.Str::uuid(), 'primary_financial_account_id' => $context['sourceFinancialAccount']->id, 'counterparty_id' => $context['supplier']->id, 'category_id' => $context['paymentCategory']->id, 'description' => 'Excess realization',
    ], [[
        'account_id' => $context['expense']->id, 'split_amount' => '25.00', 'fund_id' => $context['fund']->id,
    ]], $version->id);
    operationalAdvance($excess);
    expect(fn () => app(FinancialTransactionLifecycleService::class)->post($excess->id, 'realization-excess', hash('sha256', 'realization-excess')))->toThrow(\App\Domain\FinancialV2\FinancialPostingException::class, 'available Budget Allocation');
});

test('attachments accept only policy formats and posted source facts remain immutable', function () {
    $context = operationalV2Context();
    $receipt = operationalReceipt($context);
    $evidence = app(EvidenceService::class);
    $image = $evidence->attachToTransaction($context['entity']->id, $receipt->id, 'receipt.jpg', 'image/jpeg', 12, hash('sha256', 'image-'.Str::uuid()), 'test://image/'.Str::uuid(), 'receipt');
    $pdf = $evidence->attachToTransaction($context['entity']->id, $receipt->id, 'receipt.pdf', 'application/pdf', 13, hash('sha256', 'pdf-'.Str::uuid()), 'test://pdf/'.Str::uuid(), 'receipt');
    expect($image->status)->toBe('active')->and($pdf->status)->toBe('active');
    expect(fn () => $evidence->attachToTransaction($context['entity']->id, $receipt->id, 'receipt.exe', 'application/x-msdownload', 1, hash('sha256', 'bad-'.Str::uuid()), 'test://bad/'.Str::uuid(), 'receipt'))->toThrow(FinancialDomainException::class, 'supported image/PDF');

    operationalAdvance($receipt);
    $result = app(FinancialTransactionLifecycleService::class)->post($receipt->id, 'immutable-receipt', hash('sha256', 'immutable-receipt'));
    expect(fn () => FinancialTransaction::findOrFail($receipt->id)->update(['gross_amount' => '1.00']))->toThrow(\DomainException::class);
    expect(fn () => Journal::findOrFail($result->journalId)->update(['description' => 'changed']))->toThrow(\DomainException::class);
});

test('governed restricted Fund activation requires an effective policy matrix', function () {
    $context = operationalV2Context();
    $restrictedType = FundType::create(['accounting_entity_id' => $context['entity']->id, 'code' => 'GOV-RES', 'name' => 'Governed Restricted', 'classification' => 'restricted', 'status' => 'active']);
    $restricted = FundRestriction::create(['accounting_entity_id' => $context['entity']->id, 'fund_type_id' => $restrictedType->id, 'code' => 'GOV-RES', 'name' => 'Governed Restricted', 'severity' => 'critical', 'policy_basis' => 'Approved policy', 'status' => 'active']);
    $fund = Fund::create(['accounting_entity_id' => $context['entity']->id, 'fund_type_id' => $restrictedType->id, 'fund_restriction_id' => $restricted->id, 'code' => 'GOV-FUND', 'name' => 'Governed Fund', 'purpose_statement' => 'Restricted purpose', 'status' => 'draft']);
    FundPolicyVersion::create(['accounting_entity_id' => $context['entity']->id, 'fund_id' => $fund->id, 'version_no' => 1, 'effective_from' => now()->subDay(), 'policy_document_ref' => 'policy', 'exception_approval_level' => 'test', 'status' => 'effective']);

    expect(fn () => app(MasterDataGovernanceService::class)->activateFund($fund->id, $context['today']))->toThrow(FinancialDomainException::class, 'allowed matrix');
});

test('governed master onboarding activates only approved-ready masters and creates no financial facts', function () {
    $context = operationalV2Context();
    $draftFund = Fund::create([
        'accounting_entity_id' => $context['entity']->id,
        'fund_type_id' => $context['fundType']->id,
        'fund_restriction_id' => $context['restriction']->id,
        'code' => 'ONBOARD',
        'name' => 'Onboarding Fund',
        'purpose_statement' => 'Master onboarding test only',
        'status' => 'draft',
    ]);

    $activated = app(MasterDataGovernanceService::class)->activateFund($draftFund->id, $context['today']);
    expect($activated->status)->toBe('active');
    expect(fn () => DocumentSequence::create([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['receiptType']->id,
        'code' => 'RCV-ONBOARD',
        'name' => 'Duplicate receipt scope',
        'prefix' => 'RCV',
        'scope_key' => 'test-RCV',
        'status' => 'active',
    ]))->toThrow(QueryException::class);

    expect(Journal::where('accounting_entity_id', $context['entity']->id)->count())->toBe(0)
        ->and(JournalLine::where('accounting_entity_id', $context['entity']->id)->count())->toBe(0)
        ->and(\App\Models\FinancialV2\LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count())->toBe(0)
        ->and(FinancialTransaction::where('accounting_entity_id', $context['entity']->id)->count())->toBe(0)
        ->and(\App\Models\FinancialV2\OpeningBalanceBatch::where('accounting_entity_id', $context['entity']->id)->count())->toBe(0)
        ->and(AuditEvent::where('event_type', 'fund_activated')->where('target_id', $draftFund->id)->count())->toBe(1);
});

test('governed Fund policy versions supersede a dated predecessor and reject a same-date overlap without financial facts', function () {
    $context = operationalV2Context();
    $first = FundPolicyVersion::create([
        'accounting_entity_id' => $context['entity']->id,
        'fund_id' => $context['fund']->id,
        'version_no' => 1,
        'effective_from' => now()->subDays(2),
        'policy_document_ref' => 'approved-policy-test',
        'exception_approval_level' => 'test',
        'status' => 'draft',
    ]);
    app(MasterDataGovernanceService::class)->makeFundPolicyVersionEffective($first->id);
    $overlap = FundPolicyVersion::create([
        'accounting_entity_id' => $context['entity']->id,
        'fund_id' => $context['fund']->id,
        'version_no' => 2,
        'effective_from' => now()->subDay()->toDateString(),
        'policy_document_ref' => 'approved-policy-test-v2',
        'exception_approval_level' => 'test',
        'status' => 'draft',
    ]);

    app(MasterDataGovernanceService::class)->makeFundPolicyVersionEffective($overlap->id);
    expect($first->fresh()->status)->toBe('superseded')
        ->and($first->fresh()->effective_to->toDateString())->toBe(now()->subDays(2)->toDateString())
        ->and($overlap->fresh()->status)->toBe('effective');

    $sameDate = FundPolicyVersion::create([
        'accounting_entity_id' => $context['entity']->id,
        'fund_id' => $context['fund']->id,
        'version_no' => 3,
        'effective_from' => now()->subDay()->toDateString(),
        'policy_document_ref' => 'approved-policy-test-v3',
        'exception_approval_level' => 'test',
        'status' => 'draft',
    ]);
    expect(fn () => app(MasterDataGovernanceService::class)->makeFundPolicyVersionEffective($sameDate->id))
        ->toThrow(FinancialDomainException::class, 'date ranges must not overlap');
    expect(Journal::where('accounting_entity_id', $context['entity']->id)->count())->toBe(0)
        ->and(JournalLine::where('accounting_entity_id', $context['entity']->id)->count())->toBe(0)
        ->and(\App\Models\FinancialV2\LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count())->toBe(0);
});

test('controlled closing validates pre-close checks, preserves immutable facts and reports, then hard closes only after reconciliations', function () {
    $context = operationalV2Context();
    $receipt = operationalReceipt($context, '100.00');
    operationalAdvance($receipt);
    app(FinancialTransactionLifecycleService::class)->post($receipt->id, 'phase-5-close-receipt', hash('sha256', 'phase-5-close-receipt'));

    $reports = app(FinancialReportService::class);
    $beforeReport = $reports->report('trial-balance', $context['entity']->id, $context['period']->start_date->toDateString(), $context['period']->end_date->toDateString());
    $beforeFacts = [
        'journals' => Journal::where('accounting_entity_id', $context['entity']->id)->count(),
        'journal_lines' => JournalLine::where('accounting_entity_id', $context['entity']->id)->count(),
        'ledger' => LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count(),
    ];

    $closing = app(PeriodClosingService::class);
    $softClose = $closing->close($context['period']->id, 'soft_close', null, 'PHASE-5-SOFT');
    expect($softClose['closed'])->toBeTrue()
        ->and($softClose['run']->status)->toBe('completed')
        ->and($context['period']->fresh()->status)->toBe('soft_closed')
        ->and(collect($softClose['checks'])->every(fn (array $check) => $check['passed']))->toBeTrue();
    expect(fn () => $context['period']->fresh()->update(['status' => 'reopened']))->toThrow(\DomainException::class, 'closing service');
    expect(fn () => operationalReceipt($context, '1.00'))->toThrow(FinancialDomainException::class, 'not eligible for financial transaction work');

    expect([
        'journals' => Journal::where('accounting_entity_id', $context['entity']->id)->count(),
        'journal_lines' => JournalLine::where('accounting_entity_id', $context['entity']->id)->count(),
        'ledger' => LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count(),
    ])->toBe($beforeFacts)
        ->and($reports->report('trial-balance', $context['entity']->id, $context['period']->start_date->toDateString(), $context['period']->end_date->toDateString()))->toBe($beforeReport);

    phaseFiveCompleteReconciliation($context, $context['sourceFinancialAccount'], '100.00');
    phaseFiveCompleteReconciliation($context, $context['destinationFinancialAccount'], '0.00');
    $hardClose = $closing->close($context['period']->id, 'hard_close', null, 'PHASE-5-HARD');

    expect($hardClose['closed'])->toBeTrue()
        ->and($context['period']->fresh()->status)->toBe('hard_closed')
        ->and(ClosingRun::where('accounting_period_id', $context['period']->id)->where('status', 'completed')->count())->toBe(2)
        ->and(AuditEvent::where('target_id', $context['period']->id)->where('event_type', 'period_closed')->count())->toBe(2)
        ->and([
            'journals' => Journal::where('accounting_entity_id', $context['entity']->id)->count(),
            'journal_lines' => JournalLine::where('accounting_entity_id', $context['entity']->id)->count(),
            'ledger' => LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count(),
        ])->toBe($beforeFacts)
        ->and($reports->report('trial-balance', $context['entity']->id, $context['period']->start_date->toDateString(), $context['period']->end_date->toDateString()))->toBe($beforeReport);
});

test('closing blocks unresolved operational work and records a blocked run without changing period state', function () {
    $context = operationalV2Context();
    operationalReceipt($context, '25.00');

    $result = app(PeriodClosingService::class)->close($context['period']->id, 'soft_close', null, 'PHASE-5-BLOCKED');

    expect($result['closed'])->toBeFalse()
        ->and($result['run']->status)->toBe('blocked')
        ->and($context['period']->fresh()->status)->toBe('open')
        ->and(collect($result['checks'])->firstWhere('code', 'transaction_state')['passed'])->toBeFalse()
        ->and(AuditEvent::where('target_id', $result['run']->id)->where('event_type', 'period_closing_blocked')->count())->toBe(1);
});

test('reconciliation derives the book balance from posted V2 Ledger only and finalisation leaves financial facts unchanged', function () {
    $context = operationalV2Context();
    $receipt = operationalReceipt($context, '100.00');
    operationalAdvance($receipt);
    app(FinancialTransactionLifecycleService::class)->post($receipt->id, 'phase-5-recon-receipt', hash('sha256', 'phase-5-recon-receipt'));
    $factsBefore = [
        'journals' => Journal::where('accounting_entity_id', $context['entity']->id)->count(),
        'journal_lines' => JournalLine::where('accounting_entity_id', $context['entity']->id)->count(),
        'ledger' => LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count(),
    ];

    DB::enableQueryLog();
    DB::flushQueryLog();
    $reconciliation = app(ReconciliationService::class)->createDraft([
        'accounting_entity_id' => $context['entity']->id,
        'financial_account_id' => $context['sourceFinancialAccount']->id,
        'accounting_period_id' => $context['period']->id,
        'as_of_date' => $context['today'],
        'statement_balance' => '100.00',
    ]);
    $queries = collect(DB::getQueryLog())->pluck('query')->implode("\n");
    DB::disableQueryLog();
    expect($reconciliation->ledger_balance)->toBe('100.00')
        ->and($reconciliation->difference)->toBe('0.00')
        ->and($queries)->toContain('financial_v2_ledger_entries')
        ->and($queries)->not->toContain('`jurnal`');

    $evidence = app(EvidenceService::class)->attachToReconciliation($context['entity']->id, $reconciliation->id, 'bank-statement.pdf', 'application/pdf', 100, hash('sha256', 'phase-5-reconciliation-evidence'), 'test://reconciliation/statement', 'statement');
    $service = app(ReconciliationService::class);
    $service->startReview($reconciliation->id);
    $service->review($reconciliation->id);
    $completed = $service->complete($reconciliation->id);

    expect($completed->status)->toBe('completed')
        ->and($completed->reconciled_at)->not->toBeNull()
        ->and($evidence->target_type)->toBe('reconciliation')
        ->and(AuditEvent::where('target_id', $reconciliation->id)->where('event_type', 'reconciliation_completed')->count())->toBe(1)
        ->and([
            'journals' => Journal::where('accounting_entity_id', $context['entity']->id)->count(),
            'journal_lines' => JournalLine::where('accounting_entity_id', $context['entity']->id)->count(),
            'ledger' => LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count(),
        ])->toBe($factsBefore);
    expect(fn () => $completed->update(['notes' => 'mutated']))->toThrow(\DomainException::class, 'reconciliation service');
});

test('non-zero reconciliation cannot complete and must be retained as an explicit exception', function () {
    $context = operationalV2Context();
    $service = app(ReconciliationService::class);
    $reconciliation = $service->createDraft([
        'accounting_entity_id' => $context['entity']->id,
        'financial_account_id' => $context['sourceFinancialAccount']->id,
        'accounting_period_id' => $context['period']->id,
        'as_of_date' => $context['today'],
        'statement_balance' => '7.25',
    ]);
    $service->startReview($reconciliation->id);
    $reviewed = $service->review($reconciliation->id);

    expect($reviewed->difference)->toBe('7.25')
        ->and(fn () => $service->complete($reconciliation->id))->toThrow(FinancialDomainException::class, 'non-zero difference');
    $exception = $service->markException($reconciliation->id, 'Statement arrived with unresolved bank timing item.');

    expect($exception->status)->toBe('exception')
        ->and($exception->difference)->toBe('7.25')
        ->and($exception->notes)->toContain('Statement arrived with unresolved bank timing item.')
        ->and(AuditEvent::where('target_id', $reconciliation->id)->where('event_type', 'reconciliation_exception_recorded')->count())->toBe(1);
});
