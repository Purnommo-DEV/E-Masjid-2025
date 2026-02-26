<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ZakatTransaksi extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'zakat_transaksi';
    protected $guarded = ['id'];

    protected $casts = [
        'tanggal' => 'datetime:Y-m-d', // atau 'datetime' kalau ada jam
    ];

    public function muzakki()
    {
        return $this->belongsTo(Muzakki::class);
    }

    public function details()
    {
        return $this->hasMany(ZakatTransaksiDetail::class);
    }

    public function akun()
    {
        return $this->belongsTo(AkunKeuangan::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

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

        static::creating(function ($model) {
            if ($model->tipe === 'penerimaan') {
                $year = now()->format('Y');
                $last = static::whereYear('created_at', $year)
                              ->where('tipe', 'penerimaan')
                              ->max('id') ?? 0;
                $model->nomor_kwitansi = "ZK/{$year}/" . str_pad($last + 1, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    // Method statis ini boleh dipertahankan jika dipakai di repository/controller
    public static function generateKwitansi($tahun)
    {
        $last = self::where('nomor_kwitansi', 'like', "ZK/{$tahun}/%")
                    ->orderBy('id', 'desc')
                    ->first();
        $urut = $last ? (int) substr($last->nomor_kwitansi, -5) + 1 : 1;
        return "ZK/{$tahun}/" . str_pad($urut, 5, '0', STR_PAD_LEFT);
    }
}