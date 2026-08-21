<?php

use App\Domain\FinancialV2\MrjZiswafOpeningPosition;
use App\Domain\FinancialV2\Reporting\FinancialReportService;
use App\Domain\FinancialV2\Reporting\FundHistoryReadService;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\AuditEvent;
use App\Models\FinancialV2\BudgetAllocation;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\HistoricalFundHistory;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\JournalLine;
use App\Models\FinancialV2\LedgerEntry;
use App\Models\FinancialV2\OpeningBalanceBatch;
use Illuminate\Support\Facades\Storage;

/** @return array{0:string,1:string} */
function mrjZiswafFixtureFiles(): array
{
    $directory = storage_path('framework/testing/mrj-ziswaf-opening');
    if (! is_dir($directory)) {
        mkdir($directory, 0755, true);
    }

    $source = $directory.'/ZISWAF UPDATE 3.xlsx';
    $evidence = $directory.'/mrj-ziswaf-source-evidence.pdf';
    file_put_contents($source, 'MRJ ZISWAF test-only source fixture');
    file_put_contents($evidence, "%PDF-1.4\n% MRJ ZISWAF test-only evidence\n");

    return [$source, $evidence];
}

test('actual MRJ ZISWAF opening onboarding is source-reconciled, canonical, and idempotent in the isolated test database', function () {
    Storage::fake('local');
    [$source, $evidence] = mrjZiswafFixtureFiles();
    $sample = AccountingEntity::query()->create([
        'code' => 'MRJ-SAMPLE-QA',
        'name' => 'MRJ Sample QA',
        'legal_name' => 'MRJ Sample QA',
        'functional_currency' => 'IDR',
        'timezone' => 'Asia/Jakarta',
        'fiscal_year_start_month' => 1,
        'status' => 'active',
    ]);

    $this->artisan('financial-v2:onboard-mrj-ziswaf', [
        'source' => $source,
        'evidence' => $evidence,
        '--allow-testing' => true,
    ])->assertExitCode(0);

    $this->artisan('financial-v2:sync-mrj-ziswaf-history', [
        '--allow-testing' => true,
    ])->assertExitCode(0);

    $entity = AccountingEntity::query()->where('code', 'MRJ-ACTUAL')->firstOrFail();
    $batch = OpeningBalanceBatch::query()
        ->where('accounting_entity_id', $entity->id)
        ->where('cutover_reference', 'MRJ-ZISWAF-OPENING-2026-06-27-V1')
        ->firstOrFail();
    $reports = app(FinancialReportService::class);
    $fundRows = collect($reports->report('fund-balance', $entity->id, '2026-01-01', MrjZiswafOpeningPosition::AS_OF_DATE)['data']['rows'])->keyBy('code');
    $accountRows = collect($reports->report('account-balance', $entity->id, '2026-01-01', MrjZiswafOpeningPosition::AS_OF_DATE)['data']['rows'])->keyBy('code');

    expect($batch->status)->toBe('posted')
        ->and(Journal::whereKey($batch->journal_id)->sole()->total_debit)->toBe(MrjZiswafOpeningPosition::TOTAL)
        ->and(Journal::whereKey($batch->journal_id)->sole()->total_credit)->toBe(MrjZiswafOpeningPosition::TOTAL)
        ->and(JournalLine::where('journal_id', $batch->journal_id)->count())->toBe(13)
        ->and(LedgerEntry::where('accounting_entity_id', $entity->id)->count())->toBe(13)
        ->and(FinancialTransaction::where('accounting_entity_id', $entity->id)->count())->toBe(1)
        ->and($fundRows->map(fn (array $row) => $row['fund_balance'])->sum())->toBe((float) MrjZiswafOpeningPosition::TOTAL)
        ->and($fundRows->get('ZAKAT-MAAL')['fund_balance'])->toBe('75745386.00')
        ->and($fundRows->get('INFAQ-TROMOL')['fund_balance'])->toBe('19319949.00')
        ->and($fundRows->get('SODAQOH')['fund_balance'])->toBe('6906000.00')
        ->and($fundRows->get('SANTUNAN-YATIM')['fund_balance'])->toBe('6600000.00')
        ->and($fundRows->get('FIDYAH')['fund_balance'])->toBe('7500000.00')
        ->and($fundRows->get('DHUAFA')['fund_balance'])->toBe('9658977.00')
        ->and($accountRows->get('BNI-ZISWAF')['closing_balance'])->toBe(MrjZiswafOpeningPosition::BNI_TOTAL)
        ->and($accountRows->get('CASH-ZISWAF')['closing_balance'])->toBe(MrjZiswafOpeningPosition::CASH_TOTAL)
        ->and($reports->report('trial-balance', $entity->id, '2026-01-01', MrjZiswafOpeningPosition::AS_OF_DATE)['data']['is_balanced'])->toBeTrue();

    $sourceHistory = app(FundHistoryReadService::class);
    $sodaqoh = Fund::query()->where('accounting_entity_id', $entity->id)->where('code', 'SODAQOH')->sole();
    $sodaqohHistory = $sourceHistory->history($entity, $sodaqoh, ['from' => '2026-01-01', 'through' => MrjZiswafOpeningPosition::AS_OF_DATE])['source_history'];
    $infaq = Fund::query()->where('accounting_entity_id', $entity->id)->where('code', 'INFAQ-TROMOL')->sole();
    $infaqHistory = $sourceHistory->history($entity, $infaq, ['from' => '2026-01-01', 'through' => MrjZiswafOpeningPosition::AS_OF_DATE])['source_history'];

    expect($sodaqohHistory['rows'])->toHaveCount(2)
        ->and($sodaqohHistory['rows'][0])->toMatchArray(['date_label' => 'Maret 2026', 'description' => 'Penerimaan Ramadhan 1447 H - Sodaqoh', 'receipt' => '8506000.00', 'running_balance' => '8506000.00', 'source_reference' => 'Buku Kas Detail!A9:F9'])
        ->and($sodaqohHistory['rows'][1])->toMatchArray(['description' => 'Beras 20 Pack', 'usage' => '1600000.00', 'running_balance' => '6906000.00', 'source_reference' => 'Buku Kas Detail!A24:F24'])
        ->and($sodaqohHistory['difference'])->toBe('0.00')
        ->and($infaqHistory['activity_balance'])->toBe('16666949.00')
        ->and($infaqHistory['account_positions'][0])->toMatchArray(['description' => 'Cash Tromol Yatim', 'amount' => '2653000.00'])
        ->and($infaqHistory['opening_source_balance'])->toBe('16666949.00')
        ->and($infaqHistory['source_fund_balance'])->toBe('16666949.00')
        ->and($infaqHistory['reconciled_balance'])->toBe('16666949.00')
        ->and($infaqHistory['difference'])->toBe('0.00')
        ->and(BudgetAllocation::query()->where('accounting_entity_id', $entity->id)->count())->toBe(0);

    $user = \App\Models\User::factory()->create();
    $sodaqohPage = $this->actingAs($user)->get(route('financial-v2.funds.show', ['fund' => $sodaqoh, 'entity' => $entity->id]))
        ->assertOk()
        ->assertSee('Riwayat Penggunaan Dana')
        ->assertSee('Beras 20 Pack')
        ->assertSee('Buku Kas Detail!A24:F24')
        ->assertSee('Edit/Koreksi')
        ->assertSee('Riwayat Transaksi V2')
        ->assertSee('Riwayat Alokasi dan realisasi')
        ->assertDontSee('General Ledger');
    $sodaqohPage->assertSee('<div class="mt-4 overflow-x-auto">', false);

    $zakat = Fund::query()->where('accounting_entity_id', $entity->id)->where('code', 'ZAKAT-MAAL')->sole();
    $dhuafa = Fund::query()->where('accounting_entity_id', $entity->id)->where('code', 'DHUAFA')->sole();
    foreach ([
        [$zakat, ['Penerimaan Ramadhan 1447 H - Zakat Maal', 'Penyaluran Zakat Maal April', 'Penyaluran Zakat Maal Mei', 'Buku Kas Detail!A7:F7']],
        [$dhuafa, ['Saldo Awal Buku', 'Beasiswa Fauzan SMP AL Madina', 'SPP Mei-Juli 2026', 'Beasiswa SMP AL Madina', 'Buku Kas Detail!A19:F19']],
        [$infaq, ['Cash Tromol 10 Desember 2025', 'Penerimaan Ramadhan 1447 H - Infaq', 'Rekonsiliasi berupa admin Bank', 'Buku Kas Detail!A3:F3']],
    ] as [$fundForPage, $expectedText]) {
        $response = $this->actingAs($user)->get(route('financial-v2.funds.show', ['fund' => $fundForPage, 'entity' => $entity->id]))
            ->assertOk()
            ->assertSee('Riwayat Penggunaan Dana');

        foreach ($expectedText as $text) {
            $response->assertSee($text);
        }
    }

    $factsBeforeReplay = [
        Journal::where('accounting_entity_id', $entity->id)->count(),
        JournalLine::where('accounting_entity_id', $entity->id)->count(),
        LedgerEntry::where('accounting_entity_id', $entity->id)->count(),
        FinancialTransaction::where('accounting_entity_id', $entity->id)->count(),
    ];

    $this->artisan('financial-v2:onboard-mrj-ziswaf', [
        'source' => $source,
        'evidence' => $evidence,
        '--allow-testing' => true,
        '--purge-sample-qa' => true,
    ])->assertExitCode(0);

    expect([
        Journal::where('accounting_entity_id', $entity->id)->count(),
        JournalLine::where('accounting_entity_id', $entity->id)->count(),
        LedgerEntry::where('accounting_entity_id', $entity->id)->count(),
        FinancialTransaction::where('accounting_entity_id', $entity->id)->count(),
    ])->toBe($factsBeforeReplay)
        ->and(AccountingEntity::find($sample->id))->toBeNull();

    $this->artisan('financial-v2:onboard-mrj-ziswaf', [
        'source' => $source,
        'evidence' => $evidence,
        '--allow-testing' => true,
    ])->assertExitCode(0);

    expect([
        Journal::where('accounting_entity_id', $entity->id)->count(),
        JournalLine::where('accounting_entity_id', $entity->id)->count(),
        LedgerEntry::where('accounting_entity_id', $entity->id)->count(),
        FinancialTransaction::where('accounting_entity_id', $entity->id)->count(),
    ])->toBe($factsBeforeReplay);
});

test('actual MRJ ZISWAF source history can be corrected with lineage and audit without changing accounting facts', function () {
    Storage::fake('local');
    [$source, $evidence] = mrjZiswafFixtureFiles();
    $this->artisan('financial-v2:onboard-mrj-ziswaf', [
        'source' => $source,
        'evidence' => $evidence,
        '--allow-testing' => true,
    ])->assertExitCode(0);
    $this->artisan('financial-v2:sync-mrj-ziswaf-history', [
        '--allow-testing' => true,
    ])->assertExitCode(0);

    $entity = AccountingEntity::query()->where('code', 'MRJ-ACTUAL')->sole();
    $sodaqoh = Fund::query()->where('accounting_entity_id', $entity->id)->where('code', 'SODAQOH')->sole();
    $beras = HistoricalFundHistory::query()
        ->where('accounting_entity_id', $entity->id)
        ->where('fund_id', $sodaqoh->id)
        ->where('description', 'Beras 20 Pack')
        ->sole();
    $user = \App\Models\User::factory()->create();
    $factsBefore = [
        Journal::where('accounting_entity_id', $entity->id)->count(),
        JournalLine::where('accounting_entity_id', $entity->id)->count(),
        LedgerEntry::where('accounting_entity_id', $entity->id)->count(),
        FinancialTransaction::where('accounting_entity_id', $entity->id)->count(),
    ];

    $this->actingAs($user)->get(route('financial-v2.funds.history.edit', ['fund' => $sodaqoh, 'history' => $beras, 'entity' => $entity->id]))
        ->assertOk()
        ->assertSee('Koreksi riwayat Dana')
        ->assertSee('Beras 20 Pack')
        ->assertSee('Lineage asli');
    $this->actingAs($user)->put(route('financial-v2.funds.history.update', ['fund' => $sodaqoh, 'history' => $beras, 'entity' => $entity->id]), [
        'entity' => $entity->id,
        'fund_id' => $sodaqoh->id,
        'effective_date' => $beras->effective_date?->toDateString(),
        'date_label' => $beras->date_label,
        'entry_kind' => 'usage',
        'description' => 'Beras 20 Pack',
        'notes' => $beras->notes,
        'amount' => '1700000.00',
        'source_reference' => $beras->source_reference,
        'correction_reason' => 'Koreksi nominal berdasarkan klarifikasi bukti pembelian.',
    ])->assertRedirect(route('financial-v2.funds.show', ['fund' => $sodaqoh, 'entity' => $entity->id]));

    $updatedBeras = $beras->fresh();
    $editAudit = AuditEvent::query()
        ->where('target_type', 'historical_fund_history')
        ->where('target_id', $updatedBeras->id)
        ->where('event_type', 'historical_fund_history_corrected')
        ->sole();
    $before = json_decode((string) $editAudit->before_summary, true, flags: JSON_THROW_ON_ERROR);
    $after = json_decode((string) $editAudit->after_summary, true, flags: JSON_THROW_ON_ERROR);

    expect($updatedBeras->status)->toBe('corrected')
        ->and($updatedBeras->amount)->toBe('1700000.00')
        ->and($updatedBeras->source_filename)->toBe(MrjZiswafOpeningPosition::SOURCE_FILENAME)
        ->and($updatedBeras->source_reference)->toBe('Buku Kas Detail!A24:F24')
        ->and($updatedBeras->updated_by_user_id)->toBe($user->id)
        ->and($before['amount'])->toBe('1600000.00')
        ->and($after['amount'])->toBe('1700000.00')
        ->and($after['changed_fields'])->toBe(['amount'])
        ->and([Journal::where('accounting_entity_id', $entity->id)->count(), JournalLine::where('accounting_entity_id', $entity->id)->count(), LedgerEntry::where('accounting_entity_id', $entity->id)->count(), FinancialTransaction::where('accounting_entity_id', $entity->id)->count()])->toBe($factsBefore);

    $this->actingAs($user)->post(route('financial-v2.funds.history.store', ['fund' => $sodaqoh, 'entity' => $entity->id]), [
        'entity' => $entity->id,
        'fund_id' => $sodaqoh->id,
        'date_label' => 'Koreksi sumber Juni 2026',
        'entry_kind' => 'adjustment_in',
        'description' => 'Koreksi penjelas saldo Sodaqoh',
        'notes' => 'Menutup selisih penjelasan setelah koreksi bukti Beras.',
        'amount' => '100000.00',
        'source_reference' => 'Klarifikasi admin ZISWAF Juni 2026',
        'correction_reason' => 'Klarifikasi sumber disetujui untuk penjelasan histori.',
    ])->assertRedirect(route('financial-v2.funds.show', ['fund' => $sodaqoh, 'entity' => $entity->id]));

    $sourceHistory = app(FundHistoryReadService::class)->history($entity, $sodaqoh, [
        'from' => '2026-01-01',
        'through' => MrjZiswafOpeningPosition::AS_OF_DATE,
    ])['source_history'];
    $reports = app(FinancialReportService::class);
    $fundRow = collect($reports->report('fund-balance', $entity->id, '2026-01-01', MrjZiswafOpeningPosition::AS_OF_DATE)['data']['rows'])
        ->sole('fund_id', $sodaqoh->id);

    expect(HistoricalFundHistory::query()->where('accounting_entity_id', $entity->id)->where('fund_id', $sodaqoh->id)->where('source_filename', 'Koreksi admin')->count())->toBe(1)
        ->and($sourceHistory['activity_balance'])->toBe('6906000.00')
        ->and($sourceHistory['source_fund_balance'])->toBe('6906000.00')
        ->and($sourceHistory['difference'])->toBe('0.00')
        ->and($fundRow['fund_balance'])->toBe('6906000.00')
        ->and($reports->report('trial-balance', $entity->id, '2026-01-01', MrjZiswafOpeningPosition::AS_OF_DATE)['data']['is_balanced'])->toBeTrue()
        ->and([Journal::where('accounting_entity_id', $entity->id)->count(), JournalLine::where('accounting_entity_id', $entity->id)->count(), LedgerEntry::where('accounting_entity_id', $entity->id)->count(), FinancialTransaction::where('accounting_entity_id', $entity->id)->count()])->toBe($factsBefore);
});
