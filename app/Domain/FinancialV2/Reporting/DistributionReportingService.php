<?php

namespace App\Domain\FinancialV2\Reporting;

use App\Domain\FinancialV2\DecimalAmount;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Aggregate DTOs only: never select recipient identity or operational notes. */
final class DistributionReportingService
{
    public function __construct(private readonly PostedLedgerQuery $ledger, private readonly FinancialReportDefinitions $definitions) {}

    public function report(string $entityId, string $from, string $through, array $fundIds, ?string $programId = null, bool $public = false, bool $fundFiltered = false): array
    {
        $itemTotals = DB::table('financial_v2_distribution_items')->select('distribution_id')
            ->selectRaw('COUNT(*) as recipient_count, SUM(amount) as operational_total')->groupBy('distribution_id');
        $events = DB::table('financial_v2_distributions as distribution')
            ->join('financial_v2_programs as program', 'program.id', '=', 'distribution.program_id')
            ->leftJoinSub($itemTotals, 'items', fn ($j) => $j->on('items.distribution_id', '=', 'distribution.id'))
            ->leftJoin('financial_v2_fund_realizations as realization', 'realization.id', '=', 'distribution.realization_id')
            ->leftJoin('financial_v2_transactions as transaction', 'transaction.id', '=', 'realization.transaction_id')
            ->where('distribution.accounting_entity_id', $entityId)
            ->where('distribution.starts_on', '<=', $through)->where('distribution.ends_on', '>=', $from)
            ->when($programId, fn ($q) => $q->where('distribution.program_id', $programId))
            ->select('distribution.id', 'distribution.program_id', 'program.name as program_name', 'distribution.period_label', 'distribution.starts_on', 'distribution.ends_on', 'distribution.status', 'items.recipient_count', 'items.operational_total', 'transaction.id as transaction_id', 'transaction.status as financial_status', 'transaction.gross_amount', 'realization.status as realization_status')
            ->orderBy('distribution.starts_on')->orderBy('distribution.id')->get();

        // Same expense/type source as canonical programExpenseFunding, never item amounts as actuals.
        $facts = $this->ledger->ledger($entityId, $through)
            ->join('financial_v2_accounts as account', 'account.id', '=', 'ledger.account_id')
            ->where('ledger.accounting_date', '>=', $from)->where('account.account_class', 'expense')
            ->whereIn('transaction_type.code', $this->definitions->cashOutTypes())
            ->whereIn('financial_transaction.id', $events->pluck('transaction_id')->filter())
            ->select('financial_transaction.id as transaction_id', 'ledger.program_id', 'ledger.fund_id')
            ->selectRaw('SUM(ledger.signed_amount) as actual_amount')
            ->groupBy('financial_transaction.id', 'ledger.program_id', 'ledger.fund_id')->get()->groupBy('transaction_id');
        $splits = DB::table('financial_v2_transaction_splits')->whereIn('transaction_id', $events->pluck('transaction_id')->filter())
            ->select('transaction_id', 'program_id', 'fund_id', 'split_amount')->get()->groupBy('transaction_id');

        $acceptedIds = [];
        $rows = [];
        foreach ($events as $event) {
            $parts = $splits->get($event->transaction_id, collect());
            $inScope = $parts->isNotEmpty() && $parts->every(fn ($part) => $part->program_id === $event->program_id && in_array($part->fund_id, $fundIds, true));
            if (($public || $fundFiltered) && ! $inScope) {
                continue;
            }
            $ledgerRows = $facts->get($event->transaction_id, collect());
            $actual = DecimalAmount::sum($ledgerRows->pluck('actual_amount'));
            $operational = DecimalAmount::normalize((string) ($event->operational_total ?? 0));
            $posted = $event->status === 'finalized' && $event->financial_status === 'posted' && $event->realization_status === 'recorded'
                && $inScope && $ledgerRows->isNotEmpty() && (int) $event->recipient_count > 0
                && $ledgerRows->every(fn ($row) => $row->program_id === $event->program_id && in_array($row->fund_id, $fundIds, true))
                && DecimalAmount::equals($actual, $operational) && DecimalAmount::equals($actual, (string) $event->gross_amount)
                && DecimalAmount::equals($operational, DecimalAmount::sum($parts->pluck('split_amount')));
            if ($public && ! $posted) {
                continue;
            }
            $acceptedIds[] = $event->id;
            $row = ['program_id' => $event->program_id, 'program_name' => $event->program_name, 'period' => $event->period_label,
                'from' => $event->starts_on, 'through' => $event->ends_on, 'recipient_count' => (int) $event->recipient_count,
                'actual_amount' => $posted ? $actual : '0.00'];
            if (! $public) {
                $row += ['distribution_id' => $event->id, 'operational_total' => $operational, 'operational_status' => $event->status, 'financial_status' => $event->financial_status ?? 'unposted'];
            }
            $rows[] = $row;
        }
        $unique = DB::table('financial_v2_distribution_items as item')
            ->join('financial_v2_distributions as distribution', 'distribution.id', '=', 'item.distribution_id')
            ->whereIn('distribution.id', $acceptedIds);
        $byProgram = (clone $unique)->select('distribution.program_id')->selectRaw('COUNT(DISTINCT item.beneficiary_id) as unique_beneficiaries')->groupBy('distribution.program_id')->pluck('unique_beneficiaries', 'program_id');
        $programs = collect($rows)->groupBy('program_id')->map(fn ($group, $id) => [
            'program_id' => $id, 'program_name' => $group->first()['program_name'], 'distribution_events' => $group->count(),
            'unique_beneficiaries' => (int) $byProgram->get($id, 0), 'actual_amount' => DecimalAmount::sum($group->pluck('actual_amount')),
        ] + ($public ? [] : ['operational_total' => DecimalAmount::sum($group->pluck('operational_total'))]))->values()->all();

        $regions = DB::table('financial_v2_distribution_items')->whereIn('distribution_id', $acceptedIds)
            ->get(['beneficiary_id', 'identity_snapshot'])
            ->map(function (object $item) use ($public): array {
                $snapshot = json_decode((string) $item->identity_snapshot, true) ?: [];

                return [
                    'beneficiary_id' => $item->beneficiary_id,
                    'rt' => (string) ($snapshot['rt'] ?? ''),
                    'rw' => (string) ($snapshot['rw'] ?? ''),
                    'coordinator' => $public ? null : (string) ($snapshot['rt_coordinator_name'] ?? ''),
                ];
            })->groupBy(fn (array $row): string => json_encode([$row['rt'], $row['rw'], $row['coordinator']]))
            ->map(function (Collection $group) use ($public): array {
                $row = ['rt' => $group->first()['rt'], 'rw' => $group->first()['rw'], 'recipient_count' => $group->pluck('beneficiary_id')->unique()->count()];

                return $public ? $row : $row + ['coordinator' => $group->first()['coordinator']];
            })->values()->all();

        return ['events' => $rows, 'programs' => $programs, 'regions' => $regions, 'unique_beneficiaries' => $unique->distinct()->count('item.beneficiary_id'), 'distribution_events' => count($rows)];
    }
}
