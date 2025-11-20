<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Masjid extends Model
{
    protected $fillable = ['nama', 'slug', 'alamat', 'config'];
    protected $casts = ['config' => 'array'];

    public function beritas() { return $this->hasMany(Berita::class); }
    public function sarans() { return $this->hasMany(Saran::class); }
}
