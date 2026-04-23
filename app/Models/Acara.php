<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use RalphJSmit\Laravel\SEO\Support\HasSEO;
use RalphJSmit\Laravel\SEO\Support\SEOData;
use RalphJSmit\Laravel\SEO\SchemaCollection;

class Acara extends Model
{
    use HasSEO;

    protected $table = 'acaras';
    protected $guarded = ['id'];

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
            if (!$acara->masjid_code) {
                $acara->masjid_code = masjid();
            }
            $acara->slug = Str::slug($acara->judul);
            $acara->created_by = auth()->id();
        });

        static::updating(function ($acara) {
            if ($acara->isDirty('judul')) {
                $acara->slug = Str::slug($acara->judul);
            }
            if ($acara->isDirty('is_published') && 
                $acara->is_published === true && 
                $acara->getOriginal('is_published') === false) {
                $acara->published_at = now();
            }
        });

        static::addGlobalScope('masjid', function ($query) {
            $query->where('masjid_code', masjid());
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

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function scopeUpcoming($query)
    {
        return $query->where('mulai', '>=', now())
            ->orderBy('mulai', 'asc');
    }

    public function getWaktuFormatAttribute(): ?string
    {
        if ($this->waktu_teks) {
            return $this->waktu_teks;
        }

        if ($this->mulai && $this->selesai) {
            if ($this->mulai->isSameDay($this->selesai)) {
                return $this->mulai->format('H:i') . ' - ' . $this->selesai->format('H:i');
            }
            return $this->mulai->format('d M Y H:i');
        }

        if ($this->mulai) {
            return $this->mulai->format('H:i');
        }

        return null;
    }

    public function getTanggalLabelAttribute(): string
    {
        return $this->mulai ? $this->mulai->translatedFormat('l, d F Y') : '-';
    }

    public function getWaktuLabelAttribute(): string
    {
        if ($this->selesai) {
            return $this->mulai->format('H:i') . ' - ' . $this->selesai->format('H:i');
        }
        return $this->mulai ? $this->mulai->format('H:i') : '-';
    }
    
    public function getExcerptAttribute(): string
    {
        return Str::limit(strip_tags($this->deskripsi ?? ''), 120);
    }

    // SEO
    public function getDynamicSEOData(): SEOData
    {
        $coverImage = $this->image_path ?? secure_asset('images/default-share.jpg');

        $description = $this->deskripsi
            ? Str::limit(strip_tags($this->deskripsi), 158)
            : ($this->judul . ' di ' . ($this->lokasi ?? 'Masjid') . ' pada ' . $this->tanggal_label);

        $eventSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => $this->judul,
            'description' => $description,
            'image' => $coverImage,
            'url' => route('acara.show', $this->slug),
            'startDate' => $this->mulai?->toIso8601String(),
            'endDate' => $this->selesai?->toIso8601String(),
            'location' => [
                '@type' => 'Place',
                'name' => $this->lokasi ?? 'Masjid',
            ],
            'performer' => $this->pemateri ? [
                '@type' => 'Person',
                'name' => $this->pemateri,
            ] : null,
            'organizer' => [
                '@type' => 'Organization',
                'name' => masjid_name(),
                'url' => url('/'),
            ],
        ];

        $eventSchema = array_filter($eventSchema, fn($value) => !is_null($value));

        return new SEOData(
            title: $this->judul . ' | ' . masjid_name(),
            description: $description,
            author: $this->author?->name ?? 'Tim Masjid',
            image: $coverImage,
            published_time: $this->published_at,
            modified_time: $this->updated_at,
            schema: SchemaCollection::make()
                ->addArticle()
                ->add($eventSchema),
        );
    }
}