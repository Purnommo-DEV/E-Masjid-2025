<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class KotakInfak extends Model implements HasMedia
{
    use InteractsWithMedia;
    protected $fillable = ['akun_pendapatan_id', 'transaksi_id', 'tanggal', 'total', 'keterangan', 'created_by'];

    protected $casts = [
        'tanggal' => 'date:Y-m-d',     // atau 'datetime'
        // kalau pakai created_at & updated_at
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];


    public function akunPendapatan()
    {
        return $this->belongsTo(AkunKeuangan::class, 'akun_pendapatan_id');
    }

    public function details() 
    { 
        return $this->hasMany(DetailKotak::class, 'kotak_id', 'id'); 
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
    
    public function getCustomPathForBuktiKotak(): string
    {
        $year = $this->tanggal->format('Y');
        $month = $this->tanggal->format('m');
        return "keuangan/kotak//{$year}/{$month}";
    }

    public function registerMediaCollections(): void 
    { 
        $this->addMediaCollection('bukti')->singleFile(); 
    }
}