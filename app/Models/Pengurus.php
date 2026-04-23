<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengurus extends Model
{
    protected $fillable = ['masjid_code', 'nama', 'jabatan', 'keterangan', 'foto_path', 'urutan', 'created_by', 'updated_by'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pengurus) {
            if (!$pengurus->masjid_code) {
                $pengurus->masjid_code = masjid();
            }
        });

        static::addGlobalScope('masjid', function ($query) {
            $query->where('masjid_code', masjid());
        });
    }

    // Accessor untuk foto URL
    public function getFotoUrlAttribute(): ?string
    {
        return get_image_url($this->foto_path);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}