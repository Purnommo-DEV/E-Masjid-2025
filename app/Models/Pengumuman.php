<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengumuman extends Model
{
    protected $table = 'pengumumans';
    protected $guarded = ['id'];

    protected $casts = [
        'mulai' => 'datetime',
        'selesai' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Tambah kolom image_path jika belum ada
    // migration: tambahkan image_path column
}