<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JenisKotakInfak extends Model 
{ 
    protected $fillable = ['nama', 'wajib_jumat', 'keterangan']; 
}