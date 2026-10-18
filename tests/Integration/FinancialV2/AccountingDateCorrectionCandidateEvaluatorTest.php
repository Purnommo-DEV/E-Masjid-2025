<?php

namespace Tests\Integration\FinancialV2;

use App\Domain\FinancialV2\AccountingDateCorrectionCandidateEvaluator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AccountingDateCorrectionCandidateEvaluatorTest extends TestCase
{
    use DatabaseTransactions;

    public function test_known_candidate_evaluation_is_read_only_and_fail_closed(): void
    {
        $connection = DB::connection();
        self::assertSame('mysql', $connection->getName());
        self::assertSame('mrj_test_db', (string) $connection->selectOne('SELECT DATABASE() AS database_name')->database_name);
        self::assertStringStartsWith('8.4.9', (string) $connection->selectOne('SELECT VERSION() AS server_version')->server_version);
        self::assertGreaterThan(0, $connection->transactionLevel());

        $tables = [
            'financial_v2_transactions', 'financial_v2_journals', 'financial_v2_journal_lines',
            'financial_v2_ledger_entries', 'financial_v2_accounting_date_correction_requests',
            'financial_v2_accounting_date_correction_lineages', 'financial_v2_audit_events',
        ];
        $before = collect($tables)->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->count()])->all();

        $results = app(AccountingDateCorrectionCandidateEvaluator::class)->evaluateKnownCandidates();

        self::assertCount(9, $results);
        self::assertSame(array_keys(AccountingDateCorrectionCandidateEvaluator::CANDIDATES), array_column($results, 'transaction_id'));
        self::assertTrue(collect($results)->every(fn (array $row): bool => $row['read_only'] === true));
        self::assertTrue(collect($results)->every(fn (array $row): bool => in_array($row['status'], ['eligible', 'blocked', 'needs_governance_review'], true)));
        self::assertTrue(collect($results)->every(function (array $row): bool {
            $codes = collect($row['blockers'])->pluck('code');

            return ! $codes->contains('REPLACEMENT_REALIZATION_WORKFLOW_UNAVAILABLE')
                && ! $codes->contains('BANK_BATCH_GROUP_WORKFLOW_REQUIRED');
        }));
        self::assertSame($before, collect($tables)->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->count()])->all());
    }
}
