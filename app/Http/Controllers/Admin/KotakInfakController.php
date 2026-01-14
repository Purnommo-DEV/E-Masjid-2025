<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Interfaces\KotakInfakRepositoryInterface;
use Illuminate\Http\Request;
use App\Models\{AkunKeuangan, JenisKotakInfak, KotakInfak};
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class KotakInfakController extends Controller
{
    protected $kotakInfak;

    public function __construct(KotakInfakRepositoryInterface $kotakInfak)
    {
        $this->kotakInfak = $kotakInfak;
    }

    public function data()
    {
        return $this->kotakInfak->getKotakList();
    }

    public function index()
    {
        $jenis = JenisKotakInfak::all();
        return view('masjid.'.masjid().'.admin.keuangan.kotak-infak.index', compact(
            'jenis'
        ));
    }

    public function storeKotak(Request $request)
    {
        $request->validate([
            'akun_pendapatan_id'  => 'required|exists:akun_keuangan,id', // BARU!
            'tanggal'             => 'required|date',
            'nominal.*'           => 'nullable|integer',
            'lembar.*'            => 'nullable|integer|min:0',
            'bukti_kotak'         => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        // Validasi akun harus pendapatan
        $akun = AkunKeuangan::findOrFail($request->akun_pendapatan_id);
        if ($akun->tipe !== 'pendapatan') {
            return response()->json(['success' => false, 'message' => 'Akun harus bertipe Pendapatan!'], 422);
        }

        $data = $request->all();
        $data['created_by'] = auth()->id();
        $data['akun_pendapatan_id'] = $akun->id; // tambah ini

        try {
            $kotak = $this->kotakInfak->hitungKotak($data); // sekarang sudah bawa akun_pendapatan_id

            return response()->json([
                'success' => true,
                'message' => 'Kotak infak berhasil disimpan & tercatat di jurnal keuangan!',
                'data'    => ['id' => $kotak->id, 'total' => $kotak->total]
            ]);
        } catch (\Exception $e) {
            \Log::error('Gagal simpan kotak: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data. ' . $e->getMessage()
            ], 500);
        }
    }
}