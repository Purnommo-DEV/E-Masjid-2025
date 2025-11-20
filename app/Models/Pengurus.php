<?php
// app/Models/Pengurus.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Pengurus extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = ['nama', 'jabatan', 'keterangan', 'urutan'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('foto')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }
}