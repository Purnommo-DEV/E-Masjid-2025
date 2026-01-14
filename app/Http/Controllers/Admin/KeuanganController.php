<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Interfaces\KeuanganRepositoryInterface;
use Illuminate\Http\Request;
use App\Models\{SaldoAwal, JenisKotakInfak, KategoriKeuangan, Transaksi, KotakInfak};
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class KeuanganController extends Controller
{
    protected $keuangan;

    public function __construct(KeuanganRepositoryInterface $keuangan)
    {
        $this->keuangan = $keuangan;
    }

    public function index()
    {
        $kategori = KategoriKeuangan::all();

        $periodeSekarang = now()->format('Y-m');
        $saldoInfo = $this->keuangan->ambilSaldoAwal($periodeSekarang);

        // Hitung saldo akhir bulan ini
        $saldoAkhir = $this->keuangan->hitungSaldo(
            now()->startOfMonth(),
            now()->endOfMonth()
        )['saldoAkhir'];

        return view('masjid.'.masjid().'.admin.keuangan.index', compact(
            'kategori', 
            'saldoInfo',
            'saldoAkhir'
        ));
    }

    public function getKotakList()
    {
        $query = KotakInfak::with(['jenis_kotak', 'details', 'media'])
            ->select('kotak_infaks.*')
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'asc');

        return DataTables::of($query)
            ->addColumn('tanggal_group', fn($k) => $k->tanggal->format('d M Y'))
            ->addColumn('tanggal_raw', fn($k) => $k->tanggal->toDateString())
            ->addColumn('jenis', fn($k) => $k->jenis_kotak->nama ?? '-')
            ->addColumn('jumlah', fn($k) => 'Rp ' . number_format($k->total, 0, ',', '.'))
            ->addColumn('sudah_dihitung', fn($k) => $k->transaksi_id !== null)
            ->addColumn('detail_btn', function($k) {
                if ($k->details->isEmpty()) return '<span class="text-muted">—</span>';

                $buktiUrl = $k->getFirstMediaUrl('bukti_kotak'); // INI SUDAH PAKAI CUSTOM PATH!

                return '<button class="btn btn-sm btn-primary detail-kotak-btn rounded-circle shadow-sm"
                                data-kotak=\''.json_encode([
                                    "jenis"   => $k->jenis_kotak->nama,
                                    "total"   => $k->total,
                                    "bukti"   => $buktiUrl ?: null, // LANGSUNG URL YANG SUDAH BENAR!
                                    "details" => $k->details->map(fn($d) => [
                                        "nominal"  => $d->nominal,
                                        "lembar"   => $d->jumlah_lembar,
                                        "subtotal" => $d->subtotal
                                    ])->toArray()
                                ], JSON_UNESCAPED_SLASHES).'\'>
                            <i class="fas fa-eye"></i>
                        </button>';
            })
            ->addColumn('aksi', fn() => '') // kosong, tombol ada di rowGroup
            ->rawColumns(['detail_btn'])
            ->make(true);
    }

    public function recountHari(Request $request)
    {
        $request->validate(['tanggal' => 'required|date']);

        try {
            $result = $this->keuangan->recountHari($request->tanggal);

            return response()->json([
                'success' => true,
                'message' => $result['message']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function storeSaldoAwal(Request $request)
    {
        $request->validate([
            'periode' => 'required|date_format:Y-m',
            'jumlah' => 'required|numeric'
        ]);

        $saldo = $this->keuangan->simpanKoreksiSaldoAwal(
            $request->periode,
            $request->jumlah,
            'Koreksi manual oleh ' . auth()->user()->name
        );

        return response()->json([
            'success' => true,
            'message' => 'Saldo awal berhasil dikoreksi!',
            'saldo' => $saldo->jumlah,
            'saldo_formatted' => 'Rp ' . number_format($saldo->jumlah, 0, ',', '.')
        ]);
    }

    public function cekSaldoAwal(Request $request)
    {
        $manual = $this->keuangan->cekSaldoAwalManual($request->periode);
        return response()->json([
            'manual' => $manual
        ]);
    }

    public function storeTransaksi(Request $request)
    {
        $request->validate([
            'kategori_id' => 'required',
            'jumlah' => 'required|numeric',
            'tanggal' => 'required|date',
            'deskripsi' => 'required',
            'bukti' => 'nullable|image|max:2048'
        ]);

        // ambil semua input, tapi bila ada file, FormData akan dikirim dari JS
        $data = $request->all();

        // pastikan created_by diisi
        $data['created_by'] = auth()->id();

        $transaksi = $this->keuangan->createTransaksi($data);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Transaksi disimpan!',
                'data' => [
                    'id' => $transaksi->id,
                ]
            ]);
        }

        return back()->with('success', 'Transaksi disimpan!');
    }

    public function data(Request $request)
    {
        // Validasi & fallback
        $start = $request->filled('start_date') ? $request->start_date : now()->startOfMonth()->format('Y-m-d');
        $end   = $request->filled('end_date') ? $request->end_date : now()->format('Y-m-d');

        $transaksi = $this->keuangan->getTransaksiForDataTable($start, $end);

        return DataTables::of($transaksi)
            ->addColumn('tanggal', fn($t) => $t->tanggal->format('d/m/Y'))
            ->addColumn('kategori', fn($t) => '<span class="badge bg-' . ($t->kategori->tipe == 'pemasukan' ? 'success' : 'danger') . '">' . $t->kategori->nama . '</span>')
            ->addColumn('jumlah', fn($t) => 'Rp ' . number_format($t->jumlah, 0, ',', '.'))
            ->addColumn('deskripsi', fn($t) => '<small>' . Str::limit($t->deskripsi, 60) . '</small>')
            ->addColumn('dibuat_oleh', fn($t) => $t->creator->name ?? 'Admin')
            ->addColumn('bukti', function($t) {


                $url = $t->getBuktiUrl();
                $thumb = $t->getBuktiThumbUrl() ?: $url;

                return $url
                    ? '<a href="'.$url.'" target="_blank"><img src="'.$thumb.'" width="40" class="rounded"></a>'
                    : '—';
            })
            ->addColumn('saldo_berjalan', function($t) {
                $saldo = $t->saldo_berjalan;
                $class = $saldo >= 0 ? 'text-success' : 'text-danger';
                return '<strong class="' . $class . '">Rp ' . number_format($saldo, 0, ',', '.') . '</strong>';
            })
            ->addColumn('saldo_berjalan_raw', function($t) {
                return $t->saldo_berjalan; // angka murni utk JS
            })
            ->addColumn('aksi', function($t) {
                return '
                    <button onclick="editTransaksi(' . $t->id . ')" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button onclick="hapusTransaksi(' . $t->id . ')" class="btn btn-sm btn-danger">
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                ';
            })

            ->rawColumns([
                'kategori', 
                'deskripsi', 
                'bukti', 
                'saldo_berjalan',
                'saldo_berjalan_raw', 
                'aksi'])
            ->make(true);
    }

    public function editTransaksi($id)
    {
        $transaksi = Transaksi::with('media')->findOrFail($id);

        return response()->json([
            'id' => $transaksi->id,
            'kategori_id' => $transaksi->kategori_id,
            'jumlah' => $transaksi->jumlah,
            'tanggal' => $transaksi->tanggal->format('Y-m-d'),
            'deskripsi' => $transaksi->deskripsi,
            'bukti_url' => $transaksi->getFirstMediaUrl('bukti'),
            'bukti_thumb' => $transaksi->getFirstMediaUrl('bukti'), // thumb tidak didukung
        ]);
    }

    public function updateTransaksi(Request $request, $id)
    {
        $request->validate([
            'kategori_id' => 'required',
            'jumlah' => 'required|numeric',
            'tanggal' => 'required|date',
            'deskripsi' => 'required',
            'bukti' => 'nullable|image|max:2048',
        ]);

        $this->keuangan->updateTransaksi($id, $request->all());
        return response()->json([
            'success' => true, 
            'message' => 'Transaksi diperbarui!'
        ]);
    }

    public function destroyTransaksi($id)
    {
        $this->keuangan->deleteTransaksi($id);
        return response()->json([
            'success' => true, 
            'message' => 'Transaksi dihapus!'
        ]);
    }

    public function kotak()
    {
        $jenis = JenisKotakInfak::all();
        return view('masjid.'.masjid().'.admin.keuangan.kotak', compact(
            'jenis'
        ));
    }

    public function storeKotak(Request $request)
    {
        $request->validate([
            'jenis_kotak_id' => 'required|exists:jenis_kotak_infaks,id',
            'tanggal' => 'required|date',
            'nominal.*' => 'nullable|integer',
            'lembar.*' => 'nullable|integer|min:0',
            'bukti' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        $data = $request->all();
        $data['created_by'] = auth()->id();

        try {
            $kotak = $this->keuangan->hitungKotak($data);

            return response()->json([
                'success' => true,
                'message' => 'Kotak infak berhasil disimpan & masuk ke transaksi harian!',
                'data' => ['id' => $kotak->id]
            ]);
        } catch (\Exception $e) {
            \Log::error('Gagal simpan kotak: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data. Silakan coba lagi.'
            ], 500);
        }
    }

    public function laporan(Request $request)
    {
        $start = $request->start ?? now()->startOfMonth();
        $end = $request->end ?? now()->endOfMonth();
        $data = $this->keuangan->hitungSaldo($start, $end);
        $transaksi = Transaksi::with('kategori')->whereBetween('tanggal', [$start, $end])->get();

        return view('masjid.'.masjid().'.admin.keuangan.laporan', compact(
            'data', 
            'start', 
            'end', 
            'transaksi'
        ));
    }

    public function exportPdf(Request $request)
    {
        $start = $request->start ?? now()->startOfMonth();
        $end = $request->end ?? now()->endOfMonth();
        $data = $this->keuangan->hitungSaldo($start, $end);
        $transaksi = Transaksi::with('kategori')->whereBetween('tanggal', [$start, $end])->get();

        $pdf = \PDF::loadView('masjid.'.masjid().'.admin.keuangan.pdf', compact(
            'data', 
            'start', 
            'end', 
            'transaksi'
        ));
        
        return $pdf->download('Laporan_Keuangan_' . $start->format('M_Y') . '.pdf');
    }
}