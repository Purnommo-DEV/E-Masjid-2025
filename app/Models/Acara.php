<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Acara extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'acaras';
    protected $fillable = [
        'judul', 'slug', 'deskripsi', 'mulai', 'selesai', 'lokasi', 'penyelenggara',
        'is_published', 'published_at', 'created_by'
    ];

    protected $casts = [
        'mulai' => 'datetime',
        'selesai' => 'datetime',
        'published_at' => 'datetime',
        'is_published' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($acara) {
            $acara->slug = Str::slug($acara->judul);
            $acara->created_by = auth()->id();
        });
    }

    public function kategoris()
    {
        return $this->belongsToMany(Kategori::class, 'acara_kategori');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('poster')->singleFile();
    }

    public function registerMediaConversions(Media $media = null): void
    {
        //
    }
}