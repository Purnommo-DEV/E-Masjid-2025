<?php

use Tests\Support\UatFinancialFixture;

test('internal ZISWAF dashboard exchanges a strong token for a private read-only session', function () {
    $context = UatFinancialFixture::context();
    $token = 'Abc234Xyz9';
    config()->set('financial_reporting.internal_ziswaf_dashboard.token_hash', hash('sha256', $token));
    config()->set('financial_reporting.internal_ziswaf_dashboard.entity_id', $context['entity']->id);
    config()->set('financial_reporting.public_ziswaf.fund_codes', [$context['fund']->code]);

    $this->get(route('internal.ziswaf.dashboard'))->assertNotFound();
    $this->get(route('internal.ziswaf.access', ['token' => 'BadCode23']))->assertNotFound();

    $response = $this->get(route('internal.ziswaf.access', ['token' => $token]));
    $response->assertRedirect(route('internal.ziswaf.dashboard'));
    expect($response->headers->get('location'))->not->toContain($token);

    $this->get(route('internal.ziswaf.dashboard', ['from' => $context['today'], 'through' => $context['today']]))
        ->assertOk()
        ->assertHeader('cache-control', 'max-age=0, no-store, private')
        ->assertHeader('referrer-policy', 'no-referrer')
        ->assertSee('Ringkasan ZISWAF')
        ->assertSee('Rincian Pemasukan &amp; Pengeluaran Per Dana', false)
        ->assertSee(route('internal.ziswaf.dashboard', ['from' => $context['today'], 'through' => $context['today'], 'fund_id' => $context['fund']->id]).'#transaksi')
        ->assertSee('read-only');
});

test('internal per-Fund cards use the exact canonical public ZISWAF scope and amounts', function () {
    $context = UatFinancialFixture::context();
    $excluded = UatFinancialFixture::restrictedFund($context, 'RAMADHAN', 'Dana Ramadhan');
    config()->set('financial_reporting.public_ziswaf.entity_code', $context['entity']->code);
    config()->set('financial_reporting.public_ziswaf.fund_codes', [
        $context['fund']->code,
        $context['destinationFund']->code,
        $context['fund']->code,
    ]);
    config()->set('financial_reporting.public_ziswaf.financial_account_codes', [$context['accountA']->code]);
    config()->set('financial_reporting.internal_ziswaf_dashboard.entity_id', $context['entity']->id);
    config()->set('financial_reporting.internal_ziswaf_dashboard.token_hash', hash('sha256', 'Scope234Ab'));

    $canonicalReceipt = UatFinancialFixture::receipt($context, '1000.00', $context['fund']->id);
    $canonicalExpense = UatFinancialFixture::payment($context, '200.00', $context['fund']->id);
    $secondCanonicalReceipt = UatFinancialFixture::receipt($context, '500.00', $context['destinationFund']->id);
    $excludedReceipt = UatFinancialFixture::receipt($context, '999.00', $excluded->id);
    foreach ([
        [$canonicalReceipt, 'canonical-receipt'],
        [$canonicalExpense, 'canonical-expense'],
        [$secondCanonicalReceipt, 'second-canonical-receipt'],
        [$excludedReceipt, 'excluded-receipt'],
    ] as [$transaction, $key]) {
        UatFinancialFixture::advance($transaction);
        UatFinancialFixture::post($transaction, $key.'-'.$transaction->id);
    }

    $this->get(route('internal.ziswaf.access', ['token' => 'Scope234Ab']))->assertRedirect();
    $parameters = ['from' => $context['today'], 'through' => $context['today']];
    $response = $this->get(route('internal.ziswaf.dashboard', $parameters))->assertOk();
    $internalFunds = collect($response->viewData('report')['funds'])->keyBy('code');
    $publicFunds = collect(app(\App\Domain\FinancialV2\Reporting\PublicZiswafReportService::class)
        ->report($context['today'], $context['today'])['funds'])->keyBy('code');
    $publicPosition = app(\App\Domain\FinancialV2\Reporting\PublicZiswafReportService::class)
        ->report($context['today'], $context['today']);
    $internalPosition = $response->viewData('positionReport');

    expect($internalFunds->keys()->all())->toBe($publicFunds->keys()->all())
        ->and($internalFunds->keys()->all())->toHaveCount(2)
        ->and($internalFunds->has($excluded->code))->toBeFalse()
        ->and($internalPosition['total_fund_balance'])->toBe($publicPosition['total_fund_balance'])
        ->and($internalPosition['financial_accounts'])->toBe($publicPosition['financial_accounts']);
    foreach ($publicFunds as $code => $publicFund) {
        expect($internalFunds[$code]['receipts'])->toBe($publicFund['receipts'])
            ->and($internalFunds[$code]['expenses'])->toBe($publicFund['expenses'])
            ->and($internalFunds[$code]['fund_balance'])->toBe($publicFund['balance']);
    }

    $response->assertDontSee($excluded->name)
        ->assertSee('Total Dana ZISWAF')
        ->assertSee('Uang tersimpan di')
        ->assertDontSee('Saldo Akhir Periode')
        ->assertDontSee('Saldo Terkini')
        ->assertSee(route('internal.ziswaf.dashboard', $parameters + ['fund_id' => $context['fund']->id]).'#transaksi');

    $filtered = $this->get(route('internal.ziswaf.dashboard', $parameters + ['fund_id' => $context['fund']->id]))->assertOk();
    expect(collect($filtered->viewData('report')['funds'])->pluck('fund_id')->all())->toBe([$context['fund']->id])
        ->and(collect($filtered->viewData('report')['transactions'])->every(
            fn (array $transaction): bool => $transaction['fund'] === $context['fund']->name
        ))->toBeTrue();

    $excludedFilter = $this->get(route('internal.ziswaf.dashboard', $parameters + ['fund_id' => $excluded->id]))->assertOk();
    expect($excludedFilter->viewData('report')['funds'])->toBe([])
        ->and($excludedFilter->viewData('report')['transactions'])->toBe([]);
});

test('rotating the configured token hash revokes an existing dashboard session', function () {
    $context = UatFinancialFixture::context();
    $token = 'Rev234Code';
    config()->set('financial_reporting.internal_ziswaf_dashboard.token_hash', hash('sha256', $token));
    config()->set('financial_reporting.internal_ziswaf_dashboard.entity_id', $context['entity']->id);

    $this->get(route('internal.ziswaf.access', ['token' => $token]))->assertRedirect();
    config()->set('financial_reporting.internal_ziswaf_dashboard.token_hash', hash('sha256', 'replacement-token-value-with-adequate-entropy'));

    $this->get(route('internal.ziswaf.dashboard'))->assertNotFound();
});

test('internal ZISWAF transaction detail cannot cross the configured entity boundary', function () {
    $context = UatFinancialFixture::context();
    $token = 'Ent234Code';
    config()->set('financial_reporting.internal_ziswaf_dashboard.token_hash', hash('sha256', $token));
    config()->set('financial_reporting.internal_ziswaf_dashboard.entity_id', $context['entity']->id);
    $this->get(route('internal.ziswaf.access', ['token' => $token]))->assertRedirect();

    $other = $context['entity']->replicate();
    $other->id = (string) \Illuminate\Support\Str::uuid();
    $other->code = 'OTHER-'.substr(md5((string) microtime()), 0, 8);
    $other->name = 'Other Entity';
    $other->save();
    $transaction = UatFinancialFixture::receipt($context, '10.00', $context['fund']->id);
    \Illuminate\Support\Facades\DB::table('financial_v2_transactions')->where('id', $transaction->id)->update(['accounting_entity_id' => $other->id]);

    $this->get(route('internal.ziswaf.transactions.show', $transaction))->assertNotFound();
});

test('distribution detail is scoped to one realization and exposes paginated snapshot recipients', function () {
    $context = UatFinancialFixture::context();
    $token = 'Dst234Code';
    config()->set('financial_reporting.internal_ziswaf_dashboard.token_hash', hash('sha256', $token));
    config()->set('financial_reporting.internal_ziswaf_dashboard.entity_id', $context['entity']->id);
    $this->get(route('internal.ziswaf.access', ['token' => $token]))->assertRedirect();

    $service = app(\App\Domain\FinancialV2\DistributionService::class);
    $first = $service->create($context['entity']->id, [
        'program_id' => $context['program']->id, 'title' => 'Santunan Oktober', 'period_label' => 'Oktober 2026',
        'starts_on' => $context['today'], 'ends_on' => $context['today'],
    ], null);
    $service->item($context['entity']->id, $first->id, [
        'revision' => 0, 'display_name' => 'Penerima Khusus Oktober', 'beneficiary_type' => 'DHUAFA',
        'rt' => '03', 'rw' => '04', 'rt_coordinator_name' => 'Koordinator Aman', 'amount' => '125000.00', 'notes' => 'Paket bantuan',
    ], null, false, null);
    $service->item($context['entity']->id, $first->id, [
        'revision' => 1, 'display_name' => 'Penerima Wilayah Kedua', 'beneficiary_type' => 'DHUAFA',
        'rt' => '05', 'rw' => '04', 'rt_coordinator_name' => 'Koordinator Aman', 'amount' => '75000.00',
    ], null, false, null);

    $otherProgram = $context['program']->replicate();
    $otherProgram->id = (string) \Illuminate\Support\Str::uuid();
    $otherProgram->code = 'OTHER-'.substr(md5((string) microtime()), 0, 8);
    $otherProgram->name = 'Program Lain';
    $otherProgram->save();
    $second = $service->create($context['entity']->id, [
        'program_id' => $otherProgram->id, 'title' => 'Penyaluran Lain', 'period_label' => 'Oktober 2026',
        'starts_on' => $context['today'], 'ends_on' => $context['today'],
    ], null);
    $service->item($context['entity']->id, $second->id, [
        'revision' => 0, 'display_name' => 'Tidak Boleh Tercampur', 'amount' => '999999.00',
    ], null, false, null);

    $this->get(route('internal.ziswaf.dashboard', ['from' => $context['today'], 'through' => $context['today']]))
        ->assertOk()->assertSee('Penyaluran dan Penerima Manfaat')->assertSee('Rincian penyaluran')
        ->assertSee('Pengeluaran Aktual')->assertDontSee('Penerimaan per Pos')->assertDontSee('Perlu tindak lanjut');
    $this->get(route('internal.ziswaf.distributions.show', $first))
        ->assertOk()->assertSee('Penerima Khusus Oktober')->assertSee('Penerima Wilayah Kedua')
        ->assertSee('RT 03 / RW 04')->assertSee('RT 05 / RW 04')->assertSee('Koordinator: Koordinator Aman')
        ->assertSee('Rp125.000')->assertSee('Subtotal Rp75.000')->assertDontSee('Tidak Boleh Tercampur');
    $this->get(route('internal.ziswaf.distributions.show', [$first, 'q' => 'nama-yang-tidak-ada']))
        ->assertOk()->assertSee('Tidak ada penerima yang sesuai');
});

test('short access code endpoint is rate limited against repeated guesses', function () {
    config()->set('financial_reporting.internal_ziswaf_dashboard.token_hash', hash('sha256', 'Good234Abc'));

    foreach (range(1, 5) as $attempt) {
        $this->get(route('internal.ziswaf.access', ['token' => 'Bad234Abc']))->assertNotFound();
    }
    $this->get(route('internal.ziswaf.access', ['token' => 'Bad234Abc']))->assertTooManyRequests();
});

test('access code generator emits a non ambiguous short code and only its hash for storage', function () {
    \Illuminate\Support\Facades\Artisan::call('financial-v2:generate-internal-ziswaf-code');
    $output = \Illuminate\Support\Facades\Artisan::output();

    preg_match('/Kode akses: ([A-HJ-NP-Za-km-z2-9]{10})/', $output, $match);
    expect($match)->toHaveCount(2)
        ->and($output)->toContain(hash('sha256', $match[1]))
        ->and($match[1])->not->toMatch('/[O0Il1]/');
});
