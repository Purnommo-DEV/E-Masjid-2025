<?php

namespace Tests\Unit\FinancialV2;

use App\Domain\FinancialV2\Reporting\FundFinancialAccountCompositionReadService;
use App\Domain\FinancialV2\Reporting\PostedLedgerQuery;
use PHPUnit\Framework\TestCase;

final class AccountingDateCorrectionLiquidityTimelineTest extends TestCase
{
    public function test_atomic_reversal_is_applied_before_replaying_its_source_journal(): void
    {
        $service = new FundFinancialAccountCompositionReadService(new PostedLedgerQuery());
        $projection = $service->replayAccountingDateCorrectionTimeline(
            '6600000.00',
            [[
                'kind' => 'ledger', 'journal_id' => 'source-journal', 'ledger_entry_id' => 'source-ledger',
                'accounting_date' => '2026-10-11', 'posting_sequence' => 42,
                'balance_effect' => '-6600000.00',
            ]],
            '2026-09-13', '2026-10-11', '-6600000.00', '6600000.00', 'source-journal',
        );

        self::assertSame('0.00', $projection['minimum_projected_balance']);
        self::assertSame('0.00', $projection['ending_cumulative_effect']);
        self::assertSame(
            ['proposed_replacement', 'proposed_reversal', 'ledger'],
            array_column($projection['timeline'], 'activity_kind'),
        );
        self::assertSame(['0.00', '6600000.00', '0.00'], array_column($projection['timeline'], 'projected_balance'));
    }

    public function test_atomic_unit_does_not_treat_replacement_as_a_permanent_second_outflow(): void
    {
        $service = new FundFinancialAccountCompositionReadService(new PostedLedgerQuery());
        $projection = $service->replayAccountingDateCorrectionBatchTimeline(
            '6600000.00',
            [[
                'kind' => 'ledger', 'journal_id' => 'source-journal', 'ledger_entry_id' => 'source-ledger',
                'accounting_date' => '2026-10-11', 'posting_sequence' => 22,
                'balance_effect' => '-6600000.00',
            ]],
            '2026-09-13',
            '-6600000.00',
            [['source_date' => '2026-10-11', 'source_journal_id' => 'source-journal', 'reversal_effect' => '6600000.00']],
        );

        self::assertSame('0.00', $projection['minimum_projected_balance']);
        self::assertSame('2026-09-13', $projection['minimum_projected_date']);
        self::assertSame('0.00', $projection['ending_cumulative_effect']);
        self::assertSame(
            ['proposed_replacement_batch', 'proposed_reversal', 'ledger'],
            array_column($projection['timeline'], 'activity_kind'),
        );
        self::assertSame(['0.00', '6600000.00', '0.00'], array_column($projection['timeline'], 'projected_balance'));
    }

    public function test_batch_aggregates_members_before_authorizing_shared_fund_liquidity(): void
    {
        $service = new FundFinancialAccountCompositionReadService(new PostedLedgerQuery());
        $projection = $service->replayAccountingDateCorrectionBatchTimeline(
            '10000.00',
            [
                ['kind'=>'ledger','journal_id'=>'source-a','ledger_entry_id'=>'ledger-a','accounting_date'=>'2026-10-11','posting_sequence'=>20,'balance_effect'=>'-6000.00'],
                ['kind'=>'ledger','journal_id'=>'source-b','ledger_entry_id'=>'ledger-b','accounting_date'=>'2026-10-11','posting_sequence'=>21,'balance_effect'=>'-4000.00'],
            ],
            '2026-09-30',
            '-10000.00',
            [
                ['source_date'=>'2026-10-11','source_journal_id'=>'source-a','reversal_effect'=>'6000.00'],
                ['source_date'=>'2026-10-11','source_journal_id'=>'source-b','reversal_effect'=>'4000.00'],
            ],
        );

        self::assertSame('0.00', $projection['minimum_projected_balance']);
        self::assertSame('0.00', $projection['ending_cumulative_effect']);
        self::assertSame('0.00', $projection['timeline'][0]['projected_balance']);
        self::assertSame('0.00', $projection['timeline'][count($projection['timeline']) - 1]['projected_balance']);
    }
}
