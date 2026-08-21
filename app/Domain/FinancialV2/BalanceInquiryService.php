<?php

namespace App\Domain\FinancialV2;

use App\Domain\FinancialV2\Reporting\FundFinancialAccountCompositionReadService;
use App\Models\FinancialV2\BalanceProjection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read-model service. All balances are derived solely from posted V2 ledger
 * facts; projections are disposable caches and never an accounting source.
 */
final class BalanceInquiryService
{
    public function __construct(private readonly FundFinancialAccountCompositionReadService $fundFinancialAccounts) {}

    /** @return array{debit_total: string, credit_total: string, balance: string, through_posting_sequence: int} */
    public function accountBalance(string $entityId, string $accountId, string $asOfAccountingDate): array
    {
        $row = $this->postedLedgerQuery($entityId, $asOfAccountingDate)
            ->where('ledger.account_id', $accountId)
            ->selectRaw('COALESCE(SUM(journal_line.debit_amount), 0) as debit_total')
            ->selectRaw('COALESCE(SUM(journal_line.credit_amount), 0) as credit_total')
            ->selectRaw('COALESCE(SUM(ledger.signed_amount), 0) as balance')
            ->selectRaw('COALESCE(MAX(ledger.posting_sequence), 0) as through_posting_sequence')
            ->first();

        return [
            'debit_total' => DecimalAmount::normalize($row->debit_total),
            'credit_total' => DecimalAmount::normalize($row->credit_total),
            'balance' => DecimalAmount::normalize($row->balance),
            'through_posting_sequence' => (int) $row->through_posting_sequence,
        ];
    }

    /** @return array{debit_total: string, credit_total: string, balance: string, through_posting_sequence: int} */
    public function financialAccountBalance(string $entityId, string $financialAccountId, string $asOfAccountingDate): array
    {
        $row = $this->postedLedgerQuery($entityId, $asOfAccountingDate)
            ->where('ledger.financial_account_id', $financialAccountId)
            ->selectRaw('COALESCE(SUM(journal_line.debit_amount), 0) as debit_total')
            ->selectRaw('COALESCE(SUM(journal_line.credit_amount), 0) as credit_total')
            ->selectRaw('COALESCE(SUM(ledger.signed_amount), 0) as balance')
            ->selectRaw('COALESCE(MAX(ledger.posting_sequence), 0) as through_posting_sequence')
            ->first();

        return [
            'debit_total' => DecimalAmount::normalize($row->debit_total),
            'credit_total' => DecimalAmount::normalize($row->credit_total),
            'balance' => DecimalAmount::normalize($row->balance),
            'through_posting_sequence' => (int) $row->through_posting_sequence,
        ];
    }

    /**
     * Returns the net movement for one Kas/Rekening in an inclusive period.
     * Like every other inquiry here, this reads only posted V2 ledger facts.
     */
    public function financialAccountMovement(string $entityId, string $financialAccountId, string $fromAccountingDate, string $throughAccountingDate): string
    {
        $movement = $this->postedLedgerQuery($entityId, $throughAccountingDate)
            ->where('ledger.financial_account_id', $financialAccountId)
            ->where('ledger.accounting_date', '>=', $fromAccountingDate)
            ->sum('ledger.signed_amount');

        return DecimalAmount::normalize($movement);
    }

    /** @return Collection<int, object> */
    public function fundLiquidityDistribution(string $entityId, string $fundId, string $asOfAccountingDate): Collection
    {
        // Custody remains sourced from posted liquidity lines. A posted IFT
        // may reattribute that existing custody to another Fund without a
        // Financial Account JournalLine, so the shared projection must be used
        // by both reports and operational balance checks.
        return $this->fundFinancialAccounts
            ->composition($entityId, $asOfAccountingDate, fundId: $fundId)
            ->map(fn (object $row): object => (object) [
                'financial_account_id' => $row->financial_account_id,
                'balance' => DecimalAmount::normalize((string) $row->balance),
            ])
            ->sortBy('financial_account_id')
            ->values();
    }

    /** @return Collection<int, object> */
    public function accountBalances(string $entityId, string $asOfAccountingDate): Collection
    {
        return $this->postedLedgerQuery($entityId, $asOfAccountingDate)
            ->select('ledger.account_id')
            ->selectRaw('COALESCE(SUM(journal_line.debit_amount), 0) as debit_total')
            ->selectRaw('COALESCE(SUM(journal_line.credit_amount), 0) as credit_total')
            ->selectRaw('COALESCE(SUM(ledger.signed_amount), 0) as balance')
            ->selectRaw('COALESCE(MAX(ledger.posting_sequence), 0) as through_posting_sequence')
            ->groupBy('ledger.account_id')
            ->orderBy('ledger.account_id')
            ->get();
    }

    /**
     * Rebuilds an account-grain cache from the immutable ledger. The cache is
     * clearly marked current only after all source rows are aggregated.
     */
    public function refreshAccountProjections(string $entityId, string $asOfAccountingDate): int
    {
        $count = 0;
        foreach ($this->accountBalances($entityId, $asOfAccountingDate) as $row) {
            BalanceProjection::query()->updateOrCreate(
                [
                    'accounting_entity_id' => $entityId,
                    'projection_type' => 'account',
                    'dimension_key' => 'account:'.$row->account_id,
                    'as_of_accounting_date' => $asOfAccountingDate,
                ],
                [
                    'through_posting_sequence' => (int) $row->through_posting_sequence,
                    'debit_total' => $row->debit_total,
                    'credit_total' => $row->credit_total,
                    'balance' => $row->balance,
                    'projection_status' => 'current',
                    'built_at' => now(),
                ],
            );
            $count++;
        }

        return $count;
    }

    private function postedLedgerQuery(string $entityId, string $asOfAccountingDate)
    {
        return DB::table('financial_v2_ledger_entries as ledger')
            ->join('financial_v2_journal_lines as journal_line', 'journal_line.id', '=', 'ledger.journal_line_id')
            ->join('financial_v2_journals as journal', 'journal.id', '=', 'journal_line.journal_id')
            ->where('ledger.accounting_entity_id', $entityId)
            ->where('ledger.accounting_date', '<=', $asOfAccountingDate)
            ->where('journal.journal_status', 'posted');
    }
}
