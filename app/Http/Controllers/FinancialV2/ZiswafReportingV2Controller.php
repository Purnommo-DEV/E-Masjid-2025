<?php

namespace App\Http\Controllers\FinancialV2;

use App\Domain\FinancialV2\Reporting\FinancialReportService;
use App\Domain\FinancialV2\Reporting\ZiswafReportingV2Service;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\Category;
use App\Models\FinancialV2\Program;
use App\Models\FinancialV2\TransactionType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Internal, read-only adapter for the ZISWAF Reporting V2 presentation. */
final class ZiswafReportingV2Controller
{
    public function __construct(private readonly ZiswafReportingV2Service $reports) {}

    public function index(Request $request)
    {
        $input = $this->input($request);
        $context = $this->context($input['entity'] ?? null);
        [$from, $through] = $this->period($input);
        $report = $context['entity']
            ? $this->reports->report($context['entity'], $from, $through, null, $input)
            : $this->emptyReport($from, $through);

        return view('masjid.mrj.admin.financial-v2.ziswaf-report-v2.index', [
            'entities' => $context['entities'],
            'entity' => $context['entity'],
            'filters' => $input + ['from' => $from, 'through' => $through],
            'filterOptions' => $context['entity'] ? $this->filterOptions($context['entity']->id) : ['funds' => [], 'programs' => [], 'categories' => [], 'types' => []],
            'report' => $report,
        ]);
    }

    public function program(Request $request, Program $program)
    {
        $input = $this->input($request);
        $context = $this->context($input['entity'] ?? $program->accounting_entity_id);
        abort_unless($context['entity']?->id === $program->accounting_entity_id, 404);
        [$from, $through] = $this->period($input);

        return view('masjid.mrj.admin.financial-v2.ziswaf-report-v2.program', [
            'entities' => $context['entities'],
            'entity' => $context['entity'],
            'filters' => $input + ['from' => $from, 'through' => $through],
            'report' => $this->reports->programDetail($context['entity'], $program, $from, $through),
        ]);
    }

    /** @return array<string, mixed> */
    private function input(Request $request): array
    {
        return $request->validate([
            'entity' => ['nullable', 'uuid'],
            'from' => ['nullable', 'date'],
            'through' => ['nullable', 'date', 'after_or_equal:from'],
            'fund_id' => ['nullable', 'uuid'],
            'program_id' => ['nullable', 'uuid'],
            'category_id' => ['nullable', 'uuid'],
            'type' => ['nullable', 'string', 'max:40'],
            'status' => ['nullable', Rule::in(['posted'])],
        ]);
    }

    /** @param array<string, mixed> $input @return array{0: string, 1: string} */
    private function period(array $input): array
    {
        $from = $input['from'] ?? now()->startOfMonth()->toDateString();
        $through = $input['through'] ?? now()->toDateString();
        abort_if($from > $through, 422, 'Tanggal mulai laporan tidak boleh melewati tanggal akhir.');

        return [$from, $through];
    }

    /** @return array{entities: \Illuminate\Support\Collection<int, AccountingEntity>, entity: ?AccountingEntity} */
    private function context(?string $requestedId): array
    {
        $entities = AccountingEntity::query()->where('status', 'active')->orderBy('name')->get();
        $entity = $requestedId ? $entities->firstWhere('id', $requestedId) : ($entities->count() === 1 ? $entities->first() : null);

        return compact('entities', 'entity');
    }

    /** @return array<string, mixed> */
    private function emptyReport(string $from, string $through): array
    {
        return [
            'entity' => null,
            'period' => compact('from', 'through'),
            'source' => 'financial_v2_posted_general_ledger',
            'as_of_posting_sequence' => 0,
            'filters' => ['fund_id' => null, 'program_id' => null],
            'funds' => [], 'income_by_fund' => [], 'programs' => [], 'non_program_expenses' => [], 'transactions' => [],
            'summary' => ['opening_balance' => '0.00', 'receipts' => '0.00', 'expenses' => '0.00', 'closing_balance' => '0.00', 'planned_usage' => '0.00'],
            'diagnostics' => ['fund_balance_reconciled' => true, 'actual_expense_reconciled' => true, 'plan_excluded_from_actual' => true, 'message' => 'Pilih Entitas Financial V2 aktif untuk menampilkan laporan.'],
        ];
    }

    /** @return array<string, array<int, array<string, string>>> */
    private function filterOptions(string $entityId): array
    {
        $options = app(FinancialReportService::class)->filterOptions($entityId);
        $options['categories'] = Category::query()->where('accounting_entity_id', $entityId)->orderBy('code')->get(['id', 'code', 'name'])
            ->map(fn (Category $category): array => ['id' => $category->id, 'label' => $category->code.' — '.$category->name])->all();
        $options['types'] = TransactionType::query()->where('accounting_entity_id', $entityId)->orderBy('code')->get(['code', 'name'])
            ->map(fn (TransactionType $type): array => ['id' => $type->code, 'label' => $type->code.' — '.$type->name])->all();

        return $options;
    }
}
