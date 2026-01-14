<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AlokasiDana;
use App\Interfaces\JurnalRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AlokasiDanaController extends Controller
{
    protected $jurnal;
    public function __construct(JurnalRepositoryInterface $jurnal) { $this->jurnal = $jurnal; }

    public function index()
    {
        return view('masjid.'.masjid().'.admin.keuangan.alokasi-dana.index');
    }

    public function data()
    {
        $data = AlokasiDana::with(['creator', 'akunSumber', 'akunTujuan'])
                    ->select('alokasi_dana.*')
                    ->orderByDesc('tanggal'); // atau ->latest('tanggal') kalau field-nya ada

        return DataTables::of($data)
            ->editColumn('tanggal', function ($row) {
                return $row->tanggal
                    ? Carbon::parse($row->tanggal)->format('d/m/Y')
                    : '-';
            })
            ->editColumn('jumlah', function ($row) {
                return 'Rp ' . number_format($row->jumlah ?? 0, 0, ',', '.');
            })
            ->addColumn('dari_akun', function ($row) {
                if ($row->akunSumber) {

                    return '
                        <div class="inline-flex items-center bg-blue-50 text-blue-700 border border-blue-300 rounded-lg px-2 py-1 text-xs gap-1">
                            <span class="font-bold">' . e($row->akunSumber->kode) . '</span>
                            <span>&nbsp;' . e($row->akunSumber->nama) . '</span>
                        </div>
                    ';
                }

                return '<span class="badge badge-neutral text-xs px-2 py-1">—</span>';
            })
            ->addColumn('ke_akun', function ($row) {
                if ($row->akunSumber) {

                    return '
                        <div class="inline-flex items-center bg-green-50 text-green-700 border border-green-300 rounded-lg px-2 py-1 text-xs gap-1">
                            <span class="font-bold">' . e($row->akunSumber->kode) . '</span>
                            <span>&nbsp;' . e($row->akunSumber->nama) . '</span>
                        </div>
                    ';
                }

                return '<span class="badge badge-neutral text-xs px-2 py-1">—</span>';
            })
            ->addColumn('user', function ($row) {
                return $row->creator?->name ?? '-';
            })
            ->editColumn('keterangan', function ($row) {
                return Str::limit($row->keterangan ?? '-', 80);
            })
            ->rawColumns(['dari_akun', 'ke_akun'])
            ->make(true);

            // e() adalah helper Laravel untuk escape HTML.
            // Tujuannya: mencegah XSS (Cross Site Scripting).
    }

    public function store(Request $request)
    {
        $request->validate([
            'akun_sumber_id' => 'required',
            'akun_tujuan_id' => 'required|different:akun_sumber_id',
            'jumlah' => 'required|numeric|min:1',
            'tanggal'       => 'required|date',
            'keterangan' => 'required'
        ]);

        DB::transaction(function () use ($request) {
            $alokasi = AlokasiDana::create($request->all() + ['created_by' => auth()->id()]);

            $this->jurnal->alokasiDana(
                $request->tanggal,
                $request->akun_sumber_id,
                $request->akun_tujuan_id,
                $request->jumlah,
                $request->keterangan,
                $alokasi
            );
        });

        return response()->json(['success' => true, 'message' => 'Dana berhasil dialokasikan!']);
    }
}