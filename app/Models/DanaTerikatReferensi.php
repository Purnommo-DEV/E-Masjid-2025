<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DanaTerikatReferensi extends Model
{
    protected $table = "dana_terikat_referensi";
    protected $fillable = ['nama', 'warna'];

    public function penerima()
    {
        return $this->hasMany(DanaTerikatPenerima::class, 'referensi_id');
    }
}
