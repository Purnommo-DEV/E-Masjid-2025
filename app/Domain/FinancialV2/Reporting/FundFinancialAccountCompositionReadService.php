<?php

namespace App\Domain\FinancialV2\Reporting;

use App\Domain\FinancialV2\DecimalAmount;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read-only Fund x Financial Account attribution derived from posted facts.
 *
 * Liquidity JournalLines remain the custody source of truth. A posted IFT may
 * carry primary_financial_account_id solely as an attribution context: it
 * reclassifies which Fund owns liquidity already held in that account and
 * never creates a Financial Account movement. The IFT is applied once per
 * posted Journal (not once per double-entry line), including reversal undo.
 */
final class FundFinancialAccountCompositionReadService
{
    public function __construct(private readonly PostedLedgerQuery $postedLedger) {}

    /**
     * @return Collection<int, object{fund_id:string,fund_code:string,fund_name:string,financial_account_id:string,financial_account_code:string,financial_account_name:string,balance:string}>
     */
    public function composition(
        string $entityId,
        string $throughAccountingDate,
        ?string $fundId = null,
        ?string $financialAccountId = null,
    ): Collection {
        $base = $this->postedLedger->ledger($entityId, $throughAccountingDate)
            ->join('financial_v2_funds as composition_fund', 'composition_fund.id', '=', 'ledger.fund_id')
            ->join('financial_v2_financial_accounts as composition_account', 'composition_account.id', '=', 'ledger.financial_account_id')
            ->whereNotNull('ledger.fund_id')
            ->whereNotNull('ledger.financial_account_id')
            ->when($fundId, fn (Builder $query, string $id) => $query->where('ledger.fund_id', $id))
            ->when($financialAccountId, fn (Builder $query, string $id) => $query->where('ledger.financial_account_id', $id))
            ->select([
                'composition_fund.id as fund_id',
                'composition_fund.code as fund_code',
                'composition_fund.name as fund_name',
                'composition_account.id as financial_account_id',
                'composition_account.code as financial_account_code',
                'composition_account.name as financial_account_name',
            ])
            ->selectRaw('COALESCE(SUM(ledger.signed_amount), 0) as balance')
            ->groupBy([
                'composition_fund.id', 'composition_fund.code', 'composition_fund.name',
                'composition_account.id', 'composition_account.code', 'composition_account.name',
            ])
            ->get();

        /** @var array<string, object> $rows */
        $rows = [];
        foreach ($base as $row) {
            $row->balance = DecimalAmount::normalize((string) $row->balance);
            $rows[$this->key($row->fund_id, $row->financial_account_id)] = $row;
        }

        foreach ($this->attributionEvents($entityId, $throughAccountingDate, $fundId, $financialAccountId) as $event) {
            $amount = DecimalAmount::normalize((string) $event->attribution_amount);
            // The event query must see either side in order to find a transfer
            // involving a filtered Fund. Only emit the requested side into the
            // final projection, otherwise a Fund-detail query would leak its
            // counterparty Fund into account_composition.
            if ($fundId === null || $event->source_fund_id === $fundId) {
                $this->apply($rows, $event, 'source', DecimalAmount::negate($amount));
            }
            if ($fundId === null || $event->destination_fund_id === $fundId) {
                $this->apply($rows, $event, 'destination', $amount);
            }
        }

        return collect(array_values($rows))
            ->reject(fn (object $row): bool => DecimalAmount::equals($row->balance, '0.00'))
            ->sortBy(fn (object $row): string => $row->fund_code.'|'.$row->financial_account_code)
            ->values();
    }

    /**
     * Current-read variant used by PostingEngine after its entity lock.
     * Locking reads avoid an earlier repeatable-read snapshot authorizing a
     * payment against attribution that another committed posting has moved.
     */
    public function currentBalance(
        string $entityId,
        string $fundId,
        string $financialAccountId,
        string $throughAccountingDate,
    ): string {
        $ledgerBalance = $this->postedLedger->ledger($entityId, $throughAccountingDate)
            ->where('ledger.fund_id', $fundId)
            ->where('ledger.financial_account_id', $financialAccountId)
            ->selectRaw('COALESCE(SUM(ledger.signed_amount), 0) as balance')
            ->lockForUpdate()
            ->value('balance');
        $balance = DecimalAmount::normalize((string) $ledgerBalance);

        foreach ($this->attributionEvents($entityId, $throughAccountingDate, $fundId, $financialAccountId, true) as $event) {
            $amount = DecimalAmount::normalize((string) $event->attribution_amount);
            if ($event->source_fund_id === $fundId) {
                $balance = DecimalAmount::subtract($balance, $amount);
            }
            if ($event->destination_fund_id === $fundId) {
                $balance = DecimalAmount::add($balance, $amount);
            }
        }

        return $balance;
    }

    /**
     * Conservative backdate control for any posting that would reduce a
     * Fund/account balance. A later posted fact means validating only at the
     * proposed historical date is insufficient to prove every later running
     * balance remains non-negative.
     */
    public function hasActivityAfter(
        string $entityId,
        string $fundId,
        string $financialAccountId,
        string $accountingDate,
    ): bool {
        $hasLedgerActivity = $this->postedLedger->ledger($entityId, '9999-12-31')
            ->where('ledger.fund_id', $fundId)
            ->where('ledger.financial_account_id', $financialAccountId)
            ->where('ledger.accounting_date', '>', $accountingDate)
            ->exists();
        if ($hasLedgerActivity) {
            return true;
        }

        return $this->attributionEvents(
            $entityId,
            '9999-12-31',
            $fundId,
            $financialAccountId,
            afterAccountingDate: $accountingDate,
        )->isNotEmpty();
    }

    /** @return Collection<int, object> */
    private function attributionEvents(
        string $entityId,
        string $throughAccountingDate,
        ?string $fundId,
        ?string $financialAccountId,
        bool $lockForUpdate = false,
        ?string $afterAccountingDate = null,
    ): Collection {
        $query = $this->postedLedger->journals($entityId, $throughAccountingDate)
            ->leftJoin('financial_v2_interfund_transfers as direct_interfund', 'direct_interfund.transaction_id', '=', 'financial_transaction.id')
            ->leftJoin('financial_v2_interfund_transfers as original_interfund', 'original_interfund.transaction_id', '=', 'original_transaction.id')
            ->join('financial_v2_financial_accounts as attribution_account', function (JoinClause $join): void {
                $join->on('attribution_account.id', '=', DB::raw('COALESCE(original_transaction.primary_financial_account_id, financial_transaction.primary_financial_account_id)'));
            })
            ->join('financial_v2_funds as attribution_source_fund', function (JoinClause $join): void {
                $join->on('attribution_source_fund.id', '=', DB::raw('COALESCE(original_interfund.source_fund_id, direct_interfund.source_fund_id)'));
            })
            ->join('financial_v2_funds as attribution_destination_fund', function (JoinClause $join): void {
                $join->on('attribution_destination_fund.id', '=', DB::raw('COALESCE(original_interfund.destination_fund_id, direct_interfund.destination_fund_id)'));
            })
            ->whereRaw('COALESCE(original_transaction_type.code, transaction_type.code) = ?', ['IFT'])
            ->when($afterAccountingDate, fn (Builder $query, string $date) => $query->where('journal.accounting_date', '>', $date))
            ->when($fundId, function (Builder $query, string $id): void {
                $query->where(function (Builder $fund) use ($id): void {
                    $fund->whereRaw('COALESCE(original_interfund.source_fund_id, direct_interfund.source_fund_id) = ?', [$id])
                        ->orWhereRaw('COALESCE(original_interfund.destination_fund_id, direct_interfund.destination_fund_id) = ?', [$id]);
                });
            })
            ->when($financialAccountId, fn (Builder $query, string $id) => $query->where('attribution_account.id', $id))
            ->select([
                'journal.id as journal_id',
                'attribution_account.id as financial_account_id',
                'attribution_account.code as financial_account_code',
                'attribution_account.name as financial_account_name',
                'attribution_source_fund.id as source_fund_id',
                'attribution_source_fund.code as source_fund_code',
                'attribution_source_fund.name as source_fund_name',
                'attribution_destination_fund.id as destination_fund_id',
                'attribution_destination_fund.code as destination_fund_code',
                'attribution_destination_fund.name as destination_fund_name',
            ])
            ->selectRaw('CASE WHEN journal.reversal_of_journal_id IS NULL THEN COALESCE(financial_transaction.gross_amount, 0) ELSE -COALESCE(original_transaction.gross_amount, financial_transaction.gross_amount, 0) END as attribution_amount');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->get();
    }

    /** @param array<string, object> $rows */
    private function apply(array &$rows, object $event, string $side, string $amount): void
    {
        $fundId = $event->{$side.'_fund_id'};
        $key = $this->key($fundId, $event->financial_account_id);
        if (! isset($rows[$key])) {
            $rows[$key] = (object) [
                'fund_id' => $fundId,
                'fund_code' => $event->{$side.'_fund_code'},
                'fund_name' => $event->{$side.'_fund_name'},
                'financial_account_id' => $event->financial_account_id,
                'financial_account_code' => $event->financial_account_code,
                'financial_account_name' => $event->financial_account_name,
                'balance' => '0.00',
            ];
        }

        $rows[$key]->balance = DecimalAmount::add((string) $rows[$key]->balance, $amount);
    }

    private function key(string $fundId, string $financialAccountId): string
    {
        return $fundId.'|'.$financialAccountId;
    }
}
