<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jurnal extends Model
{
    protected $table = "jurnal";
    protected $guarded = ['id'];

    public function akun()
    { 
        return $this->belongsTo(AkunKeuangan::class); 
    }
    
    public function jurnalable() 
    { 
        return $this->morphTo(); 
    }
    
    public function user()
    { 
        return $this->belongsTo(User::class, 'created_by'); 
    }
}
