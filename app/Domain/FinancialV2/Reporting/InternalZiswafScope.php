<?php

namespace App\Domain\FinancialV2\Reporting;

use Illuminate\Support\Facades\DB;

/** Read-only grant boundary. Journal status, not transaction status, is authoritative. */
final class InternalZiswafScope
{
    public function __construct(private readonly PostedLedgerQuery $ledger) {}

    /** @return array<string, array{accessible:bool, complete:bool}> */
    public function permissions(string $entityId, array $fundIds, array $transactionIds): array
    {
        if ($fundIds === [] || $transactionIds === []) {
            return [];
        }

        $facts = $this->ledger->ledger($entityId, '9999-12-31')
            ->where('journal.accounting_entity_id', $entityId)
            ->where('financial_transaction.accounting_entity_id', $entityId)
            ->whereIn('financial_transaction.id', $transactionIds)
            ->get(['financial_transaction.id as transaction_id', 'ledger.fund_id'])
            ->groupBy('transaction_id');
        // Documents and free-text metadata may describe every split, including
        // an out-of-grant split which has no financial contribution in this view.
        $splits = DB::table('financial_v2_transaction_splits')
            ->whereIn('transaction_id', $facts->keys()->all())
            ->get(['transaction_id', 'fund_id'])->groupBy('transaction_id');

        return $facts->mapWithKeys(function ($rows, string $id) use ($fundIds, $splits): array {
            $postedFunds = $rows->pluck('fund_id')->filter()->unique();
            $allFunds = $postedFunds->merge($splits->get($id, collect())->pluck('fund_id')->filter())->unique();

            return [$id => [
                'accessible' => $postedFunds->intersect($fundIds)->isNotEmpty(),
                'complete' => $postedFunds->intersect($fundIds)->isNotEmpty() && $allFunds->diff($fundIds)->isEmpty(),
            ]];
        })->all();
    }

    /** Preserve financial deltas while suppressing inseparable private metadata. */
    public function redactRows(string $entityId, array $fundIds, array $rows): array
    {
        $permissions = $this->permissions($entityId, $fundIds, array_column($rows, 'transaction_id'));

        return collect($rows)->filter(fn (array $row): bool => $permissions[$row['transaction_id']]['accessible'] ?? false)
            ->map(function (array $row) use ($permissions): array {
                $complete = $permissions[$row['transaction_id']]['complete'];
                $row['complete_access'] = $complete;
                if (! $complete) {
                    $row['description'] = 'Transaksi dana ZISWAF';
                    $row['category'] = '—';
                    $row['program'] = '—';
                }

                return $row;
            })->values()->all();
    }
}
