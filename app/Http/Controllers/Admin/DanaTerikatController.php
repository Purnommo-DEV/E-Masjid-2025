<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AkunKeuangan;
use App\Models\DanaTerikatPenerima;
use App\Models\DanaTerikatRealisasiKoreksi;
use App\Models\DanaTerikatRealisasi;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Interfaces\DanaTerikatRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DanaTerikatController extends Controller
{
    protected $danaTerikat;

    public function __construct(DanaTerikatRepositoryInterface $danaTerikat)
    {
        $this->danaTerikat = $danaTerikat;
    }

    public function index()
    {
        return view('masjid.' . masjid() . '.admin.keuangan.dana-terikat.index');
    }

    public function data(Request $request)
    {
        $tab       = $request->tab;
        $programId = $request->program;

        $tahun = $request->tahun;
        if ($tahun === '' || $tahun === null) {
            $tahun = null;
        }

        // SALDO
        if ($tab === 'saldo') {
            $data = $this->danaTerikat->getSaldoData($programId ? (int)$programId : null, $tahun ? (int)$tahun : null);
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
                ->when($programId, fn($q) => $q->where('dana_terikat_penerima.program_id', $programId))
                ->when($tahun, fn($q) => $q->where('dana_terikat_penerima.tahun_program', $tahun));

            return DataTables::of($query)
                ->addColumn('program_nama', fn($row) => $row->program_nama ?? 'Program Dihapus')
                ->editColumn('kategori', fn($row) => ucfirst($row->kategori))
                ->editColumn('alamat', fn($row) => $row->alamat)
                ->editColumn('rt_rw', fn($row) => $row->rt_rw)
                ->filterColumn('rt_rw', function ($query, $keyword) {
                    $keyword = strtolower($keyword);
                    $query->whereRaw("LOWER(CONCAT(dana_terikat_penerima.rt, '/', dana_terikat_penerima.rw)) LIKE ?", ["%{$keyword}%"]);
                })
                ->editColumn('status_yatim', function ($row) {
                    return $row->status_yatim
                        ? '<span class="badge bg-success">Yatim</span>'
                        : '-';
                })
                ->addColumn('umur', function ($row) {
                    return $row->umur ? $row->umur . ' th' : '-';
                })
                ->editColumn('nominal_bulanan', fn($row) =>
                    'Rp ' . number_format($row->nominal_bulanan, 0, ',', '.')
                )
                ->editColumn('status_aktif', fn($row) => $row->status_aktif
                    ? '<span class="badge bg-success">Aktif</span>'
                    : '<span class="badge bg-secondary">Nonaktif</span>'
                )
                ->addColumn('aksi', fn($row) =>
                    '<button class="btn btn-sm btn-warning edit-penerima" data-id="'.$row->id.'">
                        <i class="fas fa-edit"></i>
                    </button>'
                )
                ->rawColumns(['status_yatim', 'status_aktif', 'aksi'])
                ->make(true);
        }

        // PENERIMAAN
        if ($tab === 'penerimaan') {
            $query = $this->danaTerikat->getPenerimaanQuery($programId ? (int)$programId : null, $tahun ? (int)$tahun : null);

            return DataTables::of($query)
                ->editColumn('tanggal', fn($row) => Carbon::parse($row->tanggal)->format('d M Y'))
                ->addColumn('program_nama', fn($row) => $row->program_nama ?? 'Program Dihapus')
                ->editColumn('jumlah', fn($row) => 'Rp ' . number_format($row->jumlah, 0, ',', '.'))
                ->make(true);
        }

                // REALISASI
        if ($tab === 'realisasi') {
            $queryNormal = DanaTerikatRealisasi::with(['program', 'penerima'])
                ->when($programId, fn($q) => $q->where('program_id', $programId))
                ->when($tahun, fn($q) => $q->where('tahun', $tahun));

            $queryKoreksi = DanaTerikatRealisasiKoreksi::with('program')
                ->when($programId, fn($q) => $q->where('program_id', $programId))
                ->when($tahun, fn($q) => $q->where('tahun', $tahun));

            $data = $queryNormal->get()->map(function ($item) {
                $item->bulan_tahun   = Carbon::create($item->tahun, $item->bulan, 1)->translatedFormat('F Y');
                $item->program_nama  = $item->program->nama_program ?? 'Program Dihapus';
                $item->penerima_nama = $item->penerima?->nama ?? 'Tidak diketahui';
                $item->jumlah_tampil = $item->jumlah;
                $item->tipe          = 'normal';
                $item->kwitansi      = $item->penerima_id ? 'ada' : null;
                return $item;
            });

            $koreksi = $queryKoreksi->get()->map(function ($item) {
                $item->bulan_tahun   = Carbon::create($item->tahun, $item->bulan, 1)
                    ->translatedFormat('F Y') . ' <span class="text-muted small">(Koreksi)</span>';
                $item->program_nama  = $item->program->nama_program ?? 'Program Dihapus';
                $item->penerima_nama = $item->keterangan ?? 'Koreksi Umum';
                $item->jumlah_tampil = $item->jumlah_koreksi;
                $item->tipe          = 'koreksi';
                $item->kwitansi      = null;
                return $item;
            });

            $merged = $data->concat($koreksi)
                ->sortByDesc('tahun')
                ->sortByDesc('bulan')
                ->sortBy(function ($item) {
                    return $item->tipe === 'normal' ? 1 : 0;
                })
                ->values(); // INI PENTING!

            // PASTIKAN SELALU RETURN ARRAY, BUKAN NULL!
            return response()->json($merged->toArray());
        }

        return response()->json([], 400);
    }

    // === TERIMA DANA ===
    public function storePenerimaan(Request $request)
    {
        $request->validate([
            'program_id'   => 'required|exists:dana_terikat_program,id',
            'tanggal'      => 'required|date',
            'jumlah'       => 'required|numeric|min:1',
            'donatur_nama' => 'required'
        ]);

        try {
            $this->danaTerikat->storePenerimaan($request->all());

            return response()->json(['message' => 'Dana terikat berhasil dicatat!']);
        } catch (\Throwable $e) {
            Log::error('Gagal mencatat penerimaan dana terikat', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mencatat jurnal dana terikat.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    // === PENERIMA ===
    public function storePenerima(Request $request)
    {
        $validated = $request->validate([
            'program_id'      => 'required',
            'tahun_program'   => 'required',
            'nama'            => 'required',
            'nominal_bulanan' => 'required|numeric',
            'kategori'        => 'required|in:yatim,dhuafa,lainnya',
            'tanggal_lahir'   => 'required_if:kategori,yatim|nullable|date',
            'rt'              => 'nullable|string|max:5',
            'rw'              => 'nullable|string|max:5',
            'nama_rt'         => 'nullable|string|max:255',
            'status_aktif'    => 'nullable',
            'referensi_id'    => 'nullable|integer|exists:dana_terikat_referensi,id'
        ], [
            'tanggal_lahir.required_if' => 'Tanggal lahir wajib diisi untuk kategori yatim.',
        ]);

        try {
            $this->danaTerikat->storePenerima($validated);

            return response()->json([
                'message' => 'Penerima berhasil ditambahkan',
            ]);
        } catch (\RuntimeException $e) {
            // error usia yatim
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            // fallback error tak terduga
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
            'program_id'      => 'required',
            'tahun_program'   => 'required',
            'nama'            => 'required',
            'nominal_bulanan' => 'required|numeric',
            'kategori'        => 'required|in:yatim,dhuafa,lainnya',
            'tanggal_lahir'   => 'nullable|date', // kalau mau pakai required_if juga boleh
            'rt'              => 'nullable|string|max:5',
            'rw'              => 'nullable|string|max:5',
            'nama_rt'         => 'nullable|string|max:255',
            'status_aktif'    => 'nullable',
            'referensi_id'    => 'nullable|integer|exists:dana_terikat_referensi,id'
        ]);

        try {
            $this->danaTerikat->updatePenerima((int) $id, $validated);

            return response()->json([
                'message' => 'Penerima berhasil diupdate',
            ]);
        } catch (\RuntimeException $e) {
            // error khusus (misal usia yatim > 14)
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            // fallback error tak terduga
            return response()->json([
                'message' => 'Terjadi kesalahan server',
            ], 500);
        }
    }

    // === REALISASI ===
    public function realisasi(Request $request)
    {
        $request->validate([
            'program_id' => 'required|exists:dana_terikat_program,id',
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer'
        ]);

        try {
            $this->danaTerikat->realisasiBulanan(
                (int)$request->program_id,
                (int)$request->bulan,
                (int)$request->tahun
            );

            return response()->json([
                'message' => 'Realisasi berhasil! Tidak ada duplikat.'
            ]);

        } catch (\Exception $e) {
            \Log::warning('Realisasi gagal (mungkin duplikat)', [
                'error' => $e->getMessage(),
                'program_id' => $request->program_id,
                'bulan' => $request->bulan,
                'tahun' => $request->tahun
            ]);

            return response()->json([
                'message' => $e->getMessage(),
                'type'    => 'warning'
            ], 409); // 409 = Conflict (sudah ada)
        }
    }

    public function penerimaAktif(Request $request)
    {
        $penerima = DanaTerikatPenerima::where('program_id', $request->program_id)
            ->where('tahun_program', $request->tahun)
            ->where('status_aktif', 1)
            ->select('id', 'nama', 'nominal_bulanan')
            ->orderBy('nama')
            ->get();

        return response()->json($penerima);
    }

    public function koreksiStore(Request $request)
    {
        $request->validate([
            'program_id'   => 'required|exists:dana_terikat_program,id',
            'tahun'        => 'required|integer',
            'bulan'        => 'required|integer|min:1|max:12',
            'jumlah'       => 'required|integer',
            'keterangan'   => 'required|string|max:255',
        ]);

        $this->danaTerikat->koreksiRealisasi(
            $request->program_id,
            $request->tahun,
            $request->bulan,
            $request->jumlah,
            $request->keterangan
        );

        return response()->json(['message' => 'Koreksi realisasi berhasil dicatat!']);
    }

    // === TAMBAH PROGRAM ===
    public function storeProgram(Request $request)
    {
        $request->validate([
            'kode_program'       => 'required|unique:dana_terikat_program',
            'nama_program'       => 'required',
            'akun_liabilitas_id' => 'required|exists:akun_keuangan,id'
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
            ->where('nama', 'like', '%' . $nama . '%')
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
                'nama_rt'
            ]);

        return response()->json($list);
    }

    // === DROPDOWN AKUN LIABILITAS ===
    public function akunOptions()
    {
        return $this->danaTerikat->getAkunOptionsHtml();
    }
}
