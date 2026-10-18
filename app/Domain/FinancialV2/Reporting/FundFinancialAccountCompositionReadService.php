<?php

namespace App\Domain\FinancialV2\Reporting;

use App\Domain\FinancialV2\DecimalAmount;
use App\Domain\FinancialV2\MrjZiswafOpeningPosition;
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
            if ($this->isSupersededCashTromolEvent($event)) {
                // The legacy IFT remains immutable audit lineage only. Its
                // balance effect is presented as the original Cash Tromol
                // Fund/Account composition, never as a Fund transfer.
                $this->applyOriginalCashTromolComposition($rows, $event, $fundId, $amount);

                continue;
            }
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
        return $this->balance($entityId, $fundId, $financialAccountId, $throughAccountingDate, true);
    }

    /** Repeatable-read balance for read-only plans; never use for posting authorization. */
    public function snapshotBalance(
        string $entityId,
        string $fundId,
        string $financialAccountId,
        string $throughAccountingDate,
    ): string {
        return $this->balance($entityId, $fundId, $financialAccountId, $throughAccountingDate, false);
    }

    private function balance(string $entityId, string $fundId, string $financialAccountId, string $throughAccountingDate, bool $lockingRead): string
    {
        $query = $this->postedLedger->ledger($entityId, $throughAccountingDate)
            ->where('ledger.fund_id', $fundId)
            ->where('ledger.financial_account_id', $financialAccountId)
            ->selectRaw('COALESCE(SUM(ledger.signed_amount), 0) as balance');
        if ($lockingRead) $query->lockForUpdate();
        $ledgerBalance = $query->value('balance');
        $balance = DecimalAmount::normalize((string) $ledgerBalance);

        foreach ($this->attributionEvents($entityId, $throughAccountingDate, $fundId, $financialAccountId, $lockingRead) as $event) {
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

    /**
     * Proves a proposed backdated effect against every later posted balance.
     *
     * The effect is applied to the balance on its accounting date and remains
     * part of every later balance. Checking every posted custody or attribution
     * activity in posting-sequence order prevents a historical posting from
     * making an intra-day Fund/account position fall below its minimum.
     *
     * @return array{minimum_projected_balance:string,minimum_projected_date:string}
     */
    public function projectedMinimumBalanceAfterBackdatedEffect(
        string $entityId,
        string $fundId,
        string $financialAccountId,
        string $accountingDate,
        string $effect,
    ): array {
        $projection = $this->backdatedProjection(
            $entityId,
            $fundId,
            $financialAccountId,
            $accountingDate,
            $effect,
        );

        return [
            'minimum_projected_balance' => $projection['minimum_projected_balance'],
            'minimum_projected_date' => $projection['minimum_projected_date'],
        ];
    }

    /**
     * Canonical, auditable projection used by controlled backdated posting.
     *
     * @return array{minimum_projected_balance:string,minimum_projected_date:string,timeline:array<int,array{accounting_date:string,current_balance:string,effect:string,projected_balance:string}>,activity:array<int,array<string,mixed>>}
     */
    public function backdatedProjection(
        string $entityId,
        string $fundId,
        string $financialAccountId,
        string $accountingDate,
        string $effect,
        bool $consistentSnapshot = false,
    ): array {
        $effect = DecimalAmount::normalize($effect);
        $runningBalance = $consistentSnapshot
            ? $this->snapshotBalance($entityId, $fundId, $financialAccountId, $accountingDate)
            : $this->currentBalance($entityId, $fundId, $financialAccountId, $accountingDate);
        $minimumBalance = DecimalAmount::add($runningBalance, $effect);
        $minimumDate = $accountingDate;
        $timeline = [[
            'accounting_date' => $accountingDate,
            'posting_sequence' => null,
            'activity_kind' => 'proposed_date',
            'activity_reference' => null,
            'current_balance' => $runningBalance,
            'effect' => $effect,
            'projected_balance' => $minimumBalance,
        ]];

        $ledgerActivity = $this->postedLedger->ledger($entityId, '9999-12-31')
            ->where('ledger.fund_id', $fundId)
            ->where('ledger.financial_account_id', $financialAccountId)
            ->where('ledger.accounting_date', '>', $accountingDate)
            ->select([
                'ledger.id as ledger_entry_id',
                'ledger.journal_line_id',
                'journal.id as journal_id',
                'financial_transaction.id as transaction_id',
                'financial_transaction.source_reference',
                'ledger.accounting_date',
                'ledger.posting_sequence',
                'ledger.signed_amount',
            ])
            ->orderBy('ledger.accounting_date')
            ->orderBy('ledger.posting_sequence')
            ->orderBy('ledger.line_no')
            ->get()
            ->map(fn (object $row): array => [
                'kind' => 'ledger',
                'ledger_entry_id' => $row->ledger_entry_id,
                'journal_line_id' => $row->journal_line_id,
                'journal_id' => $row->journal_id,
                'transaction_id' => $row->transaction_id,
                'source_reference' => $row->source_reference,
                'accounting_date' => (string) $row->accounting_date,
                'posting_sequence' => (int) $row->posting_sequence,
                'signed_amount' => DecimalAmount::normalize((string) $row->signed_amount),
                'balance_effect' => DecimalAmount::normalize((string) $row->signed_amount),
            ]);

        $attributionActivity = $this->attributionEvents(
            $entityId,
            '9999-12-31',
            $fundId,
            $financialAccountId,
            afterAccountingDate: $accountingDate,
        )->map(function (object $row) use ($fundId): array {
            $amount = DecimalAmount::normalize((string) $row->attribution_amount);
            $balanceEffect = '0.00';
            if ($row->source_fund_id === $fundId) {
                $balanceEffect = DecimalAmount::subtract($balanceEffect, $amount);
            }
            if ($row->destination_fund_id === $fundId) {
                $balanceEffect = DecimalAmount::add($balanceEffect, $amount);
            }

            return [
                'kind' => 'fund_attribution',
                'journal_id' => $row->journal_id,
                'source_reference' => $row->source_reference,
                'accounting_date' => (string) $row->accounting_date,
                'posting_sequence' => (int) $row->posting_sequence,
                'source_fund_id' => $row->source_fund_id,
                'destination_fund_id' => $row->destination_fund_id,
                'financial_account_id' => $row->financial_account_id,
                'attribution_amount' => $amount,
                'balance_effect' => $balanceEffect,
            ];
        });

        $activity = $ledgerActivity
            ->concat($attributionActivity)
            ->sortBy(fn (array $row): string => implode('|', [
                $row['accounting_date'],
                str_pad((string) $row['posting_sequence'], 20, '0', STR_PAD_LEFT),
                $row['kind'],
            ]))
            ->values();

        foreach ($activity as $row) {
            $runningBalance = DecimalAmount::add($runningBalance, $row['balance_effect']);
            $projected = DecimalAmount::add($runningBalance, $effect);
            $timeline[] = [
                'accounting_date' => $row['accounting_date'],
                'posting_sequence' => $row['posting_sequence'],
                'activity_kind' => $row['kind'],
                'activity_reference' => $row['ledger_entry_id'] ?? $row['journal_id'],
                'current_balance' => $runningBalance,
                'effect' => $effect,
                'projected_balance' => $projected,
            ];
            if (DecimalAmount::compare($projected, $minimumBalance) < 0) {
                $minimumBalance = $projected;
                $minimumDate = $row['accounting_date'];
            }
        }

        return [
            'minimum_projected_balance' => $minimumBalance,
            'minimum_projected_date' => $minimumDate,
            'timeline' => $timeline,
            'activity' => $activity->all(),
        ];
    }

    /**
     * Read-only projection for a bounded correction effect: replacement starts
     * at the target date and reversal removes the original effect at source.
     * Existing posted activity remains ordered by accounting date and posting
     * sequence; proposed entries are deliberately marked without a sequence.
     *
     * @return array<string,mixed>
     */
    public function accountingDateCorrectionProjection(
        string $entityId,
        string $fundId,
        string $financialAccountId,
        string $targetDate,
        string $sourceDate,
        string $replacementEffect,
        string $reversalEffect,
        ?string $sourceJournalId = null,
    ): array {
        $replacementEffect = DecimalAmount::normalize($replacementEffect);
        $reversalEffect = DecimalAmount::normalize($reversalEffect);
        $base = $this->backdatedProjection($entityId, $fundId, $financialAccountId, $targetDate, '0.00', true);
        return $this->replayAccountingDateCorrectionTimeline($base['timeline'][0]['current_balance'], $base['activity'], $targetDate, $sourceDate, $replacementEffect, $reversalEffect, $sourceJournalId);
    }

    /**
     * Canonical projection for one atomic correction unit that may contain
     * several source journals on the same Fund/account dimension. Each
     * reversal is applied immediately before its immutable source journal is
     * replayed, so the projection never invents a transient double outflow.
     *
     * @param list<array{source_date:string,source_journal_id:string,reversal_effect:string}> $reversals
     * @return array<string,mixed>
     */
    public function accountingDateCorrectionBatchProjection(
        string $entityId,
        string $fundId,
        string $financialAccountId,
        string $targetDate,
        string $replacementEffect,
        array $reversals,
    ): array {
        $base = $this->backdatedProjection($entityId, $fundId, $financialAccountId, $targetDate, '0.00', true);

        return $this->replayAccountingDateCorrectionBatchTimeline(
            $base['timeline'][0]['current_balance'],
            $base['activity'],
            $targetDate,
            $replacementEffect,
            $reversals,
        );
    }

    /** @param array<int,array<string,mixed>> $activity @param list<array{source_date:string,source_journal_id:string,reversal_effect:string}> $reversals */
    public function replayAccountingDateCorrectionBatchTimeline(string $openingBalance, array $activity, string $targetDate, string $replacementEffect, array $reversals): array
    {
        $running = DecimalAmount::normalize($openingBalance);
        $correctionEffect = DecimalAmount::normalize($replacementEffect);
        $projected = DecimalAmount::add($running, $correctionEffect);
        $minimum = $projected;
        $minimumDate = $targetDate;
        $pending = collect($reversals)->map(fn (array $row): array => [
            'source_date' => $row['source_date'],
            'source_journal_id' => $row['source_journal_id'],
            'reversal_effect' => DecimalAmount::normalize($row['reversal_effect']),
            'applied' => false,
        ])->values()->all();
        $timeline = [[
            'accounting_date' => $targetDate,
            'posting_sequence' => null,
            'activity_kind' => 'proposed_replacement_batch',
            'activity_reference' => null,
            'current_balance' => $running,
            'correction_effect' => $correctionEffect,
            'projected_balance' => $projected,
        ]];

        foreach ($activity as $row) {
            foreach ($pending as $index => $reversal) {
                if ($reversal['applied']) continue;
                if (($row['journal_id'] ?? null) === $reversal['source_journal_id'] || $row['accounting_date'] > $reversal['source_date']) {
                    $correctionEffect = DecimalAmount::add($correctionEffect, $reversal['reversal_effect']);
                    $this->appendCorrectionProjectionPoint($timeline, $reversal['source_date'], 'proposed_reversal', $running, $correctionEffect, $minimum, $minimumDate);
                    $pending[$index]['applied'] = true;
                }
            }
            $running = DecimalAmount::add($running, $row['balance_effect']);
            $projected = DecimalAmount::add($running, $correctionEffect);
            $timeline[] = [
                'accounting_date' => $row['accounting_date'],
                'posting_sequence' => $row['posting_sequence'],
                'activity_kind' => $row['kind'],
                'activity_reference' => $row['ledger_entry_id'] ?? $row['journal_id'],
                'current_balance' => $running,
                'correction_effect' => $correctionEffect,
                'projected_balance' => $projected,
            ];
            if (DecimalAmount::compare($projected, $minimum) < 0) {
                $minimum = $projected;
                $minimumDate = $row['accounting_date'];
            }
        }
        foreach ($pending as $reversal) {
            if ($reversal['applied']) continue;
            $correctionEffect = DecimalAmount::add($correctionEffect, $reversal['reversal_effect']);
            $this->appendCorrectionProjectionPoint($timeline, $reversal['source_date'], 'proposed_reversal', $running, $correctionEffect, $minimum, $minimumDate);
        }

        return [
            'opening_balance_at_target' => DecimalAmount::normalize($openingBalance),
            'replacement_effect' => DecimalAmount::normalize($replacementEffect),
            'reversal_effect' => DecimalAmount::sum(array_column($reversals, 'reversal_effect')),
            'ending_cumulative_effect' => $correctionEffect,
            'minimum_projected_balance' => $minimum,
            'minimum_projected_date' => $minimumDate,
            'timeline' => $timeline,
        ];
    }

    /** @param array<int,array<string,mixed>> $activity @return array<string,mixed> */
    public function replayAccountingDateCorrectionTimeline(string $openingBalance, array $activity, string $targetDate, string $sourceDate, string $replacementEffect, string $reversalEffect, ?string $sourceJournalId): array
    {
        $running=DecimalAmount::normalize($openingBalance);$correctionEffect=DecimalAmount::normalize($replacementEffect);$reversalEffect=DecimalAmount::normalize($reversalEffect);
        $projected=DecimalAmount::add($running,$correctionEffect);$minimum=$projected;$minimumDate=$targetDate;$reversalApplied=false;
        $timeline=[['accounting_date'=>$targetDate,'posting_sequence'=>null,'activity_kind'=>'proposed_replacement','activity_reference'=>null,'current_balance'=>$running,'correction_effect'=>$correctionEffect,'projected_balance'=>$projected]];
        foreach ($activity as $index => $row) {
            // The governed reversal and the source journal form one atomic
            // correction pair. Apply the reversal immediately before replaying
            // the source journal's liquidity entry; otherwise the replay
            // invents a transient double-outflow between the original line and
            // its reversal and can report a false negative minimum balance.
            if (! $reversalApplied && $sourceJournalId !== null && ($row['journal_id'] ?? null) === $sourceJournalId) {
                $correctionEffect = DecimalAmount::add($correctionEffect, $reversalEffect);
                $this->appendCorrectionProjectionPoint($timeline, $sourceDate, 'proposed_reversal', $running, $correctionEffect, $minimum, $minimumDate);
                $reversalApplied = true;
            }
            if (! $reversalApplied && $row['accounting_date'] > $sourceDate) {
                $correctionEffect = DecimalAmount::add($correctionEffect, $reversalEffect);
                $this->appendCorrectionProjectionPoint($timeline, $sourceDate, 'proposed_reversal', $running, $correctionEffect, $minimum, $minimumDate);
                $reversalApplied = true;
            }
            $running = DecimalAmount::add($running, $row['balance_effect']);
            $projected = DecimalAmount::add($running, $correctionEffect);
            $timeline[] = [
                'accounting_date' => $row['accounting_date'],
                'posting_sequence' => $row['posting_sequence'],
                'activity_kind' => $row['kind'],
                'activity_reference' => $row['ledger_entry_id'] ?? $row['journal_id'],
                'current_balance' => $running,
                'correction_effect' => $correctionEffect,
                'projected_balance' => $projected,
            ];
            if (DecimalAmount::compare($projected, $minimum) < 0) {
                $minimum = $projected;
                $minimumDate = $row['accounting_date'];
            }
            $nextDate = $activity[$index + 1]['accounting_date'] ?? null;
            if (! $reversalApplied && $sourceJournalId === null && $row['accounting_date'] === $sourceDate && $nextDate !== $sourceDate) {
                $correctionEffect = DecimalAmount::add($correctionEffect, $reversalEffect);
                $this->appendCorrectionProjectionPoint($timeline, $sourceDate, 'proposed_reversal', $running, $correctionEffect, $minimum, $minimumDate);
                $reversalApplied = true;
            }
        }
        if (! $reversalApplied) {
            $correctionEffect = DecimalAmount::add($correctionEffect, $reversalEffect);
            $this->appendCorrectionProjectionPoint($timeline, $sourceDate, 'proposed_reversal', $running, $correctionEffect, $minimum, $minimumDate);
        }

        return [
            'opening_balance_at_target' => DecimalAmount::normalize($openingBalance),
            'replacement_effect' => $replacementEffect,
            'reversal_effect' => $reversalEffect,
            'ending_cumulative_effect' => $correctionEffect,
            'minimum_projected_balance' => $minimum,
            'minimum_projected_date' => $minimumDate,
            'timeline' => $timeline,
        ];
    }

    /** @param array<int,array<string,mixed>> $timeline */
    private function appendCorrectionProjectionPoint(array &$timeline, string $date, string $kind, string $running, string $effect, string &$minimum, string &$minimumDate): void
    {
        $projected = DecimalAmount::add($running, $effect);
        $timeline[] = ['accounting_date' => $date, 'posting_sequence' => null, 'activity_kind' => $kind, 'activity_reference' => null, 'current_balance' => $running, 'correction_effect' => $effect, 'projected_balance' => $projected];
        if (DecimalAmount::compare($projected, $minimum) < 0) {
            $minimum = $projected;
            $minimumDate = $date;
        }
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
                'journal.posting_sequence',
                'journal.accounting_date',
                'journal.reversal_of_journal_id',
                'financial_transaction.source_reference',
                'original_transaction.source_reference as original_source_reference',
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
    private function applyOriginalCashTromolComposition(array &$rows, object $event, ?string $fundId, string $amount): void
    {
        if ($fundId === null || $event->source_fund_id === $fundId) {
            $this->apply($rows, $event, 'source', DecimalAmount::negate($amount));
        }
        if ($fundId === null || $event->destination_fund_id === $fundId) {
            $this->apply($rows, $event, 'destination', $amount);
        }
    }

    private function isSupersededCashTromolEvent(object $event): bool
    {
        $sourceReference = $event->reversal_of_journal_id !== null
            ? $event->original_source_reference
            : $event->source_reference;

        return MrjZiswafOpeningPosition::isSupersededCashTromolTransferReference($sourceReference);
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
