<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailKotak extends Model
{
    protected $fillable = ['kotak_id', 'nominal', 'jumlah_lembar', 'subtotal'];
    protected static function boot() 
    { 
        parent::boot(); 
        static::saving(
            fn($d) => $d->subtotal = $d->nominal * $d->jumlah_lembar
        ); 
    }
}