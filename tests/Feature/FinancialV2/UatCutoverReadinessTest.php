<?php

use App\Domain\FinancialV2\BudgetAllocationService;
use App\Domain\FinancialV2\FinancialPostingException;
use App\Domain\FinancialV2\FinancialTransactionLifecycleService;
use App\Domain\FinancialV2\Reporting\FinancialReportService;
use App\Models\FinancialV2\AuditEvent;
use App\Models\FinancialV2\EvidenceRequirement;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\JournalLine;
use App\Models\FinancialV2\LedgerEntry;
use App\Models\FinancialV2\Program;
use App\Models\FinancialV2\Voucher;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\Support\UatFinancialFixture;

test('UAT-001 through UAT-002: governed Friday receipt enforces evidence, preserves dimensions, audit, voucher, and report tie-out', function () {
    $context = UatFinancialFixture::context();
    $actor = User::factory()->create();
    EvidenceRequirement::create([
        'accounting_entity_id' => $context['entity']->id,
        'posting_rule_version_id' => $context['receiptVersion']->id,
        'evidence_type' => 'receipt',
        'minimum_count' => 1,
    ]);

    $missingEvidence = UatFinancialFixture::receipt($context, '10.00', null, null, $context['program']->id, $actor->id);
    UatFinancialFixture::advance($missingEvidence, $actor->id);
    expect(fn () => UatFinancialFixture::post($missingEvidence, 'uat-missing-evidence', $actor->id))
        ->toThrow(FinancialPostingException::class, 'Required evidence is missing');
    expect(Journal::where('accounting_entity_id', $context['entity']->id)->count())->toBe(0)
        ->and(LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count())->toBe(0);

    $receipt = UatFinancialFixture::receipt($context, '2500000.00', null, null, $context['program']->id, $actor->id);
    UatFinancialFixture::attachReceiptEvidence($context, $receipt, $actor->id);
    UatFinancialFixture::advance($receipt, $actor->id);
    $posted = UatFinancialFixture::post($receipt, 'uat-friday-receipt', $actor->id);
    $retry = UatFinancialFixture::post($receipt, 'uat-friday-receipt', $actor->id);
    $journal = Journal::findOrFail($posted->journalId);
    $reports = app(FinancialReportService::class);
    $trialBalance = $reports->report('trial-balance', $context['entity']->id, $context['today'], $context['today']);
    $accountBalance = $reports->report('account-balance', $context['entity']->id, $context['today'], $context['today']);
    $fundBalance = $reports->report('fund-balance', $context['entity']->id, $context['today'], $context['today'], ['fund_id' => $context['fund']->id]);
    $friday = $reports->report('friday', $context['entity']->id, $context['today'], $context['today']);
    $program = $reports->report('program', $context['entity']->id, $context['today'], $context['today'], ['program_id' => $context['program']->id]);

    expect($retry->journalId)->toBe($posted->journalId)
        ->and($journal->total_debit)->toBe('2500000.00')
        ->and($journal->total_credit)->toBe('2500000.00')
        ->and(JournalLine::where('journal_id', $journal->id)->where('program_id', $context['program']->id)->count())->toBe(2)
        ->and(LedgerEntry::whereIn('journal_line_id', JournalLine::where('journal_id', $journal->id)->pluck('id'))->count())->toBe(2)
        ->and(Voucher::where('transaction_id', $receipt->id)->count())->toBe(1)
        ->and(AuditEvent::where('target_id', $receipt->id)->where('actor_user_id', $actor->id)->whereNotNull('event_at')->count())->toBeGreaterThanOrEqual(5)
        ->and($trialBalance['data']['is_balanced'])->toBeTrue()
        ->and($accountBalance['source'])->toBe('financial_v2_posted_general_ledger')
        ->and($fundBalance['data']['has_data'])->toBeTrue()
        ->and($friday['data']['receipts'])->toBe('2500000.00')
        ->and($program['data']['has_data'])->toBeTrue();

    $inactiveProgram = Program::create([
        'accounting_entity_id' => $context['entity']->id,
        'code' => 'INACTIVE-UAT',
        'name' => 'Program UAT Tidak Aktif',
        'status' => 'suspended',
    ]);
    $invalidProgram = UatFinancialFixture::receipt($context, '1.00', null, null, $inactiveProgram->id, $actor->id);
    UatFinancialFixture::attachReceiptEvidence($context, $invalidProgram, $actor->id);
    UatFinancialFixture::advance($invalidProgram, $actor->id);
    expect(fn () => UatFinancialFixture::post($invalidProgram, 'uat-inactive-program', $actor->id))
        ->toThrow(FinancialPostingException::class, 'program_id is inactive');
    expect(Journal::where('accounting_entity_id', $context['entity']->id)->count())->toBe(1)
        ->and(LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count())->toBe(2);
});

test('UAT-003: six ZISWAF fixture funds are policy-controlled and do not mix with the operational fund', function () {
    $context = UatFinancialFixture::context();
    $funds = [];
    foreach ([
        'ZAKAT' => 'Zakat Maal',
        'INFAQ' => 'Infaq Tromol',
        'SODAQ' => 'Sodaqoh',
        'FIDYAH' => 'Fidyah',
        'DHUAFA' => 'Dhuafa',
        'YATIM' => 'Santunan Yatim',
    ] as $code => $name) {
        $fund = UatFinancialFixture::restrictedFund($context, $code, $name);
        $transaction = UatFinancialFixture::receipt($context, '50.00', $fund->id);
        UatFinancialFixture::advance($transaction);
        $result = UatFinancialFixture::post($transaction, 'uat-ziswaf-'.strtolower($code));
        $funds[] = compact('fund', 'result');
    }

    foreach ($funds as ['fund' => $fund, 'result' => $result]) {
        expect(JournalLine::where('journal_id', $result->journalId)->pluck('fund_id')->unique()->all())->toBe([$fund->id])
            ->and(LedgerEntry::where('accounting_entity_id', $context['entity']->id)->where('fund_id', $fund->id)->count())->toBe(2);
    }
    expect(LedgerEntry::where('accounting_entity_id', $context['entity']->id)->where('fund_id', $context['fund']->id)->count())->toBe(0);

    $prohibited = UatFinancialFixture::restrictedFund($context, 'ZKTBLK', 'Zakat Maal Prohibited', false);
    $blocked = UatFinancialFixture::receipt($context, '1.00', $prohibited->id);
    UatFinancialFixture::advance($blocked);
    $factsBefore = [Journal::where('accounting_entity_id', $context['entity']->id)->count(), LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count()];
    expect(fn () => UatFinancialFixture::post($blocked, 'uat-ziswaf-prohibited'))
        ->toThrow(FinancialPostingException::class, 'fail-closed');
    expect([Journal::where('accounting_entity_id', $context['entity']->id)->count(), LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count()])->toBe($factsBefore);
});

test('UAT-004 through UAT-009: payment, transfers, allocation, realization, and restricted usages preserve accounting controls', function () {
    $context = UatFinancialFixture::context();
    $service = app(FinancialTransactionLifecycleService::class);
    $budgetService = app(BudgetAllocationService::class);
    EvidenceRequirement::create([
        'accounting_entity_id' => $context['entity']->id,
        'posting_rule_version_id' => $context['paymentVersion']->id,
        'evidence_type' => 'invoice',
        'minimum_count' => 1,
    ]);
    EvidenceRequirement::create([
        'accounting_entity_id' => $context['entity']->id,
        'posting_rule_version_id' => $context['treasuryVersion']->id,
        'evidence_type' => 'transfer_proof',
        'minimum_count' => 1,
    ]);

    $receipt = UatFinancialFixture::receipt($context, '500.00');
    UatFinancialFixture::advance($receipt);
    UatFinancialFixture::post($receipt, 'uat-operating-funds');

    $payment = UatFinancialFixture::payment($context, '100.00');
    UatFinancialFixture::attachTransactionEvidence($context, $payment, 'invoice');
    UatFinancialFixture::advance($payment);
    $paymentResult = UatFinancialFixture::post($payment, 'uat-valid-payment');
    expect($paymentResult->journalId)->not->toBeEmpty();

    $transfer = $service->createTreasuryTransfer([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['treasuryType']->id,
        'business_date' => $context['today'],
        'accounting_date' => $context['today'],
        'gross_amount' => '50.00',
        'source_reference' => 'UAT-TRF-'.Str::uuid(),
        'idempotency_key' => 'uat-transfer-source-'.Str::uuid(),
        'source_financial_account_id' => $context['accountA']->id,
        'destination_financial_account_id' => $context['accountB']->id,
        'description' => 'Synthetic treasury transfer',
    ], [['account_id' => $context['cashA']->id, 'split_amount' => '50.00', 'fund_id' => $context['fund']->id]]);
    UatFinancialFixture::attachTransactionEvidence($context, $transfer, 'transfer_proof');
    UatFinancialFixture::advance($transfer);
    $transferResult = UatFinancialFixture::post($transfer, 'uat-treasury-transfer');
    $transferLines = JournalLine::where('journal_id', $transferResult->journalId)->get();
    expect($transferLines->whereIn('account_id', [$context['revenue']->id, $context['expense']->id]))->toHaveCount(0)
        ->and($transferLines->pluck('financial_account_id')->sort()->values()->all())->toBe(collect([$context['accountA']->id, $context['accountB']->id])->sort()->values()->all())
        ->and($transferLines->pluck('fund_id')->unique()->all())->toBe([$context['fund']->id]);

    $interfund = $service->createInterfundTransfer([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['interfundType']->id,
        'business_date' => $context['today'],
        'accounting_date' => $context['today'],
        'gross_amount' => '20.00',
        'source_reference' => 'UAT-IFT-'.Str::uuid(),
        'idempotency_key' => 'uat-interfund-source-'.Str::uuid(),
        'primary_financial_account_id' => $context['accountA']->id,
        'source_fund_id' => $context['fund']->id,
        'destination_fund_id' => $context['destinationFund']->id,
        'policy_basis_ref' => 'UAT-GOV-IFT',
        'reason' => 'Synthetic governed fund reclassification',
        'description' => 'Synthetic inter-fund transfer',
    ]);
    UatFinancialFixture::advance($interfund);
    $interfundResult = UatFinancialFixture::post($interfund, 'uat-interfund-transfer');
    expect(JournalLine::where('journal_id', $interfundResult->journalId)->whereNotNull('financial_account_id')->count())->toBe(0)
        ->and(JournalLine::where('journal_id', $interfundResult->journalId)->whereIn('account_id', [$context['revenue']->id, $context['expense']->id])->count())->toBe(0);

    $factsBeforeAllocation = [Journal::where('accounting_entity_id', $context['entity']->id)->count(), LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count()];
    $allocation = $budgetService->create([
        'accounting_entity_id' => $context['entity']->id,
        'accounting_period_id' => $context['period']->id,
        'fund_id' => $context['fund']->id,
        'program_id' => $context['program']->id,
        'account_id' => $context['expense']->id,
        'category_id' => $context['paymentCategory']->id,
        'allocation_reference' => 'UAT-BGT-'.Str::uuid(),
        'idempotency_key' => 'uat-budget-'.Str::uuid(),
        'allocated_amount' => '200.00',
        'effective_from' => $context['today'],
        'reason' => 'Synthetic program allocation',
    ]);
    $budgetService->submit($allocation->id);
    $version = $budgetService->approveVersion($allocation->id, $allocation->versions->sole()->id);
    expect([Journal::where('accounting_entity_id', $context['entity']->id)->count(), LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count()])->toBe($factsBeforeAllocation);

    $realization = $service->createRealization([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['paymentType']->id,
        'business_date' => $context['today'],
        'accounting_date' => $context['today'],
        'gross_amount' => '60.00',
        'source_reference' => 'UAT-REAL-'.Str::uuid(),
        'idempotency_key' => 'uat-realization-'.Str::uuid(),
        'primary_financial_account_id' => $context['accountA']->id,
        'counterparty_id' => $context['supplier']->id,
        'category_id' => $context['paymentCategory']->id,
        'description' => 'Synthetic allocation realization',
    ], [[
        'account_id' => $context['expense']->id,
        'split_amount' => '60.00',
        'fund_id' => $context['fund']->id,
        'program_id' => $context['program']->id,
    ]], $version->id);
    UatFinancialFixture::attachTransactionEvidence($context, $realization, 'invoice');
    UatFinancialFixture::advance($realization);
    UatFinancialFixture::post($realization, 'uat-realization-post');
    expect($budgetService->availability($version->id))->toBe(['allocated' => '200.00', 'actual' => '60.00', 'available' => '140.00']);

    $excess = $service->createRealization([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['paymentType']->id,
        'business_date' => $context['today'],
        'accounting_date' => $context['today'],
        'gross_amount' => '150.00',
        'source_reference' => 'UAT-REAL-'.Str::uuid(),
        'idempotency_key' => 'uat-realization-excess-'.Str::uuid(),
        'primary_financial_account_id' => $context['accountA']->id,
        'counterparty_id' => $context['supplier']->id,
        'category_id' => $context['paymentCategory']->id,
        'description' => 'Synthetic excess allocation realization',
    ], [[
        'account_id' => $context['expense']->id,
        'split_amount' => '150.00',
        'fund_id' => $context['fund']->id,
        'program_id' => $context['program']->id,
    ]], $version->id);
    UatFinancialFixture::attachTransactionEvidence($context, $excess, 'invoice');
    UatFinancialFixture::advance($excess);
    $factsBeforeExcess = [Journal::where('accounting_entity_id', $context['entity']->id)->count(), LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count()];
    expect(fn () => UatFinancialFixture::post($excess, 'uat-realization-excess'))
        ->toThrow(FinancialPostingException::class, 'available Budget Allocation');
    expect([Journal::where('accounting_entity_id', $context['entity']->id)->count(), LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count()])->toBe($factsBeforeExcess);

    $restricted = UatFinancialFixture::restrictedFund($context, 'PAYBLK', 'Restricted Payment', false);
    $restrictedPayment = UatFinancialFixture::payment($context, '1.00', $restricted->id);
    UatFinancialFixture::attachTransactionEvidence($context, $restrictedPayment, 'invoice');
    UatFinancialFixture::advance($restrictedPayment);
    expect(fn () => UatFinancialFixture::post($restrictedPayment, 'uat-restricted-payment'))
        ->toThrow(FinancialPostingException::class, 'fail-closed');
});

test('UAT-017: performance smoke posts 100 then 1,000 transactions and 10,000 ledger lines through the canonical engine', function () {
    $context = UatFinancialFixture::context();
    $startedAt = hrtime(true);
    for ($index = 1; $index <= 1000; $index++) {
        $transaction = UatFinancialFixture::receipt($context, '1.00');
        UatFinancialFixture::advance($transaction);
        UatFinancialFixture::post($transaction, 'uat-smoke-single-'.$index);
        if ($index === 100) {
            expect(Journal::where('accounting_entity_id', $context['entity']->id)->count())->toBe(100)
                ->and(LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count())->toBe(200);
        }
    }

    $batchSplits = array_fill(0, 50, [
        'account_id' => $context['revenue']->id,
        'split_amount' => '1.00',
        'fund_id' => $context['fund']->id,
    ]);
    for ($index = 1; $index <= 100; $index++) {
        $transaction = UatFinancialFixture::receipt($context, '50.00', null, $batchSplits);
        UatFinancialFixture::advance($transaction);
        UatFinancialFixture::post($transaction, 'uat-smoke-batch-'.$index);
    }

    $reports = app(FinancialReportService::class);
    $summary = $reports->report('summary', $context['entity']->id, $context['today'], $context['today']);
    $account = $reports->report('account-balance', $context['entity']->id, $context['today'], $context['today']);
    $fund = $reports->report('fund-balance', $context['entity']->id, $context['today'], $context['today'], ['fund_id' => $context['fund']->id]);
    $history = $reports->report('transaction-history', $context['entity']->id, $context['today'], $context['today'], ['per_page' => 200]);
    $trialBalance = $reports->report('trial-balance', $context['entity']->id, $context['today'], $context['today']);
    $elapsedMilliseconds = (hrtime(true) - $startedAt) / 1_000_000;

    expect(Journal::where('accounting_entity_id', $context['entity']->id)->count())->toBe(1100)
        ->and(JournalLine::where('accounting_entity_id', $context['entity']->id)->count())->toBe(12000)
        ->and(LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count())->toBe(12000)
        ->and($summary['data']['has_data'])->toBeTrue()
        ->and($account['data']['has_data'])->toBeTrue()
        ->and($fund['data']['has_data'])->toBeTrue()
        ->and($history['data']['rows'])->toHaveCount(200)
        ->and($trialBalance['data']['is_balanced'])->toBeTrue()
        ->and($elapsedMilliseconds)->toBeGreaterThan(0);
});
