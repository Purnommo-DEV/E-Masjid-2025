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
    protected $fillable = ['judul', 'slug', 'isi', 'gambar', 'excerpt', 'is_published', 'published_at', 'created_by'];

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
        $this->addMediaCollection('gambar')->singleFile();
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

    }
}