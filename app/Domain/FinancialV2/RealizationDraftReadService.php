<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\AttachmentLink;
use App\Models\FinancialV2\BudgetAllocation;
use App\Models\FinancialV2\BudgetAllocationVersion;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\FundRealization;
use App\Models\FinancialV2\TransactionSplit;
use App\Models\FinancialV2\TransactionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read model for pending Fund Realizations.
 *
 * A realization remains a non-financial operational record until its linked
 * payment is posted by the Posting Engine. This service intentionally never
 * creates, changes, or posts a transaction.
 */
final class RealizationDraftReadService
{
    /** @var array<int, string> */
    public const ACTIVE_TRANSACTION_STATUSES = ['draft', 'submitted', 'verified', 'approved'];

    public function activeForAllocationVersion(string $entityId, string $allocationVersionId): ?FinancialTransaction
    {
        $transactionIds = FundRealization::query()
            ->where('accounting_entity_id', $entityId)
            ->where('budget_allocation_version_id', $allocationVersionId)
            ->where('status', 'draft')
            ->pluck('transaction_id');

        return $this->activeQuery($entityId)
            ->whereIn('id', $transactionIds)
            ->oldest('created_at')
            ->first();
    }

    /**
     * @param  iterable<string>  $allocationVersionIds
     * @return Collection<string, Collection<int, FinancialTransaction>>
     */
    public function activeByAllocationVersions(string $entityId, iterable $allocationVersionIds): Collection
    {
        $ids = collect($allocationVersionIds)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $transactionIds = FundRealization::query()
            ->where('accounting_entity_id', $entityId)
            ->whereIn('budget_allocation_version_id', $ids)
            ->where('status', 'draft')
            ->pluck('transaction_id');

        return $this->activeQuery($entityId)
            ->whereIn('id', $transactionIds)
            ->oldest('created_at')
            ->get()
            ->groupBy(fn (FinancialTransaction $transaction) => $transaction->realization?->budget_allocation_version_id);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function page(string $entityId, array $filters = []): LengthAwarePaginator
    {
        $perPage = min(100, max(10, (int) ($filters['per_page'] ?? 20)));
        $query = $this->activeQuery($entityId);
        if ($fundId = ($filters['fund_id'] ?? null)) {
            $query->whereIn('id', TransactionSplit::query()->where('fund_id', $fundId)->pluck('transaction_id'));
        }
        if ($programId = ($filters['program_id'] ?? null)) {
            $allocationIds = BudgetAllocation::query()->where('accounting_entity_id', $entityId)->where('program_id', $programId)->pluck('id');
            $versionIds = BudgetAllocationVersion::query()->whereIn('budget_allocation_id', $allocationIds)->pluck('id');
            $query->whereIn('id', FundRealization::query()->whereIn('budget_allocation_version_id', $versionIds)->pluck('transaction_id'));
        }

        $drafts = $query
            ->latest('updated_at')
            ->paginate($perPage, ['*'], 'draft_page')
            ->withQueryString();

        $this->addPresentationSummaries($drafts->getCollection());

        return $drafts;
    }

    private function activeQuery(string $entityId): Builder
    {
        $typeId = TransactionType::query()->where('accounting_entity_id', $entityId)->where('code', 'PAY')->value('id');
        $allocationIds = BudgetAllocation::query()->where('accounting_entity_id', $entityId)->where('status', 'approved')->pluck('id');
        $versionIds = BudgetAllocationVersion::query()->whereIn('budget_allocation_id', $allocationIds)->where('status', 'approved')->pluck('id');
        $transactionIds = FundRealization::query()
            ->where('accounting_entity_id', $entityId)
            ->whereIn('budget_allocation_version_id', $versionIds)
            ->where('status', 'draft')
            ->pluck('transaction_id');

        return FinancialTransaction::query()
            ->with([
                'type:id,code,name',
                'primaryFinancialAccount:id,code,name',
                'counterparty:id,display_name',
                'category:id,code,name',
                'splits.fund:id,code,name',
                'realization.budgetAllocationVersion.allocation.fund:id,code,name',
                'realization.budgetAllocationVersion.allocation.program:id,code,name',
                'realization.budgetAllocationVersion.fundings.fund:id,code,name',
            ])
            ->where('accounting_entity_id', $entityId)
            ->whereIn('status', self::ACTIVE_TRANSACTION_STATUSES)
            ->where('transaction_type_id', $typeId)
            ->whereIn('id', $transactionIds);
    }

    /** @param Collection<int, FinancialTransaction> $transactions */
    private function addPresentationSummaries(Collection $transactions): void
    {
        if ($transactions->isEmpty()) {
            return;
        }

        $transactionIds = $transactions->pluck('id');
        $versionIds = $transactions
            ->pluck('realization.budget_allocation_version_id')
            ->filter()
            ->unique()
            ->values();
        $evidenceCounts = AttachmentLink::query()
            ->where('target_type', 'transaction')
            ->whereIn('target_id', $transactionIds)
            ->where('status', 'active')
            ->select('target_id', DB::raw('COUNT(*) as total'))
            ->groupBy('target_id')
            ->pluck('total', 'target_id');
        $recordedByVersion = $versionIds->isEmpty()
            ? collect()
            : FundRealization::query()
                ->join('financial_v2_transactions as transaction', fn ($join) => $join->whereRaw('BINARY `transaction`.`id` = BINARY `financial_v2_fund_realizations`.`transaction_id`'))
                ->join('financial_v2_journals as journal', fn ($join) => $join->whereRaw('BINARY `journal`.`transaction_id` = BINARY `transaction`.`id`'))
                ->whereIn('financial_v2_fund_realizations.budget_allocation_version_id', $versionIds)
                ->where('financial_v2_fund_realizations.status', 'recorded')
                ->where('journal.journal_status', 'posted')
                ->select('financial_v2_fund_realizations.budget_allocation_version_id', DB::raw('SUM(transaction.gross_amount) as total'))
                ->groupBy('financial_v2_fund_realizations.budget_allocation_version_id')
                ->pluck('total', 'financial_v2_fund_realizations.budget_allocation_version_id');

        $transactions->each(function (FinancialTransaction $transaction) use ($evidenceCounts, $recordedByVersion): void {
            $version = $transaction->realization?->budgetAllocationVersion;
            $allocated = DecimalAmount::normalize($version?->allocated_amount ?? '0.00');
            $recorded = DecimalAmount::normalize($recordedByVersion->get($version?->id, '0.00'));

            $transaction->setAttribute('evidence_count', (int) $evidenceCounts->get($transaction->id, 0));
            $transaction->setAttribute('allocation_availability', [
                'allocated' => $allocated,
                'recorded' => $recorded,
                'remaining' => DecimalAmount::subtract($allocated, $recorded),
            ]);
        });
    }
}
