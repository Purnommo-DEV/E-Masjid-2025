<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenerimaanPemasukan extends Model
{
    protected $guarded = ['id'];

    public function akunPendapatan()
    {
        return $this->belongsTo(AkunKeuangan::class, 'akun_pendapatan_id');
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
