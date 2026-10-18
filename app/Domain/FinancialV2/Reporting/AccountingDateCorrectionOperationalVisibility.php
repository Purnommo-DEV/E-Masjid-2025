<?php

namespace App\Domain\FinancialV2\Reporting;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Resolves which technical transactions belong to a completed accounting-date
 * correction and must not be presented as current operational activity.
 *
 * This is deliberately a reporting-only resolver. It never changes accounting
 * facts and it only hides the original/reversal after the canonical request,
 * execution pair, transactions, and journals all prove a completed pair.
 */
final class AccountingDateCorrectionOperationalVisibility
{
    /**
     * @return array{hidden_transaction_ids: array<int, string>, replacement_transaction_ids: array<int, string>}
     */
    public function operationalProjection(string $accountingEntityId): array
    {
        return $this->projectionFromFacts($this->correctionFacts($accountingEntityId));
    }

    /** @return array<int, string> */
    public function hiddenTransactionIds(string $accountingEntityId): array
    {
        return $this->operationalProjection($accountingEntityId)['hidden_transaction_ids'];
    }

    /** @return Collection<int, object> */
    private function correctionFacts(string $accountingEntityId): Collection
    {
        return DB::table('financial_v2_accounting_date_correction_lineages as lineage')
            ->join('financial_v2_accounting_date_correction_requests as request', 'request.id', '=', 'lineage.correction_request_id')
            ->join('financial_v2_accounting_date_correction_execution_pairs as pair', 'pair.correction_request_id', '=', 'request.id')
            ->join('financial_v2_transactions as original_tx', 'original_tx.id', '=', 'lineage.original_transaction_id')
            ->join('financial_v2_transactions as reversal_tx', 'reversal_tx.id', '=', 'lineage.reversal_transaction_id')
            ->join('financial_v2_transactions as replacement_tx', 'replacement_tx.id', '=', 'lineage.replacement_transaction_id')
            ->join('financial_v2_journals as original_journal', 'original_journal.id', '=', 'lineage.original_journal_id')
            ->join('financial_v2_journals as reversal_journal', 'reversal_journal.id', '=', 'lineage.reversal_journal_id')
            ->join('financial_v2_journals as replacement_journal', 'replacement_journal.id', '=', 'lineage.replacement_journal_id')
            ->where('request.accounting_entity_id', $accountingEntityId)
            ->select([
                'request.status as request_status',
                'request.accounting_entity_id as request_entity_id',
                'pair.status as pair_status',
                'pair.original_transaction_id as pair_original_transaction_id',
                'pair.original_journal_id as pair_original_journal_id',
                'pair.reversal_transaction_id as pair_reversal_transaction_id',
                'pair.replacement_transaction_id as pair_replacement_transaction_id',
                'lineage.original_transaction_id',
                'lineage.original_journal_id',
                'lineage.reversal_transaction_id',
                'lineage.reversal_journal_id',
                'lineage.replacement_transaction_id',
                'lineage.replacement_journal_id',
                'original_tx.accounting_entity_id as original_transaction_entity_id',
                'original_tx.status as original_transaction_status',
                'reversal_tx.accounting_entity_id as reversal_transaction_entity_id',
                'reversal_tx.status as reversal_transaction_status',
                'replacement_tx.accounting_entity_id as replacement_transaction_entity_id',
                'replacement_tx.status as replacement_transaction_status',
                'original_journal.transaction_id as original_journal_transaction_id',
                'original_journal.journal_status as original_journal_status',
                'reversal_journal.transaction_id as reversal_journal_transaction_id',
                'reversal_journal.journal_status as reversal_journal_status',
                'replacement_journal.transaction_id as replacement_journal_transaction_id',
                'replacement_journal.journal_status as replacement_journal_status',
            ])
            ->get();
    }

    /**
     * Pure classification kept public so the fail-closed presentation contract
     * can be regression-tested without writing accounting fixtures.
     *
     * @param  iterable<int, array<string, mixed>|object>  $facts
     * @return array<int, string>
     */
    public function hiddenIdsFromFacts(iterable $facts): array
    {
        return $this->projectionFromFacts($facts)['hidden_transaction_ids'];
    }

    /**
     * @param  iterable<int, array<string, mixed>|object>  $facts
     * @return array{hidden_transaction_ids: array<int, string>, replacement_transaction_ids: array<int, string>}
     */
    public function projectionFromFacts(iterable $facts): array
    {
        $verified = Collection::make($facts)
            ->filter(function (array|object $fact): bool {
                $value = static fn (string $key): mixed => is_array($fact) ? ($fact[$key] ?? null) : ($fact->{$key} ?? null);

                return $value('request_status') === 'completed'
                    && $value('pair_status') === 'completed'
                    && $value('original_transaction_id') !== null
                    && $value('reversal_transaction_id') !== null
                    && $value('replacement_transaction_id') !== null
                    && $value('pair_original_transaction_id') === $value('original_transaction_id')
                    && $value('pair_original_journal_id') === $value('original_journal_id')
                    && $value('pair_reversal_transaction_id') === $value('reversal_transaction_id')
                    && $value('pair_replacement_transaction_id') === $value('replacement_transaction_id')
                    && $value('original_journal_transaction_id') === $value('original_transaction_id')
                    && $value('reversal_journal_transaction_id') === $value('reversal_transaction_id')
                    && $value('replacement_journal_transaction_id') === $value('replacement_transaction_id')
                    && $value('request_entity_id') === $value('original_transaction_entity_id')
                    && $value('request_entity_id') === $value('reversal_transaction_entity_id')
                    && $value('request_entity_id') === $value('replacement_transaction_entity_id')
                    && $value('original_transaction_status') === 'reversed'
                    && $value('reversal_transaction_status') === 'posted'
                    && $value('replacement_transaction_status') === 'posted'
                    && in_array($value('original_journal_status'), ['posted', 'reversed'], true)
                    && $value('reversal_journal_status') === 'posted'
                    && $value('replacement_journal_status') === 'posted';
            });

        $hidden = $verified
            ->flatMap(function (array|object $fact): array {
                $original = is_array($fact) ? $fact['original_transaction_id'] : $fact->original_transaction_id;
                $reversal = is_array($fact) ? $fact['reversal_transaction_id'] : $fact->reversal_transaction_id;

                return [(string) $original, (string) $reversal];
            })
            ->unique()
            ->values()
            ->all();

        $replacements = $verified
            ->map(fn (array|object $fact): string => (string) (is_array($fact)
                ? $fact['replacement_transaction_id']
                : $fact->replacement_transaction_id))
            ->unique()
            ->values()
            ->all();

        return [
            'hidden_transaction_ids' => $hidden,
            'replacement_transaction_ids' => $replacements,
        ];
    }
}
