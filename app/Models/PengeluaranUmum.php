<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengeluaranUmum extends Model
{
    protected $guarded = ['id'];

    public function akunBeban()
    {
        return $this->belongsTo(AkunKeuangan::class, 'akun_beban_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Polymorphic relation ke jurnal
    public function jurnals()
    {
        return $this->morphMany(Jurnal::class, 'jurnalable');
    }
}
