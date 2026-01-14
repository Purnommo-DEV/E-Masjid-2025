<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DanaTerikatPenerimaan extends Model { 

    protected $table = 'dana_terikat_penerimaan';
    protected $guarded = ['id'];

    public function program() 
    { 
        return $this->belongsTo(DanaTerikatProgram::class); 
    }
}