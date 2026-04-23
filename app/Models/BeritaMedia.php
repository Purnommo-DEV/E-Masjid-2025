<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BeritaMedia extends Model
{
    protected $table = 'berita_media';
    protected $guarded = ['id'];

    protected $casts = [
        'is_cover' => 'boolean',
    ];

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

    public function berita()
    {
        return $this->belongsTo(Berita::class);
    }

    public function getUrlAttribute(): string
    {
        return get_image_url($this->image_path) ?? '';
    }
}