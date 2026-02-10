<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProfilMasjid extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'profil_masjids';
    protected $guarded = ['id'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('struktur')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('logo')
            ->useDisk('public')
            ->useFallbackUrl('/images/default-logo.png')
            ->singleFile(); // hanya 1 logo
    }

    public function getRekeningFormattedAttribute()
    {
        $rek = $this->rekening ?? '';
        // Tambah spasi tiap 4 digit
        return preg_replace('/(.{4})(?!$)/', '$1 ', $rek);
    }
}