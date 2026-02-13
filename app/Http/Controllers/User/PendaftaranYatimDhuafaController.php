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

    public function dataTable(Request $request)
    {
        $query = PendaftaranAnakYatimDhuafa::query();

        // Filter tahun
        if ($request->tahun) {
            $query->where('tahun_program', $request->tahun);
        }

        // Filter umur + satuan
        if ($request->umur_value && $request->umur_satuan) {
            $query->where('umur', $request->umur_value)
                  ->where('umur_satuan', $request->umur_satuan);
        } elseif ($request->umur_value) {
            $query->where('umur', $request->umur_value);
        }

        // Filter jenis kelamin
        if ($request->jenis_kelamin) {
            $query->where('jenis_kelamin', $request->jenis_kelamin);
        }

        // Filter kategori
        if ($request->kategori) {
            $query->where('kategori', $request->kategori);
        }

        // Global search (multi kolom)
        if ($request->search) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', $search)
                  ->orWhere('nama_panggilan', 'like', $search)
                  ->orWhere('nama_orang_tua', 'like', $search)
                  ->orWhere('sumber_informasi', 'like', $search)
                  ->orWhere('alamat', 'like', $search);
            });
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('kategori_display', function ($row) {
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
            ->addColumn('jenis_kelamin_display', fn($row) => $row->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan')
            ->addColumn('tanggal_lahir_formatted', fn($row) => $row->tanggal_lahir ? Carbon::parse($row->tanggal_lahir)->format('d-m-Y') : '-')
            ->addColumn('umur_display', function ($row) {

                // Jika ada tanggal lahir → hitung realtime
                if (!empty($row->tanggal_lahir)) {

                    $birth = Carbon::parse($row->tanggal_lahir);
                    $diff  = $birth->diff(now());

                    $tahun = $diff->y;
                    $bulan = $diff->m;
                    $hari  = $diff->d;

                    $parts = [];

                    if ($tahun > 0) {
                        $parts[] = $tahun . ' Tahun';
                    }

                    if ($bulan > 0) {
                        $parts[] = $bulan . ' Bulan';
                    }

                    if ($hari > 0) {
                        $parts[] = $hari . ' Hari';
                    }

                    if (empty($parts)) {
                        return '<span class="text-slate-400 italic">Baru lahir</span>';
                    }

                    return '<span class="font-medium text-slate-800">'
                            . implode(' · ', $parts) .
                           '</span>';
                }

                // Jika manual
                if (!empty($row->umur) && !empty($row->umur_satuan)) {

                    return '<span class="font-medium text-slate-700">'
                            . $row->umur . ' ' . ucfirst($row->umur_satuan) .
                           ' <span class="text-xs text-slate-400">(Manual)</span></span>';
                }

                return '<span class="text-slate-400 italic">Tidak diketahui</span>';
            })
            ->addColumn('nama_lengkap', fn($row) => $row->nama_lengkap)
            ->addColumn('nama_panggilan', fn($row) => $row->nama_panggilan ?? '-')
            ->addColumn('nama_orang_tua', fn($row) => $row->nama_orang_tua)
            ->addColumn('pekerjaan_orang_tua', fn($row) => $row->pekerjaan_orang_tua ?? '-')
            ->addColumn('alamat', fn($row) => $row->alamat)
            ->addColumn('no_wa', fn($row) => $row->no_wa ?? '-')
            ->addColumn('sumber_informasi', fn($row) => $row->sumber_informasi)
            ->addColumn('catatan_tambahan', fn($row) => $row->catatan_tambahan ?? '-')
            ->addColumn('tahun_program', fn($row) => $row->tahun_program)
            ->rawColumns(['kategori_display', 'umur_display'])
            ->make(true);
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
            'umur_satuan'         => 'nullable|in:tahun,bulan,hari',
            'jenis_kelamin'       => 'required|in:L,P',
            'alamat'              => 'required|min:2|max:255',
            'no_wa'               => 'nullable|regex:/^08[0-9]{8,12}$/',
            'nama_orang_tua'      => 'required|min:2|max:100',
            'pekerjaan_orang_tua' => 'nullable|max:100',
            'catatan_tambahan'     => 'nullable|string|max:500'
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
         * LOGIKA UMUR (VERSI FINAL)
         * ============================
         */
        if (!empty($data['tanggal_lahir'])) {

            $tglLahir = Carbon::parse($data['tanggal_lahir']);

            if ($tglLahir->isFuture()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tanggal lahir tidak valid'
                ], 422);
            }

            $now  = now();
            $diff = $tglLahir->diff($now);

            // ❌ Jika sudah masuk 14 tahun
            if ($diff->y >= 14) {

                return response()->json([
                    'success' => false,
                    'message' => "Usia melebihi batas maksimal (13 Tahun 11 Bulan). Saat ini: {$diff->y} Tahun {$diff->m} Bulan {$diff->d} Hari"
                ], 422);
            }

            // Simpan umur utama
            if ($diff->y > 0) {
                $data['umur'] = $diff->y;
                $data['umur_satuan'] = 'tahun';
            } elseif ($diff->m > 0) {
                $data['umur'] = $diff->m;
                $data['umur_satuan'] = 'bulan';
            } else {
                $data['umur'] = max($diff->d, 1);
                $data['umur_satuan'] = 'hari';
            }

        } else {

            // MODE MANUAL
            if (!isset($data['umur']) || !isset($data['umur_satuan'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Umur dan satuan wajib diisi jika tanggal lahir tidak diketahui'
                ], 422);
            }

            $umur = (int) $data['umur'];

            // Konversi manual ke estimasi tahun untuk validasi
            $tahunEstimasi = match($data['umur_satuan']) {
                'tahun' => $umur,
                'bulan' => floor($umur / 12),
                'hari'  => floor($umur / 365),
            };

            if ($tahunEstimasi >= 14) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usia melebihi batas maksimal 13 Tahun 11 Bulan'
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

    public function update(Request $request, $id)
    {
        $pendaftaran = PendaftaranAnakYatimDhuafa::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'kategori'            => 'required|in:yatim_dhuafa,dhuafa',
            'nama_lengkap'        => 'required|string|min:3|max:150',
            'nama_panggilan'      => 'nullable|string|max:60',
            'tanggal_lahir'       => 'nullable|date',
            'umur'                => 'nullable|integer|min:0|max:5000',
            'umur_satuan'         => 'nullable|in:tahun,bulan,hari',
            'jenis_kelamin'       => 'required|in:L,P',
            'alamat'              => 'required|string|min:5|max:255',
            'no_wa'               => 'nullable|regex:/^08[0-9]{8,12}$/',
            'nama_orang_tua'      => 'required|string|min:2|max:100',
            'pekerjaan_orang_tua' => 'nullable|string|max:100',
            'sumber_informasi'    => 'required|string|min:2|max:255',
            'catatan_tambahan'    => 'nullable|string|max:500',
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

        // =====================================
        // LOGIKA UMUR SAMA DENGAN STORE
        // =====================================
        if (!empty($data['tanggal_lahir'])) {

            $tglLahir = Carbon::parse($data['tanggal_lahir']);

            if ($tglLahir->isFuture()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tanggal lahir tidak boleh di masa depan'
                ], 422);
            }

            $now  = now();
            $diff = $tglLahir->diff($now);

            if ($diff->y >= 14) {
                return response()->json([
                    'success' => false,
                    'message' => "Usia melebihi batas maksimal (13 Tahun 11 Bulan). Saat ini: {$diff->y} Tahun {$diff->m} Bulan {$diff->d} Hari"
                ], 422);
            }

            if ($diff->y > 0) {
                $data['umur'] = $diff->y;
                $data['umur_satuan'] = 'tahun';
            } elseif ($diff->m > 0) {
                $data['umur'] = $diff->m;
                $data['umur_satuan'] = 'bulan';
            } else {
                $data['umur'] = max($diff->d, 1);
                $data['umur_satuan'] = 'hari';
            }

        } else {

            if (empty($data['umur']) || empty($data['umur_satuan'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jika tanggal lahir tidak diisi, umur dan satuan wajib diisi'
                ], 422);
            }

            $umur = (int) $data['umur'];

            $tahunEstimasi = match($data['umur_satuan']) {
                'tahun' => $umur,
                'bulan' => floor($umur / 12),
                'hari'  => floor($umur / 365),
            };

            if ($tahunEstimasi >= 14) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usia melebihi batas maksimal 13 Tahun 11 Bulan'
                ], 422);
            }
        }

        $pendaftaran->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diperbarui'
        ]);
    }

    public function edit($id)
    {
        $pendaftaran = PendaftaranAnakYatimDhuafa::where('id', $id)
            ->firstOrFail();

        return response()->json($pendaftaran);
    }

    public function destroy($id)
    {
        $data = PendaftaranAnakYatimDhuafa::findOrFail($id);
        $data->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil dihapus'
        ]);
    }

}