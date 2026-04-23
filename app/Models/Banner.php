<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $table = 'banners';
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'created_by'=> 'integer',
        'updated_by'=> 'integer',
    ];

    // Auto-set masjid_code saat create
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (!$model->masjid_code) {
                $model->masjid_code = masjid();
            }
        });
        
        static::addGlobalScope('masjid', function ($query) {
            $query->where('masjid_code', masjid());
        });
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getGambarUrlAttribute(): string
    {
        return get_image_url($this->image_path) ?: asset('assets/e-masjid/images/masjid-banner.jpg');
    }
    
    // Alias
    public function getImageUrlAttribute(): string
    {
        return $this->gambar_url;
    }
}