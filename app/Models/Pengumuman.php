<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengumuman extends Model
{
    protected $table = 'pengumumans';
    protected $guarded = ['id'];

    protected $casts = [
        'mulai' => 'datetime',
        'selesai' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pengumuman) {
            if (!$pengumuman->masjid_code) {
                $pengumuman->masjid_code = masjid();
            }
        });

        static::addGlobalScope('masjid', function ($query) {
            $query->where('masjid_code', masjid());
        });
    }
}