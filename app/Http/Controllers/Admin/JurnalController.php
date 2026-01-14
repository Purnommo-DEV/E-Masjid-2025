<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jurnal;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;

class JurnalController extends Controller
{
    public function index()
    {
        return view('masjid.'.masjid().'.admin.keuangan.jurnal.index');
    }

    public function data(Request $request)
    {
        $query = Jurnal::with(['akun', 'user'])
            ->when($request->bulan, function($q) use ($request) {
                $q->whereYear('tanggal', substr($request->bulan, 0, 4))
                  ->whereMonth('tanggal', substr($request->bulan, 5, 2));
            })
            ->select('jurnal.*')
            ->orderByDesc('tanggal')
            ->orderByDesc('id');

        return DataTables::of($query)
            ->addColumn('akun', function ($row) {
                if (!$row->akun) return null;

                return [
                    'id'   => $row->akun->id,
                    'kode' => $row->akun->kode,
                    'nama' => $row->akun->nama,
                    'tipe' => $row->akun->tipe,
                ];
            })
            ->addColumn('user', function ($row) {
                if (!$row->user) return null;

                return [
                    'id'   => $row->user->id,
                    'name' => $row->user->name,
                    'email'=> $row->user->email,
                ];
            })
            ->make(true);
    }
}