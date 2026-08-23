<?php

namespace App\Domain\FinancialV2\Reporting;

use App\Domain\FinancialV2\DecimalAmount;
use App\Domain\FinancialV2\MrjZiswafOpeningPosition;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\HistoricalFundHistory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

/**
 * Read-only public presentation of the governed ZISWAF disclosure scope.
 *
 * The allow-list in financial_reporting.php is deliberately explicit: a Fund
 * never becomes public merely because its code or name happens to contain a
 * familiar word. Balances and period movement remain delegated to
 * FinancialReportService, whose only financial source is the Posted V2
 * General Ledger. Historical source rows are returned separately, and only
 * as an explanation of an already-posted opening position.
 */
final class PublicZiswafReportService
{
    public function __construct(
        private readonly FinancialReportService $reports,
        private readonly FundHistoryReadService $fundHistory,
        private readonly PostedLedgerQuery $postedLedger,
    ) {}

    /** @return array<string, mixed> */
    public function report(?string $requestedFrom = null, ?string $requestedThrough = null): array
    {
        $entity = $this->publicEntity();
        [$from, $through] = $this->period($entity, $requestedFrom, $requestedThrough);
        $funds = $this->publishedFunds($entity);
        $fundReport = $this->reports->report('ziswaf', $entity->id, $from, $through);
        $accountReport = $this->reports->report('account-balance', $entity->id, $from, $through);

        $fundRows = collect($fundReport['data']['rows'] ?? [])->keyBy('code');
        $accountRows = collect($accountReport['data']['rows'] ?? [])->keyBy('code');
        $compositionByFund = collect($fundReport['data']['account_composition'] ?? [])
            ->filter(fn (array $row): bool => in_array($row['fund_code'], $this->fundCodes(), true)
                && in_array($row['financial_account_code'], $this->financialAccountCodes(), true))
            ->groupBy('fund_id')
            ->map(fn (Collection $rows): array => $rows
                ->filter(fn (array $row): bool => ! DecimalAmount::equals($row['liquidity_balance'], '0.00'))
                ->map(fn (array $row): array => [
                    'name' => $row['financial_account_name'],
                    'balance' => DecimalAmount::normalize((string) $row['liquidity_balance']),
                ])
                ->sortBy('name')
                ->values()
                ->all());
        // A published Financial Account can hold more than one Fund. Public
        // liquidity must therefore be limited to the disclosed Fund scope,
        // rather than exposing a raw account balance that includes a private
        // Fund. The composition is already calculated by the canonical report
        // query from posted liquidity lines and IFT attribution.
        $publicLiquidityByAccount = collect($fundReport['data']['account_composition'] ?? [])
            ->filter(fn (array $row): bool => in_array($row['fund_code'], $this->fundCodes(), true)
                && in_array($row['financial_account_code'], $this->financialAccountCodes(), true))
            ->groupBy('financial_account_code')
            ->map(fn (Collection $rows): string => DecimalAmount::sum($rows->pluck('liquidity_balance')));
        $fundCards = $funds->map(function (Fund $fund) use ($fundRows, $compositionByFund): array {
            /** @var array<string, mixed>|null $row */
            $row = $fundRows->get($fund->code);

            return $this->fundCard($fund, is_array($row) ? $row : [], $compositionByFund->get($fund->id, []));
        })->values()->all();
        $accounts = collect($this->financialAccountCodes())->map(function (string $code) use ($accountRows, $publicLiquidityByAccount): ?array {
            /** @var array<string, mixed>|null $row */
            $row = $accountRows->get($code);
            if (! is_array($row)) {
                return null;
            }

            return [
                'name' => $row['name'],
                'balance' => DecimalAmount::normalize((string) $publicLiquidityByAccount->get($code, '0.00')),
            ];
        })->filter()->values()->all();

        $totalFundBalance = DecimalAmount::sum(array_column($fundCards, 'balance'));
        $totalLiquidity = DecimalAmount::sum(array_column($accounts, 'balance'));

        return [
            'entity_name' => $entity->name,
            'period_from' => $from,
            'as_of' => $through,
            'updated_at' => $this->latestPostedAt($entity, $through),
            'total_fund_balance' => $totalFundBalance,
            'total_liquidity' => $totalLiquidity,
            'is_reconciled' => DecimalAmount::equals($totalFundBalance, $totalLiquidity),
            'financial_accounts' => $accounts,
            'funds' => $fundCards,
            'fund_transfers' => $this->fundTransfers($entity, $funds, $from, $through),
        ];
    }

    /** @return array<string, mixed> */
    public function fundDetail(string $fundCode, ?string $requestedAsOf = null): array
    {
        $entity = $this->publicEntity();
        [$from, $through] = $this->period($entity, null, $requestedAsOf);
        $fund = $this->publishedFunds($entity)->firstWhere('code', $fundCode);
        if (! $fund instanceof Fund) {
            throw (new ModelNotFoundException)->setModel(Fund::class, [$fundCode]);
        }

        $fundReport = $this->reports->report('ziswaf', $entity->id, $from, $through, ['fund_id' => $fund->id]);
        $row = collect($fundReport['data']['rows'] ?? [])->first();
        $history = $this->fundHistory->history($entity, $fund, [
            'from' => $from,
            'through' => $through,
            'per_page' => 50,
        ]);

        return [
            'entity_name' => $entity->name,
            'period_from' => $from,
            'as_of' => $through,
            'updated_at' => $this->latestPostedAt($entity, $through),
            'fund' => $this->fundCard($fund, is_array($row) ? $row : []),
            'official_history' => collect($history['history']->items())
                ->map(fn (array $item): array => $this->officialHistoryItem($item))
                ->values()
                ->all(),
            'source_opening_history' => collect($history['source_history']['rows'] ?? [])
                ->filter(fn (array $item): bool => in_array($item['entry_kind'], ['opening', 'receipt', 'usage', 'adjustment_in', 'adjustment_out'], true) && $item['status'] !== 'void')
                ->map(fn (array $item): array => [
                    'date' => $item['date_label'],
                    'description' => $item['description'],
                    'kind' => in_array($item['entry_kind'], ['usage', 'adjustment_out'], true) ? 'expense' : 'receipt',
                    'amount' => in_array($item['entry_kind'], ['usage', 'adjustment_out'], true) ? $item['usage'] : $item['receipt'],
                    'running_balance' => $item['running_balance'],
                ])
                ->values()
                ->all(),
            'source_opening_as_of' => $history['source_history']['opening_source_reference'] ? '27 Juni 2026' : null,
        ];
    }

    /** @return array<string, mixed> */
    public function pdfReport(?string $requestedFrom = null, ?string $requestedThrough = null): array
    {
        $report = $this->report($requestedFrom, $requestedThrough);
        $entity = $this->publicEntity();
        $funds = $this->publishedFunds($entity);
        $movementFeed = $this->reports->fundMovementsForFunds(
            $entity->id,
            $report['period_from'],
            $report['as_of'],
            $funds->pluck('id')->all(),
        );

        $details = $this->pdfFundDetails($entity, $funds, $movementFeed, $report['as_of']);

        return $report + [
            'fund_details' => array_values($details),
        ];
    }

    /** @return array{0:string, 1:string} */
    private function period(AccountingEntity $entity, ?string $requestedFrom, ?string $requestedThrough): array
    {
        $latest = $this->postedLedger->ledger($entity->id, '9999-12-31')->max('ledger.accounting_date');
        $through = $requestedThrough ?: ($latest ?: now()->toDateString());
        $earliest = $this->postedLedger->ledger($entity->id, $through)->min('ledger.accounting_date');
        $from = $requestedFrom ?: ($earliest ?: $through);
        if ($from > $through) {
            throw new \InvalidArgumentException('Tanggal mulai laporan tidak boleh melewati tanggal akhir.');
        }

        return [$from, $through];
    }

    private function publicEntity(): AccountingEntity
    {
        $entity = AccountingEntity::query()
            ->where('code', (string) config('financial_reporting.public_ziswaf.entity_code'))
            ->where('status', 'active')
            ->first();

        if (! $entity) {
            throw (new ModelNotFoundException)->setModel(AccountingEntity::class);
        }

        return $entity;
    }

    /** @return Collection<int, Fund> */
    private function publishedFunds(AccountingEntity $entity): Collection
    {
        $codes = $this->fundCodes();

        return Fund::query()
            ->where('accounting_entity_id', $entity->id)
            ->where('status', 'active')
            ->whereIn('code', $codes)
            ->get()
            ->sortBy(fn (Fund $fund): int => array_search($fund->code, $codes, true))
            ->values();
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function fundCard(Fund $fund, array $row, array $accountComposition = []): array
    {
        $transferIn = DecimalAmount::normalize((string) ($row['transfer_in'] ?? '0.00'));
        $transferOut = DecimalAmount::normalize((string) ($row['transfer_out'] ?? '0.00'));

        return [
            'code' => $fund->code,
            'name' => $fund->name,
            'receipts' => DecimalAmount::normalize((string) ($row['receipts'] ?? '0.00')),
            'expenses' => DecimalAmount::normalize((string) ($row['expenses'] ?? '0.00')),
            'transfer_in' => $transferIn,
            'transfer_out' => $transferOut,
            'transfer_net' => DecimalAmount::subtract($transferIn, $transferOut),
            'balance' => DecimalAmount::normalize((string) ($row['fund_balance'] ?? '0.00')),
            'account_composition' => $accountComposition,
        ];
    }

    /**
     * The official movement feed is fetched in one canonical report query.
     * Historical source rows are separately fetched once for the disclosed
     * Funds; they explain the governed Opening Balance and never become a
     * Journal, Ledger, or second financial fact.
     *
     * @param  Collection<int, Fund>  $funds
     * @param  array{opening_by_fund: array<string, string>, rows: array<int, array<string, mixed>>}  $movementFeed
     * @return array<string, array<string, mixed>>
     */
    private function pdfFundDetails(AccountingEntity $entity, Collection $funds, array $movementFeed, string $through): array
    {
        $details = $funds->mapWithKeys(fn (Fund $fund): array => [$fund->id => [
            'code' => $fund->code,
            'name' => $fund->name,
            'official_entries' => [],
            'source_entries' => [],
        ]])->all();
        $openingByFund = $movementFeed['opening_by_fund'];
        $cashCompositionDeltas = [];
        $movementRows = [];
        foreach ($movementFeed['rows'] as $row) {
            if (MrjZiswafOpeningPosition::isSupersededCashTromolTransferReference($row['source_reference'] ?? null)) {
                $cashCompositionDeltas[$row['fund_id']] = DecimalAmount::add(
                    $cashCompositionDeltas[$row['fund_id']] ?? '0.00',
                    $row['fund_balance_delta'],
                );

                continue;
            }
            $movementRows[] = $row;
        }
        foreach ($cashCompositionDeltas as $fundId => $delta) {
            $openingIndex = collect($movementRows)->search(fn (array $row): bool => $row['fund_id'] === $fundId
                && $row['transaction_type_code'] === 'OPB'
                && ! DecimalAmount::equals($row['fund_balance_delta'], '0.00'));
            if ($openingIndex === false) {
                $openingByFund[$fundId] = DecimalAmount::add($openingByFund[$fundId] ?? '0.00', $delta);

                continue;
            }
            $movementRows[$openingIndex]['fund_balance_delta'] = DecimalAmount::add(
                $movementRows[$openingIndex]['fund_balance_delta'],
                $delta,
            );
        }

        $officialGroups = [];
        foreach ($movementRows as $row) {
            $key = $row['fund_id'].'|'.$row['journal_id'];
            if (! isset($officialGroups[$key])) {
                // Never seed a group with the first Ledger contribution: that
                // contribution is added immediately below. Keeping it here
                // used to double every one-line IFT in the public PDF.
                $officialGroups[$key] = $row;
                $officialGroups[$key]['fund_balance_delta'] = '0.00';
            }
            $officialGroups[$key]['fund_balance_delta'] = DecimalAmount::add(
                $officialGroups[$key]['fund_balance_delta'],
                $row['fund_balance_delta'],
            );
        }

        $running = $openingByFund;
        foreach ($officialGroups as $row) {
            if (DecimalAmount::equals($row['fund_balance_delta'], '0.00')) {
                continue;
            }
            $fundId = $row['fund_id'];
            if (! isset($details[$fundId])) {
                continue;
            }
            $running[$fundId] = DecimalAmount::add($running[$fundId] ?? '0.00', $row['fund_balance_delta']);
            $kind = match ($row['transaction_type_code']) {
                'RCV' => 'receipt',
                'PAY' => 'expense',
                'IFT' => 'transfer',
                default => 'opening',
            };
            $details[$fundId]['official_entries'][] = [
                'journal_key' => $row['journal_id'],
                'source_reference' => $row['source_reference'],
                'date' => $row['accounting_date'],
                'description' => $kind === 'opening' ? 'Saldo awal yang tercatat resmi' : $row['description'],
                'kind' => $kind,
                'amount' => $this->absoluteAmount($row['fund_balance_delta']),
                'delta' => $row['fund_balance_delta'],
                'running_balance' => $running[$fundId],
            ];
        }

        $sourceRows = HistoricalFundHistory::query()
            ->where('accounting_entity_id', $entity->id)
            ->whereIn('fund_id', array_keys($details))
            ->where('effective_date', '<=', $through)
            ->whereIn('entry_kind', ['opening', 'receipt', 'usage', 'adjustment_in', 'adjustment_out'])
            ->where('status', '!=', 'void')
            ->orderBy('fund_id')
            ->orderBy('effective_date')
            ->orderBy('source_sequence')
            ->orderBy('created_at')
            ->get(['fund_id', 'effective_date', 'date_label', 'description', 'entry_kind', 'amount']);
        foreach ($sourceRows as $row) {
            if (! isset($details[$row->fund_id])) {
                continue;
            }
            $details[$row->fund_id]['source_entries'][] = [
                'date' => $row->date_label ?: $row->effective_date,
                'description' => $row->description,
                'kind' => match ($row->entry_kind) {
                    'usage', 'adjustment_out' => 'expense',
                    'receipt', 'adjustment_in' => 'receipt',
                    default => 'opening',
                },
                'amount' => DecimalAmount::normalize((string) $row->amount),
            ];
        }

        return $details;
    }

    /**
     * The public transfer disclosure has one canonical row per posted IFT
     * source reference. It deliberately does not aggregate the debit and
     * credit Fund sides as two business events.
     *
     * @param  Collection<int, Fund>  $publishedFunds
     * @return array<int, array{event_id:string,category:string,description:string,from:string,to:string,amount:string}>
     */
    private function fundTransfers(AccountingEntity $entity, Collection $publishedFunds, string $from, string $through): array
    {
        $publishedFundIds = $publishedFunds->pluck('id')->all();
        if ($publishedFundIds === []) {
            return [];
        }

        $records = $this->postedLedger->journals($entity->id, $through)
            ->leftJoin('financial_v2_interfund_transfers as direct_interfund', 'direct_interfund.transaction_id', '=', 'financial_transaction.id')
            ->leftJoin('financial_v2_interfund_transfers as original_interfund', 'original_interfund.transaction_id', '=', 'original_transaction.id')
            ->leftJoin('financial_v2_funds as direct_source_fund', 'direct_source_fund.id', '=', 'direct_interfund.source_fund_id')
            ->leftJoin('financial_v2_funds as direct_destination_fund', 'direct_destination_fund.id', '=', 'direct_interfund.destination_fund_id')
            ->leftJoin('financial_v2_funds as original_source_fund', 'original_source_fund.id', '=', 'original_interfund.source_fund_id')
            ->leftJoin('financial_v2_funds as original_destination_fund', 'original_destination_fund.id', '=', 'original_interfund.destination_fund_id')
            ->where('journal.accounting_date', '>=', $from)
            ->whereRaw("COALESCE(original_transaction_type.code, transaction_type.code) = 'IFT'")
            ->orderBy('journal.accounting_date')
            ->orderBy('journal.posting_sequence')
            ->get([
                'journal.id as journal_id', 'journal.reversal_of_journal_id',
                'financial_transaction.source_reference', 'financial_transaction.gross_amount',
                'original_transaction.source_reference as original_source_reference', 'original_transaction.gross_amount as original_gross_amount',
                'direct_source_fund.id as direct_source_fund_id', 'direct_source_fund.name as direct_source_name',
                'direct_destination_fund.id as direct_destination_fund_id', 'direct_destination_fund.name as direct_destination_name',
                'original_source_fund.id as original_source_fund_id', 'original_source_fund.name as original_source_name',
                'original_destination_fund.id as original_destination_fund_id', 'original_destination_fund.name as original_destination_name',
            ]);

        return $records->map(function (object $row) use ($publishedFundIds): ?array {
            $isReversal = $row->reversal_of_journal_id !== null;
            $sourceFundId = $isReversal ? $row->original_destination_fund_id : $row->direct_source_fund_id;
            $destinationFundId = $isReversal ? $row->original_source_fund_id : $row->direct_destination_fund_id;
            if (! in_array($sourceFundId, $publishedFundIds, true) || ! in_array($destinationFundId, $publishedFundIds, true)) {
                return null;
            }

            $sourceReference = (string) ($isReversal ? $row->original_source_reference : $row->source_reference);
            if (MrjZiswafOpeningPosition::isSupersededCashTromolTransferReference($sourceReference)) {
                return null;
            }
            $fromName = $isReversal ? $row->original_destination_name : $row->direct_source_name;
            $toName = $isReversal ? $row->original_source_name : $row->direct_destination_name;
            $amount = DecimalAmount::normalize((string) ($isReversal ? $row->original_gross_amount : $row->gross_amount));

            return [
                'event_id' => 'ift:'.$sourceReference,
                'category' => 'Pemindahan Dana',
                'description' => 'Pemindahan antar peruntukan Dana.',
                'from' => $fromName,
                'to' => $toName,
                'amount' => $amount,
            ];
        })->filter()->unique('event_id')->values()->all();
    }

    private function absoluteAmount(string $amount): string
    {
        return DecimalAmount::compare($amount, '0.00') < 0 ? DecimalAmount::negate($amount) : $amount;
    }

    /** @param array<string, mixed> $item @return array<string, string> */
    private function officialHistoryItem(array $item): array
    {
        $type = $item['transaction_type_code'];
        $kind = match ($type) {
            'RCV' => 'receipt',
            'PAY' => 'expense',
            'IFT' => 'transfer',
            default => 'opening',
        };
        $amount = match ($kind) {
            'receipt' => $item['receipt'],
            'expense' => $item['usage'],
            'transfer' => $item['transfer'],
            default => $item['fund_balance_delta'],
        };

        return [
            'date' => $item['accounting_date'],
            // An opening source reference and correction rationale belong to
            // internal traceability, not the public surface. Public readers
            // only need the business-level effect.
            'description' => match ($kind) {
                'opening' => 'Saldo awal yang tercatat resmi',
                'transfer' => 'Pemindahan Dana antar peruntukan',
                default => $item['description'],
            },
            'kind' => $kind,
            'amount' => DecimalAmount::normalize((string) $amount),
            'running_balance' => DecimalAmount::normalize((string) $item['running_fund_balance']),
        ];
    }

    private function latestPostedAt(AccountingEntity $entity, string $through): ?string
    {
        $value = $this->postedLedger->journals($entity->id, $through)->max('journal.posted_at');

        return $value ? (string) $value : null;
    }

    /** @return array<int, string> */
    private function fundCodes(): array
    {
        return array_values(array_filter(config('financial_reporting.public_ziswaf.fund_codes', []), 'is_string'));
    }

    /** @return array<int, string> */
    private function financialAccountCodes(): array
    {
        return array_values(array_filter(config('financial_reporting.public_ziswaf.financial_account_codes', []), 'is_string'));
    }
}
