<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class KotakInfak extends Model implements HasMedia
{
    use InteractsWithMedia;
    protected $fillable = ['jenis_kotak_id', 'tanggal', 'total', 'keterangan', 'created_by'];
    
    public function jenis() 
    { 
        return $this->belongsTo(JenisKotakInfak::class, 'jenis_kotak_id', 'id'); 
    }

    public function transaksi() 
    { 
        return $this->belongsTo(Transaksi::class, 'jenis_kotak_id', 'id'); 
    }

    public function details() 
    { 
        return $this->hasMany(DetailKotak::class, 'kotak_id', 'id'); 
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