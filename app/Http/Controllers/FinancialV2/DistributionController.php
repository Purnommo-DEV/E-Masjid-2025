<?php

namespace App\Http\Controllers\FinancialV2;

use App\Domain\FinancialV2\DecimalAmount;
use App\Domain\FinancialV2\BeneficiaryDuplicateService;
use App\Domain\FinancialV2\BeneficiaryImportService;
use App\Domain\FinancialV2\DistributionService;
use App\Domain\FinancialV2\DistributionRealizationLinkService;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\Counterparty;
use App\Models\FinancialV2\Distribution;
use App\Models\FinancialV2\DistributionItem;
use App\Models\FinancialV2\Program;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

final class DistributionController
{
    public function __construct(
        private readonly DistributionService $service,
        private readonly DistributionRealizationLinkService $realizationLinks,
        private readonly BeneficiaryImportService $beneficiaryImport,
        private readonly BeneficiaryDuplicateService $beneficiaryDuplicates,
    ) {}

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
        $people->setPath(route('financial-v2.beneficiaries.index'))
            ->appends($request->only(['entity', 'q', 'status', 'beneficiary_type', 'rt', 'rw', 'coordinator', 'per_page']));

        return view('masjid.mrj.admin.financial-v2.distributions.beneficiaries', compact('entity', 'people', 'perPage'));
    }

    public function exportBeneficiaries(Request $request)
    {
        $entity = $this->context($request);
        $people = $this->people($request, $entity->id)->get();
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Penerima ZISWAF');
        $sheet->fromArray(BeneficiaryImportService::HEADERS, null, 'A1');

        $statusLabels = ['active' => 'Aktif', 'inactive' => 'Tidak aktif', 'archived' => 'Arsip'];
        foreach ($people as $index => $person) {
            $row = $index + 2;
            $values = [
                $person->display_name,
                $person->contact_reference,
                $person->rt,
                $person->rw,
                $person->rt_coordinator_name,
                $person->beneficiary_type_label,
                $statusLabels[$person->status] ?? $person->status,
                $person->address,
                $person->beneficiary_notes,
            ];
            foreach ($values as $column => $value) {
                // Explicit strings prevent values beginning with =, +, -, or @ from becoming formulas.
                $sheet->setCellValueExplicit([$column + 1, $row], (string) ($value ?? ''), DataType::TYPE_STRING);
            }
        }

        $lastRow = max(1, $people->count() + 1);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:I{$lastRow}");
        $sheet->getStyle('A1:I1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:I1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF047857');
        $sheet->getStyle("A1:I{$lastRow}")->getAlignment()->setVertical('top')->setWrapText(true);
        foreach (['A' => 30, 'B' => 20, 'C' => 8, 'D' => 8, 'E' => 24, 'F' => 20, 'G' => 14, 'H' => 40, 'I' => 40] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $filename = 'penerima-ziswaf-'.strtolower($entity->code).'-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
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

    public function beneficiaryNameDuplicates(Request $request)
    {
        $entity = $this->context($request);
        $input = $request->validate(['name' => 'required|string|max:240']);

        return response()->json($this->beneficiaryDuplicates->suggestions($entity->id, $input['name']));
    }

    public function beneficiaryImportTemplate(Request $request)
    {
        $entity = $this->context($request);
        abort_unless($entity->code === 'MRJ-ACTUAL', 404);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Penerima');
        $sheet->fromArray(BeneficiaryImportService::HEADERS, null, 'A1');
        $sheet->fromArray([
            ['[CONTOH] Siti Aminah', '081234567890', '03', '06', 'Pak Ahmad', 'Dhuafa', 'Aktif', 'Jl. Contoh No. 1', 'CONTOH lengkap — jangan dihapus penandanya.'],
            ['[CONTOH] Budi', '', '', '06', '', 'Belum ditentukan', 'Aktif', '', 'CONTOH sebagian kosong — jangan dihapus penandanya.'],
        ], null, 'A2');
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        $sheet->getStyle('A2:I3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFF2CC');
        foreach (range('A', 'I') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        $sheet->getStyle('B2:B3')->getNumberFormat()->setFormatCode('@');

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'template-penerima-financial-v2.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function previewBeneficiaryImport(Request $request)
    {
        $entity = $this->context($request);
        abort_unless($entity->code === 'MRJ-ACTUAL', 404);
        $request->validate(['import_file' => ['required', 'file', 'mimes:xls,xlsx', 'max:10240']]);
        $file = $request->file('import_file');
        $extension = mb_strtolower($file->getClientOriginalExtension());
        $relativePath = $file->storeAs('financial-v2/beneficiary-imports', (string) Str::uuid().'.'.$extension, 'local');
        if (! is_string($relativePath)) {
            throw ValidationException::withMessages(['import_file' => 'File import tidak dapat disimpan sementara.']);
        }

        try {
            $preview = $this->beneficiaryImport->preview($entity->id, Storage::disk('local')->path($relativePath));
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($relativePath);
            if ($exception instanceof ValidationException) {
                throw $exception;
            }
            report($exception);
            throw ValidationException::withMessages(['import_file' => 'File Excel tidak dapat dibaca. Gunakan template resmi XLS/XLSX.']);
        }

        $token = Crypt::encryptString(json_encode([
            'entity_id' => $entity->id,
            'path' => $relativePath,
            'sha256' => hash_file('sha256', Storage::disk('local')->path($relativePath)),
            'expires_at' => now()->addMinutes(30)->timestamp,
        ], JSON_THROW_ON_ERROR));
        $view = $this->beneficiaries($request);

        return $view->with(['importPreview' => $preview, 'importToken' => $token]);
    }

    public function importBeneficiaries(Request $request)
    {
        $entity = $this->context($request);
        abort_unless($entity->code === 'MRJ-ACTUAL', 404);
        $input = $request->validate(['import_token' => ['required', 'string']]);
        try {
            $payload = json_decode(Crypt::decryptString($input['import_token']), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            throw ValidationException::withMessages(['import_file' => 'Token preview import tidak valid. Silakan preview ulang file.']);
        }
        $relativePath = (string) ($payload['path'] ?? '');
        $path = $relativePath !== '' ? Storage::disk('local')->path($relativePath) : '';
        $valid = ($payload['entity_id'] ?? null) === $entity->id
            && (int) ($payload['expires_at'] ?? 0) >= now()->timestamp
            && $relativePath !== ''
            && str_starts_with($relativePath, 'financial-v2/beneficiary-imports/')
            && Storage::disk('local')->exists($relativePath)
            && hash_equals((string) ($payload['sha256'] ?? ''), hash_file('sha256', $path));
        if (! $valid) {
            throw ValidationException::withMessages(['import_file' => 'Preview import sudah kedaluwarsa atau file berubah. Silakan preview ulang.']);
        }

        try {
            $result = $this->beneficiaryImport->import($entity->id, $path, $request->user()?->id);
        } finally {
            Storage::disk('local')->delete($relativePath);
        }

        return redirect()->route('financial-v2.beneficiaries.index', ['entity' => $entity->id])
            ->with('success', "{$result['created']} penerima baru diimpor; {$result['skipped']} baris duplikat/tidak valid dilewati.");
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
        $distribution = Distribution::forEntity($entity->id)->with([
            'program', 'items.beneficiary', 'copiedFrom.items',
            'realization.transaction.splits.fund',
            'realization.budgetAllocationVersion.allocation.program',
            'realization.budgetAllocationVersion.fundings.fund',
        ])->findOrFail($distribution);
        $total = DecimalAmount::sum($distribution->items->pluck('amount'));
        $peopleQuery = $this->people($request, $entity->id);
        if (! $request->has('status')) {
            $peopleQuery->where('status', 'active');
        }
        $people = $peopleQuery->get();
        $realizations = $distribution->realization_id ? collect() : $this->realizationLinks->candidates($distribution);
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
        $this->realizationLinks->link($entity->id, $distribution, $input['realization_id'], (int) $input['revision'], $request->user()->id);

        return redirect()->route('financial-v2.distributions.show', ['distribution' => $distribution, 'entity' => $entity->id])
            ->with('success', 'Penyaluran berhasil ditautkan ke Realisasi yang sudah ada.');
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
