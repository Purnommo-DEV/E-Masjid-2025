<?php

namespace App\Http\Controllers\FinancialV2;

use App\Domain\FinancialV2\DecimalAmount;
use App\Domain\FinancialV2\FinancialDomainException;
use App\Domain\FinancialV2\PlanningService;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\FundRealization;
use App\Models\FinancialV2\Planning;
use App\Models\FinancialV2\Program;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class PlanningController
{
    public function __construct(private readonly PlanningService $service) {}

    public function index(Request $request)
    {
        if (! $request->filled('entity')) {
            return view('masjid.mrj.admin.financial-v2.plannings.index', [
                'entity' => null,
                'entities' => AccountingEntity::query()->where('status', 'active')->orderBy('name')->get(),
                'plannings' => null,
                'programs' => collect(),
                'summary' => null,
            ]);
        }

        $entity = $this->entity($request);
        $filters = $request->validate([
            'status' => 'nullable|in:draft,approved,converted,cancelled',
            'program_id' => 'nullable|uuid',
            'period_start' => 'nullable|date_format:Y-m-d',
            'period_end' => 'nullable|date_format:Y-m-d',
            'q' => 'nullable|string|max:160',
        ]);
        $query = Planning::forEntity($entity->id)
            ->with(['program', 'fundings.fund', 'allocation'])
            ->when($filters['status'] ?? null, fn ($builder, $status) => $builder->where('status', $status))
            ->when($filters['program_id'] ?? null, fn ($builder, $program) => $builder->where('program_id', $program))
            ->when($filters['period_start'] ?? null, fn ($builder, $date) => $builder->where('period_end', '>=', $date))
            ->when($filters['period_end'] ?? null, fn ($builder, $date) => $builder->where('period_start', '<=', $date))
            ->when($filters['q'] ?? null, fn ($builder, $term) => $builder->where(fn ($search) => $search->where('planning_number', 'like', '%'.$term.'%')->orWhere('name', 'like', '%'.$term.'%')));

        $summaryQuery = Planning::forEntity($entity->id)->whereNotIn('status', ['cancelled']);
        $summary = [
            'active_count' => (clone $summaryQuery)->whereIn('status', ['draft', 'approved'])->count(),
            'total_amount' => DecimalAmount::normalize((clone $summaryQuery)->sum('total_amount')),
            'to_allocate' => DecimalAmount::normalize(Planning::forEntity($entity->id)->where('status', 'approved')->sum('total_amount')),
            'realized' => $this->realizedAmountForEntity($entity->id),
        ];

        return view('masjid.mrj.admin.financial-v2.plannings.index', [
            'entity' => $entity,
            'entities' => collect(),
            'plannings' => $query->orderByDesc('period_start')->orderByDesc('created_at')->paginate(20)->withQueryString(),
            'programs' => Program::forEntity($entity->id)->orderBy('name')->get(),
            'summary' => $summary,
        ]);
    }

    public function create(Request $request)
    {
        $entity = $this->entity($request);

        return $this->form($entity, new Planning(['period_start' => now()->toDateString(), 'period_end' => now()->toDateString(), 'status' => 'draft']), false);
    }

    public function store(Request $request)
    {
        $entity = $this->entity($request);
        $input = $this->validated($request);

        try {
            $planning = $this->service->createDraft($entity->id, $input, $input['fundings'], $request->user()->id);
        } catch (FinancialDomainException|InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['financial' => $this->message($exception)]);
        }

        return redirect()->route('financial-v2.plannings.show', ['entity' => $entity->id, 'planning' => $planning->id])->with('success', 'Draft Planning berhasil dibuat tanpa membuat transaksi akuntansi.');
    }

    public function show(Request $request, string $planning)
    {
        $entity = $this->entity($request);
        $planning = Planning::forEntity($entity->id)->with(['program', 'fundings.fund', 'allocation.versions.fundings.fund', 'approvedBy', 'convertedBy', 'cancelledBy'])->findOrFail($planning);
        $impacts = $planning->fundings->mapWithKeys(fn ($funding) => [
            $funding->fund_id => $this->service->calculateBalanceImpact($entity->id, $funding->fund_id, '0.00'),
        ]);
        $realized = $this->realizedAmount($planning);
        $realizationCount = $planning->allocation
            ? FundRealization::forEntity($entity->id)->whereHas('budgetAllocationVersion', fn ($version) => $version->where('budget_allocation_id', $planning->allocation->id))->count()
            : 0;

        return view('masjid.mrj.admin.financial-v2.plannings.show', compact('entity', 'planning', 'impacts', 'realized', 'realizationCount'));
    }

    public function edit(Request $request, string $planning)
    {
        $entity = $this->entity($request);
        $planning = Planning::forEntity($entity->id)->with('fundings')->findOrFail($planning);
        abort_unless($planning->status === 'draft', 409, 'Hanya Draft Planning yang dapat diubah.');

        return $this->form($entity, $planning, true);
    }

    public function update(Request $request, string $planning)
    {
        $entity = $this->entity($request);
        $record = Planning::forEntity($entity->id)->findOrFail($planning);
        $input = $this->validated($request);

        try {
            $record = $this->service->updateDraft($record->id, $input, $input['fundings'], $request->user()->id);
        } catch (FinancialDomainException|InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['financial' => $this->message($exception)]);
        }

        return redirect()->route('financial-v2.plannings.show', ['entity' => $entity->id, 'planning' => $record->id])->with('success', 'Draft Planning diperbarui.');
    }

    public function approve(Request $request, string $planning)
    {
        return $this->transition($request, $planning, fn ($record) => $this->service->approve($record->id, $request->user()->id), 'Planning disetujui. Saldo akuntansi tidak berubah.');
    }

    public function cancel(Request $request, string $planning)
    {
        $request->validate(['cancellation_reason' => 'required|string|max:1000']);

        return $this->transition($request, $planning, fn ($record) => $this->service->cancel($record->id, $request->input('cancellation_reason'), $request->user()->id), 'Planning dibatalkan dan tetap disimpan sebagai histori.');
    }

    public function convert(Request $request, string $planning)
    {
        return $this->transition($request, $planning, fn ($record) => $this->service->convertToAllocation($record->id, $request->user()->id), 'Planning dikonversi menjadi Draft Allocation. Belum ada transaksi akuntansi.');
    }

    public function preview(Request $request)
    {
        $entity = $this->entity($request);
        $data = $request->validate([
            'planning_id' => 'nullable|uuid',
            'period_start' => 'nullable|date_format:Y-m-d',
            'fundings' => 'required|array|min:1|max:20',
            'fundings.*.fund_id' => 'required|uuid|distinct',
            'fundings.*.amount' => ['required', 'regex:/^\d+(?:\.\d{1,2})?$/'],
        ]);
        if (filled($data['planning_id'] ?? null)) {
            Planning::forEntity($entity->id)->where('status', 'draft')->findOrFail($data['planning_id']);
        }

        try {
            $lines = collect($data['fundings'])->map(function (array $line) use ($entity, $data): array {
                $fund = Fund::forEntity($entity->id)->where('status', 'active')->find($line['fund_id']);
                if (! $fund) {
                    throw new FinancialDomainException('E-PLANNING-FUND', 'Dana aktif tidak ditemukan pada AccountingEntity ini.');
                }

                return ['fund' => ['id' => $fund->id, 'name' => $fund->name, 'code' => $fund->code]] + $this->service->calculateBalanceImpact($entity->id, $fund->id, $line['amount'], $data['planning_id'] ?? null, $data['period_start'] ?? null);
            })->values();
        } catch (FinancialDomainException|InvalidArgumentException $exception) {
            return response()->json(['ok' => false, 'message' => $this->message($exception)], 422);
        }

        return response()->json(['ok' => true, 'lines' => $lines]);
    }

    private function transition(Request $request, string $planningId, callable $action, string $success)
    {
        $entity = $this->entity($request);
        $planning = Planning::forEntity($entity->id)->findOrFail($planningId);
        try {
            $action($planning);
        } catch (FinancialDomainException|InvalidArgumentException $exception) {
            return back()->withErrors(['financial' => $this->message($exception)]);
        }

        return redirect()->route('financial-v2.plannings.show', ['entity' => $entity->id, 'planning' => $planning->id])->with('success', $success);
    }

    private function form(AccountingEntity $entity, Planning $planning, bool $editing)
    {
        return view('masjid.mrj.admin.financial-v2.plannings.form', [
            'entity' => $entity,
            'planning' => $planning,
            'editing' => $editing,
            'programs' => Program::forEntity($entity->id)->where('status', 'active')->orderBy('name')->get(),
            'funds' => Fund::forEntity($entity->id)->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    private function entity(Request $request): AccountingEntity
    {
        $request->validate(['entity' => 'required|uuid']);

        return AccountingEntity::query()->where('status', 'active')->findOrFail($request->input('entity'));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:240',
            'planning_number' => 'nullable|string|max:80|unique:financial_v2_plannings,planning_number',
            'period_start' => 'required|date_format:Y-m-d',
            'period_end' => 'required|date_format:Y-m-d|after_or_equal:period_start',
            'program_id' => 'nullable|uuid',
            'target_recipient_count' => 'nullable|integer|min:0|max:100000000',
            'amount_per_recipient' => ['nullable', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'total_amount' => ['required', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'notes' => 'nullable|string|max:4000',
            'fundings' => 'required|array|min:1|max:20',
            'fundings.*.fund_id' => 'required|uuid|distinct',
            'fundings.*.amount' => ['required', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'fundings.*.notes' => 'nullable|string|max:500',
        ]);
    }

    private function realizedAmount(Planning $planning): string
    {
        if (! $planning->allocation) {
            return '0.00';
        }

        $amounts = FundRealization::forEntity($planning->accounting_entity_id)
            ->whereHas('budgetAllocationVersion', fn ($version) => $version->where('budget_allocation_id', $planning->allocation->id))
            ->whereHas('transaction', fn ($transaction) => $transaction->where('status', 'posted'))
            ->with('transaction:id,gross_amount')
            ->get()
            ->pluck('transaction.gross_amount');

        return DecimalAmount::sum($amounts);
    }

    private function realizedAmountForEntity(string $entityId): string
    {
        $amounts = FundRealization::forEntity($entityId)
            ->whereHas('budgetAllocationVersion.allocation', fn ($allocation) => $allocation->whereNotNull('planning_id'))
            ->whereHas('transaction', fn ($transaction) => $transaction->where('status', 'posted'))
            ->with('transaction:id,gross_amount')
            ->get()
            ->pluck('transaction.gross_amount');

        return DecimalAmount::sum($amounts);
    }

    private function message(\Throwable $exception): string
    {
        return $exception instanceof FinancialDomainException
            ? '['.$exception->failureCode.'] '.$exception->getMessage()
            : $exception->getMessage();
    }
}
