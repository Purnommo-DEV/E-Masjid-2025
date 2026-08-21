<?php

namespace App\Domain\FinancialV2\Reporting;

use App\Domain\FinancialV2\DecimalAmount;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Financial V2 reporting facade.
 *
 * This service is deliberately a read model: its queries begin at immutable
 * LedgerEntry/Posted Journal facts, report a reproducible posting watermark,
 * and never write a fact, projection, snapshot, or operational record.
 */
final class FinancialReportService
{
    /** @var array<string, string> */
    public const REPORTS = [
        'summary' => 'Ringkasan Keuangan',
        'account-balance' => 'Saldo Rekening',
        'fund-balance' => 'Saldo Dana',
        'account-movement' => 'Mutasi Rekening',
        'fund-movement' => 'Mutasi Dana',
        'transaction-history' => 'Riwayat Transaksi',
        'cash-flow' => 'Arus Kas',
        'trial-balance' => 'Neraca Saldo',
        'friday' => 'Laporan Keuangan Jumat',
        'ziswaf' => 'Laporan ZISWAF',
        'program' => 'Laporan Program',
    ];

    public function __construct(
        private readonly PostedLedgerQuery $postedLedger,
        private readonly FinancialReportDefinitions $definitions,
        private readonly FundFinancialAccountCompositionReadService $fundFinancialAccounts,
    ) {}

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    public function report(string $report, string $entityId, string $fromAccountingDate, string $throughAccountingDate, array $filters = []): array
    {
        if (! array_key_exists($report, self::REPORTS)) {
            throw new InvalidArgumentException('Unknown Financial V2 report.');
        }
        if ($fromAccountingDate > $throughAccountingDate) {
            throw new InvalidArgumentException('The report start date must not be after the through date.');
        }

        // MySQL's repeatable-read transaction gives the report's multiple
        // aggregates one consistent view. The closure is intentionally query
        // only; no report cache or financial fact is mutated here.
        return DB::transaction(function () use ($report, $entityId, $fromAccountingDate, $throughAccountingDate, $filters): array {
            $data = match ($report) {
                'summary' => $this->financialSummary($entityId, $fromAccountingDate, $throughAccountingDate),
                'account-balance' => $this->financialAccountBalances($entityId, $fromAccountingDate, $throughAccountingDate, $filters),
                'fund-balance' => $this->fundBalances($entityId, $fromAccountingDate, $throughAccountingDate, $filters),
                'account-movement' => $this->financialAccountMovement($entityId, $fromAccountingDate, $throughAccountingDate, $filters),
                'fund-movement' => $this->fundMovement($entityId, $fromAccountingDate, $throughAccountingDate, $filters),
                'transaction-history' => $this->transactionHistory($entityId, $fromAccountingDate, $throughAccountingDate, $filters),
                'cash-flow' => $this->cashFlow($entityId, $fromAccountingDate, $throughAccountingDate),
                'trial-balance' => $this->trialBalance($entityId, $fromAccountingDate, $throughAccountingDate),
                'friday' => $this->fridayOperations($entityId, $fromAccountingDate, $throughAccountingDate, $filters),
                'ziswaf' => $this->ziswaf($entityId, $fromAccountingDate, $throughAccountingDate, $filters),
                'program' => $this->programReport($entityId, $fromAccountingDate, $throughAccountingDate, $filters),
            };

            return [
                'report' => $report,
                'report_label' => self::REPORTS[$report],
                'period' => ['from_accounting_date' => $fromAccountingDate, 'through_accounting_date' => $throughAccountingDate],
                'as_of_posting_sequence' => $this->postedLedger->watermark($entityId, $throughAccountingDate),
                'source' => 'financial_v2_posted_general_ledger',
                'data' => $data,
            ];
        }, 3);
    }

    /** @return array{financial_accounts: array<int, array<string, string>>, funds: array<int, array<string, string>>, programs: array<int, array<string, string>>} */
    public function filterOptions(string $entityId): array
    {
        return [
            'financial_accounts' => DB::table('financial_v2_financial_accounts')
                ->where('accounting_entity_id', $entityId)
                ->orderBy('code')
                ->get(['id', 'code', 'name'])
                ->map(fn (object $row) => ['id' => $row->id, 'label' => $row->code.' — '.$row->name])
                ->all(),
            'funds' => DB::table('financial_v2_funds')
                ->where('accounting_entity_id', $entityId)
                ->orderBy('code')
                ->get(['id', 'code', 'name'])
                ->map(fn (object $row) => ['id' => $row->id, 'label' => $row->code.' — '.$row->name])
                ->all(),
            'programs' => DB::table('financial_v2_programs')
                ->where('accounting_entity_id', $entityId)
                ->orderBy('code')
                ->get(['id', 'code', 'name'])
                ->map(fn (object $row) => ['id' => $row->id, 'label' => $row->code.' — '.$row->name])
                ->all(),
        ];
    }

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    private function financialAccountBalances(string $entityId, string $from, string $through, array $filters): array
    {
        $query = $this->postedLedger->ledger($entityId, $through)
            ->join('financial_v2_financial_accounts as financial_account', 'financial_account.id', '=', 'ledger.financial_account_id')
            ->join('financial_v2_accounts as account', 'account.id', '=', 'ledger.account_id')
            ->whereNotNull('ledger.financial_account_id')
            ->select([
                'financial_account.id as financial_account_id',
                'financial_account.code as financial_account_code',
                'financial_account.name as financial_account_name',
                'financial_account.account_type as financial_account_type',
                'account.code as account_code',
                'account.name as account_name',
            ])
            ->selectRaw('COALESCE(SUM(CASE WHEN ledger.accounting_date < ? THEN ledger.signed_amount ELSE 0 END), 0) as opening_balance', [$from])
            ->selectRaw('COALESCE(SUM(CASE WHEN ledger.accounting_date >= ? THEN journal_line.debit_amount ELSE 0 END), 0) as period_debit', [$from])
            ->selectRaw('COALESCE(SUM(CASE WHEN ledger.accounting_date >= ? THEN journal_line.credit_amount ELSE 0 END), 0) as period_credit', [$from])
            ->selectRaw('COALESCE(SUM(ledger.signed_amount), 0) as closing_balance')
            ->groupBy('financial_account.id', 'financial_account.code', 'financial_account.name', 'financial_account.account_type', 'account.code', 'account.name')
            ->orderBy('financial_account.code');

        if ($financialAccountId = $this->filterId($filters, 'financial_account_id')) {
            $query->where('ledger.financial_account_id', $financialAccountId);
        }

        $rows = $query->get()->map(fn (object $row) => [
            'financial_account_id' => $row->financial_account_id,
            'code' => $row->financial_account_code,
            'name' => $row->financial_account_name,
            'type' => $row->financial_account_type,
            'account_code' => $row->account_code,
            'account_name' => $row->account_name,
            'opening_balance' => $this->amount($row->opening_balance),
            'period_debit' => $this->amount($row->period_debit),
            'period_credit' => $this->amount($row->period_credit),
            'closing_balance' => $this->amount($row->closing_balance),
        ])->all();

        $composition = $this->fundFinancialAccounts
            ->composition($entityId, $through, financialAccountId: $financialAccountId ?? null)
            ->map(fn (object $row) => [
                'financial_account_id' => $row->financial_account_id,
                'financial_account_code' => $row->financial_account_code,
                'fund_id' => $row->fund_id,
                'fund_code' => $row->fund_code,
                'fund_name' => $row->fund_name,
                'balance' => $this->amount($row->balance),
            ])->all();

        return [
            'has_data' => $rows !== [],
            'rows' => $rows,
            'fund_composition' => $composition,
            'definition' => 'Saldo Rekening dihitung dari posted liquidity lines. Komposisi Fund memakai baris likuiditas yang sama dan IFT posted dengan rekening atribusi; IFT tidak memindahkan atau menambah saldo rekening.',
        ];
    }

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    private function fundBalances(string $entityId, string $from, string $through, array $filters): array
    {
        $effectiveType = $this->effectiveTypeSql();
        $cashInTypes = $this->definitions->cashInTypes();
        $cashOutTypes = $this->definitions->cashOutTypes();
        $fundTransferTypes = $this->definitions->fundTransferTypes();
        $adjustmentTypes = $this->definitions->adjustmentTypes();
        $fundBalanceContribution = $this->fundBalanceContributionSql($effectiveType, $fundTransferTypes);

        $query = $this->postedLedger->ledger($entityId, $through)
            ->join('financial_v2_funds as fund', 'fund.id', '=', 'ledger.fund_id')
            ->join('financial_v2_fund_types as fund_type', 'fund_type.id', '=', 'fund.fund_type_id')
            ->join('financial_v2_accounts as account', 'account.id', '=', 'ledger.account_id')
            ->whereNotNull('ledger.fund_id')
            ->select('fund.id as fund_id', 'fund.code as fund_code', 'fund.name as fund_name', 'fund_type.classification as fund_classification')
            ->selectRaw('COALESCE(SUM(CASE WHEN ledger.accounting_date < ? THEN '.$fundBalanceContribution.' ELSE 0 END), 0) as opening_fund_balance', array_merge([$from], $fundTransferTypes))
            ->selectRaw('COALESCE(SUM('.$fundBalanceContribution.'), 0) as fund_balance', $fundTransferTypes)
            ->selectRaw('COALESCE(SUM(CASE WHEN account.account_class = ? AND ledger.accounting_date >= ? AND '.$effectiveType.' IN ('.$this->placeholders($cashInTypes).') THEN ledger.signed_amount ELSE 0 END), 0) as receipts', array_merge(['revenue', $from], $cashInTypes))
            ->selectRaw('COALESCE(SUM(CASE WHEN account.account_class = ? AND ledger.accounting_date >= ? AND '.$effectiveType.' IN ('.$this->placeholders($cashOutTypes).') THEN ledger.signed_amount ELSE 0 END), 0) as expenses', array_merge(['expense', $from], $cashOutTypes))
            ->selectRaw('COALESCE(SUM(CASE WHEN account.account_class = ? AND ledger.accounting_date >= ? AND '.$effectiveType.' IN ('.$this->placeholders($fundTransferTypes).') AND journal_line.debit_amount > 0 THEN journal_line.debit_amount - journal_line.credit_amount ELSE 0 END), 0) as transfer_in', array_merge(['transfer', $from], $fundTransferTypes))
            ->selectRaw('COALESCE(SUM(CASE WHEN account.account_class = ? AND ledger.accounting_date >= ? AND '.$effectiveType.' IN ('.$this->placeholders($fundTransferTypes).') AND journal_line.credit_amount > 0 THEN journal_line.credit_amount - journal_line.debit_amount ELSE 0 END), 0) as transfer_out', array_merge(['transfer', $from], $fundTransferTypes))
            ->selectRaw('COALESCE(SUM(CASE WHEN ledger.accounting_date >= ? AND '.$effectiveType.' IN ('.$this->placeholders($adjustmentTypes).') THEN '.$fundBalanceContribution.' ELSE 0 END), 0) as adjustments', array_merge([$from], $adjustmentTypes, $fundTransferTypes))
            ->groupBy('fund.id', 'fund.code', 'fund.name', 'fund_type.classification')
            ->orderBy('fund.code');

        if ($fundId = $this->filterId($filters, 'fund_id')) {
            $query->where('ledger.fund_id', $fundId);
        }

        $fundRows = $query->get();

        $distribution = $this->fundFinancialAccounts
            ->composition($entityId, $through, fundId: $fundId ?? null)
            ->map(fn (object $row) => [
                'fund_id' => $row->fund_id,
                'fund_code' => $row->fund_code,
                'fund_name' => $row->fund_name,
                'financial_account_id' => $row->financial_account_id,
                'financial_account_code' => $row->financial_account_code,
                'financial_account_name' => $row->financial_account_name,
                'liquidity_balance' => $this->amount($row->balance),
            ])->all();

        $liquidityByFund = collect($distribution)
            ->groupBy('fund_id')
            ->map(fn ($items) => DecimalAmount::sum($items->pluck('liquidity_balance')));

        $rows = $fundRows->map(function (object $row) use ($liquidityByFund): array {
            $opening = $this->amount($row->opening_fund_balance);
            $receipts = $this->amount($row->receipts);
            $expenses = $this->amount($row->expenses);
            $transferIn = $this->amount($row->transfer_in);
            $transferOut = $this->amount($row->transfer_out);
            $adjustments = $this->amount($row->adjustments);
            $fundBalance = $this->amount($row->fund_balance);
            $explainedBalance = DecimalAmount::add($opening, $receipts);
            $explainedBalance = DecimalAmount::subtract($explainedBalance, $expenses);
            $explainedBalance = DecimalAmount::add($explainedBalance, $transferIn);
            $explainedBalance = DecimalAmount::subtract($explainedBalance, $transferOut);
            $explainedBalance = DecimalAmount::add($explainedBalance, $adjustments);

            return [
                'fund_id' => $row->fund_id,
                'code' => $row->fund_code,
                'name' => $row->fund_name,
                'classification' => $row->fund_classification,
                'opening_fund_balance' => $opening,
                'fund_balance' => $fundBalance,
                'available_liquidity' => $liquidityByFund->get($row->fund_id, '0.00'),
                'receipts' => $receipts,
                'expenses' => $expenses,
                'transfer_in' => $transferIn,
                'transfer_out' => $transferOut,
                'adjustments' => $adjustments,
                'other_policy_components' => DecimalAmount::subtract($fundBalance, $explainedBalance),
                // Backward-compatible aliases. Consumers should use the explicit fields above.
                'opening_net_position' => $opening,
                'closing_net_position' => $fundBalance,
                'usage' => $expenses,
            ];
        })->all();

        $unmappedPolicyComponents = $this->postedLedger->ledger($entityId, $through)
            ->join('financial_v2_accounts as account', 'account.id', '=', 'ledger.account_id')
            ->whereNotNull('ledger.fund_id')
            ->whereIn('account.account_class', ['liability', 'control'])
            ->when($fundId ?? null, fn ($unmappedQuery) => $unmappedQuery->where('ledger.fund_id', $fundId))
            ->distinct()
            ->orderBy('account.account_class')
            ->pluck('account.account_class')
            ->values()
            ->all();

        return [
            'has_data' => $rows !== [],
            'rows' => $rows,
            'account_composition' => $distribution,
            'liquidity_distribution' => $distribution,
            'definition' => 'Saldo Dana dihitung dari Fund-attributed posted revenue, expense, net asset, dan Inter-Fund Transfer. Likuiditas Tersedia serta Komposisi Rekening berasal dari posted liquidity lines plus atribusi IFT posted; atribusi tidak memindahkan atau menambah saldo rekening.',
            'fund_balance_scope' => [
                'included_account_classes' => ['revenue', 'expense', 'net_asset', 'transfer for IFT'],
                'unmapped_policy_component_classes' => $unmappedPolicyComponents,
                'has_unmapped_policy_component_gap' => $unmappedPolicyComponents !== [],
            ],
            'compatibility' => [
                'deprecated_aliases' => [
                    'opening_net_position' => 'Use opening_fund_balance.',
                    'closing_net_position' => 'Use fund_balance.',
                    'usage' => 'Use expenses.',
                    'liquidity_distribution' => 'Use account_composition.',
                ],
            ],
        ];
    }

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    private function financialAccountMovement(string $entityId, string $from, string $through, array $filters): array
    {
        $financialAccountId = $this->filterId($filters, 'financial_account_id');
        if (! $financialAccountId) {
            return $this->filterRequired('financial_account_id', 'Pilih Rekening untuk menampilkan mutasi yang terurut dan berskala.');
        }

        $perPage = $this->perPage($filters);
        $cursorSequence = $this->cursorSequence($filters);
        $cursorLine = $this->cursorLine($filters);
        $base = $this->postedLedger->ledger($entityId, $through)->where('ledger.financial_account_id', $financialAccountId);
        $periodOpening = $this->amount((clone $base)->where('ledger.accounting_date', '<', $from)->sum('ledger.signed_amount'));
        $pageOpening = $periodOpening;

        if ($cursorSequence !== null && $cursorLine !== null) {
            $priorMovement = (clone $base)
                ->where('ledger.accounting_date', '>=', $from)
                ->where(function (Builder $query) use ($cursorSequence, $cursorLine): void {
                    $query->where('ledger.posting_sequence', '<', $cursorSequence)
                        ->orWhere(function (Builder $sameSequence) use ($cursorSequence, $cursorLine): void {
                            $sameSequence->where('ledger.posting_sequence', $cursorSequence)->where('ledger.line_no', '<=', $cursorLine);
                        });
                })
                ->sum('ledger.signed_amount');
            $pageOpening = DecimalAmount::add($periodOpening, $this->amount($priorMovement));
        }

        $query = (clone $base)
            ->leftJoin('financial_v2_funds as fund', 'fund.id', '=', 'ledger.fund_id')
            ->leftJoin('financial_v2_programs as program', 'program.id', '=', 'ledger.program_id')
            ->join('financial_v2_accounts as account', 'account.id', '=', 'ledger.account_id')
            ->where('ledger.accounting_date', '>=', $from)
            ->select([
                'ledger.id as ledger_entry_id', 'ledger.accounting_date', 'ledger.posting_sequence', 'ledger.line_no', 'ledger.signed_amount',
                'journal.id as journal_id', 'journal.description as journal_description', 'journal.reversal_of_journal_id',
                'journal_line.debit_amount', 'journal_line.credit_amount', 'journal_line.line_description',
                'financial_transaction.id as transaction_id', 'financial_transaction.status as transaction_status',
                'transaction_type.code as transaction_type_code', 'transaction_type.name as transaction_type_name',
                'original_transaction_type.code as original_transaction_type_code',
                'voucher.voucher_number', 'account.code as account_code', 'account.name as account_name',
                'fund.code as fund_code', 'fund.name as fund_name', 'program.code as program_code', 'program.name as program_name',
            ]);

        if ($cursorSequence !== null && $cursorLine !== null) {
            $query->where(function (Builder $query) use ($cursorSequence, $cursorLine): void {
                $query->where('ledger.posting_sequence', '>', $cursorSequence)
                    ->orWhere(function (Builder $sameSequence) use ($cursorSequence, $cursorLine): void {
                        $sameSequence->where('ledger.posting_sequence', $cursorSequence)->where('ledger.line_no', '>', $cursorLine);
                    });
            });
        }

        $records = $query->orderBy('ledger.accounting_date')->orderBy('ledger.posting_sequence')->orderBy('ledger.line_no')->limit($perPage + 1)->get();
        $hasMore = $records->count() > $perPage;
        $records = $records->take($perPage);
        $running = $pageOpening;
        $rows = $records->map(function (object $row) use (&$running): array {
            $running = DecimalAmount::add($running, $this->amount($row->signed_amount));

            return $this->movementRow($row, $running);
        })->all();
        $last = $records->last();

        return [
            'has_data' => (clone $base)->exists(),
            'financial_account_id' => $financialAccountId,
            'period_opening_balance' => $periodOpening,
            'page_opening_balance' => $pageOpening,
            'rows' => $rows,
            'next_cursor' => $hasMore && $last ? ['posting_sequence' => (int) $last->posting_sequence, 'line_no' => (int) $last->line_no] : null,
            'definition' => 'Mutasi memakai urutan accounting date, immutable posting sequence, lalu journal line number.',
        ];
    }

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    private function fundMovement(string $entityId, string $from, string $through, array $filters): array
    {
        $fundId = $this->filterId($filters, 'fund_id');
        if (! $fundId) {
            return $this->filterRequired('fund_id', 'Pilih Fund untuk menampilkan mutasi posted yang terurut dan berskala.');
        }

        $perPage = $this->perPage($filters);
        $cursorSequence = $this->cursorSequence($filters);
        $cursorLine = $this->cursorLine($filters);
        $effectiveType = $this->effectiveTypeSql();
        $fundTransferTypes = $this->definitions->fundTransferTypes();
        $fundBalanceContribution = $this->fundBalanceContributionSql($effectiveType, $fundTransferTypes);
        $base = $this->postedLedger->ledger($entityId, $through)
            ->join('financial_v2_accounts as account', 'account.id', '=', 'ledger.account_id')
            ->where('ledger.fund_id', $fundId);
        $periodOpening = $this->amount((clone $base)
            ->where('ledger.accounting_date', '<', $from)
            ->selectRaw('COALESCE(SUM('.$fundBalanceContribution.'), 0) as fund_balance', $fundTransferTypes)
            ->value('fund_balance'));
        $pageOpening = $periodOpening;

        if ($cursorSequence !== null && $cursorLine !== null) {
            $priorMovement = (clone $base)
                ->where('ledger.accounting_date', '>=', $from)
                ->where(function (Builder $query) use ($cursorSequence, $cursorLine): void {
                    $query->where('ledger.posting_sequence', '<', $cursorSequence)
                        ->orWhere(function (Builder $sameSequence) use ($cursorSequence, $cursorLine): void {
                            $sameSequence->where('ledger.posting_sequence', $cursorSequence)->where('ledger.line_no', '<=', $cursorLine);
                        });
                })
                ->selectRaw('COALESCE(SUM('.$fundBalanceContribution.'), 0) as fund_balance', $fundTransferTypes)
                ->value('fund_balance');
            $pageOpening = DecimalAmount::add($periodOpening, $this->amount($priorMovement));
        }

        $query = (clone $base)
            ->leftJoin('financial_v2_financial_accounts as financial_account', 'financial_account.id', '=', 'ledger.financial_account_id')
            ->leftJoin('financial_v2_financial_accounts as attribution_account', function (JoinClause $join): void {
                $join->on('attribution_account.id', '=', DB::raw('COALESCE(original_transaction.primary_financial_account_id, financial_transaction.primary_financial_account_id)'));
            })
            ->leftJoin('financial_v2_programs as program', 'program.id', '=', 'ledger.program_id')
            ->where('ledger.accounting_date', '>=', $from)
            ->select([
                'ledger.id as ledger_entry_id', 'ledger.accounting_date', 'ledger.posting_sequence', 'ledger.line_no', 'ledger.signed_amount',
                'journal.id as journal_id', 'journal.description as journal_description', 'journal.reversal_of_journal_id',
                'journal_line.debit_amount', 'journal_line.credit_amount', 'journal_line.line_description',
                'financial_transaction.id as transaction_id', 'financial_transaction.status as transaction_status',
                'transaction_type.code as transaction_type_code', 'transaction_type.name as transaction_type_name',
                'original_transaction_type.code as original_transaction_type_code',
                'voucher.voucher_number', 'account.code as account_code', 'account.name as account_name', 'account.account_class',
                'program.code as program_code', 'program.name as program_name',
            ])
            ->selectRaw("CASE WHEN {$effectiveType} = 'IFT' THEN attribution_account.code ELSE financial_account.code END as financial_account_code")
            ->selectRaw("CASE WHEN {$effectiveType} = 'IFT' THEN attribution_account.name ELSE financial_account.name END as financial_account_name")
            ->selectRaw($fundBalanceContribution.' as fund_balance_delta', $fundTransferTypes);

        if ($cursorSequence !== null && $cursorLine !== null) {
            $query->where(function (Builder $query) use ($cursorSequence, $cursorLine): void {
                $query->where('ledger.posting_sequence', '>', $cursorSequence)
                    ->orWhere(function (Builder $sameSequence) use ($cursorSequence, $cursorLine): void {
                        $sameSequence->where('ledger.posting_sequence', $cursorSequence)->where('ledger.line_no', '>', $cursorLine);
                    });
            });
        }

        $records = $query->orderBy('ledger.accounting_date')->orderBy('ledger.posting_sequence')->orderBy('ledger.line_no')->limit($perPage + 1)->get();
        $hasMore = $records->count() > $perPage;
        $records = $records->take($perPage);
        $running = $pageOpening;
        $rows = $records->map(function (object $row) use (&$running): array {
            $fundBalanceDelta = $this->amount($row->fund_balance_delta);
            $running = DecimalAmount::add($running, $fundBalanceDelta);
            $entry = $this->movementRow($row, $this->amount($row->signed_amount));
            $entry['movement_kind'] = $this->fundMovementKind($row);
            $entry['fund_balance_delta'] = $fundBalanceDelta;
            $entry['running_fund_balance'] = $running;

            return $entry;
        })->all();
        $last = $records->last();

        return [
            'has_data' => (clone $base)->exists(),
            'fund_id' => $fundId,
            'period_opening_fund_balance' => $periodOpening,
            'page_opening_fund_balance' => $pageOpening,
            'period_opening_net_position' => $periodOpening,
            'page_opening_net_position' => $pageOpening,
            'rows' => $rows,
            'next_cursor' => $hasMore && $last ? ['posting_sequence' => (int) $last->posting_sequence, 'line_no' => (int) $last->line_no] : null,
            'definition' => 'Mutasi Dana menampilkan seluruh posted Fund-attributed lines. Dampak dan saldo berjalan Dana mengikuti formula Saldo Dana; posisi likuiditas per Rekening tersedia di laporan Saldo Dana.',
        ];
    }

    /** @return array<string, mixed> */
    private function cashFlow(string $entityId, string $from, string $through): array
    {
        return $this->cashFlowData($entityId, $from, $through, $this->definitions->cashInTypes(), $this->definitions->cashOutTypes(), $this->definitions->internalTransferTypes());
    }

    /** @return array<string, mixed> */
    private function cashFlowData(string $entityId, string $from, string $through, array $cashInTypes, array $cashOutTypes, array $transferTypes): array
    {
        $base = $this->postedLedger->ledger($entityId, $through)
            ->whereNotNull('ledger.financial_account_id');
        $period = (clone $base)->where('ledger.accounting_date', '>=', $from);
        $effectiveType = $this->postedLedger->effectiveTransactionTypeCode();
        $opening = $this->amount((clone $base)->where('ledger.accounting_date', '<', $from)->sum('ledger.signed_amount'));
        $cashIn = $this->amount((clone $period)->whereIn($effectiveType, $cashInTypes)->sum('ledger.signed_amount'));
        $cashOut = DecimalAmount::negate($this->amount((clone $period)->whereIn($effectiveType, $cashOutTypes)->sum('ledger.signed_amount')));
        $internalTransferNet = $this->amount((clone $period)->whereIn($effectiveType, $transferTypes)->sum('ledger.signed_amount'));
        $knownTypes = array_values(array_unique(array_merge($cashInTypes, $cashOutTypes, $transferTypes)));
        $unclassified = $this->amount((clone $period)->whereNotIn($effectiveType, $knownTypes)->sum('ledger.signed_amount'));
        $closing = $this->amount((clone $base)->sum('ledger.signed_amount'));

        $transferSummary = $this->postedLedger->journals($entityId, $through)
            ->where('journal.accounting_date', '>=', $from)
            ->whereIn($effectiveType, $transferTypes)
            ->selectRaw('COALESCE(SUM(CASE WHEN journal.reversal_of_journal_id IS NULL THEN journal.total_debit ELSE -journal.total_debit END), 0) as net_turnover')
            ->selectRaw('COALESCE(SUM(journal.total_debit), 0) as gross_turnover')
            ->first();

        $expectedClosing = DecimalAmount::add(
            DecimalAmount::add(DecimalAmount::subtract($opening, $cashOut), $cashIn),
            DecimalAmount::add($internalTransferNet, $unclassified),
        );

        return [
            'has_data' => (clone $base)->exists(),
            'opening_balance' => $opening,
            'cash_in' => $cashIn,
            'cash_out' => $cashOut,
            'internal_transfer_net' => $internalTransferNet,
            'internal_transfer_turnover' => $this->amount($transferSummary->gross_turnover ?? 0),
            'internal_transfer_net_turnover' => $this->amount($transferSummary->net_turnover ?? 0),
            'unclassified_cash_movement' => $unclassified,
            'closing_balance' => $closing,
            'is_tied_out' => DecimalAmount::equals($expectedClosing, $closing),
            'definition' => 'Arus kas memakai posted liquidity lines. Reversal diklasifikasikan mengikuti tipe transaksi jurnal asal; klasifikasi tampilan dapat diubah pada config/financial_reporting.php.',
        ];
    }

    /** @return array<string, mixed> */
    private function trialBalance(string $entityId, string $from, string $through): array
    {
        $rows = $this->postedLedger->ledger($entityId, $through)
            ->join('financial_v2_accounts as account', 'account.id', '=', 'ledger.account_id')
            ->select('account.id as account_id', 'account.code as account_code', 'account.name as account_name', 'account.account_class', 'account.normal_balance')
            ->selectRaw('COALESCE(SUM(CASE WHEN ledger.accounting_date < ? THEN ledger.signed_amount ELSE 0 END), 0) as opening_balance', [$from])
            ->selectRaw('COALESCE(SUM(CASE WHEN ledger.accounting_date >= ? THEN journal_line.debit_amount ELSE 0 END), 0) as debit_total', [$from])
            ->selectRaw('COALESCE(SUM(CASE WHEN ledger.accounting_date >= ? THEN journal_line.credit_amount ELSE 0 END), 0) as credit_total', [$from])
            ->selectRaw('COALESCE(SUM(ledger.signed_amount), 0) as closing_balance')
            ->groupBy('account.id', 'account.code', 'account.name', 'account.account_class', 'account.normal_balance')
            ->orderBy('account.code')
            ->get()
            ->map(fn (object $row) => [
                'account_id' => $row->account_id,
                'code' => $row->account_code,
                'name' => $row->account_name,
                'class' => $row->account_class,
                'normal_balance' => $row->normal_balance,
                'opening_balance' => $this->amount($row->opening_balance),
                'debit_total' => $this->amount($row->debit_total),
                'credit_total' => $this->amount($row->credit_total),
                'closing_balance' => $this->amount($row->closing_balance),
            ])->all();

        $totalDebit = DecimalAmount::sum(array_column($rows, 'debit_total'));
        $totalCredit = DecimalAmount::sum(array_column($rows, 'credit_total'));

        return [
            'has_data' => $rows !== [],
            'rows' => $rows,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'is_balanced' => DecimalAmount::equals($totalDebit, $totalCredit),
            'definition' => 'Trial Balance mengelompokkan Posted General Ledger per Account; total debit dan kredit periode harus sama.',
        ];
    }

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    private function transactionHistory(string $entityId, string $from, string $through, array $filters): array
    {
        $perPage = $this->perPage($filters);
        $cursor = $this->cursorSequence($filters);
        $dimensions = DB::table('financial_v2_journal_lines as journal_line')
            ->leftJoin('financial_v2_funds as fund', 'fund.id', '=', 'journal_line.fund_id')
            ->leftJoin('financial_v2_financial_accounts as financial_account', 'financial_account.id', '=', 'journal_line.financial_account_id')
            ->leftJoin('financial_v2_programs as program', 'program.id', '=', 'journal_line.program_id')
            ->select('journal_line.journal_id')
            ->selectRaw('COUNT(*) as line_count')
            ->selectRaw("GROUP_CONCAT(DISTINCT fund.code ORDER BY fund.code SEPARATOR ', ') as fund_codes")
            ->selectRaw("GROUP_CONCAT(DISTINCT financial_account.code ORDER BY financial_account.code SEPARATOR ', ') as financial_account_codes")
            ->selectRaw("GROUP_CONCAT(DISTINCT program.code ORDER BY program.code SEPARATOR ', ') as program_codes")
            ->groupBy('journal_line.journal_id');
        $evidence = DB::table('financial_v2_attachment_links as attachment_link')
            ->where('attachment_link.target_type', 'transaction')
            ->where('attachment_link.status', 'active')
            ->select('attachment_link.target_id')
            ->selectRaw('COUNT(*) as evidence_count')
            ->groupBy('attachment_link.target_id');

        $query = $this->postedLedger->journals($entityId, $through)
            ->leftJoinSub($dimensions, 'dimensions', fn (\Illuminate\Database\Query\JoinClause $join) => $join->on('dimensions.journal_id', '=', 'journal.id'))
            ->leftJoinSub($evidence, 'evidence', fn (\Illuminate\Database\Query\JoinClause $join) => $join->on('evidence.target_id', '=', 'financial_transaction.id'))
            ->where('journal.accounting_date', '>=', $from)
            ->select([
                'journal.id as journal_id', 'journal.accounting_date', 'journal.posting_sequence', 'journal.description as journal_description', 'journal.total_debit',
                'journal.reversal_of_journal_id', 'financial_transaction.id as transaction_id', 'financial_transaction.status as transaction_status',
                'transaction_type.code as transaction_type_code', 'transaction_type.name as transaction_type_name',
                'original_transaction_type.code as original_transaction_type_code', 'voucher.voucher_number',
                'dimensions.line_count', 'dimensions.fund_codes', 'dimensions.financial_account_codes', 'dimensions.program_codes', 'evidence.evidence_count',
            ]);
        if ($cursor !== null) {
            $query->where('journal.posting_sequence', '>', $cursor);
        }
        $records = $query->orderBy('journal.accounting_date')->orderBy('journal.posting_sequence')->limit($perPage + 1)->get();
        $hasMore = $records->count() > $perPage;
        $records = $records->take($perPage);
        $last = $records->last();

        return [
            'has_data' => $this->postedLedger->journals($entityId, $through)->exists(),
            'rows' => $records->map(fn (object $row) => [
                'journal_id' => $row->journal_id,
                'transaction_id' => $row->transaction_id,
                'accounting_date' => $row->accounting_date,
                'posting_sequence' => (int) $row->posting_sequence,
                'voucher_number' => $row->voucher_number,
                'transaction_type_code' => $row->transaction_type_code,
                'transaction_type_name' => $row->transaction_type_name,
                'effective_transaction_type_code' => $row->original_transaction_type_code ?: $row->transaction_type_code,
                'transaction_status' => $row->transaction_status,
                'journal_description' => $row->journal_description,
                'amount' => $this->amount($row->total_debit),
                'reversal_of_journal_id' => $row->reversal_of_journal_id,
                'line_count' => (int) ($row->line_count ?? 0),
                'fund_codes' => $row->fund_codes,
                'financial_account_codes' => $row->financial_account_codes,
                'program_codes' => $row->program_codes,
                'evidence_count' => (int) ($row->evidence_count ?? 0),
            ])->all(),
            'next_cursor' => $hasMore && $last ? ['posting_sequence' => (int) $last->posting_sequence] : null,
            'definition' => 'Register hanya memuat Posted Journal dan menyediakan transaksi, voucher, jurnal, dimensi, dan jumlah evidence untuk drill-down.',
        ];
    }

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    private function fridayOperations(string $entityId, string $from, string $through, array $filters): array
    {
        $definition = $this->definitions->friday((string) ($filters['definition'] ?? 'default'));
        $cashFlow = $this->cashFlowData(
            $entityId,
            $from,
            $through,
            $definition['cash_in_transaction_type_codes'],
            $definition['cash_out_transaction_type_codes'],
            $definition['internal_transfer_transaction_type_codes'],
        );

        return [
            'has_data' => $cashFlow['has_data'],
            'definition' => $definition,
            'opening_balance' => $cashFlow['opening_balance'],
            'receipts' => $cashFlow['cash_in'],
            'payments' => $cashFlow['cash_out'],
            'receipt_rows' => $this->classifiedJournalRows($entityId, $from, $through, $definition['cash_in_transaction_type_codes']),
            'payment_rows' => $this->classifiedJournalRows($entityId, $from, $through, $definition['cash_out_transaction_type_codes']),
            'internal_transfer_net' => $cashFlow['internal_transfer_net'],
            'unclassified_cash_movement' => $cashFlow['unclassified_cash_movement'],
            'closing_balance' => $cashFlow['closing_balance'],
            'is_tied_out' => $cashFlow['is_tied_out'],
            'definition_note' => 'Format khusus Jumat belum ditentukan oleh Accounting Policy; laporan ini adalah definisi read-only yang dapat dikonfigurasi tanpa mengubah posting atau saldo.',
        ];
    }

    /**
     * Read-only details for the Friday presentation. A posted Journal is one
     * business event, so this intentionally groups at journal level instead
     * of exposing double-entry lines to pengurus.
     *
     * @param  array<int, string>  $transactionTypeCodes
     * @return array<int, array<string, mixed>>
     */
    private function classifiedJournalRows(string $entityId, string $from, string $through, array $transactionTypeCodes): array
    {
        $effectiveType = $this->effectiveTypeSql();

        return $this->postedLedger->journals($entityId, $through)
            ->where('journal.accounting_date', '>=', $from)
            ->whereRaw($effectiveType.' IN ('.$this->placeholders($transactionTypeCodes).')', $transactionTypeCodes)
            ->orderBy('journal.accounting_date')
            ->orderBy('journal.posting_sequence')
            ->get([
                'journal.id as journal_id',
                'journal.accounting_date',
                'journal.description',
                'journal.total_debit',
                'journal.reversal_of_journal_id',
                'transaction_type.name as transaction_type_name',
                'original_transaction_type.name as original_transaction_type_name',
                'voucher.voucher_number',
            ])
            ->map(fn (object $row): array => [
                'journal_id' => $row->journal_id,
                'accounting_date' => $row->accounting_date,
                'voucher_number' => $row->voucher_number,
                'transaction_type_name' => $row->original_transaction_type_name ?: $row->transaction_type_name,
                'description' => $row->description,
                'is_reversal' => $row->reversal_of_journal_id !== null,
                'amount' => $row->reversal_of_journal_id ? DecimalAmount::negate($this->amount($row->total_debit)) : $this->amount($row->total_debit),
            ])
            ->all();
    }

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    private function ziswaf(string $entityId, string $from, string $through, array $filters): array
    {
        $fundReport = $this->fundBalances($entityId, $from, $through, $filters);

        return $fundReport + [
            'definition_note' => 'Tidak ada klasifikasi ZISWAF otomatis. Pilih Fund yang telah ditetapkan secara governance; nama Fund atau Program tidak ditafsirkan sebagai ZISWAF.',
        ];
    }

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    private function programReport(string $entityId, string $from, string $through, array $filters): array
    {
        $effectiveType = $this->effectiveTypeSql();
        $cashInTypes = $this->definitions->cashInTypes();
        $cashOutTypes = $this->definitions->cashOutTypes();
        $query = $this->postedLedger->ledger($entityId, $through)
            ->join('financial_v2_programs as program', 'program.id', '=', 'ledger.program_id')
            ->join('financial_v2_accounts as account', 'account.id', '=', 'ledger.account_id')
            ->leftJoin('financial_v2_funds as fund', 'fund.id', '=', 'ledger.fund_id')
            ->whereNotNull('ledger.program_id')
            ->select('program.id as program_id', 'program.code as program_code', 'program.name as program_name')
            ->selectRaw('COALESCE(SUM(CASE WHEN account.account_class = ? AND ledger.accounting_date >= ? AND '.$effectiveType.' IN ('.$this->placeholders($cashInTypes).') THEN ledger.signed_amount ELSE 0 END), 0) as receipts', array_merge(['revenue', $from], $cashInTypes))
            ->selectRaw('COALESCE(SUM(CASE WHEN account.account_class = ? AND ledger.accounting_date >= ? AND '.$effectiveType.' IN ('.$this->placeholders($cashOutTypes).') THEN ledger.signed_amount ELSE 0 END), 0) as usage_amount', array_merge(['expense', $from], $cashOutTypes))
            ->selectRaw("GROUP_CONCAT(DISTINCT fund.code ORDER BY fund.code SEPARATOR ', ') as fund_codes")
            ->groupBy('program.id', 'program.code', 'program.name')
            ->orderBy('program.code');
        if ($programId = $this->filterId($filters, 'program_id')) {
            $query->where('ledger.program_id', $programId);
        }

        $rows = $query->get()->map(function (object $row): array {
            $receipts = $this->amount($row->receipts);
            $usage = $this->amount($row->usage_amount);

            return [
                'program_id' => $row->program_id,
                'code' => $row->program_code,
                'name' => $row->program_name,
                'receipts' => $receipts,
                'usage' => $usage,
                'net_usage' => DecimalAmount::subtract($usage, $receipts),
                'fund_codes' => $row->fund_codes,
            ];
        })->all();

        return [
            'has_data' => $rows !== [],
            'rows' => $rows,
            'definition' => 'Program adalah dimensi penggunaan/manajemen. Laporan ini tidak membuat Program menjadi Fund atau saldo kas.',
        ];
    }

    /** @return array<string, mixed> */
    private function financialSummary(string $entityId, string $from, string $through): array
    {
        $accounts = $this->financialAccountBalances($entityId, $from, $through, []);
        $funds = $this->fundBalances($entityId, $from, $through, []);
        $cashFlow = $this->cashFlow($entityId, $from, $through);
        $trialBalance = $this->trialBalance($entityId, $from, $through);

        return [
            'has_data' => $cashFlow['has_data'],
            'cash_position' => $cashFlow['closing_balance'],
            'cash_in' => $cashFlow['cash_in'],
            'cash_out' => $cashFlow['cash_out'],
            'financial_account_count' => count($accounts['rows']),
            'fund_count' => count($funds['rows']),
            'trial_balance_is_balanced' => $trialBalance['is_balanced'],
            'trial_balance_total_debit' => $trialBalance['total_debit'],
            'trial_balance_total_credit' => $trialBalance['total_credit'],
            'definition' => 'Ringkasan ini hanya mengagregasi report query posted V2; tidak membaca saldo legacy atau tabel summary operasional.',
        ];
    }

    /** @return array<string, mixed> */
    private function filterRequired(string $field, string $message): array
    {
        return ['has_data' => false, 'requires_filter' => $field, 'message' => $message, 'rows' => []];
    }

    /** @return array<string, mixed> */
    private function movementRow(object $row, string $runningBalance): array
    {
        return [
            'ledger_entry_id' => $row->ledger_entry_id,
            'accounting_date' => $row->accounting_date,
            'posting_sequence' => (int) $row->posting_sequence,
            'line_no' => (int) $row->line_no,
            'journal_id' => $row->journal_id,
            'transaction_id' => $row->transaction_id,
            'voucher_number' => $row->voucher_number,
            'transaction_type_code' => $row->transaction_type_code,
            'effective_transaction_type_code' => $row->original_transaction_type_code ?: $row->transaction_type_code,
            'transaction_status' => $row->transaction_status,
            'reversal_of_journal_id' => $row->reversal_of_journal_id,
            'journal_description' => $row->journal_description,
            'line_description' => $row->line_description,
            'account_code' => $row->account_code,
            'account_name' => $row->account_name,
            'debit_amount' => $this->amount($row->debit_amount),
            'credit_amount' => $this->amount($row->credit_amount),
            'signed_amount' => $this->amount($row->signed_amount),
            'running_balance' => $runningBalance,
            'fund_code' => $row->fund_code ?? null,
            'fund_name' => $row->fund_name ?? null,
            'financial_account_code' => $row->financial_account_code ?? null,
            'financial_account_name' => $row->financial_account_name ?? null,
            'program_code' => $row->program_code ?? null,
            'program_name' => $row->program_name ?? null,
        ];
    }

    private function fundMovementKind(object $row): string
    {
        $type = $row->original_transaction_type_code ?: $row->transaction_type_code;
        if (in_array($type, $this->definitions->cashInTypes(), true) && $row->account_class === 'revenue') {
            return 'receipt';
        }
        if (in_array($type, $this->definitions->cashOutTypes(), true) && $row->account_class === 'expense') {
            return 'usage';
        }
        if (in_array($type, $this->definitions->fundTransferTypes(), true) && $row->account_class === 'transfer') {
            return DecimalAmount::compare($this->amount($row->debit_amount), $this->amount($row->credit_amount)) > 0 ? 'transfer_in' : 'transfer_out';
        }

        return 'ledger_effect';
    }

    private function effectiveTypeSql(): string
    {
        return 'COALESCE(original_transaction_type.code, transaction_type.code)';
    }

    /** @param array<int, string> $fundTransferTypes */
    private function fundBalanceContributionSql(string $effectiveType, array $fundTransferTypes): string
    {
        return "CASE
            WHEN account.account_class = 'revenue' THEN ledger.signed_amount
            WHEN account.account_class = 'expense' THEN -ledger.signed_amount
            WHEN account.account_class = 'net_asset' THEN ledger.signed_amount
            WHEN account.account_class = 'transfer' AND {$effectiveType} IN ({$this->placeholders($fundTransferTypes)}) THEN journal_line.debit_amount - journal_line.credit_amount
            ELSE 0
        END";
    }

    /** @param array<int, string> $values */
    private function placeholders(array $values): string
    {
        return implode(', ', array_fill(0, max(1, count($values)), '?'));
    }

    /** @param array<string, mixed> $filters */
    private function filterId(array $filters, string $key): ?string
    {
        $value = $filters[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /** @param array<string, mixed> $filters */
    private function perPage(array $filters): int
    {
        return min(200, max(1, (int) ($filters['per_page'] ?? 50)));
    }

    /** @param array<string, mixed> $filters */
    private function cursorSequence(array $filters): ?int
    {
        $value = $filters['cursor_posting_sequence'] ?? null;

        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    /** @param array<string, mixed> $filters */
    private function cursorLine(array $filters): ?int
    {
        $value = $filters['cursor_line_no'] ?? null;

        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    private function amount(mixed $value): string
    {
        return DecimalAmount::normalize((string) ($value ?? 0));
    }
}
