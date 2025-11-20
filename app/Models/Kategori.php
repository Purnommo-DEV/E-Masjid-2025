<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Kategori extends Model
{
    protected $table = 'kategori';
    protected $fillable = ['nama', 'slug', 'warna', 'tipe'];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($k) => $k->slug = Str::slug($k->nama));
    }

    public function beritas()
    {
        return $this->belongsToMany(Berita::class, 'berita_kategori');
    }

    public function scopeTipe($query, $tipe)
    {
        return $query->where('tipe', $tipe);
    }
}