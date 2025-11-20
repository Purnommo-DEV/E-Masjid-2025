<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Str;

class Transaksi extends Model implements HasMedia
{
    use InteractsWithMedia;
    protected $fillable = ['kategori_id', 'jumlah', 'tanggal', 'deskripsi', 'created_by'];
    protected $casts = ['tanggal' => 'date'];
    
    public function kategori() 
    { 
        return $this->belongsTo(KategoriKeuangan::class); 
    }
    
    public function buktiMedia()
    {
        return $this->belongsTo(Media::class, 'bukti_media_id', 'id');
    }

    public function creator() 
    { 
        return $this->belongsTo(User::class, 'created_by'); 
    }
    
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
             ->width(100)
             ->height(100)
             ->sharpen(10);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('bukti')
             ->singleFile(); // hanya 1 file
    }

    public function getCustomPathForBukti(): string
    {
        $kategori = $this->kategori;
        $namaKategori = $kategori?->nama ?? 'umum';
        $isTransfer = str_contains(strtolower($namaKategori), 'transfer');
        $folder = $isTransfer ? 'transaksi' : 'bukti';
        $year = $this->tanggal->format('Y');
        $month = $this->tanggal->format('m');
        return "keuangan/{$folder}/{$year}/{$month}";
    }

    public function getFirstMediaUrl($collectionName = 'default', $conversion = '')
    {
        $media = $this->getFirstMedia($collectionName);
        if (!$media) return '';

        $customPath = $media->getCustomProperty('custom_path');
        if ($customPath) {
            return asset('storage/' . $customPath);
        }

        return $media->getUrl($conversion);
    }

    public function getBuktiUrl()
    {
        // 1. BUKTI MANUAL: collection 'bukti' + model Transaksi
        if ($this->hasMedia('bukti')) {
            $media = $this->getFirstMedia('bukti');
            $path = $media->getCustomProperty('custom_path');
            return $path ? asset('storage/' . $path) : $media->getUrl();
        }

        // 2. BUKTI KOTAK: bukti_media_id + collection 'bukti_kotak' + model KotakInfak
        if ($this->bukti_media_id && $this->buktiMedia) {
            $media = $this->buktiMedia;
            if ($media->collection_name === 'bukti_kotak' && $media->model_type === 'App\\Models\\KotakInfak') {
                $path = $media->getCustomProperty('custom_path');
                return $path ? asset('storage/' . $path) : $media->getUrl();
            }
        }

        return null;
    }

    public function getBuktiThumbUrl()
    {
        $url = $this->getBuktiUrl();
        if (!$url) return null;

        // Custom path → tidak ada thumb → pakai original
        if (str_contains($url, 'keuangan/bukti/')) {
            return $url;
        }

        // Coba ambil thumb
        $media = $this->hasMedia('bukti') ? $this->getFirstMedia('bukti') : $this->buktiMedia;
        return $media?->getUrl('thumb') ?: $url;
    }
}