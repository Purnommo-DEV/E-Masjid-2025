<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Events\AcaraPublished;

class Acara extends Model implements HasMedia
{
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

}