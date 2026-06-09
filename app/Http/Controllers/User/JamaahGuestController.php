<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Jamaah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class JamaahGuestController extends Controller
{
    /**
     * Halaman publik form pendataan jamaah
     */
    public function indexPublik()
    {
        return view('masjid.' . masjid() . '.guest.pendataan-jamaah.index');
    }

    /**
     * Halaman admin untuk melihat data jamaah
     */
    public function index()
    {
        return view('masjid.' . masjid() . '.admin.pendataan-jamaah.index');
    }

    /**
     * Store data jamaah dari form publik
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_lengkap' => 'required|string|min:3|max:150',
            'nomor_whatsapp' => 'required|regex:/^08[0-9]{8,12}$/',
            'alamat_singkat' => 'nullable|string|max:255',
            'rt' => 'nullable|string|max:10',
            'rw' => 'nullable|string|max:10',
            'jenis_kelamin' => 'nullable|in:L,P',
            'tanggal_lahir' => 'nullable|date|before_or_equal:today',
            'bersedia_info_wa' => 'nullable|boolean',
            'minat_kajian_rutin' => 'nullable|boolean',
            'minat_tpa_tpq' => 'nullable|boolean',
            'minat_remaja_masjid' => 'nullable|boolean',
            'minat_kegiatan_sosial' => 'nullable|boolean',
            'minat_zakat_sedekah' => 'nullable|boolean',
            'minat_qurban' => 'nullable|boolean',
            'minat_kerelawanan' => 'nullable|boolean',
            'minat_lainnya' => 'nullable|boolean',
            'minat_lainnya_text' => 'nullable|string|max:255',
            'aspirasi' => 'nullable|string|max:1000',
        ], [
            'nomor_whatsapp.regex' => 'Format nomor WhatsApp tidak valid. Harus diawali 08 dan 10-14 digit',
            'nomor_whatsapp.required' => 'Nomor WhatsApp wajib diisi',
            'tanggal_lahir.before_or_equal' => 'Tanggal lahir tidak boleh melebihi hari ini',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ada kesalahan pada form',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Set default values
        $data['bersedia_info_wa'] = $request->has('bersedia_info_wa') ? (bool) $request->bersedia_info_wa : true;
        $data['tahun_pendataan'] = now()->year;
        $data['ip_address'] = $request->ip();

        // Cek duplikat (nama + nomor WA dalam 1 hari)
        $duplicate = Jamaah::where('nama_lengkap', $data['nama_lengkap'])
            ->where('nomor_whatsapp', $data['nomor_whatsapp'])
            ->whereDate('created_at', today())
            ->exists();

        if ($duplicate) {
            return response()->json([
                'success' => false,
                'message' => 'Data dengan nama dan nomor WA ini sudah terdaftar hari ini.'
            ], 422);
        }

        $jamaah = Jamaah::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Alhamdulillah, data Anda berhasil disimpan. Terima kasih telah menjadi bagian dari jamaah masjid.',
            'data' => $jamaah
        ]);
    }

    /**
     * DataTable untuk admin
     */
    public function dataTable(Request $request)
    {
        $query = Jamaah::query();

        if ($request->tahun) {
            $query->where('tahun_pendataan', $request->tahun);
        }

        if ($request->jenis_kelamin) {
            $query->where('jenis_kelamin', $request->jenis_kelamin);
        }

        if ($request->bersedia_info_wa !== null && $request->bersedia_info_wa !== '') {
            $query->where('bersedia_info_wa', $request->bersedia_info_wa);
        }

        if ($request->search) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', $search)
                    ->orWhere('nomor_whatsapp', 'like', $search)
                    ->orWhere('alamat_singkat', 'like', $search);
            });
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('jenis_kelamin_display', function ($row) {
                return match ($row->jenis_kelamin) {
                    'L' => '<span class="badge bg-primary">Laki-laki</span>',
                    'P' => '<span class="badge bg-danger">Perempuan</span>',
                    default => '<span class="badge bg-secondary">-</span>',
                };
            })
            ->addColumn('umur_display', function ($row) {
                if ($row->tanggal_lahir) {
                    $umur = $row->umur;
                    return $umur ? $umur . ' tahun' : '<span class="text-muted">-</span>';
                }
                return '<span class="text-muted">-</span>';
            })
            ->addColumn('minat_display', function ($row) {
                $minats = [];
                if ($row->minat_kajian_rutin) $minats[] = 'Kajian Rutin';
                if ($row->minat_tpa_tpq) $minats[] = 'TPA/TPQ';
                if ($row->minat_remaja_masjid) $minats[] = 'Remaja Masjid';
                if ($row->minat_kegiatan_sosial) $minats[] = 'Sosial';
                if ($row->minat_zakat_sedekah) $minats[] = 'Zakat/Sedekah';
                if ($row->minat_qurban) $minats[] = 'Qurban';
                if ($row->minat_kerelawanan) $minats[] = 'Kerelawanan';
                if ($row->minat_lainnya && $row->minat_lainnya_text) {
                    $minats[] = $row->minat_lainnya_text;
                }
                
                if (empty($minats)) {
                    return '<span class="text-muted">-</span>';
                }
                
                $badges = array_map(function($minat) {
                    return '<span class="badge bg-info me-1 mb-1">' . e($minat) . '</span>';
                }, $minats);
                
                return implode('', $badges);
            })
            ->addColumn('bersedia_info_wa_display', function ($row) {
                return $row->bersedia_info_wa 
                    ? '<span class="badge bg-success">Ya</span>'
                    : '<span class="badge bg-secondary">Tidak</span>';
            })
            ->addColumn('tanggal_daftar', function ($row) {
                return Carbon::parse($row->created_at)->translatedFormat('d M Y H:i');
            })
            ->addColumn('action', function ($row) {
                return '
                    <button type="button" class="btn btn-sm btn-info" onclick="showDetail(' . $row->id . ')">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteData(' . $row->id . ')">
                        <i class="fas fa-trash"></i>
                    </button>
                ';
            })
            ->rawColumns(['jenis_kelamin_display', 'umur_display', 'minat_display', 'bersedia_info_wa_display', 'action'])
            ->make(true);
    }

    public function show($id)
    {
        $jamaah = Jamaah::findOrFail($id);
        return response()->json($jamaah);
    }

    public function destroy($id)
    {
        $jamaah = Jamaah::findOrFail($id);
        $jamaah->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data jamaah berhasil dihapus'
        ]);
    }

    public function export(Request $request)
    {
        $query = Jamaah::query()
            ->when($request->tahun, fn($q) => $q->where('tahun_pendataan', $request->tahun));

        $jamaahs = $query->get();

        $filename = 'data-jamaah-' . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($jamaahs) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");
            
            fputcsv($file, [
                'No', 'Nama Lengkap', 'Nomor WA', 'RT', 'RW', 'Alamat',
                'Jenis Kelamin', 'Tanggal Lahir', 'Umur', 'Bersedia Info WA',
                'Minat Kajian Rutin', 'Minat TPA/TPQ', 'Minat Remaja Masjid',
                'Minat Kegiatan Sosial', 'Minat Zakat/Sedekah', 'Minat Qurban',
                'Minat Kerelawanan', 'Minat Lainnya', 'Aspirasi', 'Tanggal Daftar'
            ]);

            foreach ($jamaahs as $index => $j) {
                fputcsv($file, [
                    $index + 1,
                    $j->nama_lengkap,
                    $j->nomor_whatsapp ?? '-',
                    $j->rt ?? '-',
                    $j->rw ?? '-',
                    $j->alamat_singkat ?? '-',
                    $j->jenis_kelamin == 'L' ? 'Laki-laki' : ($j->jenis_kelamin == 'P' ? 'Perempuan' : '-'),
                    $j->tanggal_lahir?->format('d-m-Y') ?? '-',
                    $j->umur ? $j->umur . ' tahun' : '-',
                    $j->bersedia_info_wa ? 'Ya' : 'Tidak',
                    $j->minat_kajian_rutin ? 'Ya' : 'Tidak',
                    $j->minat_tpa_tpq ? 'Ya' : 'Tidak',
                    $j->minat_remaja_masjid ? 'Ya' : 'Tidak',
                    $j->minat_kegiatan_sosial ? 'Ya' : 'Tidak',
                    $j->minat_zakat_sedekah ? 'Ya' : 'Tidak',
                    $j->minat_qurban ? 'Ya' : 'Tidak',
                    $j->minat_kerelawanan ? 'Ya' : 'Tidak',
                    ($j->minat_lainnya && $j->minat_lainnya_text) ? $j->minat_lainnya_text : '-',
                    $j->aspirasi ?? '-',
                    $j->created_at->format('d-m-Y H:i'),
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}