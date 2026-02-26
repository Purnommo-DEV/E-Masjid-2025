<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Interfaces\ZakatRepositoryInterface;
use Illuminate\Http\Request;
use App\Models\ZakatTransaksi;
use App\Models\Muzakki;
use Illuminate\Support\Facades\DB;

class ZakatController extends Controller
{
    public function __construct(protected ZakatRepositoryInterface $repo) {}

    public function index() { return view('masjid.'.masjid().'.admin.keuangan.zakat.index'); }
    public function data() { return $this->repo->dataTable(); }

    public function storePenerimaan(Request $request)
    {
        $rules = [
            'muzakki_id' => 'required',
            'jenis_zakat' => 'required|in:zakat_fitrah,zakat_maal,fidyah,infaq,shodaqoh,wakaf,donasi_khusus',
            'tanggal' => 'required|date',
            'akun_id' => 'required|exists:akun_keuangan,id',
            'metode_bayar' => 'required|in:transfer,cash,beras,barang',
            // Validasi details (nominal wajib untuk jenis yang dipilih)
            'details.*.nominal' => 'required|numeric|min:1000',
        ];

        // Khusus fitrah: wajib detail jiwa
        if ($request->jenis_zakat === 'zakat_fitrah') {
            $rules['details.zakat_fitrah.jiwa'] = 'required|integer|min:1';
            $rules['details.zakat_fitrah.jumlah_beras'] = 'nullable|numeric';
            $rules['details.zakat_fitrah.satuan_beras'] = 'nullable|in:kg,liter';
        }

        $request->validate($rules);

        // Hitung total nominal dari details (karena tidak ada field 'jumlah' lagi)
        $totalNominal = 0;
        $detailsData = $request->details ?? [];
        foreach ($detailsData as $jenis => $detail) {
            $totalNominal += (int) ($detail['nominal'] ?? 0);
        }

        if ($totalNominal < 1000) {
            return response()->json(['errors' => ['details' => ['Total nominal minimal Rp1.000']]], 422);
        }

        // Handle muzakki_id (string baru atau ID existing)
        $muzakkiId = $request->muzakki_id;
        if (!is_numeric($muzakkiId)) {
            $muzakki = Muzakki::create([
                'nama' => $muzakkiId,
                'no_hp' => $request->no_hp ?? null,
                'status' => 'aktif',
            ]);
            $muzakkiId = $muzakki->id;
        } else {
            if (!Muzakki::where('id', $muzakkiId)->exists()) {
                return response()->json(['errors' => ['muzakki_id' => ['Muzakki ID tidak valid.']]], 422);
            }
        }

        // Siapkan data untuk repo
        $data = [
            'jenis_zakat' => $request->jenis_zakat,
            'muzakki_id' => $muzakkiId,
            'no_hp' => $request->no_hp,
            'tanggal' => $request->tanggal,
            'akun_id' => $request->akun_id,
            'metode_bayar' => $request->metode_bayar,
            'bukti' => $request->file('bukti'),
            'details' => $request->details ?? [],
            'total_nominal' => $totalNominal,
            'keterangan' => $request->keterangan ?? null,
        ];

        // Kalau fitrah → hitung total jiwa dari details
        if ($request->jenis_zakat === 'zakat_fitrah') {
            $data['total_jiwa'] = $request->details['zakat_fitrah']['jiwa'] ?? 0;
        }

        $this->repo->terima($data, $request);

        return response()->json([
            'success' => true,
            'message' => 'Zakat berhasil diterima & jurnal tercatat!'
        ]);
    }

    public function kwitansi($id)
    {
        $transaksi = ZakatTransaksi::findOrFail($id);
        return view('masjid.'.masjid().'.admin.keuangan.zakat.kwitansi', compact('transaksi'));
    }

    // Tambah: autocomplete muzakki
    public function searchMuzakki(Request $request)
    {
        $query = $request->query('query', '');
        $muzakki = Muzakki::where('nama', 'like', "%{$query}%")
                           ->orWhere('no_hp', 'like', "%{$query}%")
                           ->take(10)
                           ->get(['id', 'nama', 'no_hp']);
        return response()->json($muzakki);
    }

    public function editData($id)
    {
        $transaksi = ZakatTransaksi::with('details', 'muzakki')->findOrFail($id);

        // Ambil jenis dari detail pertama (satu jenis per transaksi)
        $jenisZakat = $transaksi->details->first() ? $transaksi->details->first()->jenis : null;

        $details = [];
        foreach ($transaksi->details as $d) {
            $details[$d->jenis] = [
                'nominal' => $d->nominal,
                'jiwa' => $d->jiwa,
                'jumlah_beras' => $d->jumlah_beras,
                'satuan_beras' => $d->satuan_beras,
                'keterangan_detail' => $d->keterangan_detail,
            ];
        }

        return response()->json([
            'id' => $transaksi->id,
            'muzakki_id' => $transaksi->muzakki_id,
            'muzakki_nama' => $transaksi->muzakki->nama,
            'no_hp' => $transaksi->muzakki->no_hp,
            'jenis_zakat' => $jenisZakat, // FIX: ambil dari detail pertama
            'details' => $details,
            'tanggal' => $transaksi->tanggal->format('Y-m-d'),
            'akun_id' => $transaksi->akun_id,
            'metode_bayar' => $transaksi->metode_bayar,
            'keterangan' => $transaksi->keterangan,
        ]);
    }
}