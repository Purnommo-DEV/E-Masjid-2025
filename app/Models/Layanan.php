<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Layanan extends Model
{
    protected $table = 'layanan';
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($m) {
            $m->slug = Str::slug($m->judul);
            if (!$m->masjid_code) {
                $m->masjid_code = masjid();
            }
            if (auth()->check()) $m->created_by = auth()->id();
        });
        static::updating(function ($m) {
            if (auth()->check()) $m->updated_by = auth()->id();
        });
    }

    // Accessor untuk icon/logo (jika pakai gambar)
    public function getLogoUrlAttribute(): ?string
    {
        return get_image_url($this->logo_path);
    }

    // Scope untuk layanan aktif
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}