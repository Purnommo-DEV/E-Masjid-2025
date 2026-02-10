<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Interfaces\SaldoAwalRepositoryInterface;
use App\Models\AkunKeuangan;
use App\Models\SaldoAwalPeriode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SaldoAwalController extends Controller
{
    protected $repo;
    public function __construct(SaldoAwalRepositoryInterface $repo) { $this->repo = $repo; }

    public function index()
    {
        $periodeTerakhir = $this->repo->allPeriodes()->first();

        $akuns = AkunKeuangan::whereIn('tipe', ['aset', 'liabilitas'])->orderBy('kode')->get();

        // Default semua 0
        $saldoAwal = $akuns->pluck('id')->flip()->map(fn() => 0)->toArray();

        if ($periodeTerakhir) {
            // Ambil detail dengan query langsung (lebih aman)
            $details = \App\Models\SaldoAwalDetail::where('saldo_awal_periode_id', $periodeTerakhir->id)
                ->get(['akun_id', 'jumlah']);  // <-- ambil kolom 'jumlah'

            foreach ($details as $detail) {
                if (array_key_exists($detail->akun_id, $saldoAwal)) {
                    $saldoAwal[$detail->akun_id] = (float) $detail->jumlah;  // cast ke float/int
                }
            }
        }

        return view('masjid.'.masjid().'.admin.keuangan.saldo-awal.index', compact(
            'periodeTerakhir',
            'akuns',
            'saldoAwal'
        ));
    }

    public function store(Request $request) {
        $request->validate([
            'periode'  => 'required|date',
            'saldo.*'  => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, &$periode) {
            // simpan draft dulu
            $periode = $this->repo->simpanDraft($request->all());

            // kalau lock = 1, langsung buat jurnal pembuka
            if ($request->lock) {
                $this->repo->lockPeriode($periode->id);
            }
        });

        if ($request->lock) {
            return response()->json([
                'success'=> true,
                'message'=> 'Saldo Awal di-lock & jurnal pembuka otomatis dibuat!'
            ]);
        }

        return response()->json([
            'success'=> true,
            'message'=> 'Draft berhasil disimpan'
        ]);
    }

    public function createNewPeriod()
    {
        $periodeBaru = $this->repo->createNewPeriod();

        return response()->json([
            'success' => true,
            'message' => 'Periode baru berhasil dibuat: ' . $periodeBaru->periode->format('d M Y') . '. Silakan input saldo awal.'
        ]);
    }
}
