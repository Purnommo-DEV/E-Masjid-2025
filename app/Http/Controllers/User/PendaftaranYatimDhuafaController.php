<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\PendaftaranAnakYatimDhuafa;
use Carbon\Carbon;
use Edgaras\StrSim\JaroWinkler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class PendaftaranYatimDhuafaController extends Controller
{
    // Halaman publik (guest)
    public function indexPublik()
    {
        $sumberList = PendaftaranAnakYatimDhuafa::whereNotNull('sumber_informasi')
            ->distinct()
            ->orderBy('sumber_informasi')
            ->pluck('sumber_informasi');

        $rtList = PendaftaranAnakYatimDhuafa::query()
            ->whereNotNull('rt')
            ->where('rt', '!=', '')
            ->distinct()
            ->orderBy('rt')
            ->pluck('rt');

        $rwList = PendaftaranAnakYatimDhuafa::query()
            ->whereNotNull('rw')
            ->where('rw', '!=', '')
            ->distinct()
            ->orderBy('rw')
            ->pluck('rw');

        $registrationOpen = (bool) config('santunan.registration_open', true);

        return view('masjid.'.masjid().'.guest.pendaftaran.show', compact(
            'sumberList',
            'rtList',
            'rwList',
            'registrationOpen',
        ));
    }

    public function index()
    {
        $view = config('santunan.registration_open', true)
            ? 'index'
            : 'indexClose';

        return view('masjid.'.masjid().'.guest.pendaftaran.'.$view);
    }

    public function dataTable(Request $request)
    {
        $query = $this->applyPublicFilters(
            PendaftaranAnakYatimDhuafa::query(),
            $this->validatePublicFilters($request),
        );

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('kategori_display', function ($row) {
                return match ($row->kategori) {
                    'yatim' => '<span style="
                            display:inline-block;
                            white-space:nowrap;
                            background-color:#0ea5e9;
                            color:#ffffff;
                            padding:4px 12px;
                            border-radius:9999px;
                            font-size:12px;
                            font-weight:700;
                        ">Yatim</span>',
                    'yatim_dhuafa' => '<span style="
                            display:inline-block;
                            white-space:nowrap;
                            background-color:#06b6d4;
                            color:#ffffff;
                            padding:4px 12px;
                            border-radius:9999px;
                            font-size:12px;
                            font-weight:700;
                        ">
                        Yatim&nbsp;yang&nbsp;Dhuafa
                    </span>',

                    'dhuafa' => '<span style="
                            display:inline-block;
                            white-space:nowrap;
                            background-color:#10b981;
                            color:#ffffff;
                            padding:4px 12px;
                            border-radius:9999px;
                            font-size:12px;
                            font-weight:700;
                        ">
                        Dhuafa
                    </span>',
                    default => '<span style="color:#64748b;font-style:italic;">Belum Ditentukan</span>',
                };
            })
            ->addColumn('jenis_kelamin_display', fn ($row) => $row->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan')
            ->addColumn('tanggal_lahir_formatted', fn ($row) => $row->tanggal_lahir ? Carbon::parse($row->tanggal_lahir)->format('d-m-Y') : '-')
            ->addColumn('umur_display', function ($row) {

                // Jika ada tanggal lahir → hitung realtime
                if (! empty($row->tanggal_lahir)) {

                    $birth = Carbon::parse($row->tanggal_lahir);
                    $diff = $birth->diff(now());

                    $tahun = $diff->y;
                    $bulan = $diff->m;
                    $hari = $diff->d;

                    $parts = [];

                    if ($tahun > 0) {
                        $parts[] = $tahun.' Tahun';
                    }

                    if ($bulan > 0) {
                        $parts[] = $bulan.' Bulan';
                    }

                    if ($hari > 0) {
                        $parts[] = $hari.' Hari';
                    }

                    if (empty($parts)) {
                        return '<span class="text-slate-400 italic">Baru lahir</span>';
                    }

                    return '<span class="font-medium text-slate-800">'
                            .implode(' · ', $parts).
                           '</span>';
                }

                // Jika manual
                if (! empty($row->umur) && ! empty($row->umur_satuan)) {

                    return '<span class="font-medium text-slate-700">'
                            .$row->umur.' '.ucfirst($row->umur_satuan).
                           ' <span class="text-xs text-slate-400">(Manual)</span></span>';
                }

                return '<span class="text-slate-400 italic">Tidak diketahui</span>';
            })
            ->addColumn('nama_lengkap', fn ($row) => $row->nama_lengkap)
            ->addColumn('nama_panggilan', fn ($row) => $row->nama_panggilan ?? '-')
            ->addColumn('nama_orang_tua', fn ($row) => $row->nama_orang_tua)
            ->addColumn('pekerjaan_orang_tua', fn ($row) => $row->pekerjaan_orang_tua ?? '-')
            ->addColumn('alamat', fn ($row) => $row->alamat)
            ->addColumn('rt', fn ($row) => $row->rt ?: '-')
            ->addColumn('rw', fn ($row) => $row->rw ?: '-')
            ->addColumn('nama_rt', fn ($row) => $row->nama_rt ?: '-')
            ->addColumn('no_wa', fn ($row) => $row->no_wa ?? '-')
            ->addColumn('sumber_informasi', fn ($row) => $row->sumber_informasi)
            ->addColumn('catatan_tambahan', fn ($row) => $row->catatan_tambahan ?? '-')
            ->addColumn('tahun_program', fn ($row) => $row->tahun_program)
            ->rawColumns(['kategori_display', 'umur_display'])
            ->make(true);
    }

    public function groupedData(Request $request)
    {
        $records = $this->applyPublicFilters(
            PendaftaranAnakYatimDhuafa::query(),
            $this->validatePublicFilters($request),
        )
            ->orderByRaw("CASE WHEN sumber_informasi IS NULL OR sumber_informasi = '' THEN 1 ELSE 0 END")
            ->orderBy('sumber_informasi')
            ->orderByRaw("CASE kategori WHEN 'yatim' THEN 1 WHEN 'dhuafa' THEN 2 WHEN 'yatim_dhuafa' THEN 3 ELSE 4 END")
            ->orderByRaw("CASE WHEN rw IS NULL OR rw = '' THEN 1 ELSE 0 END")
            ->orderBy('rw')
            ->orderByRaw("CASE WHEN rt IS NULL OR rt = '' THEN 1 ELSE 0 END")
            ->orderBy('rt')
            ->orderByRaw("CASE WHEN nama_rt IS NULL OR nama_rt = '' THEN 1 ELSE 0 END")
            ->orderBy('nama_rt')
            ->orderBy('nama_lengkap')
            ->get();

        return response()->json([
            'success' => true,
            'total' => $records->count(),
            'groups' => $this->buildGroupedData($records),
        ]);
    }

    public function store(Request $request)
    {
        if (! config('santunan.registration_open', true)) {
            return response()->json([
                'success' => false,
                'message' => 'Pendaftaran Santunan Ramadhan sedang ditutup.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'kategori' => 'required|in:yatim_dhuafa,dhuafa',
            'nama_lengkap' => 'required|min:3|max:150',
            'nama_panggilan' => 'nullable|max:60',
            'sumber_informasi' => 'nullable',
            'tanggal_lahir' => 'nullable|date',
            // 'tanggal_lahir'        => 'required|date|before_or_equal:' . now()->subYears(0)->toDateString(),
            'umur' => 'nullable|integer|min:0|max:13',
            'umur_satuan' => 'nullable|in:tahun,bulan,hari',
            'jenis_kelamin' => 'required|in:L,P',
            'alamat' => 'required|min:2|max:255',
            'rt' => 'nullable|string|max:5',
            'rw' => 'nullable|string|max:5',
            'nama_rt' => 'nullable|string|max:150',
            'no_wa' => 'nullable|regex:/^08[0-9]{8,12}$/',
            'nama_orang_tua' => 'required|min:2|max:100',
            'pekerjaan_orang_tua' => 'nullable|max:100',
            'catatan_tambahan' => 'nullable|string|max:500',
        ], [
            'no_wa.regex' => 'Format nomor WA tidak valid (mulai dengan 08...)',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ada kesalahan input',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $this->normalizeRegionFields($data);

        /**
         * ============================
         * LOGIKA UMUR (VERSI FINAL)
         * ============================
         */
        if (! empty($data['tanggal_lahir'])) {

            $tglLahir = Carbon::parse($data['tanggal_lahir']);

            if ($tglLahir->isFuture()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tanggal lahir tidak valid',
                ], 422);
            }

            $now = now();
            $diff = $tglLahir->diff($now);

            // ❌ Jika sudah masuk 14 tahun
            if ($diff->y >= 14) {

                return response()->json([
                    'success' => false,
                    'message' => "Usia melebihi batas maksimal (13 Tahun 11 Bulan). Saat ini: {$diff->y} Tahun {$diff->m} Bulan {$diff->d} Hari",
                ], 422);
            }

            // Simpan umur utama
            if ($diff->y > 0) {
                $data['umur'] = $diff->y;
                $data['umur_satuan'] = 'tahun';
            } elseif ($diff->m > 0) {
                $data['umur'] = $diff->m;
                $data['umur_satuan'] = 'bulan';
            } else {
                $data['umur'] = max($diff->d, 1);
                $data['umur_satuan'] = 'hari';
            }

        } else {

            // MODE MANUAL
            if (! isset($data['umur']) || ! isset($data['umur_satuan'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Umur dan satuan wajib diisi jika tanggal lahir tidak diketahui',
                ], 422);
            }

            $umur = (int) $data['umur'];

            // Konversi manual ke estimasi tahun untuk validasi
            $tahunEstimasi = match ($data['umur_satuan']) {
                'tahun' => $umur,
                'bulan' => floor($umur / 12),
                'hari' => floor($umur / 365),
            };

            if ($tahunEstimasi >= 14) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usia melebihi batas maksimal 13 Tahun 11 Bulan',
                ], 422);
            }
        }

        $data['tahun_program'] = now()->year;
        $data['ip_address'] = $request->ip();

        /**
         * ============================
         * CEK DUPLIKAT (AMAN)
         * ============================
         */
        $duplikat = PendaftaranAnakYatimDhuafa::where('nama_lengkap', $data['nama_lengkap'])
            ->where('nama_orang_tua', $data['nama_orang_tua'])
            ->when(! empty($data['tanggal_lahir']), function ($q) use ($data) {
                $q->where('tanggal_lahir', $data['tanggal_lahir']);
            })
            ->exists();

        if ($duplikat) {
            return response()->json([
                'success' => false,
                'message' => 'Data ini sudah pernah didaftarkan sebelumnya.',
            ], 422);
        }

        PendaftaranAnakYatimDhuafa::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Pendaftaran berhasil dikirim. Terima kasih!',
        ]);
    }

    public function update(Request $request, $id)
    {
        $pendaftaran = PendaftaranAnakYatimDhuafa::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'kategori' => 'required|in:yatim_dhuafa,dhuafa',
            'nama_lengkap' => 'required|string|min:3|max:150',
            'nama_panggilan' => 'nullable|string|max:60',
            'tanggal_lahir' => 'nullable|date',
            'umur' => 'nullable|integer|min:0|max:5000',
            'umur_satuan' => 'nullable|in:tahun,bulan,hari',
            'jenis_kelamin' => 'required|in:L,P',
            'alamat' => 'required|string|min:5|max:255',
            'rt' => 'nullable|string|max:5',
            'rw' => 'nullable|string|max:5',
            'nama_rt' => 'nullable|string|max:150',
            'no_wa' => 'nullable|regex:/^08[0-9]{8,12}$/',
            'nama_orang_tua' => 'required|string|min:2|max:100',
            'pekerjaan_orang_tua' => 'nullable|string|max:100',
            'sumber_informasi' => 'required|string|min:2|max:255',
            'catatan_tambahan' => 'nullable|string|max:500',
        ], [
            'no_wa.regex' => 'Format nomor WA tidak valid (mulai dengan 08...)',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ada kesalahan input',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $this->normalizeRegionFields($data);

        // =====================================
        // LOGIKA UMUR SAMA DENGAN STORE
        // =====================================
        if (! empty($data['tanggal_lahir'])) {

            $tglLahir = Carbon::parse($data['tanggal_lahir']);

            if ($tglLahir->isFuture()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tanggal lahir tidak boleh di masa depan',
                ], 422);
            }

            $now = now();
            $diff = $tglLahir->diff($now);

            if ($diff->y >= 14) {
                return response()->json([
                    'success' => false,
                    'message' => "Usia melebihi batas maksimal (13 Tahun 11 Bulan). Saat ini: {$diff->y} Tahun {$diff->m} Bulan {$diff->d} Hari",
                ], 422);
            }

            if ($diff->y > 0) {
                $data['umur'] = $diff->y;
                $data['umur_satuan'] = 'tahun';
            } elseif ($diff->m > 0) {
                $data['umur'] = $diff->m;
                $data['umur_satuan'] = 'bulan';
            } else {
                $data['umur'] = max($diff->d, 1);
                $data['umur_satuan'] = 'hari';
            }

        } else {

            if (empty($data['umur']) || empty($data['umur_satuan'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jika tanggal lahir tidak diisi, umur dan satuan wajib diisi',
                ], 422);
            }

            $umur = (int) $data['umur'];

            $tahunEstimasi = match ($data['umur_satuan']) {
                'tahun' => $umur,
                'bulan' => floor($umur / 12),
                'hari' => floor($umur / 365),
            };

            if ($tahunEstimasi >= 14) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usia melebihi batas maksimal 13 Tahun 11 Bulan',
                ], 422);
            }
        }

        $pendaftaran->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diperbarui',
        ]);
    }

    public function edit($id)
    {
        $pendaftaran = PendaftaranAnakYatimDhuafa::where('id', $id)
            ->firstOrFail();

        return response()->json($pendaftaran);
    }

    public function destroy($id)
    {
        $data = PendaftaranAnakYatimDhuafa::findOrFail($id);
        $data->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil dihapus',
        ]);
    }

    public function scanDuplikat(Request $request)
    {
        $tahun = $request->get('tahun', now()->year);

        $records = PendaftaranAnakYatimDhuafa::query()
            ->where('tahun_program', $tahun)
            ->select([
                'id',
                'nama_lengkap',
                'nama_orang_tua',
                'tahun_program',
                'umur',
                'umur_satuan',
                'alamat',
            ])
            ->get()
            ->map(function ($row, $index) {
                $row->duplicate_key = strtolower(trim($row->nama_lengkap.' '.$row->nama_orang_tua));
                $row->duplicate_order = $index;

                return $row;
            })
            ->filter(fn ($row) => strlen($row->duplicate_key) >= 8)
            ->values();

        if ($records->count() < 2) {
            return response()->json([
                'success' => true,
                'pairs' => [],
                'tahun' => $tahun,
                'message' => "Data di tahun {$tahun} kurang dari 2 record. Tidak ada yang bisa dibandingkan.",
            ]);
        }

        $pairs = new Collection;

        $recordCount = $records->count();
        $similarityCache = [];
        $recordsByLength = $records
            ->sort(function ($left, $right) {
                return (strlen($left->duplicate_key) <=> strlen($right->duplicate_key))
                    ?: ($left->duplicate_order <=> $right->duplicate_order);
            })
            ->values();

        foreach ($recordsByLength as $i => $candidateA) {
            for ($j = $i + 1; $j < $recordCount; $j++) {
                $candidateB = $recordsByLength[$j];

                $strA = $candidateA->duplicate_key;
                $strB = $candidateB->duplicate_key;

                if ($this->jaroWinklerUpperBound($strA, $strB) < 0.8) {
                    break;
                }

                [$rowA, $rowB] = $candidateA->duplicate_order < $candidateB->duplicate_order
                    ? [$candidateA, $candidateB]
                    : [$candidateB, $candidateA];

                $cacheKey = strcmp($strA, $strB) <= 0
                    ? $strA."\0".$strB
                    : $strB."\0".$strA;

                $sim = ($similarityCache[$cacheKey] ??= JaroWinkler::similarity($strA, $strB)) * 100;

                if ($sim >= 80) {
                    $pairs->push([
                        'id_a' => $rowA->id,
                        'nama_a' => $rowA->nama_lengkap,
                        'ortu_a' => $rowA->nama_orang_tua ?: '-',
                        'umur_a' => $this->formatUmur($rowA),
                        'tahun_a' => $rowA->tahun_program,
                        'alamat_a' => Str::limit($rowA->alamat ?: '-', 45, '...'),

                        'id_b' => $rowB->id,
                        'nama_b' => $rowB->nama_lengkap,
                        'ortu_b' => $rowB->nama_orang_tua ?: '-',
                        'umur_b' => $this->formatUmur($rowB),
                        'tahun_b' => $rowB->tahun_program,
                        'alamat_b' => Str::limit($rowB->alamat ?: '-', 45, '...'),

                        'similarity' => round($sim, 1),
                        '_pair_order' => [$rowA->duplicate_order, $rowB->duplicate_order],
                    ]);
                }
            }
        }

        $pairs = $pairs
            ->sort(function ($left, $right) {
                return ($right['similarity'] <=> $left['similarity'])
                    ?: ($left['_pair_order'] <=> $right['_pair_order']);
            })
            ->take(30)
            ->map(function ($pair) {
                unset($pair['_pair_order']);

                return $pair;
            })
            ->values();

        return response()->json([
            'success' => true,
            'pairs' => $pairs->toArray(),
            'total' => $pairs->count(),
            'tahun' => $tahun,
            'message' => $pairs->isEmpty() ? "Tidak ditemukan pasangan dengan kemiripan tinggi di tahun {$tahun}" : null,
        ]);
    }

    private function formatUmur($row)
    {
        if ($row->umur && $row->umur_satuan) {
            return $row->umur.' '.ucfirst($row->umur_satuan);
        }

        return '-';
    }

    private function buildGroupedData(Collection $records): array
    {
        return $records
            ->groupBy(fn ($row) => $this->groupKey($row->sumber_informasi))
            ->map(function (Collection $sourceRecords) {
                return [
                    'label' => $this->groupLabel($sourceRecords->first()->sumber_informasi, 'Sumber belum diisi'),
                    'total' => $sourceRecords->count(),
                    'categories' => $sourceRecords
                        ->groupBy(fn ($row) => $this->categoryKey($row->kategori))
                        ->map(function (Collection $categoryRecords) {
                            return [
                                'label' => $this->categoryLabel($categoryRecords->first()->kategori),
                                'total' => $categoryRecords->count(),
                                'rws' => $categoryRecords
                                    ->groupBy(fn ($row) => $this->groupKey($row->rw))
                                    ->map(function (Collection $rwRecords) {
                                        return [
                                            'label' => $this->groupLabel($rwRecords->first()->rw, 'Belum diisi'),
                                            'total' => $rwRecords->count(),
                                            'rts' => $rwRecords
                                                ->groupBy(fn ($row) => $this->groupKey($row->rt))
                                                ->map(function (Collection $rtRecords) {
                                                    return [
                                                        'label' => $this->groupLabel($rtRecords->first()->rt, 'Belum diisi'),
                                                        'total' => $rtRecords->count(),
                                                        'coordinators' => $rtRecords
                                                            ->groupBy(fn ($row) => $this->groupKey($row->nama_rt))
                                                            ->map(function (Collection $coordinatorRecords) {
                                                                return [
                                                                    'label' => $this->groupLabel($coordinatorRecords->first()->nama_rt),
                                                                    'total' => $coordinatorRecords->count(),
                                                                    'recipients' => $coordinatorRecords
                                                                        ->values()
                                                                        ->map(fn ($row) => [
                                                                            'id' => $row->id,
                                                                            'nama_lengkap' => $row->nama_lengkap,
                                                                            'nama_panggilan' => $row->nama_panggilan,
                                                                            'jenis_kelamin' => $row->jenis_kelamin,
                                                                            'tanggal_lahir' => $row->tanggal_lahir?->format('d-m-Y'),
                                                                            'umur' => $this->formatUmur($row),
                                                                            'nama_orang_tua' => $row->nama_orang_tua,
                                                                            'alamat' => $row->alamat,
                                                                        ])
                                                                        ->all(),
                                                                ];
                                                            })
                                                            ->values()
                                                            ->all(),
                                                    ];
                                                })
                                                ->values()
                                                ->all(),
                                        ];
                                    })
                                    ->values()
                                    ->all(),
                            ];
                        })
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function validatePublicFilters(Request $request): array
    {
        return $request->validate([
            'sumber_informasi' => 'nullable|string|max:255',
            'kategori' => 'nullable|in:yatim,dhuafa,yatim_dhuafa,__undetermined__',
            'rt' => 'nullable|string|max:5',
            'rw' => 'nullable|string|max:5',
            'search' => 'nullable|string|max:150',
            'tahun' => 'nullable|integer|min:2000|max:2100',
            'umur_value' => 'nullable|integer|min:0',
            'umur_satuan' => 'nullable|in:tahun,bulan,hari',
            'jenis_kelamin' => 'nullable|in:L,P',
        ]);
    }

    private function applyPublicFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['sumber_informasi'] ?? null, fn (Builder $query, string $value) => $query->where('sumber_informasi', $value))
            ->when($filters['kategori'] ?? null, function (Builder $query, string $value) {
                if ($value === '__undetermined__') {
                    return $query->where(function (Builder $categoryQuery) {
                        $categoryQuery->whereNull('kategori')
                            ->orWhere('kategori', '')
                            ->orWhereNotIn('kategori', ['yatim', 'dhuafa', 'yatim_dhuafa']);
                    });
                }

                return $query->where('kategori', $value);
            })
            ->when($filters['rt'] ?? null, fn (Builder $query, string $value) => $query->where('rt', $value))
            ->when($filters['rw'] ?? null, fn (Builder $query, string $value) => $query->where('rw', $value))
            ->when($filters['tahun'] ?? null, fn (Builder $query, int $value) => $query->where('tahun_program', $value))
            ->when($filters['umur_value'] ?? null, function (Builder $query, int $value) use ($filters) {
                $query->where('umur', $value);

                if ($filters['umur_satuan'] ?? null) {
                    $query->where('umur_satuan', $filters['umur_satuan']);
                }
            })
            ->when($filters['jenis_kelamin'] ?? null, fn (Builder $query, string $value) => $query->where('jenis_kelamin', $value))
            ->when($filters['search'] ?? null, function (Builder $query, string $value) {
                $search = '%'.$value.'%';

                $query->where(function (Builder $nameQuery) use ($search) {
                    $nameQuery->where('nama_lengkap', 'like', $search)
                        ->orWhere('nama_panggilan', 'like', $search);
                });
            });
    }

    private function groupKey(?string $value): string
    {
        return filled($value) ? $value : '__empty__';
    }

    private function groupLabel(?string $value, ?string $emptyLabel = null): ?string
    {
        return filled($value) ? $value : $emptyLabel;
    }

    private function categoryKey(?string $category): string
    {
        return in_array($category, ['yatim', 'dhuafa', 'yatim_dhuafa'], true)
            ? $category
            : '__undetermined__';
    }

    private function categoryLabel(?string $category): string
    {
        return match ($category) {
            'yatim' => 'Yatim',
            'dhuafa' => 'Dhuafa',
            'yatim_dhuafa' => 'Yatim yang Dhuafa',
            default => 'Belum Ditentukan',
        };
    }

    private function normalizeRegionFields(array &$data): void
    {
        foreach (['rt', 'rw', 'nama_rt'] as $field) {
            $value = $data[$field] ?? null;
            $data[$field] = is_string($value) && trim($value) !== '' ? trim($value) : null;
        }
    }

    private function jaroWinklerUpperBound(string $left, string $right): float
    {
        $leftLength = strlen($left);
        $rightLength = strlen($right);
        $maxMatches = min($leftLength, $rightLength);

        $jaroUpperBound = (
            ($maxMatches / $leftLength)
            + ($maxMatches / $rightLength)
            + 1
        ) / 3;

        return $jaroUpperBound + (0.4 * (1 - $jaroUpperBound));
    }
}
