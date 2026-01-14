<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Interfaces\AkunKeuanganRepositoryInterface;
use Illuminate\Http\Request;
use App\Models\AkunKeuangan;

class AkunKeuanganController extends Controller
{
    protected $repo;

    public function __construct(AkunKeuanganRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    // === 1. Halaman Index ===
    public function index()
    {
        return view('masjid.'.masjid().'.admin.keuangan.akun.index');
    }

    // === 2. Data untuk Datatables ===
    public function data()
    {
        return $this->repo->datatables(); // LANGSUNG PAKAI REPOSITORY
    }

    public function options(Request $request)
    {
        $akuns = AkunKeuangan::where('tipe', 'Liabilitas')
                    ->where('kode', 'like', '2%')
                    ->orderBy('kode')
                    ->get();

        $options = '<option value="">— Pilih Akun Liabilitas —</option>';
        foreach ($akuns as $a) {
            $options .= "<option value=\"{$a->id}\">{$a->kode} - {$a->nama}</option>";
        }

        return $options;
    }

    // === 3. Store (Tambah) ===
    public function store(Request $request)
    {
        $request->validate([
            'kode'         => 'required|unique:akun_keuangan,kode',
            'nama'         => 'required|string|max:150',
            'tipe'         => 'required|in:aset,liabilitas,ekuitas,pendapatan,beban',
            'saldo_normal' => 'required|in:debit,kredit',
            'keterangan'   => 'nullable|string|max:500',
            'jenis_beban'  => 'required_if:tipe,beban|in:kecil,besar',
            'grup'         => 'nullable|in:kotak_infak,zakat,donasi_besar', // TAMBAHAN
        ]);

        $data = $request->only(['kode', 'nama', 'tipe', 'saldo_normal', 'keterangan', 'grup']);

        $data['jenis_beban'] = $request->tipe === 'beban' 
            ? $request->jenis_beban 
            : 'tidak_berlaku';

        $this->repo->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Akun berhasil ditambahkan!'
        ]);
    }

    // === 4. Edit (ambil data untuk modal) ===
    public function edit($id)
    {
        $akun = AkunKeuangan::findOrFail($id);
        return response()->json($akun);
    }

    // === 5. Update ===
    public function update(Request $request, $id)
    {
        $request->validate([
            'kode'         => 'required|unique:akun_keuangan,kode,' . $id,
            'nama'         => 'required|string|max:150',
            'tipe'         => 'required|in:aset,liabilitas,ekuitas,pendapatan,beban',
            'saldo_normal' => 'required|in:debit,kredit',
            'keterangan'   => 'nullable|string|max:500',
            'jenis_beban'  => 'required_if:tipe,beban|in:kecil,besar',
            'grup'         => 'nullable|in:kotak_infak,zakat,donasi_besar', // TAMBAHAN
        ]);

        $data = $request->only(['kode', 'nama', 'tipe', 'saldo_normal', 'keterangan', 'grup']);

        $data['jenis_beban'] = $request->tipe === 'beban' 
            ? $request->jenis_beban 
            : 'tidak_berlaku';

        $this->repo->update($id, $data);

        return response()->json([
            'success' => true,
            'message' => 'Akun berhasil diperbarui!'
        ]);
    }

    // === 6. Delete ===
    public function destroy($id)
    {
        try {
            $akun = AkunKeuangan::findOrFail($id);
            
            // Cek apakah akun sudah dipakai di jurnal
            if ($akun->jurnals()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun tidak bisa dihapus karena sudah digunakan di jurnal!'
                ], 422);
            }

            $akun->delete();

            return response()->json([
                'success' => true,
                'message' => 'Akun berhasil dihapus!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus akun.'
            ], 500);
        }
    }
}
