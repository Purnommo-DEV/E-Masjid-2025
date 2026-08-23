<?php

use App\Domain\FinancialV2\FinancialTransactionLifecycleService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\Support\UatFinancialFixture;

/** @param array<string, mixed> $context */
function configurePublicZiswafDisclosure(array $context): void
{
    config()->set('financial_reporting.public_ziswaf', [
        'entity_code' => $context['entity']->code,
        'fund_codes' => [$context['fund']->code, $context['destinationFund']->code],
        'financial_account_codes' => [$context['accountA']->code, $context['accountB']->code],
    ]);
}

/** @param array<string, mixed> $context */
function publicZiswafIft(array $context, string $amount, array $overrides = []): void
{
    $transaction = app(FinancialTransactionLifecycleService::class)->createInterfundTransfer(array_replace([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['interfundType']->id,
        'business_date' => $context['today'],
        'accounting_date' => $context['today'],
        'gross_amount' => $amount,
        'source_reference' => 'PUBLIC-ZISWAF-IFT-'.Str::uuid(),
        'idempotency_key' => 'public-ziswaf-ift-'.Str::uuid(),
        'primary_financial_account_id' => $context['accountB']->id,
        'source_fund_id' => $context['fund']->id,
        'destination_fund_id' => $context['destinationFund']->id,
        'policy_basis_ref' => 'PUBLIC-REPORT-TEST',
        'reason' => 'Fund transfer is disclosed separately from receipt and payment.',
        'description' => 'Pemindahan Dana untuk laporan publik',
    ], $overrides));
    UatFinancialFixture::advance($transaction);
    UatFinancialFixture::post($transaction, 'public-ziswaf-ift-post-'.$transaction->id);
}

/** @param array<string, mixed> $context */
function publicZiswafTreasuryTransfer(array $context, string $amount): void
{
    $transaction = app(FinancialTransactionLifecycleService::class)->createTreasuryTransfer([
        'accounting_entity_id' => $context['entity']->id,
        'transaction_type_id' => $context['treasuryType']->id,
        'business_date' => $context['today'],
        'accounting_date' => $context['today'],
        'gross_amount' => $amount,
        'source_reference' => 'PUBLIC-ZISWAF-TRF-'.Str::uuid(),
        'idempotency_key' => 'public-ziswaf-trf-'.Str::uuid(),
        'source_financial_account_id' => $context['accountA']->id,
        'destination_financial_account_id' => $context['accountB']->id,
        'description' => 'Pemindahan kas internal untuk laporan publik',
    ], [[
        'account_id' => $context['cashA']->id,
        'split_amount' => $amount,
        'fund_id' => $context['fund']->id,
    ]]);
    UatFinancialFixture::advance($transaction);
    UatFinancialFixture::post($transaction, 'public-ziswaf-trf-post');
}

test('the public navigation places Laporan ZISWAF inside the Laporan submenu for desktop and mobile', function () {
    $navbar = file_get_contents(resource_path('views/masjid/mrj/guest/layouts/_navbar.blade.php'));

    expect($navbar)->not->toBeFalse()
        ->and(substr_count((string) $navbar, "route('public.ziswaf.index')"))->toBe(2)
        ->and(strpos((string) $navbar, 'MENU LAPORAN DROPDOWN (DESKTOP HOVER)'))->toBeLessThan(strpos((string) $navbar, 'Laporan ZISWAF'))
        ->and(strpos((string) $navbar, 'mobileLaporanSubmenu'))->toBeLessThan(strrpos((string) $navbar, 'Laporan ZISWAF'));
});

test('public ZISWAF routes are guest routes and reports use the default public guest shell', function () {
    $index = Route::getRoutes()->getByName('public.ziswaf.index');
    $pdf = Route::getRoutes()->getByName('public.ziswaf.pdf');
    $fund = Route::getRoutes()->getByName('public.ziswaf.fund');
    $reportView = file_get_contents(resource_path('views/masjid/mrj/guest/financial-v2/ziswaf-report.blade.php'));
    $fundView = file_get_contents(resource_path('views/masjid/mrj/guest/financial-v2/ziswaf-fund.blade.php'));
    $reportController = file_get_contents(app_path('Http/Controllers/FinancialV2/PublicZiswafReportController.php'));
    $guestLayout = file_get_contents(resource_path('views/masjid/master-guest.blade.php'));
    $navbar = file_get_contents(resource_path('views/masjid/mrj/guest/layouts/_navbar.blade.php'));

    expect($index)->not->toBeNull()
        ->and($fund)->not->toBeNull()
        ->and($pdf)->not->toBeNull()
        ->and($index->uri())->toBe('laporan-ziswaf')
        ->and($pdf->uri())->toBe('laporan-ziswaf/cetak')
        ->and($index->methods())->toContain('GET', 'HEAD')
        ->and($index->gatherMiddleware())->not->toContain('auth')
        ->and($fund->gatherMiddleware())->not->toContain('auth')
        ->and($reportView)->toContain("@extends('masjid.master-guest')")
        ->and($fundView)->toContain("@extends('masjid.master-guest')")
        ->and($reportController)->toContain('page_script')
        ->and($reportController)->toContain("'%s - Hal. %d/%d'")
        ->and($guestLayout)->toContain("@include(guest_layout('_navbar'))")
        ->and($guestLayout)->toContain("@include(guest_layout('_footer'))")
        ->and($navbar)->not->toContain('localhost')
        ->and($navbar)->not->toContain('127.0.0.1');
});

test('public ZISWAF report is read-only, ledger-backed, and separates Fund movement from account custody', function () {
    $context = UatFinancialFixture::context();
    configurePublicZiswafDisclosure($context);

    $receipt = UatFinancialFixture::receipt($context, '100.00');
    UatFinancialFixture::advance($receipt);
    UatFinancialFixture::post($receipt, 'public-ziswaf-receipt');

    $payment = UatFinancialFixture::payment($context, '25.00');
    UatFinancialFixture::advance($payment);
    UatFinancialFixture::post($payment, 'public-ziswaf-payment');

    publicZiswafTreasuryTransfer($context, '20.00');
    publicZiswafIft($context, '15.00');

    $response = $this->get(route('public.ziswaf.index'));
    $response->assertOk()
        ->assertSee('Laporan Dana ZISWAF')
        ->assertSee('id="mainNav"', false)
        ->assertSee('Harapan & Doa', false)
        ->assertSee('Laporan Idul Adha')
        ->assertSee('Transparansi')
        ->assertSee('Rp75,00')
        ->assertSee('Rp55,00')
        ->assertSee('Rp20,00')
        ->assertSee('Rp100,00')
        ->assertSee('Rp25,00')
        ->assertSee('Keluar Rp15,00')
        ->assertSee('Rp60,00')
        ->assertSee('Rp15,00')
        ->assertSee('Cetak laporan PDF')
        ->assertSee('name="from"', false)
        ->assertSee('name="to"', false)
        ->assertSee(route('public.ziswaf.pdf', ['from' => $context['today'], 'to' => $context['today']]))
        ->assertSee('@media print', false)
        ->assertDontSee('Tambah transaksi')
        ->assertDontSee('Hapus')
        ->assertDontSee('Journal')
        ->assertDontSee($receipt->id);

    $this->get(route('public.ziswaf.fund', ['fundCode' => $context['fund']->code]))
        ->assertOk()
        ->assertSee('Pemasukan, Pengeluaran &amp; Pemindahan Dana', false)
        ->assertSee('Synthetic UAT receipt')
        ->assertSee('Synthetic UAT payment')
        ->assertSee('Pemindahan Dana antar peruntukan')
        ->assertSee('Pemindahan dana antar peruntukan')
        ->assertDontSee($receipt->id)
        ->assertDontSee('Journal');
});

test('public ZISWAF report recalculates for the selected as-of date and does not expose unlisted funds', function () {
    $context = UatFinancialFixture::context();
    configurePublicZiswafDisclosure($context);

    $unlisted = UatFinancialFixture::restrictedFund($context, 'PRIVATE', 'Dana Tidak Dipublikasikan');
    $receipt = UatFinancialFixture::receipt($context, '100.00', $context['fund']->id);
    UatFinancialFixture::advance($receipt);
    UatFinancialFixture::post($receipt, 'public-ziswaf-selected-date');

    $unlistedReceipt = UatFinancialFixture::receipt($context, '55.00', $unlisted->id);
    UatFinancialFixture::advance($unlistedReceipt);
    UatFinancialFixture::post($unlistedReceipt, 'public-ziswaf-unlisted');

    $past = now()->subDay()->toDateString();
    $this->get(route('public.ziswaf.index', ['from' => $past, 'to' => $past]))
        ->assertOk()
        ->assertSee($past)
        ->assertSee('Rp0,00')
        ->assertDontSee('Dana Tidak Dipublikasikan');

    $this->get(route('public.ziswaf.index'))
        ->assertOk()
        ->assertSee('Rp100,00')
        ->assertDontSee('Rp155,00')
        ->assertDontSee('Dana Tidak Dipublikasikan');

    $this->get(route('public.ziswaf.fund', ['fundCode' => $unlisted->code]))->assertNotFound();
});

test('public ZISWAF disclosure hides the superseded Cash Tromol IFT and lists only the real Fund reclassification', function () {
    $context = UatFinancialFixture::context();
    configurePublicZiswafDisclosure($context);

    $receipt = UatFinancialFixture::receipt($context, '5000000.00');
    UatFinancialFixture::advance($receipt);
    UatFinancialFixture::post($receipt, 'public-ziswaf-transfer-regression-receipt');
    publicZiswafTreasuryTransfer($context, '4000000.00');

    publicZiswafIft($context, '2653000.00', [
        'source_reference' => 'MRJ-P12.5-CASH-ATTRIBUTION-2026-08-16',
        'idempotency_key' => 'public-ziswaf-cash-attribution-regression',
        'description' => 'Historical Fund Attribution Correction: Cash Tromol Yatim.',
    ]);
    publicZiswafIft($context, '1200000.00', [
        'source_reference' => 'MRJ-P12.5-FUND-RECLASS-2026-08-16',
        'idempotency_key' => 'public-ziswaf-fund-reclassification-regression',
        'description' => 'Historical Fund Reclassification: Dana Infaq & Tromol ke Dana Dhuafa & Anak Yatim.',
    ]);

    $report = app(\App\Domain\FinancialV2\Reporting\PublicZiswafReportService::class)->pdfReport($context['today'], $context['today']);
    $transfers = collect($report['fund_transfers'])->keyBy('event_id');
    $infaq = collect($report['fund_details'])->firstWhere('code', $context['fund']->code);
    $infaqTransfers = collect($infaq['official_entries'])->where('kind', 'transfer')->pluck('amount')->values()->all();
    $html = view('masjid.mrj.guest.financial-v2.ziswaf-report-pdf', ['report' => $report])->render();
    expect($transfers)->toHaveCount(1)
        ->and($transfers->has('ift:MRJ-P12.5-CASH-ATTRIBUTION-2026-08-16'))->toBeFalse()
        ->and($transfers->get('ift:MRJ-P12.5-FUND-RECLASS-2026-08-16')['amount'])->toBe('1200000.00')
        ->and($infaqTransfers)->toBe(['1200000.00'])
        ->and($html)->toContain('Rp1.200.000,00')
        ->and($html)->not->toContain('Historical Fund Attribution Correction')
        ->and($html)->not->toContain('Rp5.306.000,00', 'Rp2.400.000,00');

    $this->get(route('public.ziswaf.index', ['from' => $context['today'], 'to' => $context['today']]))
        ->assertOk()
        ->assertSee('Rp1.200.000,00')
        ->assertDontSee('Historical Fund Attribution Correction')
        ->assertDontSee('Rp5.306.000,00')
        ->assertDontSee('Rp2.400.000,00');
});

test('public ZISWAF PDF is read-only, uses the public reporting payload, and contains professional disclosure sections', function () {
    $context = UatFinancialFixture::context();
    configurePublicZiswafDisclosure($context);

    $receipt = UatFinancialFixture::receipt($context, '100.00');
    UatFinancialFixture::advance($receipt);
    UatFinancialFixture::post($receipt, 'public-ziswaf-pdf-receipt');
    $payment = UatFinancialFixture::payment($context, '25.00');
    UatFinancialFixture::advance($payment);
    UatFinancialFixture::post($payment, 'public-ziswaf-pdf-payment');
    publicZiswafTreasuryTransfer($context, '20.00');
    publicZiswafIft($context, '15.00');

    $report = app(\App\Domain\FinancialV2\Reporting\PublicZiswafReportService::class)->pdfReport($context['today'], $context['today']);
    $html = view('masjid.mrj.guest.financial-v2.ziswaf-report-pdf', [
        'report' => $report,
        'logoDataUri' => null,
        'generatedAt' => now(),
    ])->render();
    $response = $this->get(route('public.ziswaf.pdf', ['from' => $context['today'], 'to' => $context['today']]));

    expect($html)->toContain('LAPORAN DANA ZISWAF')
        ->and($html)->toContain('Ringkasan Saldo')
        ->and($html)->toContain('Rekap Dana')
        ->and($html)->toContain('Pemindahan Dana')
        ->and($html)->toContain('Mutasi resmi Financial V2')
        ->and($html)->toContain('Synthetic UAT receipt')
        ->and($html)->toContain('Synthetic UAT payment')
        ->and($html)->not->toContain($receipt->id)
        ->and($html)->not->toContain('Journal ID')
        ->and($html)->not->toContain('Ledger ID');
    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertHeader('content-disposition', 'attachment; filename=laporan-dana-ziswaf-'.$context['today'].'.pdf');
    expect($response->getContent())->toStartWith('%PDF');
});
