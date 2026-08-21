<?php

namespace App\Domain\FinancialV2\Reporting;

use App\Domain\FinancialV2\DecimalAmount;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\Fund;
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
    public function report(?string $requestedAsOf = null): array
    {
        $entity = $this->publicEntity();
        [$from, $through] = $this->period($entity, $requestedAsOf);
        $funds = $this->publishedFunds($entity);
        $fundReport = $this->reports->report('ziswaf', $entity->id, $from, $through);
        $accountReport = $this->reports->report('account-balance', $entity->id, $from, $through);

        $fundRows = collect($fundReport['data']['rows'] ?? [])->keyBy('code');
        $accountRows = collect($accountReport['data']['rows'] ?? [])->keyBy('code');
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
        $fundCards = $funds->map(function (Fund $fund) use ($fundRows): array {
            /** @var array<string, mixed>|null $row */
            $row = $fundRows->get($fund->code);

            return $this->fundCard($fund, is_array($row) ? $row : []);
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
        ];
    }

    /** @return array<string, mixed> */
    public function fundDetail(string $fundCode, ?string $requestedAsOf = null): array
    {
        $entity = $this->publicEntity();
        [$from, $through] = $this->period($entity, $requestedAsOf);
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

    /** @return array{0:string, 1:string} */
    private function period(AccountingEntity $entity, ?string $requestedAsOf): array
    {
        $latest = $this->postedLedger->ledger($entity->id, '9999-12-31')->max('ledger.accounting_date');
        $through = $requestedAsOf ?: ($latest ?: now()->toDateString());
        $earliest = $this->postedLedger->ledger($entity->id, $through)->min('ledger.accounting_date');

        return [$earliest ?: $through, $through];
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
    private function fundCard(Fund $fund, array $row): array
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
        ];
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
