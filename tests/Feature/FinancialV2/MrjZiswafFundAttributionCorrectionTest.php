<?php

use App\Domain\FinancialV2\DecimalAmount;
use App\Domain\FinancialV2\MrjZiswafOpeningPosition;
use App\Domain\FinancialV2\Reporting\FinancialReportService;
use App\Domain\FinancialV2\Reporting\PublicZiswafReportService;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\HistoricalFundHistory;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\JournalLine;
use App\Models\FinancialV2\LedgerEntry;
use App\Models\FinancialV2\Voucher;
use App\Models\User;
use Database\Seeders\FinancialV2Seeder;
use Illuminate\Support\Facades\DB;

/** @return array<string, int> */
function phase126FinancialFacts(string $entityId): array
{
    return [
        'journals' => Journal::query()->where('accounting_entity_id', $entityId)->count(),
        'journal_lines' => JournalLine::query()->where('accounting_entity_id', $entityId)->count(),
        'ledger_entries' => LedgerEntry::query()->where('accounting_entity_id', $entityId)->count(),
        'vouchers' => Voucher::query()->where('accounting_entity_id', $entityId)->count(),
    ];
}

test('Phase 12.6 treats Cash Tromol as original Dhuafa and Anak Yatim account composition idempotently', function () {
    User::factory()->create(['email' => 'superadmin@emasjid.com']);
    app(FinancialV2Seeder::class)->setContainer(app())->run();

    $entity = AccountingEntity::query()->where('code', 'MRJ-ACTUAL')->sole();
    $factsBefore = phase126FinancialFacts($entity->id);

    $this->artisan('financial-v2:correct-current-state', ['--allow-testing' => true])
        ->assertExitCode(0);
    expect(phase126FinancialFacts($entity->id))->toBe($factsBefore);

    $this->artisan('financial-v2:correct-current-state', ['--apply' => true, '--allow-testing' => true])
        ->assertExitCode(0);

    $reports = app(FinancialReportService::class);
    $fundRows = collect($reports->report('fund-balance', $entity->id, '2026-01-01', '2026-08-16')['data']['rows'])->keyBy('code');
    $accountRows = collect($reports->report('account-balance', $entity->id, '2026-01-01', '2026-08-16')['data']['rows'])->keyBy('code');
    $composition = collect($reports->report('fund-balance', $entity->id, '2026-01-01', '2026-08-16')['data']['account_composition'])
        ->where('fund_code', 'DHUAFA')
        ->keyBy('financial_account_code');
    $cashHistory = HistoricalFundHistory::query()
        ->where('accounting_entity_id', $entity->id)
        ->where('source_reference', MrjZiswafOpeningPosition::CASH_TROMOL_SOURCE_REFERENCE)
        ->sole();
    $duplicateSourceHistory = HistoricalFundHistory::query()
        ->where('accounting_entity_id', $entity->id)
        ->where('description', 'Pemindahan Dana dari alokasi Infaq & Tromol')
        ->where('source_reference', 'Sisa Alokasi Dana Ziswaf DKM MRJ TCE (per 16 agustus 2026).pdf')
        ->get();
    $publicReport = app(PublicZiswafReportService::class)->pdfReport('2026-01-01', '2026-08-16');

    expect($fundRows->get('INFAQ-TROMOL')['fund_balance'])->toBe('15466949.00')
        ->and($fundRows->get('DHUAFA')['fund_balance'])->toBe('13511977.00')
        ->and($composition->get('BNI-ZISWAF')['liquidity_balance'])->toBe('10858977.00')
        ->and($composition->get('CASH-ZISWAF')['liquidity_balance'])->toBe('2653000.00')
        ->and(DecimalAmount::sum($composition->pluck('liquidity_balance')))->toBe('13511977.00')
        ->and($accountRows->get('BNI-ZISWAF')['closing_balance'])->toBe('123077312.00')
        ->and($accountRows->get('CASH-ZISWAF')['closing_balance'])->toBe('2653000.00')
        ->and(DecimalAmount::sum($accountRows->pluck('closing_balance')))->toBe(MrjZiswafOpeningPosition::TOTAL)
        ->and($cashHistory->source_fund_code)->toBe('DHUAFA')
        ->and($cashHistory->status)->toBe('active')
        ->and($cashHistory->source_payload['position_treatment'] ?? null)->toBe('original_fund_account_composition')
        ->and($duplicateSourceHistory)->toHaveCount(2)
        ->and($duplicateSourceHistory->pluck('status')->unique()->values()->all())->toBe(['void'])
        ->and(DB::table('financial_v2_transactions')->where('accounting_entity_id', $entity->id)->where('source_reference', MrjZiswafOpeningPosition::CASH_TROMOL_SUPERSEDED_TRANSFER_REFERENCE)->count())->toBe(0)
        ->and(DB::table('financial_v2_transactions')->where('accounting_entity_id', $entity->id)->where('source_reference', MrjZiswafOpeningPosition::FUND_RECLASSIFICATION_REFERENCE)->where('status', 'posted')->count())->toBe(1)
        ->and($publicReport['fund_transfers'])->toHaveCount(1)
        ->and($publicReport['fund_transfers'][0]['amount'])->toBe('1200000.00')
        ->and(collect($publicReport['fund_details'])->flatMap(fn (array $detail) => $detail['source_entries'])->pluck('description')->contains('Pemindahan Dana dari alokasi Infaq & Tromol'))->toBeFalse()
        ->and($reports->report('trial-balance', $entity->id, '2026-01-01', '2026-08-16')['data']['is_balanced'])->toBeTrue();

    $this->artisan('financial-v2:correct-current-state', ['--apply' => true, '--allow-testing' => true])
        ->assertExitCode(0);
    expect(phase126FinancialFacts($entity->id))->toBe($factsBefore)
        ->and(Voucher::query()->where('accounting_entity_id', $entity->id)->distinct('voucher_number')->count('voucher_number'))->toBe(2)
        ->and(LedgerEntry::query()->where('financial_v2_ledger_entries.accounting_entity_id', $entity->id)
            ->leftJoin('financial_v2_journal_lines as line', 'line.id', '=', 'financial_v2_ledger_entries.journal_line_id')
            ->whereNull('line.id')
            ->count())->toBe(0);
});
