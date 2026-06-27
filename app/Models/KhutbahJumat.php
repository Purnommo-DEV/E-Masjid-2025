<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KhutbahJumat extends Model
{
    protected $table = 'khutbah_jumat';

    protected $guarded = ['id'];

    protected static function booted()
    {
        static::saved(function ($model) {
            // Clear cache untuk semua tanggal
            $dates = KhutbahJumat::pluck('tanggal_asli')->unique();
            foreach ($dates as $date) {
                Cache::forget('jadwal_jumat_'.$date);
            }
            Cache::forget('jadwal_jumat_terdekat');
        });

        static::deleted(function ($model) {
            Cache::forget('jadwal_jumat_'.$model->tanggal_asli);
            Cache::forget('jadwal_jumat_terdekat');
        });
    }
}
