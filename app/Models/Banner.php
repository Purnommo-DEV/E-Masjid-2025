<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Banner extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'banners';
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
        'created_by'=> 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATION (AUTHOR OPTIONAL)
    |--------------------------------------------------------------------------
    */
    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------------
    | MEDIA COLLECTION (1 banner = 1 gambar)
    |--------------------------------------------------------------------------
    */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('banner')->singleFile();
    }

    /*
    |--------------------------------------------------------------------------
    | MEDIA CONVERSIONS (optional thumbnail)
    |--------------------------------------------------------------------------
    */
    public function registerMediaConversions(Media $media = null): void
    {
        // contoh kalau mau resizing otomatis
        // $this->addMediaConversion('thumb')
        //     ->width(600)->height(300)
        //     ->sharpen(10);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSOR: Mengambil URL media (fallback default image)
    |--------------------------------------------------------------------------
    */
    public function getBannerUrlAttribute(): string
    {
        $media = $this->getFirstMedia('banner');

        return $media
            ? $media->getUrl()          // otomatis baca folder custom_properties
            : asset('images/default-banner.jpg'); // fallback default
    }
}
