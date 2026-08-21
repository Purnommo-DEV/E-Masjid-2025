<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Interfaces\SaldoAwalRepositoryInterface;
use App\Models\AkunKeuangan;
use App\Models\SaldoAwalDetail;
use App\Models\SaldoAwalPeriode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaldoAwalController extends Controller
{
    protected $repo;

    public function __construct(SaldoAwalRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $periodeTerakhir = $this->repo->allPeriodes()->first();

        $akuns = AkunKeuangan::whereIn('tipe', ['aset', 'liabilitas'])->orderBy('kode')->get();
        $ekuitas = AkunKeuangan::where('tipe', 'ekuitas')->orderBy('kode')->get();

        // Default semua 0
        $saldoAwal = $akuns->pluck('id')->flip()->map(fn () => 0)->toArray();

        $lawanMap = [];
        if ($periodeTerakhir) {
            // Ambil detail dengan query langsung (lebih aman)
            $details = SaldoAwalDetail::where('saldo_awal_periode_id', $periodeTerakhir->id)
                ->get(['akun_id', 'jumlah']);  // <-- ambil kolom 'jumlah'

            foreach ($details as $detail) {
                if (array_key_exists($detail->akun_id, $saldoAwal)) {
                    $saldoAwal[$detail->akun_id] = (float) $detail->jumlah;  // cast ke float/int
                }
            }
        }

        // Ambil mapping lawan yang sudah tersimpan
        if ($periodeTerakhir) {
            $lawanMap = SaldoAwalDetail::where('saldo_awal_periode_id', $periodeTerakhir->id)
                ->pluck('lawan_akun_id', 'akun_id')
                ->toArray();
        }

        return view('masjid.'.masjid().'.admin.keuangan.saldo-awal.index', compact(
            'periodeTerakhir',
            'akuns',
            'ekuitas',
            'saldoAwal',
            'lawanMap'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'periode' => 'required|date',
            'saldo.*' => 'nullable|numeric|min:0',
            'lawan.*' => 'nullable|exists:akun_keuangan,id',
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
                'success' => true,
                'message' => 'Saldo Awal di-lock & jurnal pembuka otomatis dibuat!',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Draft berhasil disimpan',
        ]);
    }

    public function getData(Request $request)
    {
        $periode = SaldoAwalPeriode::latest()->first();

        if (! $periode) {
            return response()->json([]);
        }

        $details = SaldoAwalDetail::where('saldo_awal_periode_id', $periode->id)
            ->with('akun')
            ->get();

        $data = $details->filter(function ($item) {
            $kode = $item->akun->kode ?? '';

            return str_starts_with($kode, '2') || $kode == '10003';
        })->map(function ($item) {
            return [
                'akun_id' => $item->akun_id,
                'kode' => $item->akun->kode,
                'nama' => $item->akun->nama,
                'jumlah' => $item->jumlah,
            ];
        })->values();

        return response()->json($data);
    }

    // 🔥 Update saldo awal
    public function updateSaldo(Request $request)
    {
        $request->validate([
            'saldo.*' => 'nullable|numeric|min:0',
        ]);

        $periode = SaldoAwalPeriode::latest()->first();

        if (! $periode) {
            return response()->json(['message' => 'Periode tidak ditemukan'], 404);
        }

        if ($periode->status === 'locked') {
            return response()->json(['message' => 'Periode sudah di-lock, tidak bisa diedit!'], 400);
        }

        DB::transaction(function () use ($request, $periode) {
            foreach ($request->saldo as $akunId => $jumlah) {
                $jumlahBersih = (float) str_replace('.', '', $jumlah);

                if ($jumlahBersih == 0) {
                    SaldoAwalDetail::where('saldo_awal_periode_id', $periode->id)
                        ->where('akun_id', $akunId)
                        ->delete();
                } else {
                    SaldoAwalDetail::updateOrCreate(
                        [
                            'saldo_awal_periode_id' => $periode->id,
                            'akun_id' => $akunId,
                        ],
                        [
                            'jumlah' => $jumlahBersih,
                        ]
                    );
                }
            }
        });

        return response()->json([
            'message' => 'Saldo awal berhasil diupdate!',
        ]);
    }

    public function createNewPeriod()
    {
        $periodeBaru = $this->repo->createNewPeriod();

        return response()->json([
            'success' => true,
            'message' => 'Periode baru berhasil dibuat: '.$periodeBaru->periode->format('d M Y').'. Silakan input saldo awal.',
        ]);
    }
}
