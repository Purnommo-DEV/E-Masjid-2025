<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\BudgetAllocation;
use App\Models\FinancialV2\FundRealization;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Read model for allocation and realization history.
 *
 * Budget allocations remain plans. Realization totals are calculated only
 * from recorded links to posted payment transactions, never from plans or a
 * separately maintained balance column.
 */
final class AllocationHistoryReadService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function page(string $entityId, array $filters = []): LengthAwarePaginator
    {
        $query = BudgetAllocation::query()
            ->with([
                'fund:id,code,name',
                'program:id,code,name',
                'category:id,code,name',
                'versions',
                'cancelledBy:id,name',
            ])
            ->where('accounting_entity_id', $entityId)
            ->when($filters['fund_id'] ?? null, fn ($builder, string $fundId) => $builder->where('fund_id', $fundId))
            ->when($filters['program_id'] ?? null, fn ($builder, string $programId) => $builder->where('program_id', $programId))
            ->when($filters['status'] ?? null, fn ($builder, string $status) => $builder->where('status', $status))
            ->when($filters['from'] ?? null, fn ($builder, string $from) => $builder->whereHas('versions', fn ($versions) => $versions->whereDate('effective_from', '>=', $from)))
            ->when($filters['through'] ?? null, fn ($builder, string $through) => $builder->whereHas('versions', fn ($versions) => $versions->whereDate('effective_from', '<=', $through)))
            ->latest('created_at');

        $perPage = min(100, max(10, (int) ($filters['per_page'] ?? 20)));
        $history = $query->paginate($perPage, ['*'], 'allocation_page')->withQueryString();
        $allocations = $history->getCollection();
        $versionIds = $allocations
            ->flatMap(fn (BudgetAllocation $allocation) => $allocation->versions->pluck('id'))
            ->unique()
            ->values();

        $realizations = $versionIds->isEmpty()
            ? collect()
            : FundRealization::query()
                ->select('financial_v2_fund_realizations.*')
                ->with(['transaction.type', 'transaction.primaryFinancialAccount', 'transaction.category'])
                ->join('financial_v2_transactions as transaction', 'transaction.id', '=', 'financial_v2_fund_realizations.transaction_id')
                ->join('financial_v2_journals as journal', 'journal.transaction_id', '=', 'transaction.id')
                ->where('financial_v2_fund_realizations.accounting_entity_id', $entityId)
                ->whereIn('financial_v2_fund_realizations.budget_allocation_version_id', $versionIds)
                ->where('financial_v2_fund_realizations.status', 'recorded')
                ->where('journal.journal_status', 'posted')
                ->orderBy('transaction.accounting_date')
                ->get();

        $actualByVersion = $realizations
            ->groupBy('budget_allocation_version_id')
            ->map(fn ($items) => DecimalAmount::sum($items->pluck('transaction.gross_amount')));
        $realizationsByVersion = $realizations->groupBy('budget_allocation_version_id');

        $history->setCollection($allocations->map(function (BudgetAllocation $allocation) use ($actualByVersion, $realizationsByVersion): array {
            $version = $allocation->versions
                ->sortByDesc('version_no')
                ->first();
            $allocated = $version ? DecimalAmount::normalize($version->allocated_amount) : '0.00';
            $actual = $version ? $actualByVersion->get($version->id, '0.00') : '0.00';

            return [
                'allocation' => $allocation,
                'version' => $version,
                'allocated' => $allocated,
                'realized' => $actual,
                'remaining' => DecimalAmount::subtract($allocated, $actual),
                'realizations' => $version ? $realizationsByVersion->get($version->id, collect()) : collect(),
            ];
        }));

        return $history;
    }

    /**
     * @return array{allocated: string, realized: string, remaining: string}
     */
    public function summary(string $entityId, ?string $fundId = null): array
    {
        $latestVersions = DB::table('financial_v2_budget_allocation_versions')
            ->select('budget_allocation_id', DB::raw('MAX(version_no) as version_no'))
            ->groupBy('budget_allocation_id');

        $allocated = DB::table('financial_v2_budget_allocations as allocation')
            ->joinSub($latestVersions, 'latest_version', fn ($join) => $join->on('latest_version.budget_allocation_id', '=', 'allocation.id'))
            ->join('financial_v2_budget_allocation_versions as version', function ($join): void {
                $join->on('version.budget_allocation_id', '=', 'latest_version.budget_allocation_id')
                    ->on('version.version_no', '=', 'latest_version.version_no');
            })
            ->where('allocation.accounting_entity_id', $entityId)
            ->when($fundId, fn ($query, string $id) => $query->where('allocation.fund_id', $id))
            ->whereNotIn('allocation.status', ['cancelled', 'superseded'])
            ->sum('version.allocated_amount');

        $realized = DB::table('financial_v2_fund_realizations as realization')
            ->join('financial_v2_budget_allocation_versions as version', 'version.id', '=', 'realization.budget_allocation_version_id')
            ->join('financial_v2_budget_allocations as allocation', 'allocation.id', '=', 'version.budget_allocation_id')
            ->join('financial_v2_transactions as transaction', 'transaction.id', '=', 'realization.transaction_id')
            ->join('financial_v2_journals as journal', 'journal.transaction_id', '=', 'transaction.id')
            ->where('realization.accounting_entity_id', $entityId)
            ->when($fundId, fn ($query, string $id) => $query->where('allocation.fund_id', $id))
            ->where('realization.status', 'recorded')
            ->where('journal.journal_status', 'posted')
            ->sum('transaction.gross_amount');

        $allocated = DecimalAmount::normalize($allocated);
        $realized = DecimalAmount::normalize($realized);

        return [
            'allocated' => $allocated,
            'realized' => $realized,
            'remaining' => DecimalAmount::subtract($allocated, $realized),
        ];
    }
}
