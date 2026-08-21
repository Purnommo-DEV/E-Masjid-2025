<?php

namespace App\Domain\FinancialV2\Reporting;

use App\Domain\FinancialV2\DecimalAmount;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\HistoricalFundHistory;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Read-only fund-facing history.
 *
 * A row represents one posted V2 Journal for one Fund, rather than one
 * JournalLine. This prevents an operational user from seeing the two sides of
 * one accounting event as duplicated Fund activity. The underlying source is
 * still exclusively the immutable Posted General Ledger.
 */
final class FundHistoryReadService
{
    public function __construct(
        private readonly PostedLedgerQuery $postedLedger,
        private readonly FinancialReportDefinitions $definitions,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{history: LengthAwarePaginator, period_opening: string, page_opening: string, from: string, through: string, definition: string, source_history: array<string, mixed>}
     */
    public function history(AccountingEntity $entity, Fund $fund, array $filters): array
    {
        $from = (string) ($filters['from'] ?? $fund->valid_from?->toDateString() ?? now()->startOfYear()->toDateString());
        $through = (string) ($filters['through'] ?? now()->toDateString());
        if ($from > $through) {
            throw new \InvalidArgumentException('Tanggal mulai riwayat Dana tidak boleh melewati tanggal akhir.');
        }

        $base = $this->base($entity->id, $fund->id, $through);
        $this->applyFilters($base, $filters);
        $contribution = $this->contributionSql();

        $periodOpening = $this->amount((clone $base)
            ->where('ledger.accounting_date', '<', $from)
            ->selectRaw('COALESCE(SUM('.$contribution.'), 0) as balance', $this->definitions->fundTransferTypes())
            ->value('balance'));

        $query = (clone $base)
            ->where('ledger.accounting_date', '>=', $from)
            ->leftJoin('financial_v2_financial_accounts as financial_account', 'financial_account.id', '=', 'ledger.financial_account_id')
            ->leftJoin('financial_v2_financial_accounts as attribution_account', function (JoinClause $join): void {
                $join->on('attribution_account.id', '=', DB::raw('COALESCE(original_transaction.primary_financial_account_id, financial_transaction.primary_financial_account_id)'));
            })
            ->leftJoin('financial_v2_interfund_transfers as direct_interfund', 'direct_interfund.transaction_id', '=', 'financial_transaction.id')
            ->leftJoin('financial_v2_interfund_transfers as original_interfund', 'original_interfund.transaction_id', '=', 'original_transaction.id')
            ->leftJoin('users as posting_user', 'posting_user.id', '=', 'journal.posted_by_user_id')
            ->leftJoin('financial_v2_programs as program', 'program.id', '=', 'ledger.program_id')
            ->leftJoin('financial_v2_categories as category', 'category.id', '=', 'journal_line.category_id')
            ->select([
                'journal.id as journal_id',
                'financial_transaction.id as transaction_id',
                'financial_transaction.source_reference',
                'financial_transaction.status as transaction_status',
                'journal.accounting_date',
                'journal.posted_at',
                'journal.posting_sequence',
                'journal.description as journal_description',
                'journal.reversal_of_journal_id',
                'transaction_type.code as transaction_type_code',
                'transaction_type.name as transaction_type_name',
                'original_transaction_type.code as original_transaction_type_code',
                'original_transaction_type.name as original_transaction_type_name',
                'voucher.voucher_number',
                'posting_user.name as posted_by_name',
            ])
            ->selectRaw('COALESCE(original_interfund.policy_basis_ref, direct_interfund.policy_basis_ref) as policy_basis_ref')
            ->selectRaw('COALESCE(original_interfund.reason, direct_interfund.reason) as correction_reason')
            ->selectRaw('COALESCE(SUM('.$contribution.'), 0) as fund_balance_delta', $this->definitions->fundTransferTypes())
            ->selectRaw("GROUP_CONCAT(DISTINCT NULLIF(CASE WHEN COALESCE(original_transaction_type.code, transaction_type.code) = 'IFT' THEN attribution_account.name ELSE financial_account.name END, '') ORDER BY CASE WHEN COALESCE(original_transaction_type.code, transaction_type.code) = 'IFT' THEN attribution_account.name ELSE financial_account.name END SEPARATOR ' · ') as financial_account_names")
            ->selectRaw("GROUP_CONCAT(DISTINCT NULLIF(program.name, '') ORDER BY program.name SEPARATOR ' · ') as program_names")
            ->selectRaw("GROUP_CONCAT(DISTINCT NULLIF(category.name, '') ORDER BY category.name SEPARATOR ' · ') as category_names")
            ->groupBy([
                'journal.id',
                'financial_transaction.id',
                'financial_transaction.source_reference',
                'financial_transaction.status',
                'journal.accounting_date',
                'journal.posted_at',
                'journal.posting_sequence',
                'journal.description',
                'journal.reversal_of_journal_id',
                'transaction_type.code',
                'transaction_type.name',
                'original_transaction_type.code',
                'original_transaction_type.name',
                'voucher.voucher_number',
                'posting_user.name',
                'direct_interfund.policy_basis_ref',
                'direct_interfund.reason',
                'original_interfund.policy_basis_ref',
                'original_interfund.reason',
            ])
            ->orderBy('journal.accounting_date')
            ->orderBy('journal.posting_sequence');

        $perPage = min(100, max(10, (int) ($filters['per_page'] ?? 25)));
        $history = $query->paginate($perPage, ['*'], 'fund_page')->withQueryString();
        $rows = $history->getCollection();
        $pageOpening = $periodOpening;
        if ($first = $rows->first()) {
            $prior = (clone $base)
                ->where('ledger.accounting_date', '>=', $from)
                ->where(function (Builder $prior) use ($first): void {
                    $prior->where('ledger.accounting_date', '<', $first->accounting_date)
                        ->orWhere(function (Builder $sameDate) use ($first): void {
                            $sameDate->where('ledger.accounting_date', $first->accounting_date)
                                ->where('journal.posting_sequence', '<', $first->posting_sequence);
                        });
                })
                ->selectRaw('COALESCE(SUM('.$contribution.'), 0) as balance', $this->definitions->fundTransferTypes())
                ->value('balance');
            $pageOpening = DecimalAmount::add($periodOpening, $this->amount($prior));
        }

        $sourceHistory = $this->historicalSourceHistory($entity, $fund);

        $running = $pageOpening;
        $history->setCollection($rows->map(function (object $row) use (&$running, $sourceHistory): array {
            $type = $row->original_transaction_type_code ?: $row->transaction_type_code;
            $delta = $this->amount($row->fund_balance_delta);
            $running = DecimalAmount::add($running, $delta);

            return [
                'journal_id' => $row->journal_id,
                'transaction_id' => $row->transaction_id,
                'accounting_date' => $row->accounting_date,
                'posting_sequence' => (int) $row->posting_sequence,
                'voucher_number' => $row->voucher_number,
                'transaction_type_code' => $type,
                'transaction_type_name' => $row->original_transaction_type_name ?: $row->transaction_type_name,
                'transaction_status' => $row->transaction_status,
                'description' => $row->journal_description ?: $row->source_reference,
                'source_reference' => $row->source_reference,
                'financial_account_names' => $row->financial_account_names,
                'financial_account_is_attribution' => $type === 'IFT' && filled($row->financial_account_names),
                'policy_basis_ref' => $row->policy_basis_ref,
                'correction_reason' => $row->correction_reason,
                'posted_by_name' => $row->posted_by_name,
                'posted_at' => $row->posted_at,
                'program_names' => $row->program_names,
                'category_names' => $row->category_names,
                'kind' => $this->kind($type, $delta),
                'receipt' => $type === 'RCV' ? $delta : '0.00',
                'usage' => $type === 'PAY' ? DecimalAmount::negate($delta) : '0.00',
                'transfer' => $type === 'IFT' ? $delta : '0.00',
                'adjustment' => $type === 'ADJ' ? $delta : '0.00',
                'fund_balance_delta' => $delta,
                'running_fund_balance' => $running,
                'is_reversal' => $row->reversal_of_journal_id !== null,
                // Source facts remain distinct from V2 financial facts. They
                // explain an opening position without replaying historical
                // source movements into the immutable V2 ledger.
                'opening_source_lineage' => $type === 'OPB' ? $sourceHistory['rows'] : [],
            ];
        }));

        return [
            'history' => $history,
            'period_opening' => $periodOpening,
            'page_opening' => $pageOpening,
            'from' => $from,
            'through' => $through,
            'definition' => 'Riwayat Dana mengelompokkan Posted V2 Ledger per jurnal dan Dana. Saldo berjalan menggunakan dampak Dana, bukan saldo rekening atau jumlah debit/kredit.',
            'source_history' => $sourceHistory,
        ];
    }

    /**
     * Builds a read-only historical explanation of an MRJ opening position.
     *
     * This is deliberately separate from $history above. Its source rows are
     * never placed in the V2 Journal/Ledger and therefore cannot become a
     * second posting path or be mistaken for Allocation/Realization records.
     *
     * @return array{rows: array<int, array<string, mixed>>, account_positions: array<int, array<string, mixed>>, source_fund_balance: string, source_fund_reference: string, opening_source_balance: string, opening_source_reference: string, historical_movement: string, current_source_balance: string, current_source_reference: string, account_position_total: string, activity_balance: string, reconciled_balance: string, difference: string, source_filename: string}
     */
    private function historicalSourceHistory(AccountingEntity $entity, Fund $fund): array
    {
        if ($entity->code !== 'MRJ-ACTUAL') {
            return [
                'rows' => [],
                'account_positions' => [],
                'source_fund_balance' => '0.00',
                'source_fund_reference' => '',
                'opening_source_balance' => '0.00',
                'opening_source_reference' => '',
                'historical_movement' => '0.00',
                'current_source_balance' => '0.00',
                'current_source_reference' => '',
                'account_position_total' => '0.00',
                'activity_balance' => '0.00',
                'reconciled_balance' => '0.00',
                'difference' => '0.00',
                'source_filename' => '',
            ];
        }

        $sourceEntries = HistoricalFundHistory::query()
            ->where('accounting_entity_id', $entity->id)
            ->where('fund_id', $fund->id)
            ->with('updatedBy:id,name')
            ->orderBy('source_sequence')
            ->orderBy('created_at')
            ->get();
        if ($sourceEntries->isEmpty()) {
            return [
                'rows' => [],
                'account_positions' => [],
                'source_fund_balance' => '0.00',
                'source_fund_reference' => '',
                'opening_source_balance' => '0.00',
                'opening_source_reference' => '',
                'historical_movement' => '0.00',
                'current_source_balance' => '0.00',
                'current_source_reference' => '',
                'account_position_total' => '0.00',
                'activity_balance' => '0.00',
                'reconciled_balance' => '0.00',
                'difference' => '0.00',
                'source_filename' => '',
            ];
        }

        $running = '0.00';
        $rows = [];
        $accountPositions = [];
        $openingSourceBalance = '0.00';
        $openingSourceReference = '';
        $historicalMovementReferences = [];

        foreach ($sourceEntries as $entry) {
            $kind = $entry->entry_kind;
            $amount = DecimalAmount::normalize((string) $entry->amount);
            $isVoid = $entry->status === 'void';
            if (in_array($kind, ['opening', 'receipt', 'usage', 'adjustment_in', 'adjustment_out'], true)) {
                $delta = in_array($kind, ['usage', 'adjustment_out'], true) ? DecimalAmount::negate($amount) : $amount;
                $isHistoricalFundReallocation = $this->isHistoricalFundReallocation($entry);
                if (! $isVoid) {
                    $running = DecimalAmount::add($running, $delta);
                }
                if ($isHistoricalFundReallocation && ! $isVoid && filled($entry->source_reference)) {
                    $historicalMovementReferences[] = (string) $entry->source_reference;
                }
                $rows[] = [
                    'id' => $entry->id,
                    'date_label' => $entry->date_label,
                    'description' => $entry->description,
                    'notes' => $entry->notes ?? '',
                    'source_reference' => $entry->source_reference ?? '',
                    'source_filename' => $entry->source_filename,
                    'source_worksheet' => $entry->source_worksheet,
                    'source_hash' => $entry->source_hash,
                    'entry_kind' => $kind,
                    'classification' => $isHistoricalFundReallocation ? 'Historical Fund Reallocation' : null,
                    'is_historical_fund_reallocation' => $isHistoricalFundReallocation,
                    'status' => $entry->status,
                    'correction_reason' => $entry->correction_reason,
                    'updated_at' => $entry->updated_at,
                    'updated_by_name' => $entry->updatedBy?->name,
                    'receipt' => in_array($kind, ['usage', 'adjustment_out'], true) || $isVoid ? '0.00' : $amount,
                    'usage' => in_array($kind, ['usage', 'adjustment_out'], true) && ! $isVoid ? $amount : '0.00',
                    'running_balance' => $running,
                ];
            }

            if ($kind === 'account_position') {
                $accountPositions[] = [
                    'id' => $entry->id,
                    'date_label' => $entry->date_label,
                    'description' => $entry->description,
                    'notes' => $entry->notes ?? '',
                    'source_reference' => $entry->source_reference ?? '',
                    'source_filename' => $entry->source_filename,
                    'status' => $entry->status,
                    'amount' => $isVoid ? '0.00' : $amount,
                ];
            }

            if ($kind === 'closing' && ! $isVoid) {
                $openingSourceBalance = $amount;
                $openingSourceReference = (string) ($entry->source_reference ?? '');
            }
        }

        $accountPositionTotal = DecimalAmount::sum(array_column($accountPositions, 'amount'));
        // A Cash/Bank position documents where liquidity is held. It never
        // becomes an additional Fund balance in this source-history view.
        // The source closing row is the opening baseline; documented source
        // movements after it explain the current source Fund position.
        $currentSourceBalance = $running;
        $currentSourceReference = implode('; ', array_unique(array_filter([
            $openingSourceReference,
            ...$historicalMovementReferences,
        ])));

        return [
            'rows' => $rows,
            'account_positions' => $accountPositions,
            // Existing consumers use these fields. They intentionally mean
            // the current source Fund position, not the historic baseline.
            'source_fund_balance' => $currentSourceBalance,
            'source_fund_reference' => $currentSourceReference,
            'opening_source_balance' => $openingSourceBalance,
            'opening_source_reference' => $openingSourceReference,
            'historical_movement' => DecimalAmount::subtract($currentSourceBalance, $openingSourceBalance),
            'current_source_balance' => $currentSourceBalance,
            'current_source_reference' => $currentSourceReference,
            'account_position_total' => $accountPositionTotal,
            'activity_balance' => $running,
            'reconciled_balance' => $currentSourceBalance,
            'difference' => DecimalAmount::subtract($currentSourceBalance, $running),
            'source_filename' => (string) $sourceEntries->first()->source_filename,
        ];
    }

    private function isHistoricalFundReallocation(HistoricalFundHistory $entry): bool
    {
        return $entry->status === 'corrected'
            && str_contains(strtolower((string) $entry->description), 'pemindahan dana dari alokasi');
    }

    private function base(string $entityId, string $fundId, string $through): Builder
    {
        return $this->postedLedger->ledger($entityId, $through)
            ->join('financial_v2_accounts as account', 'account.id', '=', 'ledger.account_id')
            ->where('ledger.fund_id', $fundId);
    }

    /** @param array<string, mixed> $filters */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['transaction_type_code'])) {
            $query->whereRaw('COALESCE(original_transaction_type.code, transaction_type.code) = ?', [$filters['transaction_type_code']]);
        }
        if (! empty($filters['program_id'])) {
            $query->where('ledger.program_id', $filters['program_id']);
        }
        if (! empty($filters['financial_account_id'])) {
            $financialAccountId = $filters['financial_account_id'];
            $query->where(function (Builder $account) use ($financialAccountId): void {
                $account->where('ledger.financial_account_id', $financialAccountId)
                    ->orWhere(function (Builder $attribution) use ($financialAccountId): void {
                        $attribution
                            ->whereRaw("COALESCE(original_transaction_type.code, transaction_type.code) = 'IFT'")
                            ->whereRaw('COALESCE(original_transaction.primary_financial_account_id, financial_transaction.primary_financial_account_id) = ?', [$financialAccountId]);
                    });
            });
        }
        if (! empty($filters['category_id'])) {
            $query->where('journal_line.category_id', $filters['category_id']);
        }
        if (! empty($filters['status'])) {
            $query->where('financial_transaction.status', $filters['status']);
        }
    }

    private function contributionSql(): string
    {
        $placeholders = implode(', ', array_fill(0, max(1, count($this->definitions->fundTransferTypes())), '?'));

        return "CASE
            WHEN account.account_class = 'revenue' THEN ledger.signed_amount
            WHEN account.account_class = 'expense' THEN -ledger.signed_amount
            WHEN account.account_class = 'net_asset' THEN ledger.signed_amount
            WHEN account.account_class = 'transfer'
                AND COALESCE(original_transaction_type.code, transaction_type.code) IN ({$placeholders})
                THEN journal_line.debit_amount - journal_line.credit_amount
            ELSE 0
        END";
    }

    private function kind(string $type, string $delta): string
    {
        return match ($type) {
            'OPB' => 'opening',
            'RCV' => 'receipt',
            'PAY' => 'usage',
            'TRF' => 'treasury_transfer',
            'IFT' => DecimalAmount::compare($delta, '0.00') >= 0 ? 'transfer_in' : 'transfer_out',
            'ADJ' => 'adjustment',
            default => 'ledger_effect',
        };
    }

    private function amount(mixed $amount): string
    {
        return DecimalAmount::normalize((string) ($amount ?? 0));
    }
}
