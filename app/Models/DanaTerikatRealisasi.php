<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DanaTerikatRealisasi extends Model { 

    protected $table = 'dana_terikat_realisasi';
    protected $guarded = ['id'];

    public function program() 
    { 
        return $this->belongsTo(DanaTerikatProgram::class); 
    }
    
    public function penerima() 
    {
        return $this->belongsTo(DanaTerikatPenerima::class); 
    }
}