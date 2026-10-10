<?php

namespace App\Domain\FinancialV2\Reporting;

use App\Domain\FinancialV2\DecimalAmount;
use App\Models\FinancialV2\AccountingEntity;
use Illuminate\Support\Collection;

final class ZiswafRunningBalanceService
{
    public function __construct(private readonly FinancialReportService $reports) {}

    /**
     * @param  array<int, string>  $fundIds
     * @return array{opening_balance:string, closing_balance:string, rows:array<int, array<string, mixed>>}
     */
    public function report(AccountingEntity $entity, string $from, string $through, array $fundIds, ?string $displayType = null): array
    {
        $feed = $this->reports->fundMovementsForFunds($entity->id, $from, $through, $fundIds);
        $opening = DecimalAmount::sum(array_values($feed['opening_by_fund']));
        $running = $opening;

        $rows = collect($feed['rows'])
            ->groupBy('journal_id')
            ->map(function (Collection $lines) use (&$running): array {
                $first = $lines->first();
                $delta = DecimalAmount::sum($lines->pluck('fund_balance_delta'));
                $running = DecimalAmount::add($running, $delta);

                return [
                    'journal_id' => $first['journal_id'],
                    'transaction_id' => $first['transaction_id'],
                    'date' => $first['accounting_date'],
                    'posting_sequence' => $first['posting_sequence'],
                    'description' => $first['description'],
                    'fund' => $lines->pluck('fund_name')->filter()->unique()->join(', '),
                    'type' => $first['transaction_type_code'],
                    'in' => DecimalAmount::compare($delta, '0.00') > 0 ? $delta : '0.00',
                    'out' => DecimalAmount::compare($delta, '0.00') < 0 ? DecimalAmount::negate($delta) : '0.00',
                    'delta' => $delta,
                    'running_balance' => $running,
                ];
            })
            ->values();

        return [
            'opening_balance' => $opening,
            'closing_balance' => $running,
            // Filtering is presentation-only. Running balances above always
            // include every official movement in the selected Fund scope.
            'rows' => $rows
                ->when($displayType, fn (Collection $items) => $items->where('type', $displayType))
                ->values()
                ->all(),
        ];
    }
}
