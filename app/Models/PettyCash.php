<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PettyCash extends Model
{
    protected $table = "petty_cash";
    protected $guarded = ['id'];

    public function user() 
    { 
        return $this->belongsTo(User::class, 'created_by'); 
    }

    public function akunBeban() 
    { 
        return $this->belongsTo(AkunKeuangan::class, 'akun_beban_id'); 
    }
}
