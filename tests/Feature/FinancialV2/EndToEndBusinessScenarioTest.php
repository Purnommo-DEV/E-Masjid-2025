<?php

use App\Domain\FinancialV2\BudgetAllocationService;
use App\Domain\FinancialV2\DecimalAmount;
use App\Domain\FinancialV2\FinancialPostingException;
use App\Domain\FinancialV2\FinancialTransactionLifecycleService;
use App\Domain\FinancialV2\Reporting\FinancialReportService;
use App\Models\FinancialV2\Account;
use App\Models\FinancialV2\AccountingPeriod;
use App\Models\FinancialV2\BudgetAllocation;
use App\Models\FinancialV2\CashAccountDetail;
use App\Models\FinancialV2\FinancialAccount;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\FundPolicyRule;
use App\Models\FinancialV2\FundPolicyVersion;
use App\Models\FinancialV2\FundRealization;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\JournalLine;
use App\Models\FinancialV2\LedgerEntry;
use App\Models\FinancialV2\PostingRuleLine;
use App\Models\FinancialV2\Program;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Support\UatFinancialFixture;

test('Phase 10.2: operational Friday gives a Rp4.200.000 ledger-backed closing balance', function () {
    $context = UatFinancialFixture::context();

    $receipt = phase102Receipt($context, '5000000.00', $context['fund']->id, $context['program']->id, $context['today']);
    UatFinancialFixture::advance($receipt);
    $receiptResult = UatFinancialFixture::post($receipt, 'phase102-friday-receipt');

    foreach (['500000.00', '300000.00'] as $index => $amount) {
        $payment = phase102Payment($context, $amount, $context['fund']->id, $context['program']->id, $context['today']);
        UatFinancialFixture::advance($payment);
        UatFinancialFixture::post($payment, 'phase102-friday-payment-'.($index + 1));
    }

    $report = app(FinancialReportService::class)->report('friday', $context['entity']->id, $context['today'], $context['today']);
    $journal = Journal::findOrFail($receiptResult->journalId);
    $journalLineIds = JournalLine::where('journal_id', $journal->id)->pluck('id');

    expect($report['source'])->toBe('financial_v2_posted_general_ledger')
        ->and($report['data']['opening_balance'])->toBe('0.00')
        ->and($report['data']['receipts'])->toBe('5000000.00')
        ->and($report['data']['payments'])->toBe('800000.00')
        ->and($report['data']['closing_balance'])->toBe('4200000.00')
        ->and($report['data']['receipt_rows'])->toHaveCount(1)
        ->and($report['data']['payment_rows'])->toHaveCount(2)
        ->and($journal->total_debit)->toBe('5000000.00')
        ->and($journal->total_credit)->toBe('5000000.00')
        ->and(JournalLine::where('journal_id', $journal->id)->where('fund_id', $context['fund']->id)->where('program_id', $context['program']->id)->count())->toBe(2)
        ->and(LedgerEntry::whereIn('journal_line_id', $journalLineIds)->count())->toBe(2);
});

test('Phase 10.2: Bank Operasional to Kas Operasional Rp2.000.000 is a transfer, not income or expense', function () {
    $context = UatFinancialFixture::context();
    phase102ConfigureBankToCashFixture($context);

    $bankReceipt = phase102Receipt($context, '2000000.00', $context['fund']->id, null, $context['today'], $context['accountB']->id);
    UatFinancialFixture::advance($bankReceipt);
    UatFinancialFixture::post($bankReceipt, 'phase102-bank-opening');

    $transfer = app(FinancialTransactionLifecycleService::class)->createTreasuryTransfer([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['treasuryType']->id,
        'business_date' => $context['today'],
        'accounting_date' => $context['today'],
        'gross_amount' => '2000000.00',
        'source_reference' => 'PHASE102-BANK-CASH-'.Str::uuid(),
        'idempotency_key' => 'phase102-bank-cash-'.Str::uuid(),
        'source_financial_account_id' => $context['accountB']->id,
        'destination_financial_account_id' => $context['accountA']->id,
        'description' => 'Transfer BNI Operasional ke Kas Operasional',
    ], [[
        'account_id' => $context['cashB']->id,
        'split_amount' => '2000000.00',
        'fund_id' => $context['fund']->id,
    ]]);
    UatFinancialFixture::advance($transfer);
    $result = UatFinancialFixture::post($transfer, 'phase102-bank-to-cash');
    $lines = JournalLine::where('journal_id', $result->journalId)->get();
    $accounts = app(FinancialReportService::class)->report('account-balance', $context['entity']->id, $context['today'], $context['today'])['data']['rows'];
    $balances = collect($accounts)->keyBy('financial_account_id');

    expect($lines)->toHaveCount(2)
        ->and($lines->whereIn('account_id', [$context['revenue']->id, $context['expense']->id]))->toHaveCount(0)
        ->and($lines->pluck('financial_account_id')->sort()->values()->all())->toBe(collect([$context['accountA']->id, $context['accountB']->id])->sort()->values()->all())
        ->and($lines->pluck('fund_id')->unique()->all())->toBe([$context['fund']->id])
        ->and($balances->get($context['accountB']->id)['closing_balance'])->toBe('0.00')
        ->and($balances->get($context['accountA']->id)['closing_balance'])->toBe('2000000.00');
});

test('Phase 10.2: Qurban uses Bank to Cash transfer before the actual Qurban payment', function () {
    $context = UatFinancialFixture::context();
    phase102ConfigureBankToCashFixture($context);
    $fund = phase102Fund($context, 'QURBAN', 'Dana Qurban');
    $program = phase102Program($context, 'QURBAN-1448', 'Qurban 1448 H');

    $receipt = phase102Receipt($context, '2000000.00', $fund->id, $program->id, $context['today'], $context['accountB']->id);
    UatFinancialFixture::advance($receipt);
    UatFinancialFixture::post($receipt, 'phase102-qurban-receipt');

    $transfer = app(FinancialTransactionLifecycleService::class)->createTreasuryTransfer([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['treasuryType']->id,
        'business_date' => $context['today'],
        'accounting_date' => $context['today'],
        'gross_amount' => '1000000.00',
        'source_reference' => 'PHASE102-QURBAN-TRF-'.Str::uuid(),
        'idempotency_key' => 'phase102-qurban-trf-'.Str::uuid(),
        'source_financial_account_id' => $context['accountB']->id,
        'destination_financial_account_id' => $context['accountA']->id,
        'description' => 'Transfer BNI Qurban ke Kas Qurban',
    ], [[
        'account_id' => $context['cashB']->id,
        'split_amount' => '1000000.00',
        'fund_id' => $fund->id,
    ]]);
    UatFinancialFixture::advance($transfer);
    $transferResult = UatFinancialFixture::post($transfer, 'phase102-qurban-transfer');

    $payment = phase102Payment($context, '1000000.00', $fund->id, $program->id, $context['today']);
    UatFinancialFixture::advance($payment);
    $paymentResult = UatFinancialFixture::post($payment, 'phase102-qurban-payment-after-transfer');

    expect(JournalLine::where('journal_id', $transferResult->journalId)->whereIn('account_id', [$context['revenue']->id, $context['expense']->id])->count())->toBe(0)
        ->and(JournalLine::where('journal_id', $transferResult->journalId)->pluck('fund_id')->unique()->all())->toBe([$fund->id])
        ->and(JournalLine::where('journal_id', $paymentResult->journalId)->where('account_id', $context['expense']->id)->count())->toBe(1)
        ->and(JournalLine::where('journal_id', $paymentResult->journalId)->pluck('fund_id')->unique()->all())->toBe([$fund->id]);
});

test('Phase 10.2: Santunan Anak Yatim allocation and a single Rp10.000.000 realization remain separate accounting events', function () {
    $context = UatFinancialFixture::context();
    $fund = phase102Fund($context, 'YATIM', 'Dana Santunan Anak Yatim');
    $program = phase102Program($context, 'YATIM-BULANAN', 'Santunan Anak Yatim Bulanan');

    $receipt = phase102Receipt($context, '10000000.00', $fund->id, $program->id, $context['today']);
    UatFinancialFixture::advance($receipt);
    UatFinancialFixture::post($receipt, 'phase102-yatim-funding');
    $factsBeforeAllocation = [Journal::count(), LedgerEntry::count()];

    $version = phase102ApprovedAllocation($context, $fund, $program, $context['period'], $context['today'], 'YATIM-100X100K');
    expect([Journal::count(), LedgerEntry::count()])->toBe($factsBeforeAllocation)
        ->and($version->allocated_amount)->toBe('10000000.00');

    $realization = app(FinancialTransactionLifecycleService::class)->createRealization([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['paymentType']->id,
        'business_date' => $context['today'],
        'accounting_date' => $context['today'],
        'gross_amount' => '10000000.00',
        'source_reference' => 'PHASE102-YATIM-REAL-'.Str::uuid(),
        'idempotency_key' => 'phase102-yatim-real-'.Str::uuid(),
        'primary_financial_account_id' => $context['accountA']->id,
        'counterparty_id' => $context['supplier']->id,
        'category_id' => $context['paymentCategory']->id,
        'description' => 'Realisasi santunan untuk 100 penerima @ Rp100.000',
    ], [[
        'account_id' => $context['expense']->id,
        'split_amount' => '10000000.00',
        'fund_id' => $fund->id,
        'program_id' => $program->id,
    ]], $version->id);
    UatFinancialFixture::attachTransactionEvidence($context, $realization, 'other');
    UatFinancialFixture::advance($realization);
    $result = UatFinancialFixture::post($realization, 'phase102-yatim-realization');

    expect(Journal::where('id', $result->journalId)->count())->toBe(1)
        ->and(JournalLine::where('journal_id', $result->journalId)->count())->toBe(2)
        ->and(FundRealization::where('transaction_id', $realization->id)->value('status'))->toBe('recorded')
        ->and(app(BudgetAllocationService::class)->availability($version->id))->toBe([
            'allocated' => '10000000.00',
            'actual' => '10000000.00',
            'available' => '0.00',
        ]);
});

test('Phase 10.2: four monthly Santunan allocations and realizations preserve a ledger history without a recipient register', function () {
    $context = UatFinancialFixture::context();
    $fund = phase102Fund($context, 'YATIM-4B', 'Dana Santunan Bulanan');
    $program = phase102Program($context, 'YATIM-4B', 'Santunan Anak Yatim Bulanan', now()->startOfYear(), now()->endOfYear());
    $context['receiptVersion']->update(['effective_from' => now()->startOfYear()->toDateString()]);
    $context['paymentVersion']->update(['effective_from' => now()->startOfYear()->toDateString()]);
    $versions = [];

    foreach (range(1, 4) as $month) {
        $date = Carbon::now()->startOfYear()->addMonths($month - 1)->addDays(14);
        $period = AccountingPeriod::create([
            'accounting_entity_id' => $context['entity']->id,
            'accounting_calendar_id' => $context['period']->accounting_calendar_id,
            'period_no' => $month + 1,
            'period_name' => 'Santunan '.str_pad((string) $month, 2, '0', STR_PAD_LEFT),
            'start_date' => $date->copy()->startOfMonth()->toDateString(),
            'end_date' => $date->copy()->endOfMonth()->toDateString(),
            'status' => 'open',
        ]);
        $version = phase102ApprovedAllocation($context, $fund, $program, $period, $date->toDateString(), 'YATIM-'.str_pad((string) $month, 2, '0', STR_PAD_LEFT));
        $versions[] = $version;

        $receipt = phase102Receipt($context, '10000000.00', $fund->id, $program->id, $date->toDateString());
        UatFinancialFixture::advance($receipt);
        UatFinancialFixture::post($receipt, 'phase102-yatim-month-fund-'.$month);

        $realization = phase102Realization($context, $fund, $program, $version->id, '10000000.00', $date->toDateString());
        UatFinancialFixture::advance($realization);
        UatFinancialFixture::post($realization, 'phase102-yatim-month-real-'.$month);

        $monthReport = app(FinancialReportService::class)->report('program', $context['entity']->id, $date->copy()->startOfMonth()->toDateString(), $date->copy()->endOfMonth()->toDateString(), ['program_id' => $program->id]);
        expect($monthReport['data']['rows'][0]['usage'])->toBe('10000000.00');
    }

    $availability = collect($versions)->map(fn ($version) => app(BudgetAllocationService::class)->availability($version->id));
    expect(BudgetAllocation::where('fund_id', $fund->id)->count())->toBe(4)
        ->and(DecimalAmount::sum($availability->pluck('allocated')))->toBe('40000000.00')
        ->and(DecimalAmount::sum($availability->pluck('actual')))->toBe('40000000.00')
        ->and(DecimalAmount::sum($availability->pluck('available')))->toBe('0.00')
        ->and(FundRealization::whereIn('budget_allocation_version_id', collect($versions)->pluck('id'))->count())->toBe(4);
});

test('Phase 10.2: Zakat, Qurban, Ramadhan, Social, and Hall Rental remain separate Fund and Program dimensions', function () {
    $context = UatFinancialFixture::context();
    $zakat = UatFinancialFixture::restrictedFund($context, 'ZAKAT', 'Zakat Maal');
    $zakatProgram = phase102Program($context, 'ZAKAT-OK', 'Penyaluran Zakat');
    $operationalProgram = phase102Program($context, 'OPS-UMUM', 'Operasional Umum');
    $zakatPolicy = FundPolicyVersion::where('fund_id', $zakat->id)->sole();
    FundPolicyRule::create([
        'accounting_entity_id' => $context['entity']->id,
        'fund_policy_version_id' => $zakatPolicy->id,
        'transaction_type_id' => $context['paymentType']->id,
        'program_id' => $zakatProgram->id,
        'decision' => 'allowed',
    ]);

    $zakatReceipt = phase102Receipt($context, '20000000.00', $zakat->id, $zakatProgram->id, $context['today']);
    UatFinancialFixture::advance($zakatReceipt);
    UatFinancialFixture::post($zakatReceipt, 'phase102-zakat-receipt');

    $factsBeforeRejectedUse = [Journal::count(), LedgerEntry::count()];
    $prohibitedUse = phase102Payment($context, '5000000.00', $zakat->id, $operationalProgram->id, $context['today']);
    UatFinancialFixture::advance($prohibitedUse);
    expect(fn () => UatFinancialFixture::post($prohibitedUse, 'phase102-zakat-prohibited'))
        ->toThrow(FinancialPostingException::class, 'fail-closed');
    expect([Journal::count(), LedgerEntry::count()])->toBe($factsBeforeRejectedUse);

    $allowedUse = phase102Payment($context, '5000000.00', $zakat->id, $zakatProgram->id, $context['today']);
    UatFinancialFixture::advance($allowedUse);
    UatFinancialFixture::post($allowedUse, 'phase102-zakat-allowed');

    $report = app(FinancialReportService::class)->report('fund-balance', $context['entity']->id, $context['today'], $context['today'], ['fund_id' => $zakat->id]);
    $zakatRow = collect($report['data']['rows'])->sole('fund_id', $zakat->id);
    $zakatLiquidity = DecimalAmount::sum(collect($report['data']['liquidity_distribution'])->where('fund_id', $zakat->id)->pluck('liquidity_balance'));

    $funds = [
        'QURBAN' => ['Dana Qurban', 'Qurban 1448 H'],
        'RAMADHAN' => ['Dana Ramadhan', 'Iftar Ramadhan'],
        'SOSIAL' => ['Dana Sosial/Kematian', 'Bantuan Kematian'],
        'SEWAAULA' => ['Dana Sewa Aula', 'Sewa Aula'],
    ];
    $posted = [];
    foreach ($funds as $code => [$fundName, $programName]) {
        $fund = phase102Fund($context, $code, $fundName);
        $program = phase102Program($context, $code, $programName);
        $financialAccountId = null;
        if ($code === 'SEWAAULA') {
            $hallAccount = phase102CashFinancialAccount($context, 'KAS-AULA', 'Kas Sewa Aula');
            PostingRuleLine::where('posting_rule_version_id', $context['receiptVersion']->id)->where('line_no', 1)->update(['account_id' => $hallAccount->account_id]);
            $financialAccountId = $hallAccount->id;
        }
        $transaction = phase102Receipt($context, '2000000.00', $fund->id, $program->id, $context['today'], $financialAccountId);
        UatFinancialFixture::advance($transaction);
        $result = UatFinancialFixture::post($transaction, 'phase102-'.$code);
        $posted[$code] = compact('fund', 'program', 'result');
    }

    PostingRuleLine::where('posting_rule_version_id', $context['receiptVersion']->id)->where('line_no', 1)->update(['account_id' => $context['cashA']->id]);

    $ramadhanOperational = phase102Program($context, 'OPS-RAM', 'Operasional Ramadhan');
    $operationalReceipt = phase102Receipt($context, '1000000.00', $context['fund']->id, $ramadhanOperational->id, $context['today']);
    UatFinancialFixture::advance($operationalReceipt);
    UatFinancialFixture::post($operationalReceipt, 'phase102-operational-ramadhan');

    $qurbanPayment = phase102Payment($context, '500000.00', $posted['QURBAN']['fund']->id, $posted['QURBAN']['program']->id, $context['today']);
    UatFinancialFixture::advance($qurbanPayment);
    $qurbanResult = UatFinancialFixture::post($qurbanPayment, 'phase102-qurban-payment');

    expect($zakatRow['receipts'])->toBe('20000000.00')
        ->and($zakatRow['expenses'])->toBe('5000000.00')
        ->and($zakatRow['fund_balance'])->toBe('15000000.00')
        ->and($zakatRow['available_liquidity'])->toBe('15000000.00')
        ->and($zakatLiquidity)->toBe('15000000.00')
        ->and($zakatRow['closing_net_position'])->toBe('15000000.00')
        ->and(JournalLine::where('journal_id', $posted['SEWAAULA']['result']->journalId)->pluck('fund_id')->unique()->all())->toBe([$posted['SEWAAULA']['fund']->id])
        ->and(JournalLine::where('journal_id', $posted['SEWAAULA']['result']->journalId)->whereNotNull('financial_account_id')->value('financial_account_id'))->toBe($hallAccount->id)
        ->and(JournalLine::where('journal_id', $qurbanResult->journalId)->whereIn('account_id', [$context['revenue']->id, $context['expense']->id])->count())->toBe(1)
        ->and(JournalLine::where('journal_id', $qurbanResult->journalId)->pluck('fund_id')->unique()->all())->toBe([$posted['QURBAN']['fund']->id]);
});

/** @param array<string, mixed> $context */
function phase102Receipt(array $context, string $amount, string $fundId, ?string $programId, string $date, ?string $financialAccountId = null): \App\Models\FinancialV2\FinancialTransaction
{
    return app(FinancialTransactionLifecycleService::class)->createReceipt([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['receiptType']->id,
        'business_date' => $date,
        'accounting_date' => $date,
        'gross_amount' => $amount,
        'source_reference' => 'PHASE102-RCV-'.Str::uuid(),
        'idempotency_key' => 'phase102-rcv-'.Str::uuid(),
        'primary_financial_account_id' => $financialAccountId ?? $context['accountA']->id,
        'category_id' => $context['receiptCategory']->id,
        'description' => 'Penerimaan simulasi Phase 10.2',
    ], [[
        'account_id' => $context['revenue']->id,
        'split_amount' => $amount,
        'fund_id' => $fundId,
        'program_id' => $programId,
    ]]);
}

/** @param array<string, mixed> $context */
function phase102Payment(array $context, string $amount, string $fundId, ?string $programId, string $date): \App\Models\FinancialV2\FinancialTransaction
{
    return app(FinancialTransactionLifecycleService::class)->createPayment([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['paymentType']->id,
        'business_date' => $date,
        'accounting_date' => $date,
        'gross_amount' => $amount,
        'source_reference' => 'PHASE102-PAY-'.Str::uuid(),
        'idempotency_key' => 'phase102-pay-'.Str::uuid(),
        'primary_financial_account_id' => $context['accountA']->id,
        'counterparty_id' => $context['supplier']->id,
        'category_id' => $context['paymentCategory']->id,
        'description' => 'Pengeluaran simulasi Phase 10.2',
    ], [[
        'account_id' => $context['expense']->id,
        'split_amount' => $amount,
        'fund_id' => $fundId,
        'program_id' => $programId,
    ]]);
}

/** @param array<string, mixed> $context */
function phase102Realization(array $context, Fund $fund, Program $program, string $versionId, string $amount, string $date): \App\Models\FinancialV2\FinancialTransaction
{
    return app(FinancialTransactionLifecycleService::class)->createRealization([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['paymentType']->id,
        'business_date' => $date,
        'accounting_date' => $date,
        'gross_amount' => $amount,
        'source_reference' => 'PHASE102-REAL-'.Str::uuid(),
        'idempotency_key' => 'phase102-real-'.Str::uuid(),
        'primary_financial_account_id' => $context['accountA']->id,
        'counterparty_id' => $context['supplier']->id,
        'category_id' => $context['paymentCategory']->id,
        'description' => 'Realisasi santunan bulanan Phase 10.2',
    ], [[
        'account_id' => $context['expense']->id,
        'split_amount' => $amount,
        'fund_id' => $fund->id,
        'program_id' => $program->id,
    ]], $versionId);
}

/** @param array<string, mixed> $context */
function phase102ApprovedAllocation(array $context, Fund $fund, Program $program, AccountingPeriod $period, string $effectiveFrom, string $reference): \App\Models\FinancialV2\BudgetAllocationVersion
{
    $service = app(BudgetAllocationService::class);
    $allocation = $service->create([
        'accounting_entity_id' => $context['entity']->id,
        'accounting_period_id' => $period->id,
        'fund_id' => $fund->id,
        'program_id' => $program->id,
        'account_id' => $context['expense']->id,
        'category_id' => $context['paymentCategory']->id,
        'allocation_reference' => $reference,
        'idempotency_key' => 'phase102-budget-'.Str::uuid(),
        'allocated_amount' => '10000000.00',
        'effective_from' => $effectiveFrom,
        'reason' => 'Rencana santunan 100 penerima @ Rp100.000',
    ]);
    $service->submit($allocation->id);

    return $service->approveVersion($allocation->id, $allocation->versions->sole()->id);
}

/** @param array<string, mixed> $context */
function phase102Fund(array $context, string $code, string $name): Fund
{
    return Fund::create([
        'accounting_entity_id' => $context['entity']->id,
        'fund_type_id' => $context['fundType']->id,
        'fund_restriction_id' => $context['restriction']->id,
        'code' => $code,
        'name' => $name,
        'purpose_statement' => 'Fixture bisnis Phase 10.2',
        'status' => 'active',
    ]);
}

/** @param array<string, mixed> $context */
function phase102Program(array $context, string $code, string $name, mixed $startDate = null, mixed $endDate = null): Program
{
    return Program::create([
        'accounting_entity_id' => $context['entity']->id,
        'code' => $code,
        'name' => $name,
        'start_date' => $startDate ?? now()->subDay()->toDateString(),
        'end_date' => $endDate ?? now()->addDay()->toDateString(),
        'status' => 'active',
    ]);
}

/** @param array<string, mixed> $context */
function phase102ConfigureBankToCashFixture(array $context): void
{
    PostingRuleLine::where('posting_rule_version_id', $context['receiptVersion']->id)->where('line_no', 1)->update(['account_id' => $context['cashB']->id]);
    PostingRuleLine::where('posting_rule_version_id', $context['treasuryVersion']->id)->where('line_no', 1)->update(['account_id' => $context['cashA']->id]);
    PostingRuleLine::where('posting_rule_version_id', $context['treasuryVersion']->id)->where('line_no', 2)->update(['account_id' => $context['cashB']->id]);
}

/** @param array<string, mixed> $context */
function phase102CashFinancialAccount(array $context, string $code, string $name): FinancialAccount
{
    $account = Account::create([
        'accounting_entity_id' => $context['entity']->id,
        'account_group_id' => $context['cashA']->account_group_id,
        'code' => $code,
        'name' => $name,
        'account_class' => 'asset',
        'normal_balance' => 'debit',
        'is_posting_account' => true,
        'is_liquidity_account' => true,
        'status' => 'active',
    ]);
    $financialAccount = FinancialAccount::create([
        'accounting_entity_id' => $context['entity']->id,
        'account_id' => $account->id,
        'code' => $code,
        'name' => $name,
        'account_type' => 'cash',
        'opening_date' => now()->subYear()->toDateString(),
        'status' => 'active',
    ]);
    CashAccountDetail::create([
        'financial_account_id' => $financialAccount->id,
        'cash_location' => 'Fixture '.$name,
        'cash_count_frequency' => 'daily',
    ]);

    return $financialAccount;
}
