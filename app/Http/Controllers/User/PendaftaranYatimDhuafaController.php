<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\PendaftaranAnakYatimDhuafa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PendaftaranYatimDhuafaController extends Controller
{
    public function index()
    {
        return view('masjid.'.masjid().'.guest.pendaftaran.index');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kategori'             => 'required|in:yatim_dhuafa,dhuafa',
            'nama_lengkap'         => 'required|min:3|max:150',
            'nama_panggilan'       => 'nullable|max:60',
            'sumber_informasi'     => 'nullable',
            'tanggal_lahir'        => 'required|date|before_or_equal:' . now()->subYears(0)->toDateString(),
            'jenis_kelamin'        => 'required|in:L,P',
            'alamat'               => 'required|min:5|max:255',
            'no_wa'                => 'nullable|regex:/^08[0-9]{8,12}$/',
            'nama_orang_tua'       => 'required|min:3|max:100',
            'pekerjaan_orang_tua'  => 'nullable|max:100',
        ], [
            'tanggal_lahir.before_or_equal' => 'Usia anak harus di bawah atau sama dengan 13 tahun.',
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

        // hitung umur
        $tglLahir = \Carbon\Carbon::parse($data['tanggal_lahir']);
        $umur = $tglLahir->age;

        if ($umur > 13) {
            return response()->json([
                'success' => false,
                'message' => 'Usia anak melebihi 13 tahun'
            ], 422);
        }

        $data['umur']         = $umur;
        $data['ip_address']   = $request->ip();
        $data['tahun_program']= now()->year;

        // cek duplikat sederhana (nama + tgl lahir + nama orang tua)
        $duplikat = PendaftaranAnakYatimDhuafa::where('nama_lengkap', $data['nama_lengkap'])
            ->where('tanggal_lahir', $data['tanggal_lahir'])
            ->where('nama_orang_tua', $data['nama_orang_tua'])
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
}