<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Pengumuman extends Model
{
    protected $table = 'pengumumans';
    protected $guarded = ['id'];

    protected $casts = [
        'mulai' => 'datetime',
        'selesai' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pengumuman) {
            if (!$pengumuman->masjid_code) {
                $pengumuman->masjid_code = masjid();
            }

            if (Schema::hasColumn('pengumumans', 'slug') && !$pengumuman->slug) {
                $pengumuman->slug = static::uniqueSlug($pengumuman->judul);
            }
        });

        static::updating(function ($pengumuman) {
            if (Schema::hasColumn('pengumumans', 'slug') && $pengumuman->isDirty('judul')) {
                $pengumuman->slug = static::uniqueSlug($pengumuman->judul, $pengumuman->id);
            }
        });

        static::addGlobalScope('masjid', function ($query) {
            $query->where('masjid_code', masjid());
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('mulai')->orWhere('mulai', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('selesai')->orWhere('selesai', '>=', now());
            });
    }

    public function getPublicIdentifierAttribute(): string|int
    {
        return Schema::hasColumn('pengumumans', 'slug') && $this->slug
            ? $this->slug
            : $this->id;
    }

    protected static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title) ?: 'pengumuman';
        $slug = $baseSlug;
        $counter = 2;

        while (static::withoutGlobalScopes()
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
