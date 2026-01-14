<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AkunKeuangan extends Model
{
    protected $table = "akun_keuangan";
    protected $guarded = ['id'];

    public function children()
    {
        return $this->hasMany(AkunKeuangan::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(AkunKeuangan::class, 'parent_id');
    }

    public function jurnals()
    {
        return $this->hasMany(Jurnal::class, 'akun_id');
    }

    // === SCOPE UNTUK DROPDOWN OPTION BERDASARKAN GRUP ===
    public function scopeKotakInfak($query)
    {
        return $query->where('tipe', 'pendapatan')->where('grup', 'kotak_infak')->orderBy('kode');
    }

    public function scopeZakat($query)
    {
        return $query->where('tipe', 'liabilitas')->where('grup', 'zakat')->orderBy('kode');
    }

    public function scopeDonasiBesar($query)
    {
        return $query->where('tipe', 'pendapatan')->where('grup', 'donasi_besar')->orderBy('kode');
    }

    public function scopeBebanKecil($query)
    {
        return $query->where('tipe', 'beban')->where('jenis_beban', 'kecil')->orderBy('kode');
    }

    public function scopeBebanBesar($query)
    {
        return $query->where('tipe', 'beban')->where('jenis_beban', 'besar')->orderBy('kode');
    }

    // Untuk tampilan badge di tabel
    public function getGrupBadgeAttribute()
    {
        return match ($this->grup) {
            'kotak_infak'   => '<span class="badge bg-success">Kotak Infak</span>',
            'zakat'         => '<span class="badge bg-warning text-dark">Zakat</span>',
            'donasi_besar'  => '<span class="badge bg-primary">Donasi/Transfer</span>',
            default         => '<span class="badge bg-secondary">Umum</span>',
        };
    }
}
