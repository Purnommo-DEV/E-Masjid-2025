<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoPage extends Model
{
    protected $fillable = [
        'masjid_code',
        'page_key',
        'title',
        'description',
        'image',
        'canonical_url',
        'robots',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($seoPage) {
            if (! $seoPage->masjid_code) {
                $seoPage->masjid_code = masjid();
            }
        });

        static::addGlobalScope('masjid', function ($query) {
            $query->where('masjid_code', masjid());
        });
    }
}
