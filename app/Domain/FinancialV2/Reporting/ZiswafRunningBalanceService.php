<?php

namespace App\Domain\FinancialV2\Reporting;

use App\Domain\FinancialV2\DecimalAmount;
use App\Models\FinancialV2\AccountingEntity;
use Illuminate\Support\Collection;

final class ZiswafRunningBalanceService
{
    public function __construct(
        private readonly FinancialReportService $reports,
        private readonly AccountingDateCorrectionOperationalVisibility $correctionVisibility,
    ) {}

    /**
     * @param  array<int, string>  $fundIds
     * @return array{opening_balance:string, closing_balance:string, rows:array<int, array<string, mixed>>}
     */
    public function report(AccountingEntity $entity, string $from, string $through, array $fundIds, ?string $displayType = null): array
    {
        $feed = $this->reports->fundMovementsForFunds($entity->id, $from, $through, $fundIds);
        $opening = DecimalAmount::sum(array_values($feed['opening_by_fund']));
        $allMovementLines = collect($feed['rows']);
        $officialClosing = DecimalAmount::add($opening, DecimalAmount::sum($allMovementLines->pluck('fund_balance_delta')));
        $correctionProjection = $this->correctionVisibility->operationalProjection($entity->id);
        $hiddenIds = $correctionProjection['hidden_transaction_ids'];
        $replacementIds = $correctionProjection['replacement_transaction_ids'];

        $effectiveMovementLines = $allMovementLines
            ->reject(fn (array $row): bool => in_array($row['transaction_id'], $hiddenIds, true))
            ->values();
        $effectiveClosing = DecimalAmount::add($opening, DecimalAmount::sum($effectiveMovementLines->pluck('fund_balance_delta')));

        // A completed original/reversal pair must be net-zero in the selected
        // ledger scope. If that invariant ever fails, retain the full official
        // chronology rather than presenting a non-reconciling projection.
        $reconciled = DecimalAmount::equals($effectiveClosing, $officialClosing);
        if (! $reconciled) {
            $effectiveMovementLines = $allMovementLines;
            $replacementIds = [];
        }

        $running = $opening;

        $rows = $effectiveMovementLines
            ->groupBy('journal_id')
            ->map(function (Collection $lines) use (&$running, $replacementIds): array {
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
                    'business_date_corrected' => in_array($first['transaction_id'], $replacementIds, true),
                ];
            })
            ->values();

        return [
            'opening_balance' => $opening,
            'closing_balance' => $officialClosing,
            'reconciled_with_ledger' => $reconciled,
            // Type filtering remains presentation-only; balances above are
            // calculated before rows are narrowed for display.
            'rows' => $rows
                ->when($displayType, fn (Collection $items) => $items->where('type', $displayType))
                ->values()
                ->all(),
        ];
    }
}
