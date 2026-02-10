<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\PendaftaranAnakYatimDhuafa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class PendaftaranYatimDhuafaController extends Controller
{

    // Halaman publik (guest)
    public function indexPublik()
    {
        return view('masjid.' . masjid() . '.guest.pendaftaran.show');
    }

    public function index()
    {
        return view('masjid.'.masjid().'.guest.pendaftaran.index');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kategori'            => 'required|in:yatim_dhuafa,dhuafa',
            'nama_lengkap'        => 'required|min:3|max:150',
            'nama_panggilan'      => 'nullable|max:60',
            'sumber_informasi'    => 'nullable',
            'tanggal_lahir'       => 'nullable|date',
            // 'tanggal_lahir'        => 'required|date|before_or_equal:' . now()->subYears(0)->toDateString(),
            'umur'                => 'nullable|integer|min:0|max:13',
            'jenis_kelamin'       => 'required|in:L,P',
            'alamat'              => 'required|min:5|max:255',
            'no_wa'               => 'nullable|regex:/^08[0-9]{8,12}$/',
            'nama_orang_tua'      => 'required|min:3|max:100',
            'pekerjaan_orang_tua' => 'nullable|max:100',
        ], [
            'no_wa.regex' => 'Format nomor WA tidak valid (mulai dengan 08...)',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ada kesalahan input',
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        /**
         * ============================
         * LOGIKA UMUR (INTI MASALAH)
         * ============================
         */
        if (!empty($data['tanggal_lahir'])) {
            // Ada tanggal lahir → hitung umur otomatis
            $umur = Carbon::parse($data['tanggal_lahir'])->age;

            if ($umur > 13) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usia anak melebihi 13 tahun'
                ], 422);
            }

            $data['umur'] = $umur;
        } else {
            // Tanggal lahir kosong → umur HARUS dari input manual
            if (!isset($data['umur'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Umur wajib diisi jika tanggal lahir tidak diketahui'
                ], 422);
            }
        }

        $data['tahun_program'] = now()->year;
        $data['ip_address']    = $request->ip();

        /**
         * ============================
         * CEK DUPLIKAT (AMAN)
         * ============================
         */
        $duplikat = PendaftaranAnakYatimDhuafa::where('nama_lengkap', $data['nama_lengkap'])
            ->where('nama_orang_tua', $data['nama_orang_tua'])
            ->when(!empty($data['tanggal_lahir']), function ($q) use ($data) {
                $q->where('tanggal_lahir', $data['tanggal_lahir']);
            })
            ->exists();

        if ($duplikat) {
            return response()->json([
                'success' => false,
                'message' => 'Data ini sudah pernah didaftarkan sebelumnya.'
            ], 422);
        }

        PendaftaranAnakYatimDhuafa::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Pendaftaran berhasil dikirim. Terima kasih!'
        ]);
    }


    // DataTables untuk publik (kolom terbatas)
    public function dataTable(Request $request)
    {
        $query = PendaftaranAnakYatimDhuafa::query();

        if ($request->tahun) $query->where('tahun_program', $request->tahun);
        if ($request->umur_min) $query->where('umur', '>=', $request->umur_min);
        if ($request->umur_max) $query->where('umur', '<=', $request->umur_max);
        if ($request->jenis_kelamin) $query->where('jenis_kelamin', $request->jenis_kelamin);
        if ($request->kategori) $query->where('kategori', $request->kategori);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('nama_lengkap', fn($row) => $row->nama_lengkap)
            ->addColumn('nama_panggilan', fn($row) => $row->nama_panggilan ?? '-')
            ->addColumn('tanggal_lahir', function ($row) {
                return $row->tanggal_lahir
                    ? Carbon::parse($row->tanggal_lahir)->format('Y-m-d')
                    : '-';
            })
            ->addColumn('umur', fn($row) => $row->umur)
            ->addColumn('kategori', function ($row) {
                return match ($row->kategori) {
                    'yatim_dhuafa' => '<span style="
                            display:inline-block;
                            white-space:nowrap;
                            background-color:#06b6d4;
                            color:#ffffff;
                            padding:4px 12px;
                            border-radius:9999px;
                            font-size:12px;
                            font-weight:700;
                        ">
                        Yatim&nbsp;Dhuafa
                    </span>',

                    'dhuafa' => '<span style="
                            display:inline-block;
                            white-space:nowrap;
                            background-color:#10b981;
                            color:#ffffff;
                            padding:4px 12px;
                            border-radius:9999px;
                            font-size:12px;
                            font-weight:700;
                        ">
                        Dhuafa
                    </span>',

                    default => '-',
                };
            })
            ->addColumn('jenis_kelamin', fn($row) => $row->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan')
            ->addColumn('alamat', fn($row) => $row->alamat)
            ->addColumn('no_wa', fn($row) => $row->no_wa ?? '-')
            ->addColumn('nama_orang_tua', fn($row) => $row->nama_orang_tua)
            ->addColumn('pekerjaan_orang_tua', fn($row) => $row->pekerjaan_orang_tua ?? '-')
            ->addColumn('sumber_informasi', fn($row) => $row->sumber_informasi)
            ->addColumn('catatan_tambahan', fn($row) => $row->catatan_tambahan ?? '-')
            ->addColumn('tahun_program', fn($row) => $row->tahun_program)
            ->addColumn('status', fn($row) => match($row->status) {
                'baru' => '<span class="badge badge-warning">Baru</span>',
                'diterima' => '<span class="badge badge-success">Diterima</span>',
                'ditolak' => '<span class="badge badge-error">Ditolak</span>',
                default => '<span class="badge badge-ghost">Proses</span>',
            })
            ->rawColumns(['kategori', 'status'])
            ->make(true);
    }

    // DataTables untuk admin (tambah kolom action)
    public function dataTableAdmin(Request $request)
    {
        $query = PendaftaranAnakYatimDhuafa::query();

        if ($request->tahun) $query->where('tahun_program', $request->tahun);
        if ($request->umur_min) $query->where('umur', '>=', $request->umur_min);
        if ($request->umur_max) $query->where('umur', '<=', $request->umur_max);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('nama', fn($row) => $row->nama_lengkap . ($row->nama_panggilan ? " ({$row->nama_panggilan})" : ''))
            ->addColumn('umur', fn($row) => $row->umur . ' tahun')
            ->addColumn('kategori', fn($row) => $row->kategori == 'yatim_dhuafa' ? 'Yatim Dhuafa' : 'Dhuafa')
            ->addColumn('tahun_program', fn($row) => $row->tahun_program)
            ->addColumn('status', fn($row) => match($row->status) {
                'baru' => '<span class="badge badge-warning">Baru</span>',
                'diterima' => '<span class="badge badge-success">Diterima</span>',
                'ditolak' => '<span class="badge badge-error">Ditolak</span>',
                default => '<span class="badge badge-ghost">Proses</span>',
            })
            ->addColumn('action', function ($row) {
                return '
                    <button class="btn btn-sm btn-warning" onclick="openEditModal(' . $row->id . ')">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit
                    </button>
                ';
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function editGuest($id)
    {
        $pendaftaran = PendaftaranAnakYatimDhuafa::where('id', $id)
            ->firstOrFail();

        return response()->json($pendaftaran);
    }

    // Guest: update data miliknya sendiri
    public function updateGuest(Request $request, $id)
    {
        $pendaftaran = PendaftaranAnakYatimDhuafa::where('id', $id)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'kategori'             => 'required|in:yatim_dhuafa,dhuafa',
            'nama_lengkap'         => 'required|string|min:3|max:150',
            'nama_panggilan'       => 'nullable|string|max:60',
            'tanggal_lahir'        => 'nullable',
            'umur'                 => 'required|integer|min:0|max:13',
            // 'tanggal_lahir'        => 'required|date',
            'jenis_kelamin'        => 'required|in:L,P',
            'alamat'               => 'required|string|min:5|max:255',
            'no_wa'                => 'nullable|regex:/^08[0-9]{8,12}$/',
            'nama_orang_tua'       => 'required|string|min:3|max:100',
            'pekerjaan_orang_tua'  => 'nullable|string|max:100',
            'sumber_informasi'     => 'required|string|min:3|max:255',
            'catatan_tambahan'     => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ada kesalahan input',
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Validasi ulang umur
        $umurDihitung = Carbon::parse($data['tanggal_lahir'])->age;
        if ($umurDihitung > 13 || $umurDihitung < 0) {
            return response()->json([
                'success' => false,
                'message' => $umurDihitung < 0 ? 'Tanggal lahir tidak boleh di masa depan' : 'Usia melebihi batas maksimal 13 tahun'
            ], 422);
        }

        $data['umur'] = $umurDihitung > 0
            ? $umurDihitung
            : ($data['umur'] ?? null);

        $pendaftaran->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diperbarui'
        ]);
    }
}