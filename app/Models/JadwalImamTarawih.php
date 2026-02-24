<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JadwalImamTarawih extends Model
{
    use HasFactory;

    protected $table = 'jadwal_imam_tarawih';

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal' => 'date',
    ];
}