<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Layanan extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'layanan';
    protected $guarded = ['id'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($m) {
            $m->slug = Str::slug($m->judul);
            if (auth()->check()) $m->created_by = auth()->id();
        });
        static::updating(function ($m) {
            if (auth()->check()) $m->updated_by = auth()->id();
        });
    }
}
