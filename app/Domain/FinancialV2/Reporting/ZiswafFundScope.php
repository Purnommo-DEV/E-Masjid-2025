<?php

namespace App\Domain\FinancialV2\Reporting;

use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\Fund;
use Illuminate\Support\Collection;

/** Resolves the governance-approved Fund scope shared by every ZISWAF report. */
final class ZiswafFundScope
{
    /** @return Collection<int, Fund> */
    public function funds(AccountingEntity $entity): Collection
    {
        $codes = $this->codes();

        return Fund::query()
            ->where('accounting_entity_id', $entity->id)
            ->where('status', 'active')
            ->whereIn('code', $codes)
            ->get()
            ->sortBy(fn (Fund $fund): int => array_search($fund->code, $codes, true))
            ->values();
    }

    /** @return array<int, string> */
    public function ids(AccountingEntity $entity): array
    {
        return $this->funds($entity)->pluck('id')->all();
    }

    /** @return array<int, string> */
    public function codes(): array
    {
        return array_values(array_unique(array_filter(
            config('financial_reporting.public_ziswaf.fund_codes', []),
            'is_string',
        )));
    }
}
