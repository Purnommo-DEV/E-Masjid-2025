<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SantunanSourceInformationRenameHistory extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'tahun_program' => 'integer',
        'semua_tahun' => 'boolean',
        'jumlah_terdampak' => 'integer',
        'changed_at' => 'datetime',
    ];
}
