<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Interfaces\ZakatRepositoryInterface;
use Illuminate\Http\Request;
use App\Models\ZakatTransaksi;

class ZakatController extends Controller
{
    public function __construct(protected ZakatRepositoryInterface $repo) {}

    public function index() { return view('masjid.'.masjid().'.admin.keuangan.zakat.index'); }
    public function data() { return $this->repo->dataTable(); }

    public function storePenerimaan(Request $request)
    {
        $rules = [
            'jenis_zakat.*' => 'required|in:zakat_fitrah,zakat_maal,fidyah,infaq,shodaqoh',
            'muzakki_utama' => 'required|string|max:100',
            'jumlah'        => 'required|numeric|min:1000',
            'tanggal'       => 'required|date',
            'akun_id'       => 'required|exists:akun_keuangan,id',
        ];

        // Kalau Zakat Fitrah dicentang → wajib isi daftar keluarga
        if ($request->has('jenis_zakat') && in_array('zakat_fitrah', $request->jenis_zakat)) {
            $rules['anggota_keluarga'] = 'required|array|min:1';
            $rules['anggota_keluarga.*'] = 'required|string|max:100';
            $rules['jiwa'] = 'required|array|min:1';
            $rules['jiwa.*'] = 'required|integer|min:1';
        }

        $request->validate($rules);

        $daftarKeluarga = [];

        if ($request->filled('anggota_keluarga') && in_array('zakat_fitrah', $request->jenis_zakat)) {
            foreach ($request->anggota_keluarga as $i => $nama) {
                $daftarKeluarga[] = [
                    'nama' => $nama,
                    'jiwa' => $request->jiwa[$i] ?? 1
                ];
            }
        }

        $this->repo->terima([
            'jenis_zakat'     => $request->jenis_zakat,
            'muzakki_utama'   => $request->muzakki_utama,
            'no_hp'           => $request->no_hp,
            'daftar_keluarga' => $daftarKeluarga, // kosong kalau bukan fitrah
            'total_jiwa'      => in_array('zakat_fitrah', $request->jenis_zakat) ? collect($daftarKeluarga)->sum('jiwa') : 0,
            'satuan_beras'    => $request->satuan_beras,
            'jumlah'          => $request->jumlah,
            'tanggal'         => $request->tanggal,
            'keterangan'      => $request->keterangan,
            'akun_id'         => $request->akun_id,
        ], $request);

        return response()->json([
            'success' => true,
            'message' => 'Zakat berhasil diterima & jurnal otomatis tercatat!'
        ]);
    }

    public function kwitansi($id)
    {
        $transaksi = ZakatTransaksi::findOrFail($id);
        return view('masjid.'.masjid().'.admin.keuangan.zakat.kwitansi', compact('transaksi'));
    }
}