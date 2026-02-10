<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendaftaranAnakYatimDhuafa extends Model
{
    protected $table = 'pendaftaran_anak_yatim_dhuafa';

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];
}