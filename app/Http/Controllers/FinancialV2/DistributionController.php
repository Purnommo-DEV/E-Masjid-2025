<?php

namespace App\Http\Controllers\FinancialV2;

use App\Domain\FinancialV2\DecimalAmount;
use App\Domain\FinancialV2\DistributionService;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\Counterparty;
use App\Models\FinancialV2\Distribution;
use App\Models\FinancialV2\DistributionItem;
use App\Models\FinancialV2\FundRealization;
use App\Models\FinancialV2\Program;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

final class DistributionController
{
    public function __construct(private readonly DistributionService $service) {}

    private function context(Request $request): AccountingEntity
    {
        $request->validate(['entity' => 'required|uuid']);

        return AccountingEntity::where('status', 'active')->findOrFail($request->input('entity'));
    }

    private function people(Request $request, string $entityId)
    {
        $filters = $request->validate(['q' => 'nullable|string|max:240', 'status' => 'nullable|in:active,inactive,archived', 'beneficiary_type' => 'nullable|in:YATIM,DHUAFA,YATIM_DHUAFA,BELUM_DITENTUKAN', 'rt' => 'nullable|string|max:10', 'rw' => 'nullable|string|max:10', 'coordinator' => 'nullable|string|max:160']);

        return Counterparty::forEntity($entityId)->where('party_type', 'beneficiary')
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($q) => $q->where('display_name', 'like', '%'.$term.'%')->orWhere('contact_reference', 'like', '%'.$term.'%')->orWhere('rt', 'like', '%'.$term.'%')->orWhere('rw', 'like', '%'.$term.'%')))
            ->when($filters['status'] ?? null, fn ($q, $value) => $q->where('status', $value))
            ->when($filters['beneficiary_type'] ?? null, fn ($q, $value) => $q->where('beneficiary_type', $value))
            ->when($filters['rt'] ?? null, fn ($q, $value) => $q->where('rt', $value))
            ->when($filters['rw'] ?? null, fn ($q, $value) => $q->where('rw', $value))
            ->when($filters['coordinator'] ?? null, fn ($q, $value) => $q->where('rt_coordinator_name', 'like', '%'.$value.'%'))
            ->orderByRaw("CASE WHEN NULLIF(TRIM(rw), '') IS NULL THEN 1 ELSE 0 END")
            ->orderByRaw("LOWER(TRIM(COALESCE(rw, ''))) ASC")
            ->orderByRaw("CASE WHEN NULLIF(TRIM(rt), '') IS NULL THEN 1 ELSE 0 END")
            ->orderByRaw("LOWER(TRIM(COALESCE(rt, ''))) ASC")
            ->orderByRaw('LOWER(display_name) ASC')->orderBy('id');
    }

    public function beneficiaries(Request $request)
    {
        if (! $request->filled('entity')) {
            return $this->chooseEntity();
        }
        $entity = $this->context($request);
        $request->validate(['per_page' => 'nullable|in:10,20,100,all']);
        $perPage = (string) $request->input('per_page', '20');
        $query = $this->people($request, $entity->id);
        $groupTotals = (clone $query)->reorder()
            ->selectRaw("LOWER(TRIM(COALESCE(rw, ''))) AS rw_group_key, LOWER(TRIM(COALESCE(rt, ''))) AS rt_group_key, COUNT(*) AS aggregate")
            ->groupByRaw("LOWER(TRIM(COALESCE(rw, ''))), LOWER(TRIM(COALESCE(rt, '')))")
            ->get()->mapWithKeys(fn ($row) => [$this->beneficiaryGroupKey($row->rw_group_key, $row->rt_group_key) => (int) $row->aggregate]);

        if ($perPage === 'all') {
            $allPeople = $query->get();
            $people = new LengthAwarePaginator($allPeople, $allPeople->count(), max(1, $allPeople->count()), 1, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);
        } else {
            $people = $query->paginate((int) $perPage)->withQueryString();
        }
        $people->getCollection()->each(function (Counterparty $person) use ($groupTotals): void {
            $person->setAttribute('matching_group_total', $groupTotals->get($this->beneficiaryGroupKey($person->rw, $person->rt), 0));
        });

        return view('masjid.mrj.admin.financial-v2.distributions.beneficiaries', compact('entity', 'people', 'perPage'));
    }

    public function destroyBeneficiaries(Request $request)
    {
        $entity = $this->context($request);
        $input = $request->validate([
            'beneficiary_ids' => 'required|array|min:1',
            'beneficiary_ids.*' => 'required|uuid|distinct',
        ]);
        $result = $this->service->deleteBeneficiaries($entity->id, $input['beneficiary_ids'], $request->user()->id);
        $response = back();
        if ($result['deleted'] > 0) {
            $response->with('success', number_format($result['deleted'], 0, ',', '.').' penerima berhasil dihapus.');
        }
        if ($result['protected'] > 0) {
            $response->with('warning', number_format($result['protected'], 0, ',', '.').' penerima tidak dapat dihapus karena sudah memiliki riwayat atau referensi Financial V2.');
        }

        return $response;
    }

    public function beneficiary(Request $request, string $beneficiary)
    {
        $entity = $this->context($request);
        $person = Counterparty::forEntity($entity->id)->where('party_type', 'beneficiary')->findOrFail($beneficiary);
        $history = DistributionItem::where('beneficiary_id', $person->id)->whereHas('distribution', fn ($q) => $q->forEntity($entity->id))
            ->with(['distribution.program', 'distribution.realization.transaction'])->latest()->paginate(20)->withQueryString();

        return view('masjid.mrj.admin.financial-v2.distributions.beneficiary', compact('entity', 'person', 'history'));
    }

    public function saveBeneficiary(Request $request, ?string $beneficiary = null)
    {
        $entity = $this->context($request);
        $person = $this->service->saveBeneficiary($entity->id, $request->all(), $beneficiary, $request->user()->id);

        return redirect()->route('financial-v2.beneficiaries.show', ['entity' => $entity->id, 'beneficiary' => $person->id])->with('success', 'Data penerima disimpan.');
    }

    public function index(Request $request)
    {
        if (! $request->filled('entity')) {
            return $this->chooseEntity();
        }
        $entity = $this->context($request);
        $request->validate(['program_id' => 'nullable|uuid', 'next_start' => 'nullable|date_format:Y-m-d', 'next_end' => 'nullable|date_format:Y-m-d']);
        $programs = Program::forEntity($entity->id)->orderBy('name')->get();
        $distributions = Distribution::forEntity($entity->id)->when($request->input('program_id'), fn ($q, $id) => $q->where('program_id', $id))
            ->with(['program', 'realization.transaction'])->withCount('items')->withSum('items', 'amount')->orderByDesc('starts_on')->paginate(20)->withQueryString();

        return view('masjid.mrj.admin.financial-v2.distributions.index', compact('entity', 'programs', 'distributions'));
    }

    public function store(Request $request)
    {
        $entity = $this->context($request);
        $distribution = $request->boolean('copy_previous')
            ? $this->service->copyPrevious($entity->id, $request->all(), $request->user()->id)
            : $this->service->create($entity->id, $request->all(), $request->user()->id);

        return redirect()->route('financial-v2.distributions.show', ['entity' => $entity->id, 'distribution' => $distribution->id])->with('success', 'Draft penyaluran dibuat.');
    }

    public function show(Request $request, string $distribution)
    {
        $entity = $this->context($request);
        $distribution = Distribution::forEntity($entity->id)->with(['program', 'realization.transaction.splits.fund', 'items', 'copiedFrom.items'])->findOrFail($distribution);
        $total = DecimalAmount::sum($distribution->items->pluck('amount'));
        $peopleQuery = $this->people($request, $entity->id);
        if (! $request->has('status')) {
            $peopleQuery->where('status', 'active');
        }
        $people = $peopleQuery->get();
        $realizations = FundRealization::forEntity($entity->id)->whereNotIn('status', ['cancelled', 'reversed'])
            ->whereHas('transaction', fn ($q) => $q->whereNotIn('status', ['cancelled', 'reversed'])->whereHas('splits', fn ($s) => $s->where('program_id', $distribution->program_id)))
            ->whereNotIn('id', Distribution::whereNotNull('realization_id')->select('realization_id'))
            ->with('transaction')->latest()->paginate(15, ['*'], 'realization_page')->withQueryString();
        $oldIds = $distribution->copiedFrom?->items->pluck('beneficiary_id') ?? collect();
        $newIds = $distribution->items->pluck('beneficiary_id');
        $continuity = ['previous' => $oldIds->count(), 'current' => $newIds->count(), 'added' => $newIds->diff($oldIds)->count(), 'removed' => $oldIds->diff($newIds)->count()];

        return view('masjid.mrj.admin.financial-v2.distributions.show', compact('entity', 'distribution', 'total', 'people', 'realizations', 'continuity'));
    }

    public function destroy(Request $request, string $distribution)
    {
        $entity = $this->context($request);
        $this->service->deleteDraft($entity->id, $distribution, $request->user()->id);

        return redirect()->route('financial-v2.distributions.index', ['entity' => $entity->id])
            ->with('success', 'Draft penyaluran berhasil dihapus.');
    }

    public function item(Request $request, string $distribution, ?string $item = null)
    {
        $entity = $this->context($request);
        if ($item === null && ! $request->isMethod('delete') && $request->has('items')) {
            $count = count($request->input('items', []));
            $this->service->addItemsToDraft($entity->id, $distribution, $request->all(), $request->user()->id);

            return back()->with('success', $count.' penerima ditambahkan ke draft.')
                ->with('distribution_batch_added', true);
        }
        $this->service->item($entity->id, $distribution, $request->all(), $item, $request->isMethod('delete'), $request->user()->id);

        return back()->with('success', 'Daftar penerima diperbarui.');
    }

    public function finalize(Request $request, string $distribution)
    {
        $entity = $this->context($request);
        $input = $request->validate(['realization_id' => 'required|uuid', 'revision' => 'required|integer|min:0']);
        $this->service->finalize($entity->id, $distribution, $input['realization_id'], (int) $input['revision'], $request->user()->id);

        return back()->with('success', 'Penyaluran difinalisasi. Status posting mengikuti Financial V2.');
    }

    private function chooseEntity()
    {
        return view('masjid.mrj.admin.financial-v2.distributions.entity', ['entity' => null, 'entities' => AccountingEntity::where('status', 'active')->orderBy('name')->get()]);
    }

    private function beneficiaryGroupKey(?string $rw, ?string $rt): string
    {
        return mb_strtolower(trim((string) $rw))."\x1F".mb_strtolower(trim((string) $rt));
    }
}
