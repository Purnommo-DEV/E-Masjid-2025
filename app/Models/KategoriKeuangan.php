<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KategoriKeuangan extends Model
{
    protected $fillable = ['nama', 'tipe', 'deskripsi'];
}