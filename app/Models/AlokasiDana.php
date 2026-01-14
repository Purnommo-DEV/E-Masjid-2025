<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlokasiDana extends Model
{
    protected $table = "alokasi_dana";
    protected $guarded = ['id'];

    public function akunSumber()
    {
        return $this->belongsTo(AkunKeuangan::class, 'akun_sumber_id');
    }

    public function akunTujuan()
    {
        return $this->belongsTo(AkunKeuangan::class, 'akun_tujuan_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function jurnals()
    {
        return $this->morphMany(Jurnal::class, 'jurnalable');
    }
}
