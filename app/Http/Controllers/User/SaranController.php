<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Saran;
use Illuminate\Http\Request; // PASTIKAN INI ADA DAN BENAR
use Illuminate\Support\Facades\Validator;

class SaranController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama'     => 'nullable|string|max:100',
            'telepon'  => 'nullable|string|max:20',
            'pesan'    => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ada kesalahan validasi.',
                'errors'  => $validator->errors()
            ], 422);
        }

        Saran::create([
            'nama'       => $request->nama,
            'kontak'     => $request->telepon,
            'pesan'      => $request->pesan,
            'status'     => 'baru',
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pesan berhasil dikirim! Terima kasih atas saran dan masukannya. Kami akan segera membacanya.'
        ]);
    }
}