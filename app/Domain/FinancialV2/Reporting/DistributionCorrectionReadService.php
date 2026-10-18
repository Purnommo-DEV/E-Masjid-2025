<?php
namespace App\Domain\FinancialV2\Reporting;
use App\Models\FinancialV2\Distribution;
use App\Models\FinancialV2\DistributionCorrectionLineage;
use Illuminate\Support\Collection;

/** Read overlay: one operational distribution remains one distribution and one recipient set. */
final class DistributionCorrectionReadService
{
    public function forDistributions(Collection $distributions): Collection
    {
        $overlays = DistributionCorrectionLineage::query()->whereIn('original_distribution_id', $distributions->pluck('id'))->get()->keyBy('original_distribution_id');
        return $distributions->map(function (Distribution $distribution) use ($overlays): Distribution {
            $overlay = $overlays->get($distribution->id);
            $distribution->setAttribute('correction_lineage_id', $overlay?->id);
            $distribution->setAttribute('original_realization_id', $distribution->realization_id);
            $distribution->setAttribute('effective_accounting_realization_id', $overlay?->replacement_realization_id ?? $distribution->realization_id);
            $distribution->setAttribute('has_accounting_replacement', $overlay !== null);
            return $distribution;
        })->values();
    }
}
