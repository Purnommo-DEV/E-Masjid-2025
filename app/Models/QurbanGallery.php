<?php
// app/Models/QurbanGallery.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QurbanGallery extends Model
{
    protected $table = 'qurban_galleries';
    protected $guarded = ['id'];

    protected $casts = [
        'is_cover' => 'boolean',
        'urutan' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($gallery) {
            if (!$gallery->masjid_code) {
                $gallery->masjid_code = masjid();
            }
        });

        static::addGlobalScope('masjid', function ($query) {
            $query->where('masjid_code', masjid());
        });
    }

    // Relasi ke Qurban
    public function qurban()
    {
        return $this->belongsTo(Qurban::class);
    }

    // Accessor image URL
    public function getImageUrlAttribute(): ?string
    {
        return get_image_url($this->image_path);
    }

    // Accessor thumbnail
    public function getThumbUrlAttribute(): ?string
    {
        return get_image_url($this->image_path);
    }
}