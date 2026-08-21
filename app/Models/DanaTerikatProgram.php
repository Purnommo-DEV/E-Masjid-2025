<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DanaTerikatProgram extends Model {
    
    protected $table = 'dana_terikat_program';
    
    protected $fillable = [
        'kode_program',
        'nama_program',
        'akun_liabilitas_id',
        'akun_aset_id',
        'keterangan',
        'aktif'
    ];
    
    public function penerima() 
    { 
        return $this->hasMany(DanaTerikatPenerima::class, 'program_id'); 
    }
    
    public function penerimaan() 
    { 
        return $this->hasMany(DanaTerikatPenerimaan::class, 'program_id'); 
    }

    public function akun() 
    { 
        return $this->belongsTo(AkunKeuangan::class, 'akun_liabilitas_id'); 
    }

    public function akunAset()
    {
        return $this->belongsTo(AkunKeuangan::class, 'akun_aset_id');
    }
}