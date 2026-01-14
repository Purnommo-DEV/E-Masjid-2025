<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Interfaces\JurnalRepositoryInterface;
use App\Models\AkunKeuangan;
use App\Models\Jurnal;
use App\Models\PengeluaranUmum;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PengeluaranController extends Controller
{
    protected $jurnal;

    public function __construct(JurnalRepositoryInterface $jurnal)
    {
        $this->jurnal = $jurnal;
    }

    public function index()
    {
        return view('masjid.'.masjid().'.admin.keuangan.pengeluaran.index');
    }

    public function data()
    {

        $data = PengeluaranUmum::with(['creator','akunBeban'])->latest()->get();

        return DataTables::of($data)
            ->editColumn('tanggal', fn($row) => Carbon::parse($row->tanggal)->format('d/m/Y'))
            ->editColumn('jumlah', fn($row) => 'Rp ' . number_format($row->jumlah, 0, ',', '.'))
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
            ->addColumn('user', fn($row) => $row->creator?->name ?? '-')
            ->rawColumns(['jumlah', 'akun_beban'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'akun_beban_id' => 'required|exists:akun_keuangan,id',
            'jumlah'        => 'required|numeric|min:1000',
            'tanggal'       => 'required|date',
            'keterangan'    => 'required|string|max:500',
        ]);

        // Cek apakah akun beban benar-benar "besar"
        $akun = AkunKeuangan::findOrFail($request->akun_beban_id);
        if ($akun->jenis_beban !== 'besar') {
            return response()->json([
                'success' => false,
                'message' => 'Akun ini hanya boleh digunakan di Petty Cash!'
            ], 422);
        }

        DB::transaction(function () use ($request, $akun) {
            // 1. Buat record di tabel pengeluaran_umum
            $pengeluaran = PengeluaranUmum::create([
                'akun_beban_id' => $request->akun_beban_id,
                'jumlah'        => $request->jumlah,
                'tanggal'       => $request->tanggal,
                'keterangan'    => $request->keterangan,
                'created_by'    => auth()->id(),
            ]);

            // 2. Otomatis buat jurnal lewat repository (sama persis seperti PettyCash)
            $this->jurnal->pengeluaranUmum(
                $request->tanggal,
                $request->akun_beban_id,
                $request->jumlah,
                $request->keterangan,
                $pengeluaran   // ← ini yang bikin jurnalable_id & type terisi otomatis
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'Pengeluaran umum berhasil dicatat!'
        ]);
    }
}