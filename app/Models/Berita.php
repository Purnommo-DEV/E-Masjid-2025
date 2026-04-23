<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use RalphJSmit\Laravel\SEO\Support\HasSEO;
use RalphJSmit\Laravel\SEO\Support\SEOData;
use RalphJSmit\Laravel\SEO\SchemaCollection;

class Berita extends Model
{
    use HasSEO;

    protected $table = 'beritas';
    protected $guarded = ['id'];

    protected $casts = [
        'published_at' => 'datetime',
        'is_published' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($berita) {
            if (!$berita->masjid_code) {
                $berita->masjid_code = masjid();
            }
            $berita->slug = Str::slug($berita->judul);
            $berita->created_by = auth()->id();
        });

        static::updating(function ($berita) {
            if ($berita->isDirty('judul')) {
                $berita->slug = Str::slug($berita->judul);
            }
            if ($berita->isDirty('is_published') && 
                $berita->is_published === true && 
                $berita->getOriginal('is_published') === false) {
                $berita->published_at = now();
            }
        });

        static::addGlobalScope('masjid', function ($query) {
            $query->where('masjid_code', masjid());
        });
    }

    // Relasi ke media (multiple gambar)
    public function media()
    {
        return $this->hasMany(BeritaMedia::class)->orderBy('urutan');
    }

    // Ambil gambar cover (gambar pertama atau yang ditandai is_cover=true)
    public function coverImage()
    {
        return $this->hasOne(BeritaMedia::class)->where('is_cover', true);
    }

    // Accessor untuk URL cover
    public function getCoverUrlAttribute(): ?string
    {
        $cover = $this->coverImage;
        if ($cover) {
            return get_image_url($cover->image_path);
        }
        
        $first = $this->media()->first();
        if ($first) {
            return get_image_url($first->image_path);
        }
        
        return null;
    }

    // Accessor untuk semua gambar (array)
    public function getGalleryAttribute(): array
    {
        return $this->media->map(function ($item) {
            return [
                'id' => $item->id,
                'url' => get_image_url($item->image_path),
                'file_name' => $item->file_name,
                'is_cover' => $item->is_cover,
                'urutan' => $item->urutan,
            ];
        })->toArray();
    }

    // Kategori
    public function kategoris()
    {
        return $this->belongsToMany(Kategori::class, 'berita_kategori');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('published_at', 'desc');
    }

    // Accessor excerpt
    public function getExcerptAttribute($length = 160): string
    {
        return Str::limit(strip_tags($this->isi ?? ''), $length);
    }

    // SEO
    public function getDynamicSEOData(): SEOData
    {
        $coverImage = $this->cover_url ?? secure_asset('images/default-share.jpg');

        $description = $this->excerpt ?: Str::limit(strip_tags($this->isi ?? ''), 158);

        return new SEOData(
            title: $this->judul . ' | ' . masjid_name(),
            description: $description,
            author: $this->author?->name ?? 'Tim Masjid',
            image: $coverImage,
            published_time: $this->published_at,
            modified_time: $this->updated_at,
            schema: SchemaCollection::make()->addArticle(),
        );
    }
}