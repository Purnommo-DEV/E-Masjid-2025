<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Interfaces\DanaAlokasiRepositoryInterface;
use App\Models\AkunKeuangan;
use Illuminate\Http\Request;

class DanaAlokasiController extends Controller
{
    protected $danaAlokasi;

    public function __construct(DanaAlokasiRepositoryInterface $danaAlokasi)
    {
        $this->danaAlokasi = $danaAlokasi;
    }

    public function store(Request $request)
    {
        // 🔥 BERSIHKAN TITIK & KOMA DI CONTROLLER
        $jumlahBersih = str_replace(['.', ','], '', $request->jumlah_alokasi);

        $request->merge([
            'jumlah_alokasi' => $jumlahBersih,
        ]);
        $request->validate([
            'program_id' => 'required|exists:dana_terikat_program,id',
            'tanggal' => 'required|date',
            'jumlah_alokasi' => 'required|numeric|min:1',
            'keterangan' => 'nullable|string|max:255',
            'bulan_cakupan' => 'nullable|integer|min:1|max:12',
        ]);

        // Default sumber: Zakat Maal (20002), tujuan: Dana Terikat (20004)
        $akunSumberId = $request->akun_sumber_id ?? AkunKeuangan::where('kode', '20002')->first()?->id;
        $akunTujuanId = $request->akun_tujuan_id ?? AkunKeuangan::where('kode', '20004')->first()?->id;

        $data = $request->all();
        $data['jumlah'] = $request->jumlah_alokasi;
        $data['akun_sumber_id'] = $akunSumberId;
        $data['akun_tujuan_id'] = $akunTujuanId;

        $this->danaAlokasi->alokasi($data);

        return response()->json([
            'success' => true,
            'message' => 'Alokasi dana berhasil!',
        ]);
    }

    public function riwayat(Request $request, $programId)
    {
        $riwayat = $this->danaAlokasi->getRiwayat($programId);

        return response()->json($riwayat);
    }

    public function hitungAlokasi(Request $request)
    {
        $request->validate([
            'per_bulan' => 'required|numeric|min:1',
            'bulan' => 'required|integer|min:1|max:12',
        ]);

        $total = $request->per_bulan * $request->bulan;

        return response()->json([
            'total' => $total,
            'per_bulan' => $request->per_bulan,
            'bulan' => $request->bulan,
            'formatted' => 'Rp '.number_format($total, 0, ',', '.'),
        ]);
    }
}
