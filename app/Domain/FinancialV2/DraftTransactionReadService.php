<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\FinancialTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Read-only discovery for operational transactions that have not been posted.
 *
 * Fund Realizations retain their dedicated workflow and are deliberately
 * excluded from this listing.
 */
final class DraftTransactionReadService
{
    /** @var array<int, string> */
    public const DISCOVERABLE_STATUSES = ['draft', 'submitted', 'verified', 'approved', 'rejected', 'cancelled'];

    /** @param array<string, mixed> $filters */
    public function page(string $entityId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->baseQuery($entityId)
            ->with([
                'type:id,code,name',
                'primaryFinancialAccount:id,code,name',
                'counterparty:id,display_name',
                'category:id,code,name',
                'splits.fund:id,code,name',
                'interfundTransfer.sourceFund:id,code,name',
                'interfundTransfer.destinationFund:id,code,name',
            ]);

        if (! empty($filters['year'])) {
            $query->whereYear('accounting_date', (int) $filters['year']);
        }
        if (! empty($filters['type'])) {
            $this->applyTypeFilter($query, $filters['type']);
        }
        if (! empty($filters['financial_account_id'])) {
            $query->where('primary_financial_account_id', $filters['financial_account_id']);
        }
        if (($filters['status'] ?? 'draft') !== 'all') {
            $query->where('status', $filters['status'] ?? 'draft');
        }
        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $where) use ($search): void {
                $where->where('source_reference', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhereHas('counterparty', fn (Builder $counterparty) => $counterparty->where('display_name', 'like', '%'.$search.'%'))
                    ->orWhereHas('primaryFinancialAccount', fn (Builder $account) => $account
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('code', 'like', '%'.$search.'%'))
                    ->orWhereHas('category', fn (Builder $category) => $category
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('code', 'like', '%'.$search.'%'))
                    ->orWhereHas('splits.fund', fn (Builder $fund) => $fund
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('code', 'like', '%'.$search.'%'))
                    ->orWhereHas('interfundTransfer.sourceFund', fn (Builder $fund) => $fund->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('interfundTransfer.destinationFund', fn (Builder $fund) => $fund->where('name', 'like', '%'.$search.'%'));
            });
        }

        return $query
            ->latest('accounting_date')
            ->latest('updated_at')
            ->paginate(20, ['*'], 'draft_page')
            ->withQueryString();
    }

    /** @return Collection<int, int> */
    public function years(string $entityId): Collection
    {
        return $this->baseQuery($entityId)
            ->select('accounting_date')
            ->distinct()
            ->pluck('accounting_date')
            ->map(fn ($date): int => (int) substr((string) $date, 0, 4))
            ->push((int) now()->year)
            ->unique()
            ->sortDesc()
            ->values();
    }

    private function baseQuery(string $entityId): Builder
    {
        return FinancialTransaction::query()
            ->where('accounting_entity_id', $entityId)
            ->whereIn('status', self::DISCOVERABLE_STATUSES)
            ->whereHas('type', fn (Builder $type) => $type->whereIn('code', ['RCV', 'PAY', 'TRF', 'IFT']))
            ->whereDoesntHave('realization');
    }

    private function applyTypeFilter(Builder $query, string $type): void
    {
        $bankCategoryCodes = array_keys(BankMutationService::CATEGORY_CODES);

        match ($type) {
            'bank_mutation' => $query->whereHas('category', fn (Builder $category) => $category->whereIn('code', $bankCategoryCodes)),
            'receipt' => $query
                ->whereHas('type', fn (Builder $transactionType) => $transactionType->where('code', 'RCV'))
                ->whereDoesntHave('category', fn (Builder $category) => $category->whereIn('code', $bankCategoryCodes)),
            'payment' => $query
                ->whereHas('type', fn (Builder $transactionType) => $transactionType->where('code', 'PAY'))
                ->whereDoesntHave('category', fn (Builder $category) => $category->whereIn('code', $bankCategoryCodes)),
            'transfer' => $query->whereHas('type', fn (Builder $transactionType) => $transactionType->whereIn('code', ['TRF', 'IFT'])),
            default => null,
        };
    }
}
