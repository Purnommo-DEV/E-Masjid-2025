<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PettyCash;
use App\Interfaces\JurnalRepositoryInterface;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\SaldoAwalPeriode;
use App\Models\SaldoAwalDetail;

class PettyCashController extends Controller
{
    protected $jurnal;

    public function __construct(JurnalRepositoryInterface $jurnal)
    {
        $this->jurnal = $jurnal;
    }

    public function index()
    {
        // SALDO KAS KECIL YANG BENAR = Saldo Awal + (Isi Ulang - Pengeluaran)
        $saldoAwalKasKecil = 0;

        // Ambil Saldo Awal Kas Kecil (akun_id = 10005 atau sesuai kode akun kas kecil kamu)
        $periodeLocked = SaldoAwalPeriode::where('status', 'locked')->latest()->first();
        if ($periodeLocked) {
            $saldoAwalKasKecil = SaldoAwalDetail::where('saldo_awal_periode_id', $periodeLocked->id)
                ->where('akun_id', akunIdByKode(10005)) // ← GANTI dengan ID akun Kas Kecil kamu
                ->sum('jumlah');
        }

        // Hitung dari transaksi petty cash
        $isiUlang    = PettyCash::where('tipe', 'isi_ulang')->sum('jumlah');
        $pengeluaran = PettyCash::where('tipe', 'pengeluaran')->sum('jumlah');

        // SALDO AKHIR YANG BENAR
        $saldo = $saldoAwalKasKecil + $isiUlang - $pengeluaran;

        return view('masjid.'.masjid().'.admin.keuangan.petty-cash.index', compact('saldo'));
    }

    public function saldo()
    {
        // SALDO KAS KECIL YANG BENAR = Saldo Awal + (Isi Ulang - Pengeluaran)
        $saldoAwalKasKecil = 0;

        // Ambil Saldo Awal Kas Kecil (akun_id = 10005 atau sesuai kode akun kas kecil kamu)
        $periodeLocked = SaldoAwalPeriode::where('status', 'locked')->latest()->first();
        if ($periodeLocked) {
            $saldoAwalKasKecil = SaldoAwalDetail::where('saldo_awal_periode_id', $periodeLocked->id)
                ->where('akun_id', akunIdByKode(10005)) // ← pastikan fungsi akunIdByKode tersedia
                ->sum('jumlah');
        }

        // Hitung dari transaksi petty cash
        $isiUlang    = PettyCash::where('tipe', 'isi_ulang')->sum('jumlah');
        $pengeluaran = PettyCash::where('tipe', 'pengeluaran')->sum('jumlah');

        // SALDO AKHIR
        $saldo = $saldoAwalKasKecil + $isiUlang - $pengeluaran;

        return response()->json(['saldo' => (int) $saldo]);
    }

    public function data()
    {
        $data = PettyCash::with(['user','akunBeban'])->latest()->get();

        return DataTables::of($data)
            ->addColumn('user', fn($row) => $row->creator?->name ?? '-')
            ->addColumn('akun_beban', function ($row) {
                if ($row->akunBeban) {

                    return '
                        <div class="inline-flex items-center bg-red-50 text-red-700 border border-red-300 rounded-lg px-2 py-1 text-xs gap-1">
                            <span class="font-bold">' . e($row->akunBeban->kode) . '</span>
                            <span>&nbsp;' . e($row->akunBeban->nama) . '</span>
                        </div>
                    ';
                }

                return '<span class="badge badge-neutral text-xs px-2 py-1">—</span>';
            })
            ->addColumn('tipe', function ($row) {

                if ($row->tipe === "isi_ulang") {
                    return '
                        <span class="badge badge-success text-xs px-2 py-1">Isi Ulang</span>
                    ';
                }

                if ($row->tipe === "pengeluaran") {
                    return '
                        <span class="badge badge-error text-xs px-2 py-1">Pengeluaran</span>
                    ';
                }

                return '<span class="badge badge-neutral text-xs px-2 py-1">—</span>';
            })
            ->rawColumns(['akun_beban'])
            ->editColumn('tanggal', fn($row) => \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y'))
            ->editColumn('jumlah', fn($row) => 'Rp ' . number_format($row->jumlah, 0, ',', '.'))
            ->rawColumns(['akun_beban', 'tipe'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipe'           => 'required|in:isi_ulang,pengeluaran',
            'jumlah'         => 'required|numeric|min:1',
            'tanggal'        => 'required|date',
            'keterangan'     => 'required|string',
            'akun_beban_id'  => 'required_if:tipe,pengeluaran'
        ]);

        DB::transaction(function () use ($request) {
            $petty = PettyCash::create($request->all() + ['created_by' => auth()->id()]);

            if ($request->tipe === 'isi_ulang') {
                $this->jurnal->isiUlangPettyCash(
                    $request->tanggal,
                    $request->jumlah,
                    $request->keterangan,
                    $petty
                );
            } else {
                $this->jurnal->pengeluaranDariPettyCash(
                    $request->tanggal,
                    $request->akun_beban_id,
                    $request->jumlah,
                    $request->keterangan,
                    $petty
                );
            }
        });

        return response()->json(['success' => true, 'message' => 'Petty Cash berhasil dicatat!']);
    }
}