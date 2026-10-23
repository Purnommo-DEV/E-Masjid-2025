<?php
namespace App\Domain\FinancialV2\Reporting;
use App\Models\FinancialV2\Distribution;
use App\Models\FinancialV2\DistributionCorrectionLineage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Read overlay: one operational distribution remains one distribution and one recipient set. */
final class DistributionCorrectionReadService
{
    public function __construct(private readonly AccountingDateCorrectionOperationalVisibility $correctionVisibility) {}

    /**
     * Return only replacement realizations whose completed correction pair is
     * valid according to the canonical operational-visibility contract.
     *
     * @param  array<int, string>  $distributionIds
     * @return Collection<string, string> original distribution id => effective realization id
     */
    public function verifiedEffectiveRealizationIds(string $entityId, array $distributionIds): Collection
    {
        if ($distributionIds === []) {
            return collect();
        }

        $verifiedReplacementTransactions = $this->correctionVisibility
            ->operationalProjection($entityId)['replacement_transaction_ids'];
        if ($verifiedReplacementTransactions === []) {
            return collect();
        }

        return DB::table('financial_v2_distribution_correction_lineages as distribution_lineage')
            ->join('financial_v2_distributions as distribution', 'distribution.id', '=', 'distribution_lineage.original_distribution_id')
            ->join('financial_v2_replacement_realization_lineages as realization_lineage', 'realization_lineage.id', '=', 'distribution_lineage.replacement_realization_lineage_id')
            ->join('financial_v2_fund_realizations as replacement_realization', 'replacement_realization.id', '=', 'distribution_lineage.replacement_realization_id')
            ->where('distribution_lineage.accounting_entity_id', $entityId)
            ->where('distribution.accounting_entity_id', $entityId)
            ->whereIn('distribution_lineage.original_distribution_id', $distributionIds)
            ->whereIn('replacement_realization.transaction_id', $verifiedReplacementTransactions)
            ->where('replacement_realization.status', 'recorded')
            ->whereColumn('distribution.realization_id', 'distribution_lineage.original_realization_id')
            ->whereColumn('realization_lineage.original_realization_id', 'distribution_lineage.original_realization_id')
            ->whereColumn('realization_lineage.replacement_realization_id', 'distribution_lineage.replacement_realization_id')
            ->whereColumn('realization_lineage.replacement_transaction_id', 'replacement_realization.transaction_id')
            ->pluck('distribution_lineage.replacement_realization_id', 'distribution_lineage.original_distribution_id');
    }

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
