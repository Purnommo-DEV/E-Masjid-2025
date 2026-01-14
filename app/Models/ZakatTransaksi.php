<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Zakattransaksi extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'zakat_transaksi';
    protected $fillable = [
        'nomor_kwitansi','jenis_zakat','muzakki_utama','no_hp','daftar_keluarga',
        'total_jiwa','satuan_beras','jumlah','tanggal','keterangan','tipe','akun_id','created_by'
    ];
    protected $casts = ['jenis_zakat' => 'array', 'daftar_keluarga' => 'array', 'tanggal' => 'date'];

    public function akun() { return $this->belongsTo(AkunKeuangan::class); }
    public function user() { return $this->belongsTo(User::class, 'created_by'); }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('bukti_zakat')->singleFile();
    }

    public function getBuktiUrlAttribute()
    {
        return $this->getFirstMediaUrl('bukti_zakat') ?: null;
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($m) {
            if ($m->tipe === 'penerimaan') {
                $year = now()->format('Y');
                $last = static::whereYear('created_at', $year)->where('tipe','penerimaan')->max('id') ?? 0;
                $m->nomor_kwitansi = "ZK/{$year}/" . str_pad($last + 1, 5, '0', STR_PAD_LEFT);
            }
        });
    }
}