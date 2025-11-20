<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaldoAwal extends Model 
{
    protected $fillable = ['periode', 'jumlah', 'keterangan', 'created_by']; 
}
