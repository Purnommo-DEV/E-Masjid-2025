<?php

namespace App\Repositories\mrj;
use App\Models\AkunKeuangan;
use Yajra\DataTables\Facades\DataTables;

use App\Interfaces\AkunKeuanganRepositoryInterface;

class AkunKeuanganRepository implements AkunKeuanganRepositoryInterface
{
    public function datatables()
    {
        $query = AkunKeuangan::query()
            ->select(['id', 'kode', 'nama', 'tipe', 'saldo_normal', 'jenis_beban', 'is_active', 'keterangan', 'grup']);

        return DataTables::of($query)
            ->make(true);
    }

    public function forKotakInfak()
    {
        return AkunKeuangan::kotakInfak()->get();
    }

    public function forZakat()
    {
        return AkunKeuangan::zakat()->get();
    }

    public function forDonasiBesar()
    {
        return AkunKeuangan::donasiBesar()->get();
    }

    public function bebanKecil()
    {
        return AkunKeuangan::bebanKecil()->get();
    }

    public function bebanBesar()
    {
        return AkunKeuangan::bebanBesar()->get();
    }

    public function all()
    { 
        return AkunKeuangan::all(); 
    }
    
    public function find($id)
    { 
        return AkunKeuangan::findOrFail($id);
    }
    
    public function create(array $data)
    { 
        return AkunKeuangan::create($data); 
    }
    
    public function update($id, array $data)
    { 
        $akun = $this->find($id); 
        $akun->update($data); return $akun; 
    }

    public function delete($id) 
    { 
        return AkunKeuangan::destroy($id); 
    }
}
