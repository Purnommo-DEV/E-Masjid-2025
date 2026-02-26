<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZakatTransaksiDetail extends Model
{
    use HasFactory;

    protected $table = 'zakat_transaksi_detail';
    protected $guarded = ['id'];

    public function transaksi()
    {
        return $this->belongsTo(ZakatTransaksi::class);
    }

    public function kadar()
    {
        return $this->belongsTo(KadarZakat::class);
    }
}