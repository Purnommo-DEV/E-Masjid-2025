<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Pengumuman extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'pengumumans';
    protected $fillable = [
        'judul', 'isi', 'tipe', 'mulai', 'selesai', 'is_active'
    ];

    protected $casts = [
        'mulai' => 'datetime',
        'selesai' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('banner')->singleFile();
    }

    public function registerMediaConversions(Media $media = null): void
    {
        //
    }
}
