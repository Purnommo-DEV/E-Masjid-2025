<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Galeri extends Model
{
    protected $table = 'galeris';
    protected $guarded = ['id'];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($galeri) {
            if (!$galeri->masjid_code) {
                $galeri->masjid_code = masjid();
            }
            $galeri->created_by = auth()->id();
        });

        static::updating(function ($galeri) {
            $galeri->updated_by = auth()->id();
        });

        static::addGlobalScope('masjid', function ($query) {
            $query->where('masjid_code', masjid());
        });
    }

    // Relasi ke media (multiple foto)
    public function media()
    {
        return $this->hasMany(GaleriMedia::class)->orderBy('urutan');
    }

    // Accessor untuk semua foto (array)
    public function getFotosAttribute(): array
    {
        return $this->media->map(function ($item) {
            return [
                'id' => $item->id,
                'url' => get_image_url($item->image_path),
                'file_name' => $item->file_name,
                'urutan' => $item->urutan,
            ];
        })->toArray();
    }

    // Accessor untuk thumbnail (foto pertama)
    public function getThumbnailUrlAttribute(): ?string
    {
        $first = $this->media()->first();
        if ($first) {
            return get_image_url($first->image_path);
        }
        
        // Jika video, ambil thumbnail YouTube
        if ($this->tipe === 'video' && $this->url_video) {
            return $this->getYouTubeThumbnail();
        }
        
        return null;
    }

    // Ambil thumbnail YouTube
    public function getYouTubeThumbnail(): ?string
    {
        if (!$this->url_video) return null;
        
        preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&]+)/', $this->url_video, $matches);
        $videoId = $matches[1] ?? null;
        
        if ($videoId) {
            return "https://img.youtube.com/vi/{$videoId}/0.jpg";
        }
        
        return null;
    }

    // Kategori
    public function kategoris()
    {
        return $this->belongsToMany(Kategori::class, 'galeri_kategori', 'galeri_id', 'kategori_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scope published
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}