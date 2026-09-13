<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SantunanParticipation;
use App\Models\SantunanPerson;
use App\Services\SantunanIdentityMatcher;
use Carbon\Carbon;
use Edgaras\StrSim\JaroWinkler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class PendaftaranYatimDhuafaController extends Controller
{
    public function indexPublik(Request $request)
    {
        $selectedYear = $this->selectedYear($request);
        $yearOptions = $this->yearOptions($selectedYear, true);
        $filterOptions = $this->filterOptionValues($selectedYear);
        $sumberList = $filterOptions['sources'];
        $rtList = $filterOptions['rts'];
        $rwList = $filterOptions['rws'];
        $categoryList = $filterOptions['categories'];
        $registrationOpen = (bool) config('santunan.registration_open', true);

        return view('masjid.'.masjid().'.guest.pendaftaran.show', compact(
            'selectedYear', 'yearOptions', 'sumberList', 'rtList', 'rwList', 'categoryList', 'registrationOpen'
        ));
    }

    public function index(Request $request)
    {
        $view = config('santunan.registration_open', true) ? 'index' : 'indexClose';
        $selectedYear = $this->selectedYear($request);
        $yearOptions = $this->yearOptions($selectedYear, true);

        return view('masjid.'.masjid().'.guest.pendaftaran.'.$view, compact('selectedYear', 'yearOptions'));
    }

    public function filterOptions(Request $request): JsonResponse
    {
        $validated = $request->validate(['tahun' => ['required', 'integer', 'min:2000', 'max:2100']]);

        return response()->json(['success' => true] + $this->filterOptionValues((int) $validated['tahun']));
    }

    public function yearCandidates(Request $request): JsonResponse
    {
        $validated = $request->validate(['tahun' => ['required', 'integer', 'min:2000', 'max:2100']]);
        $records = $this->participationQuery()
            ->forYear((int) $validated['tahun'])
            ->orderBy('santunan_persons.nama_lengkap')
            ->get()
            ->map(fn (SantunanParticipation $row) => [
                'id' => $row->id,
                'person_id' => $row->person_id,
                'nama_lengkap' => $row->nama_lengkap,
                'kategori' => $row->kategori,
                'sumber_informasi' => $row->sumber_informasi,
                'rt' => $row->rt,
                'rw' => $row->rw,
            ]);

        return response()->json(['success' => true, 'records' => $records]);
    }

    public function startYear(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'target_year' => ['required', 'integer', 'min:2000', 'max:2100', 'gt:source_year'],
            'participation_ids' => ['required', 'array', 'min:1'],
            'participation_ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $sourceYear = (int) $validated['source_year'];
        $targetYear = (int) $validated['target_year'];
        $ids = collect($validated['participation_ids'])->map(fn ($id) => (int) $id)->values();

        $result = DB::transaction(function () use ($sourceYear, $targetYear, $ids): array {
            $records = SantunanParticipation::query()
                ->where('tahun_program', $sourceYear)
                ->whereIn('id', $ids)
                ->lockForUpdate()
                ->get();

            if ($records->count() !== $ids->count()) {
                throw ValidationException::withMessages([
                    'participation_ids' => 'Pilihan peserta tidak valid atau bukan berasal dari tahun sumber.',
                ]);
            }

            $created = 0;
            $alreadyExists = 0;

            foreach ($records as $record) {
                if (SantunanParticipation::query()->where('person_id', $record->person_id)->where('tahun_program', $targetYear)->exists()) {
                    $alreadyExists++;

                    continue;
                }

                $copy = $record->replicate(['legacy_registration_id']);
                $copy->tahun_program = $targetYear;
                $copy->legacy_registration_id = null;
                $copy->status = 'baru';
                $copy->save();
                $created++;
            }

            return compact('created', 'alreadyExists');
        });

        return response()->json([
            'success' => true,
            'message' => "{$result['created']} participation {$targetYear} dibuat tanpa mengubah {$sourceYear}.",
        ] + $result);
    }

    public function dataTable(Request $request)
    {
        $filters = $this->validatedPublicFilters($request);
        $query = $this->applyPublicFilters($this->participationQuery(), $filters);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('kategori_display', fn ($row) => $this->categoryBadge($row->kategori))
            ->addColumn('jenis_kelamin_display', fn ($row) => $row->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan')
            ->addColumn('tanggal_lahir_formatted', fn ($row) => $row->tanggal_lahir ? Carbon::parse($row->tanggal_lahir)->format('d-m-Y') : '-')
            ->addColumn('umur_display', fn ($row) => $this->formatUmur($row))
            ->editColumn('rt', fn ($row) => $row->rt ?: '-')
            ->editColumn('rw', fn ($row) => $row->rw ?: '-')
            ->editColumn('nama_rt', fn ($row) => $row->nama_rt ?: '-')
            ->editColumn('no_wa', fn ($row) => $row->no_wa ?: '-')
            ->editColumn('nama_panggilan', fn ($row) => $row->nama_panggilan ?: '-')
            ->editColumn('pekerjaan_orang_tua', fn ($row) => $row->pekerjaan_orang_tua ?: '-')
            ->editColumn('catatan_tambahan', fn ($row) => $row->catatan_tambahan ?: '-')
            ->rawColumns(['kategori_display'])
            ->make(true);
    }

    public function groupedData(Request $request): JsonResponse
    {
        $filters = $this->validatedPublicFilters($request);
        $records = $this->applyPublicFilters($this->participationQuery(), $filters)
            ->orderByRaw("CASE WHEN santunan_participations.sumber_informasi IS NULL OR santunan_participations.sumber_informasi = '' THEN 1 ELSE 0 END")
            ->orderBy('santunan_participations.sumber_informasi')
            ->orderByRaw("CASE santunan_participations.kategori WHEN 'dhuafa' THEN 1 WHEN 'yatim_dhuafa' THEN 2 ELSE 3 END")
            ->orderByRaw("CASE WHEN santunan_participations.rw IS NULL OR santunan_participations.rw = '' THEN 1 ELSE 0 END")
            ->orderBy('santunan_participations.rw')
            ->orderByRaw("CASE WHEN santunan_participations.rt IS NULL OR santunan_participations.rt = '' THEN 1 ELSE 0 END")
            ->orderBy('santunan_participations.rt')
            ->orderByRaw("CASE WHEN santunan_participations.nama_rt IS NULL OR santunan_participations.nama_rt = '' THEN 1 ELSE 0 END")
            ->orderBy('santunan_participations.nama_rt')
            ->orderBy('santunan_persons.nama_lengkap')
            ->get();

        return response()->json([
            'success' => true,
            'tahun' => $filters['tahun'],
            'total' => $records->count(),
            'groups' => $this->buildGroupedData($records),
        ]);
    }

    public function store(Request $request, SantunanIdentityMatcher $matcher): JsonResponse
    {
        if (! config('santunan.registration_open', true)) {
            return response()->json(['success' => false, 'message' => 'Pendaftaran Santunan Ramadhan sedang ditutup.'], 422);
        }

        $data = Validator::make($request->all(), $this->registrationRules())->validate();
        $this->normalizeRegionFields($data);
        $this->normalizeAge($data);
        $match = $matcher->match($data);

        if ($match['status'] === 'ambiguous') {
            return response()->json([
                'success' => false,
                'message' => 'Identitas memiliki kandidat yang perlu diperiksa. Data tidak disimpan.',
                'errors' => ['identity' => ['Identitas ambigu dan tidak boleh digabung otomatis.']],
                'review' => $match['candidates'],
            ], 422);
        }

        $person = DB::transaction(function () use ($data, $match, $request): SantunanPerson {
            $person = $match['person'] ?: SantunanPerson::query()->create($this->personData($data));

            if (SantunanParticipation::query()->where('person_id', $person->id)->where('tahun_program', $data['tahun_program'])->exists()) {
                throw ValidationException::withMessages([
                    'identity' => "Orang ini sudah terdaftar pada tahun {$data['tahun_program']}.",
                ]);
            }

            SantunanParticipation::query()->create($this->participationData($data, $person->id, $request->ip()));

            return $person;
        });

        return response()->json([
            'success' => true,
            'message' => 'Pendaftaran berhasil dikirim. Terima kasih!',
            'person_id' => $person->id,
            'identity_reused' => $match['status'] === 'matched',
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $participation = SantunanParticipation::query()->with('person')->findOrFail($id);
        $data = Validator::make($request->all(), $this->registrationRules($participation->tahun_program))->validate();
        $this->normalizeRegionFields($data);
        $this->normalizeAge($data);

        DB::transaction(function () use ($participation, $data): void {
            $participation->person->update($this->personData($data));
            $participation->update($this->participationData($data, $participation->person_id, $participation->ip_address));
        });

        return response()->json(['success' => true, 'message' => "Data {$participation->tahun_program} berhasil diperbarui"]);
    }

    public function edit(int $id): JsonResponse
    {
        return response()->json($this->participationQuery()->where('santunan_participations.id', $id)->firstOrFail());
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate(['tahun_program' => ['required', 'integer', 'min:2000', 'max:2100']]);
        $participation = SantunanParticipation::query()->findOrFail($id);

        if ((int) $participation->tahun_program !== (int) $validated['tahun_program']) {
            throw ValidationException::withMessages(['tahun_program' => 'Participation bukan berasal dari tahun yang sedang dipilih.']);
        }

        $participation->delete();

        return response()->json(['success' => true, 'message' => 'Participation tahun terpilih berhasil dihapus. Master person dan histori tahun lain tetap ada.']);
    }

    public function scanDuplikat(Request $request): JsonResponse
    {
        $validated = $request->validate(['tahun' => ['required', 'integer', 'min:2000', 'max:2100']]);
        $tahun = (int) $validated['tahun'];
        $records = $this->participationQuery()->forYear($tahun)->get()->values();
        $pairs = collect();

        foreach ($records as $leftIndex => $left) {
            foreach ($records->slice($leftIndex + 1) as $right) {
                $leftKey = strtolower(trim($left->nama_lengkap.' '.$left->nama_orang_tua));
                $rightKey = strtolower(trim($right->nama_lengkap.' '.$right->nama_orang_tua));

                if (min(strlen($leftKey), strlen($rightKey)) < 8) {
                    continue;
                }

                $similarity = JaroWinkler::similarity($leftKey, $rightKey) * 100;
                if ($similarity < 80) {
                    continue;
                }

                $pairs->push([
                    'id_a' => $left->id, 'nama_a' => $left->nama_lengkap, 'ortu_a' => $left->nama_orang_tua ?: '-',
                    'umur_a' => $this->formatUmur($left), 'tahun_a' => $left->tahun_program,
                    'alamat_a' => str($left->alamat ?: '-')->limit(45)->toString(),
                    'id_b' => $right->id, 'nama_b' => $right->nama_lengkap, 'ortu_b' => $right->nama_orang_tua ?: '-',
                    'umur_b' => $this->formatUmur($right), 'tahun_b' => $right->tahun_program,
                    'alamat_b' => str($right->alamat ?: '-')->limit(45)->toString(),
                    'similarity' => round($similarity, 1),
                ]);
            }
        }

        $pairs = $pairs->sortByDesc('similarity')->take(30)->values();

        return response()->json([
            'success' => true, 'pairs' => $pairs, 'total' => $pairs->count(), 'tahun' => $tahun,
            'message' => $pairs->isEmpty() ? "Tidak ditemukan pasangan dengan kemiripan tinggi di tahun {$tahun}" : null,
        ]);
    }

    private function participationQuery(): Builder
    {
        return SantunanParticipation::query()
            ->join('santunan_persons', 'santunan_persons.id', '=', 'santunan_participations.person_id')
            ->select([
                'santunan_participations.*', 'santunan_persons.nama_lengkap', 'santunan_persons.nama_panggilan',
                'santunan_persons.tanggal_lahir', 'santunan_persons.jenis_kelamin',
            ]);
    }

    private function validatedPublicFilters(Request $request): array
    {
        $filters = $request->validate([
            'tahun' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'sumber_informasi' => ['nullable', 'string', 'max:255'],
            'kategori' => ['nullable', Rule::in(SantunanParticipation::CATEGORIES)],
            'rt' => ['nullable', 'string', 'max:5'],
            'rw' => ['nullable', 'string', 'max:5'],
            'search' => ['nullable', 'string', 'max:150'],
            'umur_value' => ['nullable', 'integer', 'min:0'],
            'umur_satuan' => ['nullable', Rule::in(['tahun', 'bulan', 'hari'])],
            'jenis_kelamin' => ['nullable', Rule::in(['L', 'P'])],
        ]);
        $filters['tahun'] = (int) ($filters['tahun'] ?? $this->activeYear());

        return $filters;
    }

    private function applyPublicFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->forYear((int) $filters['tahun'])
            ->when($filters['sumber_informasi'] ?? null, fn (Builder $query, string $value) => $query->where('santunan_participations.sumber_informasi', $value))
            ->when($filters['kategori'] ?? null, fn (Builder $query, string $value) => $query->where('santunan_participations.kategori', $value))
            ->when($filters['rt'] ?? null, fn (Builder $query, string $value) => $query->where('santunan_participations.rt', $value))
            ->when($filters['rw'] ?? null, fn (Builder $query, string $value) => $query->where('santunan_participations.rw', $value))
            ->when($filters['umur_value'] ?? null, function (Builder $query, int $value) use ($filters): void {
                $query->where('santunan_participations.umur', $value);
                if ($filters['umur_satuan'] ?? null) {
                    $query->where('santunan_participations.umur_satuan', $filters['umur_satuan']);
                }
            })
            ->when($filters['jenis_kelamin'] ?? null, fn (Builder $query, string $value) => $query->where('santunan_persons.jenis_kelamin', $value))
            ->when($filters['search'] ?? null, function (Builder $query, string $value): void {
                $search = '%'.$value.'%';
                $query->where(fn (Builder $nameQuery) => $nameQuery
                    ->where('santunan_persons.nama_lengkap', 'like', $search)
                    ->orWhere('santunan_persons.nama_panggilan', 'like', $search));
            });
    }

    private function filterOptionValues(int $year): array
    {
        $query = SantunanParticipation::query()->forYear($year);

        return [
            'sources' => (clone $query)->whereNotNull('sumber_informasi')->where('sumber_informasi', '!=', '')->distinct()->orderBy('sumber_informasi')->pluck('sumber_informasi')->all(),
            'categories' => (clone $query)->distinct()->pluck('kategori')->sortBy(fn ($value) => array_search($value, SantunanParticipation::CATEGORIES, true))->values()->all(),
            'rws' => (clone $query)->whereNotNull('rw')->where('rw', '!=', '')->distinct()->orderBy('rw')->pluck('rw')->all(),
            'rts' => (clone $query)->whereNotNull('rt')->where('rt', '!=', '')->distinct()->orderBy('rt')->pluck('rt')->all(),
        ];
    }

    private function selectedYear(Request $request): int
    {
        $year = $request->integer('tahun');

        return $year >= 2000 && $year <= 2100 ? $year : $this->activeYear();
    }

    private function activeYear(): int
    {
        return max((int) now()->year, (int) (SantunanParticipation::query()->max('tahun_program') ?: now()->year));
    }

    private function yearOptions(int $selectedYear, bool $includeNextYear = false): array
    {
        $years = SantunanParticipation::query()->distinct()->pluck('tahun_program')->map(fn ($year) => (int) $year);
        $years->push((int) now()->year, $selectedYear);
        if ($includeNextYear) {
            $years->push($this->activeYear() + 1);
        }

        return $years->unique()->sortDesc()->values()->all();
    }

    private function registrationRules(?int $fixedYear = null): array
    {
        $yearRules = ['required', 'integer', 'min:2000', 'max:2100'];
        if ($fixedYear !== null) {
            $yearRules[] = Rule::in([$fixedYear]);
        }

        return [
            'person_id' => ['nullable', 'uuid', 'exists:santunan_persons,id'],
            'tahun_program' => $yearRules,
            'kategori' => ['required', Rule::in(SantunanParticipation::CATEGORIES)],
            'nama_lengkap' => ['required', 'string', 'min:3', 'max:150'],
            'nama_panggilan' => ['nullable', 'string', 'max:60'],
            'sumber_informasi' => ['required', 'string', 'min:2', 'max:255'],
            'tanggal_lahir' => ['nullable', 'date'],
            'umur' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'umur_satuan' => ['nullable', Rule::in(['tahun', 'bulan', 'hari'])],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'alamat' => ['required', 'string', 'min:2', 'max:255'],
            'rt' => ['nullable', 'string', 'max:5'],
            'rw' => ['nullable', 'string', 'max:5'],
            'nama_rt' => ['nullable', 'string', 'max:150'],
            'no_wa' => ['nullable', 'regex:/^08[0-9]{8,12}$/'],
            'nama_orang_tua' => ['required', 'string', 'min:2', 'max:255'],
            'pekerjaan_orang_tua' => ['nullable', 'string', 'max:255'],
            'catatan_tambahan' => ['nullable', 'string', 'max:500'],
        ];
    }

    private function normalizeAge(array &$data): void
    {
        if (filled($data['tanggal_lahir'] ?? null)) {
            $birth = Carbon::parse($data['tanggal_lahir']);
            if ($birth->isFuture()) {
                throw ValidationException::withMessages(['tanggal_lahir' => 'Tanggal lahir tidak boleh di masa depan.']);
            }
            $diff = $birth->diff(now());
            if ($diff->y >= 14) {
                throw ValidationException::withMessages(['tanggal_lahir' => 'Usia melebihi batas maksimal 13 tahun 11 bulan.']);
            }
            [$data['umur'], $data['umur_satuan']] = $diff->y > 0
                ? [$diff->y, 'tahun']
                : ($diff->m > 0 ? [$diff->m, 'bulan'] : [max($diff->d, 1), 'hari']);

            return;
        }

        if (! isset($data['umur'], $data['umur_satuan'])) {
            throw ValidationException::withMessages(['umur' => 'Umur dan satuan wajib diisi jika tanggal lahir tidak diketahui.']);
        }
        $estimatedYears = match ($data['umur_satuan']) {
            'tahun' => (int) $data['umur'], 'bulan' => floor((int) $data['umur'] / 12), 'hari' => floor((int) $data['umur'] / 365),
        };
        if ($estimatedYears >= 14) {
            throw ValidationException::withMessages(['umur' => 'Usia melebihi batas maksimal 13 tahun 11 bulan.']);
        }
    }

    private function personData(array $data): array
    {
        return collect($data)->only(['nama_lengkap', 'nama_panggilan', 'tanggal_lahir', 'jenis_kelamin'])->all();
    }

    private function participationData(array $data, string $personId, ?string $ipAddress): array
    {
        return collect($data)->only([
            'tahun_program', 'kategori', 'sumber_informasi', 'umur', 'umur_satuan', 'alamat', 'rt', 'rw',
            'nama_rt', 'nama_orang_tua', 'pekerjaan_orang_tua', 'no_wa', 'catatan_tambahan',
        ])->all() + ['person_id' => $personId, 'ip_address' => $ipAddress];
    }

    private function buildGroupedData(Collection $records): array
    {
        return $records->groupBy(fn ($row) => $this->groupKey($row->sumber_informasi))->map(fn (Collection $sourceRecords) => [
            'label' => $this->groupLabel($sourceRecords->first()->sumber_informasi, 'Sumber belum diisi'),
            'total' => $sourceRecords->count(),
            'categories' => $sourceRecords->groupBy('kategori')->map(fn (Collection $categoryRecords) => [
                'label' => $this->categoryLabel($categoryRecords->first()->kategori),
                'total' => $categoryRecords->count(),
                'rws' => $categoryRecords->groupBy(fn ($row) => $this->groupKey($row->rw))->map(fn (Collection $rwRecords) => [
                    'label' => $this->groupLabel($rwRecords->first()->rw, 'Belum diisi'),
                    'total' => $rwRecords->count(),
                    'rts' => $rwRecords->groupBy(fn ($row) => $this->groupKey($row->rt))->map(fn (Collection $rtRecords) => [
                        'label' => $this->groupLabel($rtRecords->first()->rt, 'Belum diisi'),
                        'total' => $rtRecords->count(),
                        'coordinators' => $rtRecords->groupBy(fn ($row) => $this->groupKey($row->nama_rt))->map(fn (Collection $coordinatorRecords) => [
                            'label' => $this->groupLabel($coordinatorRecords->first()->nama_rt),
                            'total' => $coordinatorRecords->count(),
                            'recipients' => $coordinatorRecords->values()->map(fn ($row) => [
                                'id' => $row->id, 'nama_lengkap' => $row->nama_lengkap, 'nama_panggilan' => $row->nama_panggilan,
                                'jenis_kelamin' => $row->jenis_kelamin, 'tanggal_lahir' => $row->tanggal_lahir?->format('d-m-Y'),
                                'umur' => $this->formatUmur($row), 'nama_orang_tua' => $row->nama_orang_tua, 'alamat' => $row->alamat,
                            ])->all(),
                        ])->values()->all(),
                    ])->values()->all(),
                ])->values()->all(),
            ])->values()->all(),
        ])->values()->all();
    }

    private function categoryBadge(string $category): string
    {
        $label = $category === 'dhuafa' ? 'DHUAFA' : 'YATIM&nbsp;YANG&nbsp;DHUAFA';
        $color = $category === 'dhuafa' ? '#10b981' : '#06b6d4';

        return '<span style="display:inline-block;white-space:nowrap;background-color:'.$color.';color:#fff;padding:4px 12px;border-radius:9999px;font-size:12px;font-weight:700">'.$label.'</span>';
    }

    private function categoryLabel(string $category): string
    {
        return $category === 'dhuafa' ? 'DHUAFA' : 'YATIM YANG DHUAFA';
    }

    private function formatUmur($row): string
    {
        return $row->umur !== null && filled($row->umur_satuan) ? $row->umur.' '.ucfirst($row->umur_satuan) : '-';
    }

    private function groupKey(?string $value): string
    {
        return filled($value) ? $value : '__empty__';
    }

    private function groupLabel(?string $value, ?string $emptyLabel = null): ?string
    {
        return filled($value) ? $value : $emptyLabel;
    }

    private function normalizeRegionFields(array &$data): void
    {
        foreach (['rt', 'rw', 'nama_rt'] as $field) {
            $value = $data[$field] ?? null;
            $data[$field] = is_string($value) && trim($value) !== '' ? trim($value) : null;
        }
    }
}
