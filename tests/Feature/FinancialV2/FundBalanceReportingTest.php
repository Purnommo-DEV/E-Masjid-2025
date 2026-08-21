<?php

use App\Domain\FinancialV2\DecimalAmount;
use App\Domain\FinancialV2\FinancialDomainException;
use App\Domain\FinancialV2\FinancialPostingException;
use App\Domain\FinancialV2\FinancialTransactionLifecycleService;
use App\Domain\FinancialV2\PostingEngine;
use App\Domain\FinancialV2\Reporting\FinancialReportService;
use App\Models\FinancialV2\ApprovalDecision;
use App\Models\FinancialV2\Attachment;
use App\Models\FinancialV2\AttachmentLink;
use App\Models\FinancialV2\DocumentSequence;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\JournalLine;
use App\Models\FinancialV2\LedgerEntry;
use App\Models\FinancialV2\PostingRule;
use App\Models\FinancialV2\PostingRuleVersion;
use App\Models\FinancialV2\ReasonCode;
use App\Models\FinancialV2\TransactionSplit;
use App\Models\FinancialV2\TransactionType;
use Illuminate\Support\Str;
use Tests\Support\UatFinancialFixture;

test('Fund balance separates revenue, expense, liquidity, and a treasury transfer', function () {
    $context = UatFinancialFixture::context();

    $receipt = UatFinancialFixture::receipt($context, '20000000.00');
    UatFinancialFixture::advance($receipt);
    UatFinancialFixture::post($receipt, 'phase103-zakat-receipt');

    $payment = UatFinancialFixture::payment($context, '5000000.00');
    UatFinancialFixture::advance($payment);
    UatFinancialFixture::post($payment, 'phase103-zakat-payment');

    $reports = app(FinancialReportService::class);
    $beforeTransfer = $reports->report('fund-balance', $context['entity']->id, $context['today'], $context['today'], ['fund_id' => $context['fund']->id]);
    $beforeRow = collect($beforeTransfer['data']['rows'])->sole('fund_id', $context['fund']->id);

    $transfer = app(FinancialTransactionLifecycleService::class)->createTreasuryTransfer([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['treasuryType']->id,
        'business_date' => $context['today'],
        'accounting_date' => $context['today'],
        'gross_amount' => '5000000.00',
        'source_reference' => 'PHASE103-TRF-'.Str::uuid(),
        'idempotency_key' => 'phase103-trf-'.Str::uuid(),
        'source_financial_account_id' => $context['accountA']->id,
        'destination_financial_account_id' => $context['accountB']->id,
        'description' => 'Synthetic bank to cash treasury transfer',
    ], [[
        'account_id' => $context['cashA']->id,
        'split_amount' => '5000000.00',
        'fund_id' => $context['fund']->id,
    ]]);
    UatFinancialFixture::advance($transfer);
    UatFinancialFixture::post($transfer, 'phase103-treasury-transfer');

    $factsBeforeRead = [Journal::count(), JournalLine::count(), LedgerEntry::count(), FinancialTransaction::count()];
    $afterTransfer = $reports->report('fund-balance', $context['entity']->id, $context['today'], $context['today'], ['fund_id' => $context['fund']->id]);
    $afterRow = collect($afterTransfer['data']['rows'])->sole('fund_id', $context['fund']->id);
    $composition = collect($afterTransfer['data']['account_composition']);

    expect($beforeRow['fund_balance'])->toBe('15000000.00')
        ->and($beforeRow['available_liquidity'])->toBe('15000000.00')
        ->and($afterRow['fund_balance'])->toBe('15000000.00')
        ->and($afterRow['available_liquidity'])->toBe('15000000.00')
        ->and($afterRow['receipts'])->toBe('20000000.00')
        ->and($afterRow['expenses'])->toBe('5000000.00')
        ->and($afterRow['transfer_in'])->toBe('0.00')
        ->and($afterRow['transfer_out'])->toBe('0.00')
        ->and($afterRow['adjustments'])->toBe('0.00')
        ->and($afterRow['other_policy_components'])->toBe('0.00')
        ->and($afterRow['closing_net_position'])->toBe('15000000.00')
        ->and(DecimalAmount::sum($composition->pluck('liquidity_balance')))->toBe($afterRow['available_liquidity'])
        ->and($composition->sole('financial_account_id', $context['accountA']->id)['liquidity_balance'])->toBe('10000000.00')
        ->and($composition->sole('financial_account_id', $context['accountB']->id)['liquidity_balance'])->toBe('5000000.00')
        ->and([Journal::count(), JournalLine::count(), LedgerEntry::count(), FinancialTransaction::count()])->toBe($factsBeforeRead);
});

test('Inter-Fund Transfer moves Fund balance without changing total Fund balance', function () {
    $context = UatFinancialFixture::context();

    $receipt = UatFinancialFixture::receipt($context, '100.00');
    UatFinancialFixture::advance($receipt);
    UatFinancialFixture::post($receipt, 'phase103-ift-receipt');

    $interfund = app(FinancialTransactionLifecycleService::class)->createInterfundTransfer([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['interfundType']->id,
        'business_date' => $context['today'],
        'accounting_date' => $context['today'],
        'gross_amount' => '20.00',
        'source_reference' => 'PHASE103-IFT-'.Str::uuid(),
        'idempotency_key' => 'phase103-ift-'.Str::uuid(),
        'primary_financial_account_id' => $context['accountA']->id,
        'source_fund_id' => $context['fund']->id,
        'destination_fund_id' => $context['destinationFund']->id,
        'policy_basis_ref' => 'PHASE103-IFT-GOV',
        'reason' => 'Synthetic governed Fund transfer',
        'description' => 'Synthetic inter-Fund transfer',
    ]);
    UatFinancialFixture::advance($interfund);
    UatFinancialFixture::post($interfund, 'phase103-interfund-transfer');

    $reports = app(FinancialReportService::class);
    $fundReport = $reports->report('fund-balance', $context['entity']->id, $context['today'], $context['today']);
    $rows = collect($fundReport['data']['rows'])->keyBy('fund_id');
    $trialBalance = $reports->report('trial-balance', $context['entity']->id, $context['today'], $context['today']);

    expect($rows->get($context['fund']->id)['fund_balance'])->toBe('80.00')
        ->and($rows->get($context['fund']->id)['transfer_out'])->toBe('20.00')
        ->and($rows->get($context['fund']->id)['transfer_in'])->toBe('0.00')
        ->and($rows->get($context['destinationFund']->id)['fund_balance'])->toBe('20.00')
        ->and($rows->get($context['destinationFund']->id)['transfer_in'])->toBe('20.00')
        ->and($rows->get($context['destinationFund']->id)['transfer_out'])->toBe('0.00')
        ->and(DecimalAmount::sum($rows->pluck('fund_balance')))->toBe('100.00')
        ->and($trialBalance['data']['is_balanced'])->toBeTrue();
});

test('tagged Inter-Fund Transfer reattributes Fund liquidity without moving the Financial Account', function () {
    $context = UatFinancialFixture::context();

    $receipt = UatFinancialFixture::receipt($context, '100.00');
    UatFinancialFixture::advance($receipt);
    UatFinancialFixture::post($receipt, 'phase125-attribution-receipt');

    $interfund = app(FinancialTransactionLifecycleService::class)->createInterfundTransfer([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['interfundType']->id,
        'business_date' => $context['today'],
        'accounting_date' => $context['today'],
        'gross_amount' => '20.00',
        'source_reference' => 'PHASE125-IFT-'.Str::uuid(),
        'idempotency_key' => 'phase125-ift-'.Str::uuid(),
        'primary_financial_account_id' => $context['accountA']->id,
        'source_fund_id' => $context['fund']->id,
        'destination_fund_id' => $context['destinationFund']->id,
        'policy_basis_ref' => 'PHASE-12.5-ATTRIBUTION-TEST',
        'reason' => 'Reattribute existing liquidity without moving custody.',
        'description' => 'Synthetic Fund attribution correction',
    ]);
    UatFinancialFixture::advance($interfund);
    $posting = UatFinancialFixture::post($interfund, 'phase125-attribution-ift');

    $reports = app(FinancialReportService::class);
    $accountReport = $reports->report('account-balance', $context['entity']->id, $context['today'], $context['today']);
    $fundReport = $reports->report('fund-balance', $context['entity']->id, $context['today'], $context['today']);
    $accountRows = collect($accountReport['data']['rows'])->keyBy('financial_account_id');
    $composition = collect($accountReport['data']['fund_composition'])
        ->where('financial_account_id', $context['accountA']->id)
        ->keyBy('fund_id');
    $fundRows = collect($fundReport['data']['rows'])->keyBy('fund_id');

    expect($accountRows->get($context['accountA']->id)['closing_balance'])->toBe('100.00')
        ->and($composition->get($context['fund']->id)['balance'])->toBe('80.00')
        ->and($composition->get($context['destinationFund']->id)['balance'])->toBe('20.00')
        ->and(DecimalAmount::sum($composition->pluck('balance')))->toBe('100.00')
        ->and($fundRows->get($context['fund']->id)['available_liquidity'])->toBe('80.00')
        ->and($fundRows->get($context['destinationFund']->id)['available_liquidity'])->toBe('20.00')
        ->and(JournalLine::query()->where('journal_id', $posting->journalId)->whereNotNull('financial_account_id')->count())->toBe(0);

    $destinationPayment = UatFinancialFixture::payment($context, '15.00', $context['destinationFund']->id);
    UatFinancialFixture::advance($destinationPayment);
    UatFinancialFixture::post($destinationPayment, 'phase125-attributed-payment');

    $sourceOverspend = UatFinancialFixture::payment($context, '81.00', $context['fund']->id);
    UatFinancialFixture::advance($sourceOverspend);

    expect(fn () => UatFinancialFixture::post($sourceOverspend, 'phase125-source-overspend'))
        ->toThrow(FinancialPostingException::class, 'Fund liquidity balance policy');

    $reversal = phase125TaggedIftReversal($context, $interfund);
    expect(fn () => app(PostingEngine::class)->post($reversal->id, 'phase125-spent-attribution-reversal', hash('sha256', 'phase125-spent-attribution-reversal')))
        ->toThrow(FinancialPostingException::class, 'Interfund attribution or its reversal exceeds');
});

test('reversal of an unspent tagged Inter-Fund Transfer restores attribution without moving custody', function () {
    $context = UatFinancialFixture::context();

    $receipt = UatFinancialFixture::receipt($context, '100.00');
    UatFinancialFixture::advance($receipt);
    UatFinancialFixture::post($receipt, 'phase125-reversal-receipt');

    $interfund = app(FinancialTransactionLifecycleService::class)->createInterfundTransfer([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['interfundType']->id,
        'business_date' => $context['today'],
        'accounting_date' => $context['today'],
        'gross_amount' => '20.00',
        'source_reference' => 'PHASE125-REVERSIBLE-IFT-'.Str::uuid(),
        'idempotency_key' => 'phase125-reversible-ift-'.Str::uuid(),
        'primary_financial_account_id' => $context['accountA']->id,
        'source_fund_id' => $context['fund']->id,
        'destination_fund_id' => $context['destinationFund']->id,
        'policy_basis_ref' => 'PHASE-12.5-REVERSAL-TEST',
        'reason' => 'Reattribute existing liquidity for reversal coverage.',
        'description' => 'Synthetic reversible Fund attribution',
    ]);
    UatFinancialFixture::advance($interfund);
    UatFinancialFixture::post($interfund, 'phase125-reversible-attribution-ift');

    $reversal = phase125TaggedIftReversal($context, $interfund);
    $reversalPosting = app(PostingEngine::class)->post($reversal->id, 'phase125-unspent-attribution-reversal', hash('sha256', 'phase125-unspent-attribution-reversal'));

    $reports = app(FinancialReportService::class);
    $accountReport = $reports->report('account-balance', $context['entity']->id, $context['today'], $context['today']);
    $fundReport = $reports->report('fund-balance', $context['entity']->id, $context['today'], $context['today']);
    $composition = collect($accountReport['data']['fund_composition'])
        ->where('financial_account_id', $context['accountA']->id)
        ->keyBy('fund_id');
    $fundRows = collect($fundReport['data']['rows'])->keyBy('fund_id');

    expect(collect($accountReport['data']['rows'])->sole('financial_account_id', $context['accountA']->id)['closing_balance'])->toBe('100.00')
        ->and($composition->get($context['fund']->id)['balance'])->toBe('100.00')
        ->and($composition->has($context['destinationFund']->id))->toBeFalse()
        ->and($fundRows->get($context['fund']->id)['fund_balance'])->toBe('100.00')
        ->and($fundRows->get($context['destinationFund']->id)['fund_balance'])->toBe('0.00')
        ->and(JournalLine::query()->where('journal_id', $reversalPosting->journalId)->whereNotNull('financial_account_id')->count())->toBe(0);
});

test('Inter-Fund Transfer requires account attribution and rejects a reduction before later posted activity', function () {
    $context = UatFinancialFixture::context();
    $lifecycle = app(FinancialTransactionLifecycleService::class);

    expect(fn () => $lifecycle->createInterfundTransfer([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['interfundType']->id,
        'business_date' => $context['today'],
        'accounting_date' => $context['today'],
        'gross_amount' => '1.00',
        'source_reference' => 'PHASE125-AMBIGUOUS-IFT-'.Str::uuid(),
        'idempotency_key' => 'phase125-ambiguous-ift-'.Str::uuid(),
        'source_fund_id' => $context['fund']->id,
        'destination_fund_id' => $context['destinationFund']->id,
        'policy_basis_ref' => 'PHASE-12.5-ATTRIBUTION-REQUIRED',
        'reason' => 'Missing attribution must fail.',
    ]))->toThrow(FinancialDomainException::class, 'primary_financial_account_id');

    $receipt = UatFinancialFixture::receipt($context, '100.00');
    UatFinancialFixture::advance($receipt);
    UatFinancialFixture::post($receipt, 'phase125-backdate-receipt');

    $tomorrow = now()->addDay()->toDateString();
    $laterPayment = $lifecycle->createPayment([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['paymentType']->id,
        'business_date' => $tomorrow,
        'accounting_date' => $tomorrow,
        'gross_amount' => '90.00',
        'source_reference' => 'PHASE125-LATER-PAY-'.Str::uuid(),
        'idempotency_key' => 'phase125-later-pay-'.Str::uuid(),
        'primary_financial_account_id' => $context['accountA']->id,
        'counterparty_id' => $context['supplier']->id,
        'category_id' => $context['paymentCategory']->id,
        'description' => 'Later posted payment for backdate protection.',
    ], [[
        'account_id' => $context['expense']->id,
        'split_amount' => '90.00',
        'fund_id' => $context['fund']->id,
    ]]);
    UatFinancialFixture::advance($laterPayment);
    UatFinancialFixture::post($laterPayment, 'phase125-later-payment');

    $backdatedIft = $lifecycle->createInterfundTransfer([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['interfundType']->id,
        'business_date' => $context['today'],
        'accounting_date' => $context['today'],
        'gross_amount' => '20.00',
        'source_reference' => 'PHASE125-BACKDATED-IFT-'.Str::uuid(),
        'idempotency_key' => 'phase125-backdated-ift-'.Str::uuid(),
        'primary_financial_account_id' => $context['accountA']->id,
        'source_fund_id' => $context['fund']->id,
        'destination_fund_id' => $context['destinationFund']->id,
        'policy_basis_ref' => 'PHASE-12.5-BACKDATE-CONTROL',
        'reason' => 'Must not invalidate a later running balance.',
    ]);
    UatFinancialFixture::advance($backdatedIft);

    expect(fn () => UatFinancialFixture::post($backdatedIft, 'phase125-backdated-attribution'))
        ->toThrow(FinancialPostingException::class, 'backdated Fund attribution reduction is blocked');
});

/** @param array<string, mixed> $context */
function phase125TaggedIftReversal(array $context, FinancialTransaction $original): FinancialTransaction
{
    $type = TransactionType::create([
        'accounting_entity_id' => $context['entity']->id,
        'code' => 'REV',
        'name' => 'Reversal',
        'voucher_prefix' => 'REV',
        'status' => 'active',
    ]);
    $rule = PostingRule::create([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $type->id,
        'code' => 'REV-ATTRIBUTION',
        'name' => 'Reversal Attribution',
        'rule_family' => 'reversal',
        'status' => 'active',
    ]);
    PostingRuleVersion::create([
        'accounting_entity_id' => $context['entity']->id,
        'posting_rule_id' => $rule->id,
        'version_no' => 1,
        'effective_from' => now()->subDay()->toDateString(),
        'input_contract_ref' => 'phase125-test',
        'journal_template_ref' => 'original-journal-reversal',
        'business_rule_refs' => 'PHASE-12.5-REVERSAL-CONTROL',
        'status' => 'effective',
    ]);
    DocumentSequence::create([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $type->id,
        'code' => 'REV-ATTRIBUTION',
        'name' => 'Reversal Attribution',
        'prefix' => 'REV',
        'scope_key' => 'phase125-reversal',
        'status' => 'active',
    ]);
    $reason = ReasonCode::create([
        'accounting_entity_id' => $context['entity']->id,
        'code' => 'REV-ATTRIBUTION',
        'name' => 'Reverse Fund Attribution',
        'reason_class' => 'reversal',
        'status' => 'active',
    ]);
    $reversal = FinancialTransaction::create([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $type->id,
        'status' => 'approved',
        'source_reference' => 'PHASE125-REV-'.Str::uuid(),
        'business_date' => $context['today'],
        'accounting_date' => $context['today'],
        'description' => 'Governed tagged IFT reversal test',
        'gross_amount' => $original->gross_amount,
        'primary_financial_account_id' => $original->primary_financial_account_id,
        'reason_code_id' => $reason->id,
        'related_transaction_id' => $original->id,
        'idempotency_key' => 'phase125-reversal-source-'.Str::uuid(),
        'correlation_id' => (string) Str::uuid(),
    ]);
    TransactionSplit::create([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_id' => $reversal->id,
        'line_no' => 1,
        'split_amount' => $original->gross_amount,
        'account_id' => $context['transferOut']->id,
        'fund_id' => $context['destinationFund']->id,
    ]);
    ApprovalDecision::create([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_id' => $reversal->id,
        'step_no' => 1,
        'decision' => 'approved',
        'decision_at' => now(),
        'comment' => 'Phase 12.5 reversal regression approval',
    ]);
    $attachment = Attachment::create([
        'accounting_entity_id' => $context['entity']->id,
        'original_filename' => 'phase125-reversal.pdf',
        'media_type' => 'application/pdf',
        'byte_size' => 1,
        'content_hash' => hash('sha256', $reversal->id),
        'storage_reference' => 'test://phase125/reversal/'.$reversal->id,
        'status' => 'active',
        'received_at' => now(),
    ]);
    AttachmentLink::create([
        'accounting_entity_id' => $context['entity']->id,
        'attachment_id' => $attachment->id,
        'target_type' => 'transaction',
        'target_id' => $reversal->id,
        'evidence_type' => 'policy',
        'status' => 'active',
    ]);

    return $reversal;
}
