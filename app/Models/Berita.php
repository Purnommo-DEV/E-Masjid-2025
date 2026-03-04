<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use RalphJSmit\Laravel\SEO\Support\HasSEO;
use RalphJSmit\Laravel\SEO\Support\SEOData;
use RalphJSmit\Laravel\SEO\SchemaCollection;

class Berita extends Model implements HasMedia
{
    use HasSEO;
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
        $this->addMediaConversion('thumb')
            ->width(400)
            ->height(300)
            ->sharpen(10);
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

    public function getDynamicSEOData(): SEOData
    {
        // Ambil gambar cover (sama seperti sebelumnya)
        $media = $this->getFirstMedia('gambar') ?? $this->getFirstMedia('banner');
        $coverImage = $media
            ? secure_asset('storage/' . ($media->custom_properties['folder'] ?? 'berita') . '/' . $media->file_name)
            : secure_asset('images/default-share.jpg'); // default umum

        // Cek apakah ini program Ramadhan (dari kategori)
        $isRamadhan = $this->kategoris->contains(function ($kategori) {
            return Str::slug($kategori->nama) === 'program-ramadhan-1447h' 
                || Str::slug($kategori->slug ?? '') === 'program-ramadhan-1447h';
        });

        if ($isRamadhan) {
            // Override gambar default khusus Ramadhan
            $coverImage = secure_asset('images/default-ramadhan.png');

            // Deskripsi lebih spesifik Ramadhan
            $description = $this->excerpt
                ? Str::limit(strip_tags($this->excerpt), 158)
                : ($this->judul . ' — Program Ramadan 1447 H di Masjid Raudhotul Jannah TCE. Informasi lengkap seputar ibadah, kajian, dan kegiatan sosial selama bulan Ramadhan.');
        } else {
            // Deskripsi default (seperti berita biasa)
            $description = $this->excerpt
                ? Str::limit(strip_tags($this->excerpt), 158)
                : Str::limit(strip_tags($this->isi ?? ''), 158);
        }

        return new SEOData(
            title: $this->judul . ($isRamadhan ? ' | Program Ramadhan 1447 H' : '') . ' | Masjid Raudhotul Jannah',
            description: $description,
            author: $this->createdBy?->name ?? 'Tim Masjid Raudhotul Jannah',
            image: $coverImage,
            published_time: $this->published_at,
            modified_time: $this->updated_at,
            // Schema: Article utama + kalau Ramadhan bisa tambah custom jika perlu
            schema: SchemaCollection::make()->addArticle(),
        );
    }

    public function getCustomImageUrl(Media $media, string $conversion = ''): string
    {
        $folder = $media->getCustomProperty('folder');
        $fileName = $media->file_name;

        if ($folder && $fileName) {
            $path = $folder . '/' . $fileName;
            if ($conversion && $media->hasGeneratedConversion($conversion)) {
                // Jika pakai conversion, ambil nama file conversion (Spatie simpan di attribute file_name_{conversion})
                $path = $folder . '/' . $media->getAttribute("file_name_{$conversion}");
            }
            return asset('storage/' . $path);
        }

        // Fallback ke default Spatie kalau custom folder gagal
        return $media->getUrl($conversion);
    }
}