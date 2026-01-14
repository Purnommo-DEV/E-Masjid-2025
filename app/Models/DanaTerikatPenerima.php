<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DanaTerikatPenerima extends Model {

    protected $table = 'dana_terikat_penerima';
    
    protected $guarded = ['id'];
    
    protected $casts = [
        'tanggal_lahir' => 'date',
        'status_yatim'  => 'boolean',
    ];

    // Akses umur (otomatis dihitung)
    public function getUmurAttribute()
    {
        if (! $this->tanggal_lahir) {
            return null;
        }

        return $this->tanggal_lahir->age; // Carbon age
    }

    // Akses "masih_yatim" berdasarkan kategori & umur
    public function getMasihYatimAttribute()
    {
        if ($this->kategori !== 'yatim' || ! $this->tanggal_lahir) {
            return false;
        }

        return $this->umur < 15; // patokan yang kamu mau
    }

    public function program() 
    { 
        return $this->belongsTo(DanaTerikatProgram::class); 
    }

    protected static function booted()
    {
        static::saving(function ($model) {
            if ($model->kategori === 'yatim' && $model->tanggal_lahir) {
                $model->status_yatim = $model->tanggal_lahir->age < 15;
            } else {
                $model->status_yatim = 0;
            }
        });
    }

    public function referensi()
    {
        return $this->belongsTo(DanaTerikatReferensi::class, 'referensi_id');
    }
}