<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PesanJamaah extends Model
{
    protected $table = 'pesan_jamaah';

    protected $guarded = ['id'];

    protected $casts = [
        'is_read' => 'boolean',
    ];
}
