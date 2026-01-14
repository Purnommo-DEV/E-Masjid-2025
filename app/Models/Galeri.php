<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Galeri extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $guarded = ['id'];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($g) => $g->created_by = auth()->id());
    }

    public function kategoris()
    {
        return $this->belongsToMany(Kategori::class, 'galeri_kategori');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('foto');
    }

    // App/Models/Media.php atau override
    public function getUrl($conversion = '')
    {
        if ($conversion === '' && isset($this->custom_properties['folder'])) {
            return asset('storage/' . $this->custom_properties['folder'] . '/' . $this->file_name);
        }
        return parent::getUrl($conversion);
    }

    public function registerMediaConversions(Media $media = null): void
    {
        //
    }
}