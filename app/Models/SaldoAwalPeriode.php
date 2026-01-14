<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaldoAwalPeriode extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'periode' => 'date', // atau 'datetime'
    ];
    
    public function details()
    { 
        return $this->hasMany(SaldoAwalDetail::class, 'saldo_awal_periode_id'); 
    }
    
    public function user()
    { 
        return $this->belongsTo(User::class, 'created_by'); 
    }
}
