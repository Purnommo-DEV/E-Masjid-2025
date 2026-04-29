<?php
// app/Models/Qurban.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Qurban extends Model
{
    use HasFactory;

    protected $table = 'qurbans';

    protected $fillable = [
        'masjid_code',
        'judul',
        'slug',
        'jenis_hewan',
        'harga',
        'harga_full',
        'max_share',
        'stok',
        'berat_min',
        'berat_max',
        'deskripsi_singkat',
        'deskripsi_lengkap',
        'image_path',
        'video_url',
        'health_certificate_path',
        'is_active',
        'is_featured',
        'urutan',
        'deadline',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'harga' => 'integer',
        'harga_full' => 'integer',
        'max_share' => 'integer',
        'stok' => 'integer',
        'berat_min' => 'integer',
        'berat_max' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'urutan' => 'integer',
        'deadline' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($qurban) {
            if (!$qurban->masjid_code) {
                $qurban->masjid_code = masjid();
            }
            if (!$qurban->slug) {
                $qurban->slug = Str::slug($qurban->judul);
            }
        });

        static::updating(function ($qurban) {
            if ($qurban->isDirty('judul')) {
                $qurban->slug = Str::slug($qurban->judul);
            }
        });

        static::addGlobalScope('masjid', function ($query) {
            $query->where('masjid_code', masjid());
        });
    }

    // ==================== ACCESSORS ====================

    // Format harga Rupiah
    public function getHargaFormattedAttribute()
    {
        return 'Rp ' . number_format($this->harga, 0, ',', '.');
    }

    public function getHargaFullFormattedAttribute()
    {
        if ($this->harga_full) {
            return 'Rp ' . number_format($this->harga_full, 0, ',', '.');
        }
        return null;
    }

    // Range berat
    public function getBeratRangeAttribute()
    {
        if ($this->berat_min && $this->berat_max) {
            return $this->berat_min . ' - ' . $this->berat_max . ' kg';
        }
        if ($this->berat_min) {
            return '≥ ' . $this->berat_min . ' kg';
        }
        if ($this->berat_max) {
            return '≤ ' . $this->berat_max . ' kg';
        }
        return '-';
    }

    // Label jenis hewan
    public function getJenisLabelAttribute()
    {
        $labels = [
            'sapi' => 'Sapi',
            'kambing' => 'Kambing',
            'kerbau' => 'Kerbau',
        ];
        return $labels[$this->jenis_hewan] ?? ucfirst($this->jenis_hewan);
    }

    // Icon jenis hewan
    public function getJenisIconAttribute()
    {
        $icons = [
            'sapi' => '🐮',
            'kambing' => '🐐',
            'kerbau' => '🐃',
        ];
        return $icons[$this->jenis_hewan] ?? '🐏';
    }

    // Badge share
    public function getShareBadgeAttribute()
    {
        if ($this->max_share == 1) {
            return '1 Ekor';
        }
        return 'Kolektif ' . $this->max_share . ' Orang';
    }

    // Image URL (thumbnail/cover)
    public function getImageUrlAttribute(): ?string
    {
        return get_image_url($this->image_path);
    }

    // Health certificate URL
    public function getHealthCertificateUrlAttribute(): ?string
    {
        return get_image_url($this->health_certificate_path);
    }

    // Cover image URL (dari gallery)
    public function getCoverImageUrlAttribute(): ?string
    {
        $cover = $this->coverGallery;
        if ($cover) {
            return $cover->image_url;
        }
        
        $first = $this->galleries()->first();
        if ($first) {
            return $first->image_url;
        }
        
        return $this->image_url;
    }

    // All gallery images as array
    public function getGalleryImagesAttribute(): array
    {
        return $this->galleries->map(function ($gallery) {
            return [
                'id' => $gallery->id,
                'url' => $gallery->image_url,
                'thumb' => $gallery->thumb_url,
                'title' => $gallery->title,
                'description' => $gallery->description,
                'is_cover' => $gallery->is_cover,
                'urutan' => $gallery->urutan,
            ];
        })->toArray();
    }

    // ==================== RELATIONSHIPS ====================

    // Gallery images
    public function galleries()
    {
        return $this->hasMany(QurbanGallery::class)->orderBy('urutan');
    }

    // Cover gallery (one)
    public function coverGallery()
    {
        return $this->hasOne(QurbanGallery::class)->where('is_cover', true);
    }

    // Registrations
    public function registrations()
    {
        return $this->hasMany(QurbanRegistration::class);
    }

    // Creator
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Updater
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ==================== SCOPES ====================

    // Scope for active qurban
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope for featured qurban
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    // Scope for masjid
    public function scopeForMasjid($query, $masjidCode = null)
    {
        $masjidCode = $masjidCode ?? masjid();
        return $query->where('masjid_code', $masjidCode);
    }

    // Scope by jenis hewan
    public function scopeByJenis($query, $jenis)
    {
        return $query->where('jenis_hewan', $jenis);
    }

    // Scope available (active and has stock)
    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)->where('stok', '>', 0);
    }
}