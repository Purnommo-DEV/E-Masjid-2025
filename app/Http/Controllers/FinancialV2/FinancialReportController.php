<?php

namespace App\Http\Controllers\FinancialV2;

use App\Domain\FinancialV2\Reporting\FinancialReportService;
use App\Models\FinancialV2\AccountingEntity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Presentation adapter for the Financial V2 read model.
 *
 * The controller does not query or mutate financial facts. All report content
 * comes from FinancialReportService, which reads only Posted V2 ledger/journal
 * sources. The JSON endpoint is intentionally export/AJAX-ready but does not
 * create a report snapshot or write any operational data.
 */
final class FinancialReportController
{
    public function __construct(private readonly FinancialReportService $reports) {}

    public function index(Request $request)
    {
        $input = $this->validatedInput($request);
        $context = $this->context($input['entity'] ?? null);
        $report = $input['report'] ?? 'summary';
        [$from, $through] = $this->dateRange($input);
        $data = $context['entity']
            ? $this->reports->report($report, $context['entity']->id, $from, $through, $input)
            : $this->emptyReport($report, $from, $through);

        return view('masjid.mrj.admin.financial-v2.reports.index', [
            'entities' => $context['entities'],
            'entity' => $context['entity'],
            'reportDefinitions' => FinancialReportService::REPORTS,
            'report' => $report,
            'filters' => array_merge($input, ['from' => $from, 'through' => $through]),
            'filterOptions' => $context['entity'] ? $this->reports->filterOptions($context['entity']->id) : ['financial_accounts' => [], 'funds' => [], 'programs' => []],
            'reportData' => $data,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $input = $this->validatedInput($request);
        $context = $this->context($input['entity'] ?? null);
        abort_unless($context['entity'], 404, 'Entitas Financial V2 aktif belum dipilih atau belum tersedia.');
        [$from, $through] = $this->dateRange($input);
        $report = $input['report'] ?? 'summary';

        return response()->json($this->reports->report($report, $context['entity']->id, $from, $through, $input));
    }

    /** @return array<string, mixed> */
    private function validatedInput(Request $request): array
    {
        return $request->validate([
            'entity' => ['nullable', 'uuid'],
            'report' => ['nullable', Rule::in(array_keys(FinancialReportService::REPORTS))],
            'from' => ['nullable', 'date'],
            'through' => ['nullable', 'date'],
            'financial_account_id' => ['nullable', 'uuid'],
            'fund_id' => ['nullable', 'uuid'],
            'program_id' => ['nullable', 'uuid'],
            'definition' => ['nullable', 'string', 'max:80'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
            'cursor_posting_sequence' => ['nullable', 'integer', 'min:1'],
            'cursor_line_no' => ['nullable', 'integer', 'min:1'],
        ]);
    }

    /** @return array{0: string, 1: string} */
    private function dateRange(array $input): array
    {
        $from = $input['from'] ?? now()->startOfMonth()->toDateString();
        $through = $input['through'] ?? now()->toDateString();
        abort_if($from > $through, 422, 'Tanggal awal laporan tidak boleh setelah tanggal akhir.');

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
    private function emptyReport(string $report, string $from, string $through): array
    {
        return [
            'report' => $report,
            'report_label' => FinancialReportService::REPORTS[$report],
            'period' => ['from_accounting_date' => $from, 'through_accounting_date' => $through],
            'as_of_posting_sequence' => 0,
            'source' => 'financial_v2_posted_general_ledger',
            'data' => ['has_data' => false, 'rows' => [], 'message' => 'Pilih satu Entitas Financial V2 aktif untuk menampilkan laporan.'],
        ];
    }
}
