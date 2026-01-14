<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Interfaces\SaldoAwalRepositoryInterface;
use App\Models\AkunKeuangan;
use Illuminate\Support\Facades\DB;

class SaldoAwalController extends Controller
{
    protected $repo;
    public function __construct(SaldoAwalRepositoryInterface $repo) { $this->repo = $repo; }

    public function index() {
        $periodeTerakhir = $this->repo->allPeriodes()->first();
        $akuns = AkunKeuangan::whereIn('tipe',['aset','liabilitas'])->orderBy('kode')->get();
        return view('masjid.'.masjid().'.admin.keuangan.saldo-awal.index', compact('periodeTerakhir','akuns'));
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
}
