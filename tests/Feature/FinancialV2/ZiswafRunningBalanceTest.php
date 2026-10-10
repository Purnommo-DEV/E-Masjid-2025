<?php

use App\Domain\FinancialV2\FinancialTransactionLifecycleService;
use App\Domain\FinancialV2\Reporting\ZiswafReportingV2Service;
use App\Domain\FinancialV2\Reporting\ZiswafRunningBalanceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\UatFinancialFixture;

test('running balance uses canonical posted Fund movements and remains stable across filters and pagination', function () {
    $context = UatFinancialFixture::context();
    $from = $context['today'];
    $openingDate = now()->subDay()->toDateString();
    config()->set('financial_reporting.public_ziswaf.entity_code', $context['entity']->code);
    config()->set('financial_reporting.public_ziswaf.fund_codes', [$context['fund']->code, $context['destinationFund']->code]);
    config()->set('financial_reporting.public_ziswaf.financial_account_codes', [$context['accountA']->code, $context['accountB']->code]);
    config()->set('financial_reporting.internal_ziswaf_dashboard.entity_id', $context['entity']->id);
    config()->set('financial_reporting.internal_ziswaf_dashboard.token_hash', hash('sha256', 'Run234Code'));

    $post = static function ($transaction, string $key): void {
        UatFinancialFixture::advance($transaction);
        UatFinancialFixture::post($transaction, $key.'-'.$transaction->id);
    };

    $opening = UatFinancialFixture::receipt($context, '100.00', $context['fund']->id);
    DB::table('financial_v2_transactions')->where('id', $opening->id)->update([
        'business_date' => $openingDate,
        'accounting_date' => $openingDate,
    ]);
    $post($opening->fresh(), 'running-opening');

    foreach (range(1, 16) as $sequence) {
        $post(UatFinancialFixture::receipt($context, '10.00', $context['fund']->id), 'running-receipt-'.$sequence);
    }
    $post(UatFinancialFixture::payment($context, '20.00', $context['fund']->id), 'running-payment');

    $treasury = app(FinancialTransactionLifecycleService::class)->createTreasuryTransfer([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['treasuryType']->id,
        'business_date' => $from,
        'accounting_date' => $from,
        'gross_amount' => '10.00',
        'source_reference' => 'RUN-TRF-'.Str::uuid(),
        'idempotency_key' => 'run-trf-'.Str::uuid(),
        'source_financial_account_id' => $context['accountA']->id,
        'destination_financial_account_id' => $context['accountB']->id,
        'description' => 'Transfer kas internal',
    ], [[
        'account_id' => $context['cashA']->id,
        'split_amount' => '10.00',
        'fund_id' => $context['fund']->id,
    ]]);
    $post($treasury, 'running-treasury');

    $interfund = app(FinancialTransactionLifecycleService::class)->createInterfundTransfer([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['interfundType']->id,
        'business_date' => $from,
        'accounting_date' => $from,
        'gross_amount' => '25.00',
        'source_reference' => 'RUN-IFT-'.Str::uuid(),
        'idempotency_key' => 'run-ift-'.Str::uuid(),
        'primary_financial_account_id' => $context['accountA']->id,
        'source_fund_id' => $context['fund']->id,
        'destination_fund_id' => $context['destinationFund']->id,
        'policy_basis_ref' => 'RUNNING-BALANCE-TEST',
        'reason' => 'Uji saldo berjalan',
        'description' => 'Pemindahan antar dana',
    ]);
    $post($interfund, 'running-interfund');

    $fundIds = [$context['fund']->id, $context['destinationFund']->id];
    $service = app(ZiswafRunningBalanceService::class);
    $consolidated = $service->report($context['entity'], $from, $from, $fundIds);
    $official = app(ZiswafReportingV2Service::class)->report($context['entity'], $from, $from, $fundIds);
    $rows = collect($consolidated['rows']);

    expect($consolidated['opening_balance'])->toBe('100.00')
        ->and($rows->pluck('posting_sequence')->all())->toBe($rows->pluck('posting_sequence')->sort()->values()->all())
        ->and($rows->where('type', 'TRF')->sole()['delta'])->toBe('0.00')
        ->and($rows->where('type', 'IFT')->sole()['delta'])->toBe('0.00')
        ->and($consolidated['closing_balance'])->toBe('240.00')
        ->and($consolidated['closing_balance'])->toBe($official['summary']['closing_balance']);

    $sourceFund = $service->report($context['entity'], $from, $from, [$context['fund']->id]);
    expect($sourceFund['opening_balance'])->toBe('100.00')
        ->and(collect($sourceFund['rows'])->where('type', 'IFT')->sole()['out'])->toBe('25.00')
        ->and($sourceFund['closing_balance'])->toBe('215.00');

    $receiptsOnly = $service->report($context['entity'], $from, $from, $fundIds, 'RCV');
    expect($receiptsOnly['rows'])->toHaveCount(16)
        ->and($receiptsOnly['closing_balance'])->toBe('240.00');

    $this->get(route('internal.ziswaf.access', ['token' => 'Run234Code']))->assertRedirect();
    $pageTwo = $this->get(route('internal.ziswaf.dashboard', [
        'from' => $from,
        'through' => $from,
        'balance_page' => 2,
    ]))->assertOk();
    $paginator = $pageTwo->viewData('runningBalanceRows');
    expect($paginator->currentPage())->toBe(2)
        ->and($paginator->total())->toBe(19)
        ->and($paginator->items()[0]['running_balance'])->toBe('260.00')
        ->and($pageTwo->viewData('runningBalance')['closing_balance'])->toBe('240.00');

    $empty = $service->report($context['entity'], now()->addDay()->toDateString(), now()->addDay()->toDateString(), $fundIds);
    expect($empty['rows'])->toBe([])
        ->and($empty['opening_balance'])->toBe('240.00')
        ->and($empty['closing_balance'])->toBe('240.00');
});
