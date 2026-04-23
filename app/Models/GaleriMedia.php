<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GaleriMedia extends Model
{
    protected $table = 'galeri_media';
    protected $guarded = ['id'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($media) {
            if (!$media->masjid_code) {
                $media->masjid_code = masjid();
            }
        });

        static::addGlobalScope('masjid', function ($query) {
            $query->where('masjid_code', masjid());
        });
    }

    public function galeri()
    {
        return $this->belongsTo(Galeri::class);
    }

    public function getUrlAttribute(): string
    {
        return get_image_url($this->image_path) ?? '';
    }
}