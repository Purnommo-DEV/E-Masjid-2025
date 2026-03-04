<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use RalphJSmit\Laravel\SEO\Support\HasSEO;
use RalphJSmit\Laravel\SEO\Support\SEOData;
use RalphJSmit\Laravel\SEO\SchemaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Events\AcaraPublished;

class Acara extends Model implements HasMedia
{
    use HasSEO;
    use InteractsWithMedia;

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
            $acara->slug = Str::slug($acara->judul);
            $acara->created_by = auth()->id();
        });
    }

    protected static function booted()
    {
        // set published_at otomatis
        static::updating(function ($acara) {
            if (
                $acara->isDirty('is_published') &&
                $acara->is_published === true &&
                $acara->getOriginal('is_published') === false
            ) {
                $acara->published_at = now();
            }
        });

        // trigger event saat publish pertama
        static::updated(function ($acara) {
            if (
                $acara->wasChanged('is_published') &&
                $acara->is_published === true
            ) {
                event(new AcaraPublished($acara));
            }
        });

        static::creating(function ($acara) {
            $acara->slug = Str::slug($acara->judul);
        });

        static::updating(function ($acara) {
            if ($acara->isDirty('judul')) {
                $acara->slug = Str::slug($acara->judul);
            }
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

    // --- SCOPES ---
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
                     ->where(function($q){
                         $q->whereNull('published_at')->orWhere('published_at','<=',now());
                     });
    }

    public function getPosterUrlAttribute(): ?string
    {
        //Cara Panggil d blade $berita->poster_url
        $media = $this->getFirstMedia('poster');

        if (!$media) {
            return null;
        }

        if ($media->hasCustomProperty('folder')) {
            $folder = $media->getCustomProperty('folder');
            return asset('storage/' . $folder . '/' . $media->file_name);
        }

        return $media->getUrl();
    }

    public function scopeUpcoming($query)
    {
        return $query->where('mulai','>=',now())
                     ->orderBy('mulai','asc');
    }

    protected function waktuFormat(): Attribute
    {
        return Attribute::get(function () {

            // 1️⃣ Jika waktu_teks terisi, gunakan selalu itu
            if ($this->waktu_teks) {
                return $this->waktu_teks;
            }

            // 2️⃣ Jika punya mulai & selesai
            if ($this->mulai && $this->selesai) {
                if ($this->mulai->isSameDay($this->selesai)) {
                    return $this->mulai->format('H:i') . ' - ' . $this->selesai->format('H:i');
                }
                return $this->mulai->format('d M Y H:i');
            }

            // 3️⃣ Jika hanya mulai
            if ($this->mulai) {
                return $this->mulai->format('H:i');
            }

            return null;
        });
    }

    public function getDynamicSEOData(): SEOData
    {
        // Ambil poster dari media collection 'poster' (fallback ke default)
        $media = $this->getFirstMedia('poster');
        $coverImage = $media
            ? secure_asset('storage/' . ($media->custom_properties['folder'] ?? 'poster') . '/' . $media->file_name)
            : secure_asset('images/default-share.jpg');

        // Deskripsi untuk meta, OG, dan schema (prioritas: deskripsi → fallback dari judul + lokasi + tanggal)
        $description = $this->deskripsi
            ? Str::limit(strip_tags($this->deskripsi), 158)
            : ($this->judul . ' di ' . ($this->lokasi ?? 'Masjid Raudhotul Jannah') . ' pada ' . ($this->mulai?->translatedFormat('l, d F Y') ?? 'segera'));

        // Custom Event Schema sebagai array JSON-LD (valid schema.org/Event)
        $eventSchema = [
            '@context' => 'https://schema.org',
            '@type'      => 'Event',
            'name'       => $this->judul,
            'description' => $description,
            'image'      => $coverImage,
            'url'        => route('acara.show', $this->slug),
            'startDate'  => $this->mulai?->toIso8601String(),
            'endDate'    => $this->selesai?->toIso8601String(),
            'location'   => [
                '@type' => 'Place',
                'name'  => $this->lokasi ?? 'Masjid Raudhotul Jannah Taman Cipulir Estate',
                // Kalau nanti ada kolom alamat lengkap, bisa ditambah:
                // 'address' => [
                //     '@type' => 'PostalAddress',
                //     'streetAddress' => $this->alamat ?? null,
                //     'addressLocality' => 'South Tangerang',
                //     'addressRegion' => 'Banten',
                //     'postalCode' => '15325',
                //     'addressCountry' => 'ID'
                // ],
            ],
            'performer'  => $this->pemateri ? [
                '@type' => 'Person',
                'name'  => $this->pemateri,
            ] : null,
            'organizer'  => [
                '@type' => 'Organization',
                'name'  => 'Masjid Raudhotul Jannah',
                'url'   => url('/'),
            ],
        ];

        // Hapus nilai null agar JSON-LD lebih bersih
        $eventSchema = array_filter($eventSchema, function ($value) {
            return !is_null($value);
        });

        return new SEOData(
            title: $this->judul . ' | Masjid Raudhotul Jannah',
            description: $description,
            author: $this->author?->name ?? 'Tim Masjid Raudhotul Jannah',
            image: $coverImage,
            published_time: $this->published_at,   // Carbon atau null
            modified_time: $this->updated_at,      // Carbon atau null
            // Schema: gabung Article (fallback) + custom Event
            schema: SchemaCollection::make()
                ->addArticle()                     // tetap pakai Article sebagai base
                ->add($eventSchema),               // tambah Event custom
        );
    }

}