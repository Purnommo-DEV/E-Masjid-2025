<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DanaTerikatRealisasiKoreksi extends Model
{
    protected $table = 'dana_terikat_realisasi_koreksi';
    protected $guarded = ['id'];

    public function program()
    { 
        return $this->belongsTo(DanaTerikatProgram::class); 
    }

    public function user()
    { 
        return $this->belongsTo(User::class, 'created_by'); 
    }

    public function getJumlahFormattedAttribute()
    {
        $jumlah = $this->jumlah_koreksi;
        $prefix = $jumlah >= 0 ? '+' : '';
        return $prefix . 'Rp ' . number_format(abs($jumlah), 0, ',', '.');
    }
}
