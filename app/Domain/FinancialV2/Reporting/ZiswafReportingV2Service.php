<?php

namespace App\Domain\FinancialV2\Reporting;

use App\Domain\FinancialV2\DecimalAmount;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\BudgetAllocation;
use App\Models\FinancialV2\BudgetAllocationFunding;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\Program;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Read-only ZISWAF Reporting V2 presentation model.
 *
 * Financial values are delegated to FinancialReportService / PostedLedgerQuery.
 * Allocation and realization records supply only plan and linkage context; this
 * service never writes a Journal, Ledger, transaction, balance, or projection.
 */
final class ZiswafReportingV2Service
{
    public function __construct(
        private readonly FinancialReportService $reports,
        private readonly PostedLedgerQuery $postedLedger,
        private readonly FinancialReportDefinitions $definitions,
        private readonly DistributionReportingService $distributions,
    ) {}

    /**
     * @param  array<int, string>|null  $fundIds
     * @param  array{program_id?: string, category_id?: string, type?: string, per_page?: int, cursor_posting_sequence?: int}|null  $filters
     * @return array<string, mixed>
     */
    public function report(AccountingEntity $entity, string $from, string $through, ?array $fundIds = null, ?array $filters = null): array
    {
        $this->assertPeriod($from, $through);
        $scopeFundIds = $this->fundScope($entity, $fundIds);
        $filterFundId = $this->filterId($filters ?? [], 'fund_id');
        if ($filterFundId && ! in_array($filterFundId, $scopeFundIds, true)) {
            $scopeFundIds = [];
        } elseif ($filterFundId) {
            $scopeFundIds = [$filterFundId];
        }

        $canonical = $this->reports->report('ziswaf', $entity->id, $from, $through);
        $canonicalRows = collect($canonical['data']['rows'] ?? [])->keyBy('fund_id');
        $funds = Fund::query()
            ->with('restriction:id,name,policy_basis')
            ->where('accounting_entity_id', $entity->id)
            ->when($scopeFundIds !== [], fn ($query) => $query->whereIn('id', $scopeFundIds))
            ->when($scopeFundIds === [], fn ($query) => $query->whereRaw('1 = 0'))
            ->orderBy('code')
            ->get();

        $fundRows = $funds->map(fn (Fund $fund): array => $this->fundRow($fund, $canonicalRows->get($fund->id, [])))->values();
        $fundIdsForFacts = $fundRows->pluck('fund_id')->all();
        $programId = $this->filterId($filters ?? [], 'program_id');
        $allProgramFundingRows = $fundIdsForFacts === []
            ? collect()
            : collect($this->reports->programExpenseFunding($entity->id, $from, $through, $fundIdsForFacts));
        $fundingRows = $programId
            ? collect($this->reports->programExpenseFunding($entity->id, $from, $through, $fundIdsForFacts, $programId))
            : $allProgramFundingRows;
        $nonProgramByFund = $fundIdsForFacts === []
            ? collect()
            : collect($this->reports->nonProgramExpenseByFund($entity->id, $from, $through, $fundIdsForFacts))
                ->map(fn (array $row): array => [
                    'fund_id' => $row['fund_id'], 'fund_code' => $row['fund_code'], 'fund_name' => $row['fund_name'], 'amount' => $row['actual_expense'],
                ]);
        $programs = $this->programRows($entity, $from, $through, $fundIdsForFacts, $fundingRows, $programId);
        $summary = $this->summary($fundRows);
        $summary['planned_usage'] = DecimalAmount::sum($programs->pluck('allocation'));
        $allProgramActual = DecimalAmount::sum($allProgramFundingRows->pluck('actual_expense'));
        $nonProgramActual = DecimalAmount::sum($nonProgramByFund->pluck('amount'));

        return [
            'entity' => ['id' => $entity->id, 'name' => $entity->name, 'code' => $entity->code],
            'period' => ['from' => $from, 'through' => $through],
            'source' => 'financial_v2_posted_general_ledger',
            'as_of_posting_sequence' => $canonical['as_of_posting_sequence'] ?? 0,
            'filters' => ['fund_id' => $filterFundId, 'program_id' => $this->filterId($filters ?? [], 'program_id')],
            'funds' => $fundRows->all(),
            'summary' => $summary,
            'income_by_fund' => $fundRows->map(fn (array $row): array => [
                'fund_id' => $row['fund_id'], 'fund_code' => $row['code'], 'fund_name' => $row['name'], 'amount' => $row['receipts'],
            ])->all(),
            'programs' => $programs->values()->all(),
            'distributions' => $this->distributions->report($entity->id, $from, $through, $fundIdsForFacts, $programId, false, $filterFundId !== null),
            'non_program_expenses' => $nonProgramByFund->all(),
            'transactions' => $this->transactionHistory($entity, $from, $through, $fundIdsForFacts, $filters ?? []),
            'diagnostics' => [
                'fund_balance_reconciled' => DecimalAmount::equals($summary['closing_balance'], DecimalAmount::sum($fundRows->pluck('fund_balance'))),
                'actual_expense_reconciled' => DecimalAmount::equals($summary['expenses'], DecimalAmount::add($allProgramActual, $nonProgramActual)),
                'plan_excluded_from_actual' => true,
                'message' => 'Actual hanya berasal dari Posted Financial V2 Journal/Ledger. Anggaran dan alokasi ditampilkan terpisah sebagai rencana.',
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function programDetail(AccountingEntity $entity, Program $program, string $from, string $through, ?array $fundIds = null): array
    {
        abort_unless($program->accounting_entity_id === $entity->id, 404);
        $report = $this->report($entity, $from, $through, $fundIds, ['program_id' => $program->id]);
        $programRow = collect($report['programs'])->firstWhere('program_id', $program->id);

        return $report + [
            'program' => $programRow ?: [
                'program_id' => $program->id,
                'code' => $program->code,
                'name' => $program->name,
                'status' => $program->status,
                'budget' => '0.00', 'allocation' => '0.00', 'realization' => '0.00', 'actual_expense' => '0.00', 'remaining' => '0.00',
                'funding_sources' => [],
            ],
            'program_model' => $program,
        ];
    }

    /** @return array<string, mixed> */
    public function publicReport(?string $requestedFrom = null, ?string $requestedThrough = null): array
    {
        $entity = AccountingEntity::query()
            ->where('code', (string) config('financial_reporting.public_ziswaf.entity_code'))
            ->where('status', 'active')
            ->firstOrFail();
        $latest = $this->postedLedger->ledger($entity->id, '9999-12-31')->max('ledger.accounting_date');
        $through = $requestedThrough ?: ($latest ?: now()->toDateString());
        $from = $requestedFrom ?: Carbon::parse($through)->startOfMonth()->toDateString();
        $this->assertPeriod($from, $through);
        $fundCodes = array_values(array_filter(config('financial_reporting.public_ziswaf.fund_codes', []), 'is_string'));
        $fundIds = Fund::query()->where('accounting_entity_id', $entity->id)->whereIn('code', $fundCodes)->pluck('id')->all();

        $report = $this->report($entity, $from, $through, $fundIds);
        $report['programs'] = $this->publicProgramRows(collect($report['programs']));
        $report['distributions'] = $this->distributions->report($entity->id, $from, $through, $fundIds, null, true);
        unset($report['summary']['planned_usage'], $report['transactions']);

        return $report;
    }

    /** @param array<int, array<string, mixed>> $fundRows @return array<string, string> */
    private function summary(Collection $fundRows): array
    {
        return [
            'opening_balance' => DecimalAmount::sum($fundRows->pluck('opening_fund_balance')),
            'receipts' => DecimalAmount::sum($fundRows->pluck('receipts')),
            'expenses' => DecimalAmount::sum($fundRows->pluck('expenses')),
            'closing_balance' => DecimalAmount::sum($fundRows->pluck('fund_balance')),
            'planned_usage' => '0.00',
        ];
    }

    /** @param array<string, mixed>|null $row @return array<string, mixed> */
    private function fundRow(Fund $fund, ?array $row): array
    {
        $row ??= [];

        return [
            'fund_id' => $fund->id,
            'code' => $fund->code,
            'name' => $fund->name,
            'classification' => $row['classification'] ?? null,
            'restriction' => $fund->restriction?->name,
            'purpose_statement' => $fund->purpose_statement,
            'opening_fund_balance' => $this->amount($row['opening_fund_balance'] ?? '0.00'),
            'receipts' => $this->amount($row['receipts'] ?? '0.00'),
            'expenses' => $this->amount($row['expenses'] ?? '0.00'),
            'movement' => DecimalAmount::add($this->amount($row['transfer_in'] ?? '0.00'), DecimalAmount::negate($this->amount($row['transfer_out'] ?? '0.00'))),
            'fund_balance' => $this->amount($row['fund_balance'] ?? '0.00'),
            'available_liquidity' => $this->amount($row['available_liquidity'] ?? '0.00'),
        ];
    }

    /**
     * @param  array<int, string>  $fundIds
     * @param  Collection<int, array<string, mixed>>  $fundingRows
     * @return Collection<int, array<string, mixed>>
     */
    private function programRows(AccountingEntity $entity, string $from, string $through, array $fundIds, Collection $fundingRows, ?string $onlyProgramId): Collection
    {
        $plans = $this->planRows($entity, $from, $through, $fundIds, $onlyProgramId);
        $actualByProgram = $fundingRows->groupBy('program_id')->map(fn (Collection $rows): string => DecimalAmount::sum($rows->pluck('actual_expense')));
        $programMeta = Program::query()
            ->where('accounting_entity_id', $entity->id)
            ->when($onlyProgramId, fn ($query) => $query->whereKey($onlyProgramId))
            ->whereIn('id', collect($plans)->pluck('program_id')->merge($fundingRows->pluck('program_id'))->unique()->all() ?: ['00000000-0000-0000-0000-000000000000'])
            ->get()
            ->keyBy('id');
        $programIds = collect($plans)->pluck('program_id')->merge($fundingRows->pluck('program_id'))->filter()->unique()->sort()->values();

        return $programIds->map(function (string $programId) use ($plans, $actualByProgram, $fundingRows, $programMeta): array {
            $plan = $plans->get($programId, $this->emptyPlan());
            $actual = $actualByProgram->get($programId, '0.00');
            $allocated = $plan['allocation'];
            $realization = $this->amount($plan['realization']);
            $fundingSources = $this->fundingSources($plan['funding_sources'], $fundingRows->where('program_id', $programId));
            $program = $programMeta->get($programId);

            return [
                'program_id' => $programId,
                'code' => $program?->code ?? $plan['program_code'],
                'name' => $program?->name ?? $plan['program_name'],
                'status' => $this->programStatus($plan['status'], $allocated, $realization, $actual, $program?->status),
                'budget' => $plan['budget'],
                'allocation' => $allocated,
                'realization' => $realization,
                'actual_expense' => $actual,
                'remaining' => DecimalAmount::subtract($allocated, $realization),
                'funding_sources' => $fundingSources,
            ];
        });
    }

    /**
     * Allocation is plan only.  Realization is calculated from its linked
     * posted expense ledger lines, never from a draft transaction amount.
     *
     * @param  array<int, string>  $fundIds
     * @return Collection<string, array<string, mixed>>
     */
    private function planRows(AccountingEntity $entity, string $from, string $through, array $fundIds, ?string $onlyProgramId): Collection
    {
        if ($fundIds === []) {
            return collect();
        }
        $allocations = BudgetAllocation::query()
            ->with(['fund:id,code,name', 'program:id,code,name,status', 'versions.fundings.fund:id,code,name'])
            ->where('accounting_entity_id', $entity->id)
            ->whereNotNull('program_id')
            ->whereNotIn('status', ['cancelled', 'superseded'])
            ->when($onlyProgramId, fn ($query) => $query->where('program_id', $onlyProgramId))
            ->whereHas('versions', function ($query) use ($from, $through): void {
                $query->whereNotIn('status', ['cancelled', 'superseded'])
                    ->whereDate('effective_from', '<=', $through)
                    ->where(fn ($effective) => $effective->whereNull('effective_to')->orWhereDate('effective_to', '>=', $from));
            })
            ->get();
        $realized = $this->postedRealizationByVersion($entity->id, $from, $through);

        return $allocations->flatMap(function (BudgetAllocation $allocation) use ($from, $through, $fundIds, $realized): Collection {
            return $allocation->versions
                ->filter(fn ($version): bool => ! in_array($version->status, ['cancelled', 'superseded'], true)
                    && $version->effective_from?->toDateString() <= $through
                    && (! $version->effective_to || $version->effective_to->toDateString() >= $from))
                ->map(function ($version) use ($allocation, $fundIds, $realized): array {
                    $fundings = $version->fundings->isNotEmpty()
                        ? $version->fundings->map(fn (BudgetAllocationFunding $funding): array => [
                            'fund_id' => $funding->fund_id, 'fund_code' => $funding->fund?->code ?? '—', 'fund_name' => $funding->fund?->name ?? 'Dana', 'allocated' => $this->amount($funding->amount),
                        ])
                        : collect([['fund_id' => $allocation->fund_id, 'fund_code' => $allocation->fund?->code ?? '—', 'fund_name' => $allocation->fund?->name ?? 'Dana', 'allocated' => $this->amount($version->allocated_amount)]]);
                    if ($fundings->pluck('fund_id')->intersect($fundIds)->isEmpty()) {
                        return [];
                    }

                    return [
                        'program_id' => $allocation->program_id,
                        'program_code' => $allocation->program?->code ?? '—',
                        'program_name' => $allocation->program?->name ?? 'Program',
                        'budget' => $this->amount($version->allocated_amount),
                        'allocation' => $this->amount($version->allocated_amount),
                        'realization' => $realized->get($version->id, '0.00'),
                        'status' => $allocation->status === 'submitted' ? 'submitted' : $version->status,
                        'funding_sources' => $fundings->whereIn('fund_id', $fundIds)->values()->all(),
                    ];
                })->filter()->values();
        })->groupBy('program_id')->map(function (Collection $rows): array {
            return [
                'program_id' => $rows->first()['program_id'],
                'program_code' => $rows->first()['program_code'],
                'program_name' => $rows->first()['program_name'],
                'budget' => DecimalAmount::sum($rows->pluck('budget')),
                'allocation' => DecimalAmount::sum($rows->pluck('allocation')),
                'realization' => DecimalAmount::sum($rows->pluck('realization')),
                'status' => $rows->contains(fn (array $row): bool => $row['status'] === 'approved') ? 'approved' : $rows->first()['status'],
                'funding_sources' => $rows->pluck('funding_sources')->flatten(1)->all(),
            ];
        });
    }

    /** @return Collection<string, string> */
    private function postedRealizationByVersion(string $entityId, string $from, string $through): Collection
    {
        $cashOutTypes = $this->definitions->cashOutTypes();
        $placeholders = implode(', ', array_fill(0, max(1, count($cashOutTypes)), '?'));
        $effectiveType = 'COALESCE(original_transaction_type.code, transaction_type.code)';

        return $this->postedLedger->ledger($entityId, $through)
            ->join('financial_v2_fund_realizations as realization', 'realization.transaction_id', '=', 'financial_transaction.id')
            ->join('financial_v2_accounts as account', 'account.id', '=', 'ledger.account_id')
            ->where('ledger.accounting_date', '>=', $from)
            ->where('account.account_class', 'expense')
            ->where('realization.status', 'recorded')
            ->whereNotNull('realization.budget_allocation_version_id')
            ->whereRaw($effectiveType.' IN ('.$placeholders.')', $cashOutTypes)
            ->select('realization.budget_allocation_version_id')
            ->selectRaw('COALESCE(SUM(ledger.signed_amount), 0) as realized_amount')
            ->groupBy('realization.budget_allocation_version_id')
            ->get()
            ->mapWithKeys(fn (object $row): array => [$row->budget_allocation_version_id => $this->amount($row->realized_amount)]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $planned
     * @param  Collection<int, array<string, mixed>>  $actual
     * @return array<int, array<string, mixed>>
     */
    private function fundingSources(array $planned, Collection $actual): array
    {
        $plannedByFund = collect($planned)->groupBy('fund_id')->map(fn (Collection $rows): array => $rows->first() + ['allocated' => DecimalAmount::sum($rows->pluck('allocated'))]);
        $actualByFund = $actual->keyBy('fund_id');
        $fundIds = $plannedByFund->keys()->merge($actualByFund->keys())->unique()->sort();

        return $fundIds->map(function (string $fundId) use ($plannedByFund, $actualByFund): array {
            $plan = $plannedByFund->get($fundId, []);
            $actual = $actualByFund->get($fundId, []);

            return [
                'fund_id' => $fundId,
                'fund_code' => $plan['fund_code'] ?? $actual['fund_code'] ?? '—',
                'fund_name' => $plan['fund_name'] ?? $actual['fund_name'] ?? 'Dana',
                'allocated' => $this->amount($plan['allocated'] ?? '0.00'),
                'realized' => $this->amount($actual['actual_expense'] ?? '0.00'),
            ];
        })->all();
    }

    /** @param Collection<int, array<string, mixed>> $programs @return array<int, array<string, mixed>> */
    private function publicProgramRows(Collection $programs): array
    {
        return $programs
            ->filter(fn (array $program): bool => ! DecimalAmount::equals($program['actual_expense'], '0.00'))
            ->map(fn (array $program): array => [
                'program_id' => $program['program_id'],
                'code' => $program['code'],
                'name' => $program['name'],
                'actual_expense' => $program['actual_expense'],
                'funding_sources' => collect($program['funding_sources'])
                    ->filter(fn (array $source): bool => ! DecimalAmount::equals($source['realized'], '0.00'))
                    ->map(fn (array $source): array => [
                        'fund_id' => $source['fund_id'], 'fund_code' => $source['fund_code'], 'fund_name' => $source['fund_name'],
                    ])->values()->all(),
            ])->values()->all();
    }

    /**
     * Journal-level history intentionally avoids showing the two sides of a
     * double-entry as separate business transactions.
     *
     * @param  array<int, string>  $fundIds
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function transactionHistory(AccountingEntity $entity, string $from, string $through, array $fundIds, array $filters): array
    {
        if ($fundIds === []) {
            return [];
        }
        $cashIn = $this->definitions->cashInTypes();
        $cashOut = $this->definitions->cashOutTypes();
        $acceptedTypes = array_values(array_unique([...$cashIn, ...$cashOut]));
        $effectiveType = 'COALESCE(original_transaction_type.code, transaction_type.code)';
        $dimensions = DB::table('financial_v2_journal_lines as journal_line')
            ->leftJoin('financial_v2_funds as fund', 'fund.id', '=', 'journal_line.fund_id')
            ->leftJoin('financial_v2_programs as program', 'program.id', '=', 'journal_line.program_id')
            ->select('journal_line.journal_id')
            ->selectRaw("GROUP_CONCAT(DISTINCT fund.name ORDER BY fund.code SEPARATOR ', ') as fund_names")
            ->selectRaw("GROUP_CONCAT(DISTINCT program.name ORDER BY program.code SEPARATOR ', ') as program_names")
            ->groupBy('journal_line.journal_id');
        $query = $this->postedLedger->journals($entity->id, $through)
            ->leftJoinSub($dimensions, 'dimensions', fn ($join) => $join->on('dimensions.journal_id', '=', 'journal.id'))
            ->leftJoin('financial_v2_categories as category', 'category.id', '=', 'financial_transaction.category_id')
            ->where('journal.accounting_date', '>=', $from)
            ->whereRaw($effectiveType.' IN ('.implode(', ', array_fill(0, max(1, count($acceptedTypes)), '?')).')', $acceptedTypes)
            ->whereExists(function (Builder $scope) use ($fundIds): void {
                $scope->selectRaw('1')->from('financial_v2_journal_lines as scoped_line')
                    ->whereColumn('scoped_line.journal_id', 'journal.id')
                    ->whereIn('scoped_line.fund_id', $fundIds);
            })
            ->select([
                'journal.id as journal_id', 'journal.accounting_date', 'journal.posting_sequence', 'journal.description', 'journal.total_debit', 'journal.reversal_of_journal_id',
                'financial_transaction.id as transaction_id', 'transaction_type.name as transaction_type_name', 'transaction_type.code as transaction_type_code',
                'original_transaction_type.code as original_transaction_type_code', 'category.name as category_name', 'dimensions.fund_names', 'dimensions.program_names', 'voucher.voucher_number',
            ]);
        if ($fundId = $this->filterId($filters, 'fund_id')) {
            $query->whereExists(function (Builder $scope) use ($fundId): void {
                $scope->selectRaw('1')->from('financial_v2_journal_lines as scoped_fund_line')
                    ->whereColumn('scoped_fund_line.journal_id', 'journal.id')->where('scoped_fund_line.fund_id', $fundId);
            });
        }
        if ($programId = $this->filterId($filters, 'program_id')) {
            $query->whereExists(function (Builder $scope) use ($programId): void {
                $scope->selectRaw('1')->from('financial_v2_journal_lines as scoped_program_line')
                    ->whereColumn('scoped_program_line.journal_id', 'journal.id')->where('scoped_program_line.program_id', $programId);
            });
        }
        if ($categoryId = $this->filterId($filters, 'category_id')) {
            $query->where('financial_transaction.category_id', $categoryId);
        }
        if ($type = $filters['type'] ?? null) {
            $query->whereRaw($effectiveType.' = ?', [$type]);
        }

        return $query->orderByDesc('journal.accounting_date')->orderByDesc('journal.posting_sequence')->limit(100)->get()
            ->map(function (object $row) use ($cashIn): array {
                $type = $row->original_transaction_type_code ?: $row->transaction_type_code;
                $amount = $this->amount($row->total_debit);
                if ($row->reversal_of_journal_id) {
                    $amount = DecimalAmount::negate($amount);
                }

                return [
                    'journal_id' => $row->journal_id, 'transaction_id' => $row->transaction_id, 'date' => $row->accounting_date,
                    'description' => $row->description, 'fund' => $row->fund_names ?: '—', 'program' => $row->program_names ?: 'Non-Program',
                    'category' => $row->category_name ?: '—', 'type' => $type, 'status' => 'POSTED',
                    'in' => in_array($type, $cashIn, true) ? $amount : '0.00',
                    'out' => in_array($type, $cashIn, true) ? '0.00' : $amount,
                ];
            })->all();
    }

    /** @return array<int, string> */
    private function fundScope(AccountingEntity $entity, ?array $fundIds): array
    {
        if ($fundIds !== null) {
            return array_values(array_unique(array_filter($fundIds, 'is_string')));
        }

        return Fund::query()->where('accounting_entity_id', $entity->id)->pluck('id')->all();
    }

    /** @return array<string, mixed> */
    private function emptyPlan(): array
    {
        return ['program_id' => null, 'program_code' => '—', 'program_name' => 'Program', 'budget' => '0.00', 'allocation' => '0.00', 'realization' => '0.00', 'status' => null, 'funding_sources' => []];
    }

    private function programStatus(?string $planStatus, string $allocation, string $realization, string $actual, ?string $programStatus): string
    {
        if ($planStatus === null) {
            return $actual !== '0.00' ? 'POSTED' : strtoupper($programStatus ?? 'DRAFT');
        }
        if (DecimalAmount::equals($actual, '0.00')) {
            return match ($planStatus) {
                'draft' => 'DRAFT', 'submitted' => 'SUBMITTED', default => 'BELUM DIREALISASIKAN'
            };
        }
        if (DecimalAmount::compare($realization, $allocation) >= 0) {
            return 'COMPLETED';
        }

        return 'BERJALAN';
    }

    private function assertPeriod(string $from, string $through): void
    {
        if ($from > $through) {
            throw new InvalidArgumentException('Tanggal mulai laporan tidak boleh melewati tanggal akhir.');
        }
    }

    /** @param array<string, mixed> $filters */
    private function filterId(array $filters, string $key): ?string
    {
        $value = $filters[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function amount(mixed $value): string
    {
        return DecimalAmount::normalize((string) ($value ?? 0));
    }
}
