<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\FundPolicyRule;
use App\Models\FinancialV2\FundPolicyVersion;
use App\Models\FinancialV2\TransactionType;
use Illuminate\Database\Eloquent\Builder;

final class FundPolicyCompatibilityService
{
    /**
     * Validates whether every planned funding source can be used for the
     * proposed Payment dimensions. PostingEngine repeats the authoritative
     * check against the final transaction when a realization is posted.
     *
     * @param  iterable<string>  $fundIds
     */
    public function assertAllocationCompatible(
        string $entityId,
        iterable $fundIds,
        string $effectiveDate,
        ?string $accountId,
        ?string $categoryId,
        ?string $programId,
    ): void {
        $paymentType = TransactionType::query()
            ->where('accounting_entity_id', $entityId)
            ->where('code', TransactionTypeCode::Payment->value)
            ->where('status', 'active')
            ->first();
        if (! $paymentType) {
            throw new FinancialDomainException('E-BUDGET-POLICY', 'Jenis transaksi pengeluaran yang aktif belum tersedia untuk memeriksa sumber Dana.');
        }

        foreach (collect($fundIds)->filter()->unique() as $fundId) {
            $fund = Fund::query()->with('type')->find($fundId);
            if (! $fund || $fund->accounting_entity_id !== $entityId || $fund->status !== 'active'
                || ($fund->valid_from && $fund->valid_from->toDateString() > $effectiveDate)
                || ($fund->valid_to && $fund->valid_to->toDateString() < $effectiveDate)) {
                throw new FinancialDomainException('E-BUDGET-FUND', 'Sumber Dana tidak aktif atau berada di luar entitas/periode alokasi.');
            }

            if (! in_array($fund->type?->classification, ['restricted', 'perpetual_restricted', 'custodial', 'syariah'], true)) {
                continue;
            }

            $policy = FundPolicyVersion::query()
                ->where('fund_id', $fund->id)
                ->where('status', 'effective')
                ->where('effective_from', '<=', $effectiveDate)
                ->where(fn (Builder $query) => $query->whereNull('effective_to')->orWhere('effective_to', '>=', $effectiveDate))
                ->orderByDesc('effective_from')
                ->first();
            $decisions = $policy ? FundPolicyRule::query()
                ->where('fund_policy_version_id', $policy->id)
                ->where('transaction_type_id', $paymentType->id)
                ->where(fn (Builder $query) => $accountId ? $query->whereNull('account_id')->orWhere('account_id', $accountId) : $query->whereNull('account_id'))
                ->where(fn (Builder $query) => $categoryId ? $query->whereNull('category_id')->orWhere('category_id', $categoryId) : $query->whereNull('category_id'))
                ->where(fn (Builder $query) => $programId ? $query->whereNull('program_id')->orWhere('program_id', $programId) : $query->whereNull('program_id'))
                ->whereNull('cost_center_id')
                ->pluck('decision') : collect();

            if ($decisions->contains('prohibited') || ! $decisions->contains('allowed')) {
                throw new FinancialDomainException('E-FUND-RESTRICTED', "Dana {$fund->name} tidak dapat digunakan untuk program/kategori alokasi tersebut.");
            }
        }
    }
}
