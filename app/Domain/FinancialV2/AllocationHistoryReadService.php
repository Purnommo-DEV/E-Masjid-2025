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
                'versions.fundings.fund:id,code,name',
                'cancelledBy:id,name',
            ])
            ->where('accounting_entity_id', $entityId)
            ->when($filters['fund_id'] ?? null, fn ($builder, string $fundId) => $builder->whereHas('versions.fundings', fn ($fundings) => $fundings->where('fund_id', $fundId)))
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
                ->with(['transaction.type', 'transaction.primaryFinancialAccount', 'transaction.category', 'transaction.splits.fund:id,code,name'])
                ->join('financial_v2_transactions as transaction', fn ($join) => $join->whereRaw('BINARY `transaction`.`id` = BINARY `financial_v2_fund_realizations`.`transaction_id`'))
                ->join('financial_v2_journals as journal', fn ($join) => $join->whereRaw('BINARY `journal`.`transaction_id` = BINARY `transaction`.`id`'))
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

        $filterFundId = $filters['fund_id'] ?? null;
        $history->setCollection($allocations->map(function (BudgetAllocation $allocation) use ($actualByVersion, $realizationsByVersion, $filterFundId): array {
            $versions = $allocation->versions->sortByDesc('version_no');
            $version = $allocation->status === 'approved'
                ? $versions->firstWhere('status', 'approved')
                : $versions->first();
            $pendingVersion = $allocation->status === 'approved'
                ? $versions->firstWhere('status', 'draft')
                : null;
            $allocated = $version
                ? DecimalAmount::normalize($filterFundId ? ($version->fundings->firstWhere('fund_id', $filterFundId)?->amount ?? '0.00') : $version->allocated_amount)
                : '0.00';
            $actual = ! $version
                ? '0.00'
                : ($filterFundId
                    ? DecimalAmount::sum($realizationsByVersion->get($version->id, collect())->flatMap(fn (FundRealization $realization) => $realization->transaction?->splits?->where('fund_id', $filterFundId)->pluck('split_amount') ?? collect()))
                    : $actualByVersion->get($version->id, '0.00'));

            return [
                'allocation' => $allocation,
                'version' => $version,
                'allocated' => $allocated,
                'realized' => $actual,
                'remaining' => DecimalAmount::subtract($allocated, $actual),
                'fundings' => $version?->fundings ?? collect(),
                'realizations' => $version ? $realizationsByVersion->get($version->id, collect()) : collect(),
                'pending_version' => $pendingVersion,
            ];
        }));

        return $history;
    }

    /**
     * @return array{allocated: string, realized: string, remaining: string}
     */
    public function summary(string $entityId, ?string $fundId = null): array
    {
        $latestVersions = DB::table('financial_v2_budget_allocation_versions as candidate')
            ->join('financial_v2_budget_allocations as candidate_allocation', fn ($join) => $join->whereRaw('BINARY `candidate_allocation`.`id` = BINARY `candidate`.`budget_allocation_id`'))
            ->where(function ($query): void {
                $query->where(function ($approved): void {
                    $approved->where('candidate_allocation.status', 'approved')->where('candidate.status', 'approved');
                })->orWhere(function ($pending): void {
                    $pending->whereIn('candidate_allocation.status', ['draft', 'submitted'])->where('candidate.status', 'draft');
                });
            })
            ->select('candidate.budget_allocation_id', DB::raw('MAX(candidate.version_no) as version_no'))
            ->groupBy('budget_allocation_id');

        $allocatedQuery = DB::table('financial_v2_budget_allocations as allocation')
            ->joinSub($latestVersions, 'latest_version', fn ($join) => $join->whereRaw('BINARY `latest_version`.`budget_allocation_id` = BINARY `allocation`.`id`'))
            ->join('financial_v2_budget_allocation_versions as version', function ($join): void {
                $join->whereRaw('BINARY `version`.`budget_allocation_id` = BINARY `latest_version`.`budget_allocation_id`')
                    ->on('version.version_no', '=', 'latest_version.version_no');
            })
            ->where('allocation.accounting_entity_id', $entityId)
            ->whereNotIn('allocation.status', ['cancelled', 'superseded']);
        if ($fundId) {
            $allocatedQuery
                ->join('financial_v2_budget_allocation_fundings as funding', fn ($join) => $join->whereRaw('BINARY `funding`.`budget_allocation_version_id` = BINARY `version`.`id`'))
                ->where('funding.fund_id', $fundId);
        }
        $allocated = $allocatedQuery->sum($fundId ? 'funding.amount' : 'version.allocated_amount');

        $realizedQuery = DB::table('financial_v2_fund_realizations as realization')
            ->join('financial_v2_budget_allocation_versions as version', fn ($join) => $join->whereRaw('BINARY `version`.`id` = BINARY `realization`.`budget_allocation_version_id`'))
            ->join('financial_v2_budget_allocations as allocation', fn ($join) => $join->whereRaw('BINARY `allocation`.`id` = BINARY `version`.`budget_allocation_id`'))
            ->join('financial_v2_transactions as transaction', fn ($join) => $join->whereRaw('BINARY `transaction`.`id` = BINARY `realization`.`transaction_id`'))
            ->join('financial_v2_journals as journal', fn ($join) => $join->whereRaw('BINARY `journal`.`transaction_id` = BINARY `transaction`.`id`'))
            ->where('realization.accounting_entity_id', $entityId)
            ->where('realization.status', 'recorded')
            ->where('journal.journal_status', 'posted');
        if ($fundId) {
            $realizedQuery
                ->join('financial_v2_transaction_splits as split', fn ($join) => $join->whereRaw('BINARY `split`.`transaction_id` = BINARY `transaction`.`id`'))
                ->where('split.fund_id', $fundId);
        }
        $realized = $realizedQuery->sum($fundId ? 'split.split_amount' : 'transaction.gross_amount');

        $allocated = DecimalAmount::normalize($allocated);
        $realized = DecimalAmount::normalize($realized);

        return [
            'allocated' => $allocated,
            'realized' => $realized,
            'remaining' => DecimalAmount::subtract($allocated, $realized),
        ];
    }
}
