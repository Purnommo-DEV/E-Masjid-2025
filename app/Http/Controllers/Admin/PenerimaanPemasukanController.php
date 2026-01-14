<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PenerimaanPemasukan;
use App\Interfaces\JurnalRepositoryInterface;
use App\Models\AkunKeuangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class PenerimaanPemasukanController extends Controller
{
    protected $jurnal;

    public function __construct()
    {
        $this->jurnal = app(JurnalRepositoryInterface::class);
    }

    public function index()
    {
        return view('masjid.'.masjid().'.admin.keuangan.penerimaan.index');
    }

    public function data()
    {
        $data = PenerimaanPemasukan::with(['akunPendapatan', 'creator'])
                    ->latest()
                    ->get();

        return DataTables::of($data)
            ->editColumn('tanggal', fn($row) => Carbon::parse($row->tanggal)->format('d/m/Y'))
            ->editColumn('jumlah', fn($row) => 'Rp ' . number_format($row->jumlah, 0, ',', '.'))
            ->addColumn('akun_pendapatan', function ($row) {
                if ($row->akunPendapatan) {

                    return '
                        <div class="inline-flex items-center bg-red-50 text-red-700 border border-red-300 rounded-lg px-2 py-1 text-xs gap-1">
                            <span class="font-bold">' . e($row->akunPendapatan->kode) . '</span>
                            <span>&nbsp;' . e($row->akunPendapatan->nama) . '</span>
                        </div>
                    ';
                }

                return '<span class="badge badge-neutral text-xs px-2 py-1">—</span>';
            })
            ->addColumn('sumber', fn($row) => $row->sumber_nama ?: '-')
            ->addColumn('user', fn($row) => $row->creator->name)
            ->rawColumns(['jumlah', 'akun_pendapatan'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'akun_pendapatan_id' => 'required|exists:akun_keuangan,id',
            'jumlah'             => 'required|numeric|min:1000',
            'tanggal'            => 'required|date',
            'keterangan'         => 'required|string|max:500',
            'sumber_nama'        => 'nullable|string|max:255',
            'sumber_telepon'     => 'nullable|string|max:20',
            'keterangan_tambahan'=> 'nullable|string',
        ]);

        $akun = AkunKeuangan::findOrFail($request->akun_pendapatan_id);
        if ($akun->tipe !== 'pendapatan') {
            return response()->json(['message' => 'Harus memilih akun bertipe Pendapatan!'], 422);
        }

        DB::transaction(function () use ($request, $akun) {
            $deskripsi = $request->keterangan;
            if ($request->sumber_nama) {
                $deskripsi .= " | Dari: {$request->sumber_nama}";
                if ($request->sumber_telepon) $deskripsi .= " ({$request->sumber_telepon})";
                if ($request->keterangan_tambahan) $deskripsi .= " | {$request->keterangan_tambahan}";
            }

            $penerimaan = PenerimaanPemasukan::create([
                'akun_pendapatan_id' => $request->akun_pendapatan_id,
                'jumlah'             => $request->jumlah,
                'tanggal'            => $request->tanggal,
                'keterangan'         => $request->keterangan,
                'sumber_nama'        => $request->sumber_nama,
                'sumber_telepon'     => $request->sumber_telepon,
                'keterangan_tambahan'=> $request->keterangan_tambahan,
                'created_by'         => auth()->id(),
            ]);

            $this->jurnal->penerimaanPemasukan(
                $request->tanggal,
                $akun->id,
                $request->jumlah,
                $deskripsi,
                $penerimaan
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'Penerimaan pemasukan berhasil dicatat!'
        ]);
    }
}