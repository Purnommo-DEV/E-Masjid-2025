<?php

use App\Domain\FinancialV2\DecimalAmount;
use App\Domain\FinancialV2\HistoricalFundHistoryService;
use App\Domain\FinancialV2\MrjZiswafOpeningPosition;
use App\Domain\FinancialV2\Reporting\FinancialReportService;
use App\Domain\FinancialV2\Reporting\FundHistoryReadService;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\AttachmentLink;
use App\Models\FinancialV2\AuditEvent;
use App\Models\FinancialV2\FinancialAccount;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\FundPolicyRule;
use App\Models\FinancialV2\FundPolicyVersion;
use App\Models\FinancialV2\HistoricalFundHistory;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\JournalLine;
use App\Models\FinancialV2\LedgerEntry;
use App\Models\FinancialV2\OpeningBalanceBatch;
use App\Models\FinancialV2\OpeningBalanceLine;
use App\Models\FinancialV2\TransactionType;
use App\Models\FinancialV2\Voucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** @return array{0:string,1:string} */
function phase125SourceFixtures(): array
{
    $directory = storage_path('framework/testing/phase125-fund-attribution');
    if (! is_dir($directory)) {
        mkdir($directory, 0755, true);
    }

    $source = $directory.'/ZISWAF UPDATE 3.xlsx';
    $evidence = $directory.'/Sisa Alokasi Dana Ziswaf per 16 Agustus 2026.pdf';
    file_put_contents($source, 'Phase 12.5 isolated workbook fixture');
    file_put_contents($evidence, "%PDF-1.4\n% Phase 12.5 isolated p.3-p.4 evidence fixture\n");

    return [$source, $evidence];
}

test('Phase 12.5 corrects Fund attribution canonically and replays without duplicate facts', function () {
    Storage::fake('local');
    [$source, $evidence] = phase125SourceFixtures();

    $this->artisan('financial-v2:onboard-mrj-ziswaf', [
        'source' => $source,
        'evidence' => $evidence,
        '--allow-testing' => true,
    ])->assertExitCode(0);
    $this->artisan('financial-v2:sync-mrj-ziswaf-history', ['--allow-testing' => true])->assertExitCode(0);
    $this->artisan('financial-v2:provision-mrj-operational-master', ['--allow-testing' => true])->assertExitCode(0);

    $entity = AccountingEntity::query()->where('code', 'MRJ-ACTUAL')->sole();
    $infaq = Fund::query()->where('accounting_entity_id', $entity->id)->where('code', 'INFAQ-TROMOL')->sole();
    $dhuafa = Fund::query()->where('accounting_entity_id', $entity->id)->where('code', 'DHUAFA')->sole();
    $bni = FinancialAccount::query()->where('accounting_entity_id', $entity->id)->where('code', 'BNI-ZISWAF')->sole();
    $cash = FinancialAccount::query()->where('accounting_entity_id', $entity->id)->where('code', 'CASH-ZISWAF')->sole();
    $opening = OpeningBalanceBatch::query()->where('accounting_entity_id', $entity->id)->sole();
    $openingSnapshot = [
        'batch_id' => $opening->id,
        'journal_id' => $opening->journal_id,
        'status' => $opening->status,
        'line_ids' => OpeningBalanceLine::query()->where('opening_balance_batch_id', $opening->id)->orderBy('id')->pluck('id')->all(),
        'journal_line_ids' => JournalLine::query()->where('journal_id', $opening->journal_id)->orderBy('id')->pluck('id')->all(),
    ];
    $dhuafaUuid = $dhuafa->id;
    $cashHistory = HistoricalFundHistory::query()
        ->where('accounting_entity_id', $entity->id)
        ->where('source_reference', 'Sisa Alokasi Dana!D66:E66')
        ->sole();
    $cashHistoryLineage = $cashHistory->only([
        'id', 'source_key', 'source_fund_code', 'source_filename', 'source_worksheet',
        'source_reference', 'source_hash', 'import_batch_reference', 'entry_kind', 'amount',
    ]);
    $factsBefore = phase125Facts($entity->id);
    $nonFinancialBefore = [
        'fund' => [$dhuafa->id, $dhuafa->name, $dhuafa->updated_at?->toJSON()],
        'history' => [$cashHistory->id, $cashHistory->fund_id, $cashHistory->status, $cashHistory->correction_reason, $cashHistory->corrected_at?->toJSON(), $cashHistory->updated_at?->toJSON()],
        'policies' => FundPolicyVersion::query()->whereIn('fund_id', [$infaq->id, $dhuafa->id])->count(),
        'policy_rules' => FundPolicyRule::query()->whereIn('fund_policy_version_id', FundPolicyVersion::query()->whereIn('fund_id', [$infaq->id, $dhuafa->id])->pluck('id'))->count(),
        'audits' => AuditEvent::query()->where('accounting_entity_id', $entity->id)->count(),
        'attachments' => DB::table('financial_v2_attachments')->where('accounting_entity_id', $entity->id)->count(),
        'attachment_links' => AttachmentLink::query()->where('accounting_entity_id', $entity->id)->count(),
        'storage' => Storage::disk('local')->allFiles(),
    ];

    $this->artisan('financial-v2:correct-mrj-ziswaf-fund-attribution', [
        'source' => $source,
        'evidence' => $evidence,
        '--allow-testing' => true,
    ])->assertExitCode(0);
    expect(phase125Facts($entity->id))->toBe($factsBefore)
        ->and([
            'fund' => [$dhuafa->fresh()->id, $dhuafa->fresh()->name, $dhuafa->fresh()->updated_at?->toJSON()],
            'history' => [$cashHistory->fresh()->id, $cashHistory->fresh()->fund_id, $cashHistory->fresh()->status, $cashHistory->fresh()->correction_reason, $cashHistory->fresh()->corrected_at?->toJSON(), $cashHistory->fresh()->updated_at?->toJSON()],
            'policies' => FundPolicyVersion::query()->whereIn('fund_id', [$infaq->id, $dhuafa->id])->count(),
            'policy_rules' => FundPolicyRule::query()->whereIn('fund_policy_version_id', FundPolicyVersion::query()->whereIn('fund_id', [$infaq->id, $dhuafa->id])->pluck('id'))->count(),
            'audits' => AuditEvent::query()->where('accounting_entity_id', $entity->id)->count(),
            'attachments' => DB::table('financial_v2_attachments')->where('accounting_entity_id', $entity->id)->count(),
            'attachment_links' => AttachmentLink::query()->where('accounting_entity_id', $entity->id)->count(),
            'storage' => Storage::disk('local')->allFiles(),
        ])->toBe($nonFinancialBefore);

    $this->artisan('financial-v2:correct-mrj-ziswaf-fund-attribution', [
        'source' => $source,
        'evidence' => $evidence,
        '--apply' => true,
        '--allow-testing' => true,
    ])->assertExitCode(0);

    $infaq->refresh();
    $dhuafa->refresh();
    expect($dhuafa->id)->toBe($dhuafaUuid)
        ->and($dhuafa->name)->toBe('Dana Dhuafa & Anak Yatim')
        ->and($infaq->name)->toContain('Infaq');

    $reports = app(FinancialReportService::class);
    $fundReport = $reports->report('fund-balance', $entity->id, '2026-01-01', '2026-08-16');
    $fundRows = collect($fundReport['data']['rows'])->keyBy('code');
    $composition = collect($fundReport['data']['account_composition']);
    $accountRows = collect($reports->report('account-balance', $entity->id, '2026-01-01', '2026-08-16')['data']['rows'])->keyBy('code');
    $infaqComposition = $composition->where('fund_id', $infaq->id)->values();
    $dhuafaComposition = $composition->where('fund_id', $dhuafa->id)->keyBy('financial_account_code');

    expect($fundRows->get('INFAQ-TROMOL')['fund_balance'])->toBe('15466949.00')
        ->and($fundRows->get('INFAQ-TROMOL')['available_liquidity'])->toBe('15466949.00')
        ->and($fundRows->get('DHUAFA')['fund_balance'])->toBe('13511977.00')
        ->and($fundRows->get('DHUAFA')['available_liquidity'])->toBe('13511977.00')
        ->and(DecimalAmount::add($fundRows->get('INFAQ-TROMOL')['fund_balance'], $fundRows->get('DHUAFA')['fund_balance']))->toBe('28978926.00')
        ->and(DecimalAmount::sum($fundRows->pluck('fund_balance')))->toBe(MrjZiswafOpeningPosition::TOTAL)
        ->and($accountRows->get('BNI-ZISWAF')['closing_balance'])->toBe('123077312.00')
        ->and($accountRows->get('CASH-ZISWAF')['closing_balance'])->toBe('2653000.00')
        ->and(DecimalAmount::sum($accountRows->pluck('closing_balance')))->toBe(MrjZiswafOpeningPosition::TOTAL)
        ->and($infaqComposition)->toHaveCount(1)
        ->and($infaqComposition->sole()['financial_account_code'])->toBe('BNI-ZISWAF')
        ->and($infaqComposition->sole()['liquidity_balance'])->toBe('15466949.00')
        ->and($dhuafaComposition->get('BNI-ZISWAF')['liquidity_balance'])->toBe('10858977.00')
        ->and($dhuafaComposition->get('CASH-ZISWAF')['liquidity_balance'])->toBe('2653000.00')
        ->and(DecimalAmount::sum($dhuafaComposition->pluck('liquidity_balance')))->toBe('13511977.00');

    $iftType = TransactionType::query()->where('accounting_entity_id', $entity->id)->where('code', 'IFT')->sole();
    $corrections = FinancialTransaction::query()
        ->with('interfundTransfer')
        ->where('accounting_entity_id', $entity->id)
        ->where('transaction_type_id', $iftType->id)
        ->whereIn('source_reference', [
            'MRJ-P12.5-CASH-ATTRIBUTION-2026-08-16',
            'MRJ-P12.5-FUND-RECLASS-2026-08-16',
        ])
        ->get()
        ->keyBy(fn (FinancialTransaction $transaction): string => DecimalAmount::normalize($transaction->gross_amount));
    expect($corrections)->toHaveCount(2);

    foreach (['1200000.00' => $bni->id, '2653000.00' => $cash->id] as $amount => $expectedAccountId) {
        $transaction = $corrections->get($amount);
        expect($transaction)->not->toBeNull()
            ->and($transaction->status)->toBe('posted')
            ->and($transaction->primary_financial_account_id)->toBe($expectedAccountId)
            ->and($transaction->interfundTransfer->source_fund_id)->toBe($infaq->id)
            ->and($transaction->interfundTransfer->destination_fund_id)->toBe($dhuafa->id)
            ->and($transaction->interfundTransfer->policy_basis_ref)->toContain('PHASE-12.5')
            ->and($transaction->interfundTransfer->reason)->not->toBeEmpty();
    }
    expect($corrections->get('1200000.00')->interfundTransfer->policy_basis_ref)->toContain('PDF')
        ->and($corrections->get('2653000.00')->interfundTransfer->policy_basis_ref)->toContain('Sisa Alokasi Dana!D66:E66');

    $journals = Journal::query()->whereIn('transaction_id', $corrections->pluck('id'))->get();
    expect($journals)->toHaveCount(2)
        ->and(JournalLine::query()->whereIn('journal_id', $journals->pluck('id'))->count())->toBe(4)
        ->and(JournalLine::query()->whereIn('journal_id', $journals->pluck('id'))->whereNotNull('financial_account_id')->count())->toBe(0)
        ->and(LedgerEntry::query()->whereIn('journal_line_id', JournalLine::query()->whereIn('journal_id', $journals->pluck('id'))->pluck('id'))->count())->toBe(4)
        ->and($journals->every(fn (Journal $journal): bool => DecimalAmount::equals($journal->total_debit, $journal->total_credit)))->toBeTrue()
        ->and($reports->report('trial-balance', $entity->id, '2026-01-01', '2026-08-16')['data']['is_balanced'])->toBeTrue();

    foreach ($corrections as $transaction) {
        expect(AttachmentLink::query()
            ->where('accounting_entity_id', $entity->id)
            ->where('target_type', 'transaction')
            ->where('target_id', $transaction->id)
            ->where('evidence_type', 'policy')
            ->where('status', 'active')
            ->count())->toBe(1);
    }

    $cashHistory->refresh();
    expect($cashHistory->only(array_keys($cashHistoryLineage)))->toMatchArray($cashHistoryLineage)
        ->and($cashHistory->fund_id)->toBe($dhuafa->id)
        ->and($cashHistory->status)->toBe('corrected')
        ->and($cashHistory->correction_reason)->toContain('Phase 12.5')
        ->and($cashHistory->corrected_at)->not->toBeNull()
        ->and(AuditEvent::query()->where('target_type', 'historical_fund_history')->where('target_id', $cashHistory->id)->where('event_type', 'historical_fund_history_corrected')->count())->toBe(1)
        ->and(AuditEvent::query()->where('target_type', 'fund')->where('target_id', $dhuafa->id)->where('event_type', 'fund_active_renamed')->count())->toBe(1);

    $history = app(FundHistoryReadService::class);
    $infaqHistory = $history->history($entity, $infaq, ['from' => '2026-08-16', 'through' => '2026-08-16', 'transaction_type_code' => 'IFT', 'per_page' => 100]);
    $dhuafaHistory = $history->history($entity, $dhuafa, ['from' => '2026-08-16', 'through' => '2026-08-16', 'transaction_type_code' => 'IFT', 'per_page' => 100]);
    expect($infaqHistory['history']->getCollection())->toHaveCount(2)
        ->and(DecimalAmount::sum($infaqHistory['history']->getCollection()->pluck('fund_balance_delta')))->toBe('-3853000.00')
        ->and(DecimalAmount::sum($dhuafaHistory['history']->getCollection()->pluck('fund_balance_delta')))->toBe('3853000.00')
        ->and($infaqHistory['history']->getCollection()->pluck('financial_account_names')->sort()->values()->all())->toBe(['BNI ZISWAF', 'Cash Tromol Yatim'])
        ->and($infaqHistory['history']->getCollection()->every(fn (array $row): bool => $row['financial_account_is_attribution'] && filled($row['policy_basis_ref']) && filled($row['correction_reason']) && filled($row['posted_by_name']) && filled($row['posted_at'])))->toBeTrue()
        ->and($infaqHistory['source_history']['account_positions'])->toHaveCount(0)
        ->and($infaqHistory['source_history']['difference'])->toBe('0.00')
        ->and($dhuafaHistory['source_history']['account_positions'][0])->toMatchArray(['description' => 'Cash Tromol Yatim', 'amount' => '2653000.00'])
        ->and($dhuafaHistory['source_history']['difference'])->toBe('0.00');

    // These are explanatory source-history rows only. They must explain the
    // approved historical Rp1.200.000 reallocation without becoming another
    // Journal/Ledger posting path or changing the opening position.
    $factsBeforeSourceHistoryCorrection = phase125Facts($entity->id);
    $sourceHistoryService = app(HistoricalFundHistoryService::class);
    foreach ([
        [$infaq, 'usage', 'Pemindahan Dana dari alokasi Infaq & Tromol', 'Pemindahan historis dari Infaq & Tromol ke Dana Dhuafa & Anak Yatim; tidak memindahkan rekening atau likuiditas.'],
        [$dhuafa, 'receipt', 'Pemindahan Dana dari alokasi Infaq & Tromol', 'Pemindahan historis dari Infaq & Tromol ke Dana Dhuafa & Anak Yatim; tidak memindahkan rekening atau likuiditas.'],
    ] as [$fund, $entryKind, $description, $notes]) {
        $sourceHistoryService->createCorrection($entity->id, $fund->id, [
            'entry_kind' => $entryKind,
            'date_label' => '16 Agustus 2026',
            'description' => $description,
            'notes' => $notes,
            'amount' => '1200000.00',
            'source_reference' => 'Sisa Alokasi Dana Ziswaf DKM MRJ TCE (per 16 agustus 2026).pdf · p.3–4',
            'correction_reason' => 'Phase 12.5 historical source explanation for the approved Fund reclassification.',
        ]);
    }
    expect(phase125Facts($entity->id))->toBe($factsBeforeSourceHistoryCorrection);

    $infaqSourceHistory = $history->history($entity, $infaq, ['from' => '2026-08-16', 'through' => '2026-08-16', 'per_page' => 100])['source_history'];
    $dhuafaSourceHistory = $history->history($entity, $dhuafa, ['from' => '2026-08-16', 'through' => '2026-08-16', 'per_page' => 100])['source_history'];
    expect($infaqSourceHistory['opening_source_balance'])->toBe('16666949.00')
        ->and($infaqSourceHistory['historical_movement'])->toBe('-1200000.00')
        ->and($infaqSourceHistory['current_source_balance'])->toBe('15466949.00')
        ->and($infaqSourceHistory['difference'])->toBe('0.00')
        ->and(collect($infaqSourceHistory['rows'])->last())->toMatchArray([
            'description' => 'Pemindahan Dana dari alokasi Infaq & Tromol',
            'usage' => '1200000.00',
            'running_balance' => '15466949.00',
            'classification' => 'Historical Fund Reallocation',
            'is_historical_fund_reallocation' => true,
        ])
        ->and($dhuafaSourceHistory['opening_source_balance'])->toBe('9658977.00')
        ->and($dhuafaSourceHistory['historical_movement'])->toBe('1200000.00')
        ->and($dhuafaSourceHistory['current_source_balance'])->toBe('10858977.00')
        ->and($dhuafaSourceHistory['difference'])->toBe('0.00')
        ->and($dhuafaSourceHistory['account_positions'][0])->toMatchArray(['description' => 'Cash Tromol Yatim', 'amount' => '2653000.00'])
        ->and(collect($dhuafaSourceHistory['rows'])->last())->toMatchArray([
            'description' => 'Pemindahan Dana dari alokasi Infaq & Tromol',
            'receipt' => '1200000.00',
            'running_balance' => '10858977.00',
            'classification' => 'Historical Fund Reallocation',
            'is_historical_fund_reallocation' => true,
        ])
        ->and(phase125Facts($entity->id))->toBe($factsBeforeSourceHistoryCorrection);

    foreach ([$infaq, $dhuafa] as $fund) {
        $expectedTransferAccountId = JournalLine::query()
            ->whereIn('journal_id', $journals->pluck('id'))
            ->where('fund_id', $fund->id)
            ->pluck('account_id')
            ->unique()
            ->sole();
        $correctionPolicy = FundPolicyVersion::query()
            ->where('fund_id', $fund->id)
            ->where('version_no', 3)
            ->where('status', 'superseded')
            ->whereDate('effective_from', '2026-08-16')
            ->whereDate('effective_to', '2026-08-16')
            ->where('policy_document_ref', 'like', '%PHASE-12.5%')
            ->sole();
        expect(FundPolicyRule::query()
            ->where('fund_policy_version_id', $correctionPolicy->id)
            ->where('transaction_type_id', $iftType->id)
            ->where('account_id', $expectedTransferAccountId)
            ->where('decision', 'allowed')
            ->count())->toBe(1);
        expect(FundPolicyRule::query()
            ->where('fund_policy_version_id', $correctionPolicy->id)
            ->where('transaction_type_id', $iftType->id)
            ->whereNull('account_id')
            ->count())->toBe(0);

        $restoredPolicy = FundPolicyVersion::query()
            ->where('fund_id', $fund->id)
            ->where('version_no', 4)
            ->where('status', 'effective')
            ->whereDate('effective_from', '2026-08-17')
            ->where('policy_document_ref', 'like', '%POST-CORRECTION-FAIL-CLOSED%')
            ->sole();
        expect(FundPolicyRule::query()
            ->where('fund_policy_version_id', $restoredPolicy->id)
            ->where('transaction_type_id', $iftType->id)
            ->count())->toBe(0);
    }

    expect([
        'batch_id' => $opening->fresh()->id,
        'journal_id' => $opening->fresh()->journal_id,
        'status' => $opening->fresh()->status,
        'line_ids' => OpeningBalanceLine::query()->where('opening_balance_batch_id', $opening->id)->orderBy('id')->pluck('id')->all(),
        'journal_line_ids' => JournalLine::query()->where('journal_id', $opening->journal_id)->orderBy('id')->pluck('id')->all(),
    ])->toBe($openingSnapshot);

    $user = \App\Models\User::factory()->create();
    $this->actingAs($user)->get(route('financial-v2.funds.groups.show', ['group' => 'ziswaf', 'entity' => $entity->id]))
        ->assertOk()
        ->assertSee('Dana Dhuafa &amp; Anak Yatim', false)
        ->assertSee('Rp13.511.977,00');
    $this->actingAs($user)->get(route('financial-v2.funds.show', ['fund' => $dhuafa, 'entity' => $entity->id, 'from' => '2026-08-16', 'through' => '2026-08-16']))
        ->assertOk()
        ->assertSee('Rp13.511.977,00')
        ->assertSee('Rp10.858.977,00')
        ->assertSee('Cash Tromol Yatim')
        ->assertSee('Mutasi historis')
        ->assertSee('Historical Fund Reallocation')
        ->assertSee('Saldo baseline sumber')
        ->assertSee('Saldo Dana sumber kini')
        ->assertSee('Rekonsiliasi riwayat sumber')
        ->assertSee('PASS · Selisih Rp0,00')
        ->assertDontSee('Selisih rekonsiliasi')
        ->assertSee('Rekening atribusi (saldo tidak berpindah)')
        ->assertSee('PHASE-12.5');
    $this->actingAs($user)->get(route('financial-v2.funds.show', ['fund' => $infaq, 'entity' => $entity->id, 'from' => '2026-08-16', 'through' => '2026-08-16']))
        ->assertOk()
        ->assertSee('Rp15.466.949,00')
        ->assertSee('Pemindahan Dana dari alokasi Infaq &amp; Tromol', false)
        ->assertSee('Rp16.666.949,00')
        ->assertSee('Rp1.200.000,00')
        ->assertSee('PASS · Selisih Rp0,00')
        ->assertDontSee('Selisih rekonsiliasi');

    foreach ($corrections as $transaction) {
        $this->actingAs($user)->get(route('financial-v2.transactions.show', $transaction))
            ->assertOk()
            ->assertSee('Rekening atribusi (saldo tidak berpindah)')
            ->assertSee($transaction->interfundTransfer->policy_basis_ref);
    }
    foreach ([$infaq, $dhuafa] as $fund) {
        $response = $this->actingAs($user)->get(route('financial-v2.transactions.index', ['entity' => $entity->id, 'fund_id' => $fund->id, 'type' => 'IFT']))
            ->assertOk();
        foreach ($corrections as $transaction) {
            $response->assertSee($transaction->source_reference);
        }
    }

    $afterFirstApply = phase125Facts($entity->id) + [
        'policies' => FundPolicyVersion::query()->whereIn('fund_id', [$infaq->id, $dhuafa->id])->count(),
        'policy_rules' => FundPolicyRule::query()->whereIn('fund_policy_version_id', FundPolicyVersion::query()->whereIn('fund_id', [$infaq->id, $dhuafa->id])->pluck('id'))->count(),
        'attachment_links' => AttachmentLink::query()->whereIn('target_id', $corrections->pluck('id'))->count(),
        'historical_rows' => HistoricalFundHistory::query()->where('accounting_entity_id', $entity->id)->count(),
    ];
    $transactionIds = $corrections->pluck('id')->sort()->values()->all();
    $journalIds = $journals->pluck('id')->sort()->values()->all();

    $this->artisan('financial-v2:correct-mrj-ziswaf-fund-attribution', [
        'source' => $source,
        'evidence' => $evidence,
        '--apply' => true,
        '--allow-testing' => true,
    ])->assertExitCode(0);

    expect(phase125Facts($entity->id) + [
        'policies' => FundPolicyVersion::query()->whereIn('fund_id', [$infaq->id, $dhuafa->id])->count(),
        'policy_rules' => FundPolicyRule::query()->whereIn('fund_policy_version_id', FundPolicyVersion::query()->whereIn('fund_id', [$infaq->id, $dhuafa->id])->pluck('id'))->count(),
        'attachment_links' => AttachmentLink::query()->whereIn('target_id', $corrections->pluck('id'))->count(),
        'historical_rows' => HistoricalFundHistory::query()->where('accounting_entity_id', $entity->id)->count(),
    ])->toBe($afterFirstApply)
        ->and(FinancialTransaction::query()->whereIn('id', $transactionIds)->count())->toBe(2)
        ->and(Journal::query()->whereIn('id', $journalIds)->count())->toBe(2)
        ->and(DB::table('financial_v2_vouchers')->select('voucher_number')->groupBy('voucher_number')->havingRaw('COUNT(*) > 1')->count())->toBe(0);
});

/** @return array<string, int> */
function phase125Facts(string $entityId): array
{
    return [
        'transactions' => FinancialTransaction::query()->where('accounting_entity_id', $entityId)->count(),
        'journals' => Journal::query()->where('accounting_entity_id', $entityId)->count(),
        'journal_lines' => JournalLine::query()->where('accounting_entity_id', $entityId)->count(),
        'ledger_entries' => LedgerEntry::query()->where('accounting_entity_id', $entityId)->count(),
        'vouchers' => Voucher::query()->where('accounting_entity_id', $entityId)->count(),
        'opening_batches' => OpeningBalanceBatch::query()->where('accounting_entity_id', $entityId)->count(),
        'opening_lines' => OpeningBalanceLine::query()->where('accounting_entity_id', $entityId)->count(),
    ];
}
