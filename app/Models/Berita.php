<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Berita extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'beritas';
    protected $guarded = ['id'];

    protected $casts = [
        'published_at' => 'datetime',
        'is_published' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($b) {
            $b->slug = Str::slug($b->judul);
            $b->created_by = auth()->id();
        });

        static::updating(function ($b) {
            $b->slug = Str::slug($b->judul);
        });
    }

    public function kategoris()
    {
        return $this->belongsToMany(Kategori::class, 'berita_kategori');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('gambar');
    }

    public function registerMediaConversions(Media $media = null): void
    {
        // Kosong atau tambah thumbnail jika perlu
    }

    // Override getUrl agar selalu pakai custom folder jika ada
    public function getUrl($conversion = '')
    {
        $media = $this->getFirstMedia('gambar');
        if (!$media) {
            return null;
        }

        // Prioritaskan custom folder dari custom_properties
        if ($conversion === '' && $media->hasCustomProperty('folder')) {
            $folder = $media->getCustomProperty('folder');
            return asset('storage/' . $folder . '/' . $media->file_name);
        }

        // Fallback default Spatie (hanya jika custom folder tidak ada)
        return $media->getUrl($conversion);
    }

    public function getGambarUrlAttribute(): ?string
    {
        return $this->getUrl();
    }
}