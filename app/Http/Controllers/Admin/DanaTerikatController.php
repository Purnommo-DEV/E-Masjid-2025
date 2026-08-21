<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Interfaces\DanaTerikatRepositoryInterface;
use App\Models\DanaTerikatPenerima;
use App\Models\DanaTerikatProgram;
use App\Models\DanaTerikatRealisasi;
use App\Models\DanaTerikatRealisasiKoreksi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class DanaTerikatController extends Controller
{
    protected $danaTerikat;

    public function __construct(DanaTerikatRepositoryInterface $danaTerikat)
    {
        $this->danaTerikat = $danaTerikat;
    }

    public function index()
    {
        return view('masjid.'.masjid().'.admin.keuangan.dana-terikat.index');
    }

    public function data(Request $request)
    {
        $tab = $request->tab;
        $programId = $request->program;

        $tahun = $request->tahun;
        if ($tahun === '' || $tahun === null) {
            $tahun = null;
        }

        // SALDO
        if ($tab === 'saldo') {
            $data = $this->danaTerikat->getSaldoData($programId ? (int) $programId : null, $tahun ? (int) $tahun : null);

            return response()->json($data);
        }

        // PENERIMA
        if ($tab == 'penerima') {
            $query = DanaTerikatPenerima::query()
                ->leftJoin('dana_terikat_program', 'dana_terikat_penerima.program_id', '=', 'dana_terikat_program.id')
                ->leftJoin('dana_terikat_referensi', 'dana_terikat_penerima.referensi_id', '=', 'dana_terikat_referensi.id')
                ->select(
                    'dana_terikat_penerima.*',
                    'dana_terikat_program.nama_program as program_nama',
                    'dana_terikat_referensi.nama as referensi_nama',
                    'dana_terikat_referensi.warna as referensi_warna',
                    DB::raw("CONCAT(dana_terikat_penerima.rt, '/', dana_terikat_penerima.rw) as rt_rw")
                )
                ->when($programId, fn ($q) => $q->where('dana_terikat_penerima.program_id', $programId))
                ->when($tahun, fn ($q) => $q->where('dana_terikat_penerima.tahun_program', $tahun));

            $data = $query->get()->map(function ($item) {
                $item->rt_rw = trim($item->rt_rw ?? '-');
                $item->nama_rt = $item->nama_rt ?? 'Umum';
                $item->program_nama = $item->program_nama ?? 'Program Dihapus';
                $item->nominal_bulanan = $item->nominal_bulanan ?? 0;

                return $item;
            })->sortBy([
                ['rt_rw', 'asc'],
                ['nama_rt', 'asc'],
                ['nama', 'asc'],
            ])->values();

            return response()->json($data);
        }

        // PENERIMAAN
        if ($tab === 'penerimaan') {
            $query = $this->danaTerikat->getPenerimaanQuery($programId ? (int) $programId : null, $tahun ? (int) $tahun : null);

            return DataTables::of($query)
                ->editColumn('tanggal', fn ($row) => Carbon::parse($row->tanggal)->format('d M Y'))
                ->addColumn('program_nama', fn ($row) => $row->program_nama ?? 'Program Dihapus')
                ->editColumn('jumlah', fn ($row) => 'Rp '.number_format($row->jumlah, 0, ',', '.'))
                ->make(true);
        }

        // REALISASI
        if ($tab === 'realisasi') {
            $queryNormal = DanaTerikatRealisasi::with(['program', 'penerima'])
                ->when($programId, fn ($q) => $q->where('program_id', $programId))
                ->when($tahun, fn ($q) => $q->where('tahun', $tahun));

            $queryKoreksi = DanaTerikatRealisasiKoreksi::with(['program', 'penerima'])
                ->when($programId, fn ($q) => $q->where('program_id', $programId))
                ->when($tahun, fn ($q) => $q->where('tahun', $tahun));

            $dataNormal = $queryNormal->get()->map(function ($item) {
                $item->bulan_tahun = Carbon::create($item->tahun, $item->bulan, 1)->translatedFormat('F Y');
                $item->program_nama = $item->program->nama_program ?? 'Program Dihapus';
                $item->penerima_nama = $item->nama_saat_realisasi ?? $item->penerima?->nama ?? 'Tidak diketahui';

                $item->rt_rw = $item->penerima
                    ? trim(($item->penerima->rt ?? '').'/'.($item->penerima->rw ?? ''))
                    : '-';
                $item->nama_rt = $item->penerima?->nama_rt ?? 'Umum';

                $item->jumlah_tampil = $item->jumlah;
                $item->tipe = 'normal';

                return $item;
            });

            $dataKoreksi = $queryKoreksi->get()->map(function ($item) {
                $item->bulan_tahun = Carbon::create($item->tahun, $item->bulan, 1)->translatedFormat('F Y');
                $item->program_nama = $item->program->nama_program ?? 'Program Dihapus';
                $item->penerima_nama = $item->keterangan ?? 'Koreksi Umum';

                $item->rt_rw = $item->penerima
                    ? trim(($item->penerima->rt ?? '').'/'.($item->penerima->rw ?? ''))
                    : '-';
                $item->nama_rt = $item->penerima?->nama_rt ?? 'Koreksi Umum';

                $item->jumlah_tampil = $item->jumlah_koreksi;
                $item->tipe = 'koreksi';

                return $item;
            });

            $merged = $dataNormal->concat($dataKoreksi)
                ->sortBy([
                    ['tahun', 'desc'],
                    ['bulan', 'desc'],
                    ['program_nama', 'asc'],
                    ['penerima_nama', 'asc'],
                ])
                ->values();

            return response()->json($merged->toArray());
        }

        // ===== STATUS BULANAN (BARU) =====
        if ($tab === 'status_bulanan') {
            $data = $this->danaTerikat->getStatusBulanan(
                (int) $programId,
                (int) $request->bulan,
                (int) $request->tahun
            );

            return response()->json($data);
        }

        return response()->json([], 400);
    }

    // === TERIMA DANA ===
    public function storePenerimaan(Request $request)
    {
        $request->validate([
            'program_id' => 'required|exists:dana_terikat_program,id',
            'jenis_dana' => 'required|in:dana_terikat,zakat_maal,zakat_fitrah,fidyah,infaq_umum,shodaqoh,dana_titipan',
            'tanggal' => 'required|date',
            'jumlah' => 'required|numeric|min:1',
            'donatur_nama' => 'required',
            'is_saldo_awal' => 'nullable|boolean',
        ]);

        try {
            $this->danaTerikat->storePenerimaan($request->all());

            $isSaldoAwal = $request->is_saldo_awal == 1;
            $jenisDana = $request->jenis_dana;

            $labelJenis = match ($jenisDana) {
                'dana_terikat' => 'Infaq Terikat (Y/D)',
                'zakat_maal' => 'Zakat Maal',
                'zakat_fitrah' => 'Zakat Fitrah',
                'fidyah' => 'Fidyah',
                'infaq_umum' => 'Infaq Umum',
                'shodaqoh' => 'Shodaqoh',
                'dana_titipan' => 'Dana Titipan',
                default => $jenisDana,
            };

            $message = $isSaldoAwal
                ? "Saldo awal ({$labelJenis}) berhasil dicatat tanpa jurnal"
                : "Penerimaan {$labelJenis} berhasil dicatat!";

            return response()->json(['message' => $message]);
        } catch (\Throwable $e) {
            Log::error('Gagal mencatat penerimaan dana terikat', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mencatat penerimaan.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    // === PENERIMA ===
    public function storePenerima(Request $request)
    {
        $validated = $request->validate([
            'program_id' => 'required',
            'tahun_program' => 'required',
            'nama' => 'required',
            'nominal_bulanan' => 'required|numeric',
            'kategori' => 'required|in:yatim,dhuafa,operasional,lainnya',
            'tanggal_lahir' => 'required_if:kategori,yatim|nullable|date',
            'rt' => 'nullable|string|max:5',
            'rw' => 'nullable|string|max:5',
            'nama_rt' => 'nullable|string|max:255',
            'status_aktif' => 'nullable',
            'referensi_id' => 'nullable|integer|exists:dana_terikat_referensi,id',
            'keterangan' => 'nullable|string|max:65535',  // <-- BARU
        ], [
            'tanggal_lahir.required_if' => 'Tanggal lahir wajib diisi untuk kategori yatim.',
        ]);

        try {
            $this->danaTerikat->storePenerima($validated);

            return response()->json([
                'message' => 'Penerima berhasil ditambahkan',
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan server',
            ], 500);
        }
    }

    public function showPenerima(Request $request)
    {
        $penerima = $this->danaTerikat->findPenerima($request->id);

        return response()->json($penerima);
    }

    public function updatePenerima(Request $request, $id)
    {
        $validated = $request->validate([
            'program_id' => 'required',
            'tahun_program' => 'required',
            'nama' => 'required',
            'nominal_bulanan' => 'required|numeric',
            'kategori' => 'required|in:yatim,dhuafa,operasional,lainnya',
            'tanggal_lahir' => 'nullable|date',
            'rt' => 'nullable|string|max:5',
            'rw' => 'nullable|string|max:5',
            'nama_rt' => 'nullable|string|max:255',
            'status_aktif' => 'nullable',
            'referensi_id' => 'nullable|integer|exists:dana_terikat_referensi,id',
            'keterangan' => 'nullable|string|max:65535',
        ]);

        try {
            $this->danaTerikat->updatePenerima((int) $id, $validated);

            return response()->json([
                'message' => 'Penerima berhasil diupdate',
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan server',
            ], 500);
        }
    }

    public function destroyPenerima($id)
    {
        try {
            $penerima = DanaTerikatPenerima::findOrFail($id);
            $penerima->delete();

            return response()->json([
                'message' => 'Penerima berhasil dihapus',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Gagal menghapus penerima',
            ], 500);
        }
    }

    // === REALISASI ===
    public function realisasi(Request $request)
    {
        $request->validate([
            'program_id' => 'required|exists:dana_terikat_program,id',
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer',
        ]);

        try {
            $this->danaTerikat->realisasiBulanan(
                (int) $request->program_id,
                (int) $request->bulan,
                (int) $request->tahun,
                $request->penerima_ids ? explode(',', $request->penerima_ids) : null
            );

            return response()->json([
                'message' => 'Realisasi berhasil!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 409);
        }
    }

    public function penerimaAktif(Request $request)
    {
        $penerima = DanaTerikatPenerima::where('program_id', $request->program_id)
            ->where('tahun_program', $request->tahun)
            ->where('status_aktif', 1)
            ->select('id', 'nama', 'nominal_bulanan', 'kategori')
            ->orderBy('nama')
            ->get();

        return response()->json($penerima);
    }

    public function koreksiStore(Request $request)
    {
        $request->validate([
            'program_id_koreksi' => 'required|exists:dana_terikat_program,id',
            'tahun' => 'required|integer',
            'bulan' => 'required|integer|min:1|max:12',
            'jumlah_koreksi' => 'required|numeric',
            'keterangan' => 'nullable|string|max:255',  // 🔥 nullable
        ]);

        // 🔥 BERSIHKAN: hapus titik (format ribuan) dan tanda +
        $jumlahBersih = str_replace(['.', '+'], '', $request->jumlah_koreksi);

        // 🔥 CEK APAKAH NEGATIF
        $isNegatif = str_starts_with($request->jumlah_koreksi, '-');

        // 🔥 CAST KE INTEGER
        $jumlah = (int) $jumlahBersih;  // -7500

        // 🔥 JIKA NEGATIF, PERTAHANKAN (JANGAN DIBALIK!)
        // HAPUS: if ($isNegatif) { $jumlah = -$jumlah; }

        // 🔥 ATAU LEBIH SIMPLE:
        $jumlah = (int) str_replace(['.', '+'], '', $request->jumlah_koreksi);
        // "-7500" → -7500 ✅

        $this->danaTerikat->koreksiRealisasi(
            (int) $request->program_id_koreksi,
            (int) $request->tahun,
            (int) $request->bulan,
            $jumlah,  // ← -7500
            $request->keterangan ?: 'Koreksi realisasi'
        );

        return response()->json(['message' => 'Koreksi realisasi berhasil dicatat!']);
    }

    // === STATUS BULANAN (BARU) ===

    public function getStatusBulanan(Request $request)
    {
        $request->validate([
            'program_id' => 'required|exists:dana_terikat_program,id',
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer',
        ]);

        $data = $this->danaTerikat->getStatusBulanan(
            (int) $request->program_id,
            (int) $request->bulan,
            (int) $request->tahun
        );

        return response()->json($data);
    }

    public function getStatusBulananById(Request $request, $penerimaId)
    {
        $request->validate([
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer',
        ]);

        $data = $this->danaTerikat->getStatusBulananById(
            (int) $penerimaId,
            (int) $request->bulan,
            (int) $request->tahun
        );

        return response()->json($data);
    }

    public function updateStatusBulanan(Request $request)
    {
        $request->validate([
            'penerima_id' => 'required|exists:dana_terikat_penerima,id',
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer',
            'status_dapat' => 'required|boolean',
            'alasan_tidak_dapat' => 'nullable|string|max:255',
        ]);

        $penerima = DanaTerikatPenerima::findOrFail($request->penerima_id);

        $status = $this->danaTerikat->updateStatusBulanan(
            (int) $request->penerima_id,
            (int) $request->bulan,
            (int) $request->tahun,
            [
                'program_id' => $penerima->program_id,
                'status_dapat' => $request->status_dapat,
                'alasan_tidak_dapat' => $request->alasan_tidak_dapat,
                'verified_by' => auth()->user()->name,
            ]
        );

        return response()->json([
            'message' => 'Status bulanan berhasil diperbarui',
            'data' => $status,
        ]);
    }

    public function updateStatusBulananLengkap(Request $request)
    {
        $request->validate([
            'penerima_id' => 'required|exists:dana_terikat_penerima,id',
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer',
            'nama' => 'nullable|string|max:255',
            'status_dapat' => 'required|boolean',
            'nominal' => 'nullable|numeric|min:0',
            'alasan' => 'nullable|string|max:255',
            'verifikator' => 'nullable|string|max:255',
        ]);

        $penerima = DanaTerikatPenerima::findOrFail($request->penerima_id);

        $status = $this->danaTerikat->updateStatusBulanan(
            (int) $request->penerima_id,
            (int) $request->bulan,
            (int) $request->tahun,
            [
                'program_id' => $penerima->program_id,
                'status_dapat' => $request->status_dapat,
                'alasan_tidak_dapat' => $request->alasan,
                'nama_alternatif' => $request->nama,
                'nominal_alternatif' => $request->nominal,
                'verified_by' => $request->verifikator ?? auth()->user()->name,
            ]
        );

        return response()->json([
            'message' => 'Status bulanan berhasil diperbarui',
            'data' => $status,
        ]);
    }

    public function copyStatusBulanan(Request $request)
    {
        $request->validate([
            'program_id' => 'required|exists:dana_terikat_program,id',
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer',
        ]);

        try {
            $count = $this->danaTerikat->copyStatusDariBulanLalu(
                (int) $request->program_id,
                (int) $request->bulan,
                (int) $request->tahun
            );

            return response()->json([
                'message' => "Berhasil menyalin {$count} data status dari bulan sebelumnya",
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function realisasiDariStatus(Request $request)
    {
        $request->validate([
            'program_id' => 'required|exists:dana_terikat_program,id',
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer',
            'penerima_ids' => 'required|array|min:1',
            'penerima_ids.*' => 'exists:dana_terikat_penerima,id',
        ]);

        try {
            $result = $this->danaTerikat->realisasiDariStatusBulanan(
                (int) $request->program_id,
                (int) $request->bulan,
                (int) $request->tahun,
                $request->penerima_ids
            );

            return response()->json([
                'message' => "Berhasil merealisasikan {$result['count']} penerima dengan total Rp ".
                    number_format($result['total'], 0, ',', '.'),
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    // === TAMBAH PROGRAM ===
    public function storeProgram(Request $request)
    {
        $request->validate([
            'kode_program' => 'required|unique:dana_terikat_program',
            'nama_program' => 'required',
            'akun_liabilitas_id' => 'required|exists:akun_keuangan,id',
            'akun_aset_id' => 'required|exists:akun_keuangan,id',
        ]);

        $this->danaTerikat->storeProgram($request->all());

        return response()->json(['message' => 'Program berhasil ditambahkan']);
    }

    public function cekNamaPenerima(Request $request)
    {
        $nama = trim($request->nama ?? '');

        if ($nama === '') {
            return response()->json([]);
        }

        $list = DanaTerikatPenerima::query()
            ->where('nama', 'like', '%'.$nama.'%')
            ->orderBy('nama')
            ->limit(10)
            ->get([
                'id',
                'nama',
                'alamat',
                'rt',
                'rw',
                'tahun_program',
                'status_yatim',
                'nama_rt',
            ]);

        return response()->json($list);
    }

    public function akunOptions(Request $request)
    {
        $tipe = $request->get('tipe', 'liabilitas');

        return $this->danaTerikat->getAkunOptionsHtml($tipe);
    }

    // public function exportStatusExcel(Request $request)
    // {
    //     $request->validate([
    //         'program_id' => 'required|exists:dana_terikat_program,id',
    //         'bulan' => 'required|integer|min:1|max:12',
    //         'tahun' => 'required|integer'
    //     ]);

    //     $data = $this->danaTerikat->getStatusBulanan(
    //         (int)$request->program_id,
    //         (int)$request->bulan,
    //         (int)$request->tahun
    //     );

    //     $program = DanaTerikatProgram::find($request->program_id);
    //     $bulanName = Carbon::create()->month($request->bulan)->translatedFormat('F');

    //     // Gunakan library Excel (misal: Maatwebsite)
    //     return Excel::download(new StatusBulananExport($data, $program, $bulanName),
    //         "Status_Bulanan_{$program->kode_program}_{$bulanName}_{$request->tahun}.xlsx");
    // }

    public function kwitansi($id)
    {
        $realisasi = DanaTerikatRealisasi::with(['program', 'penerima', 'statusBulanan', 'createdBy'])
            ->findOrFail($id);

        // Data untuk kwitansi
        $data = [
            'realisasi' => $realisasi,
            'nomor_kwitansi' => 'KW-'.str_pad($realisasi->id, 6, '0', STR_PAD_LEFT),
            'tanggal' => $realisasi->created_at->format('d/m/Y'),
            'program' => $realisasi->program->nama_program,
            'penerima' => $realisasi->nama_saat_realisasi ?? $realisasi->penerima?->nama,
            'jumlah' => $realisasi->jumlah,
            'bulan' => Carbon::create($realisasi->tahun, $realisasi->bulan, 1)->translatedFormat('F Y'),
            'dibuat_oleh' => $realisasi->createdBy?->name ?? 'Sistem',
        ];

        return view('masjid.'.masjid().'.admin.keuangan.dana-terikat.kwitansi', $data);
    }
}
