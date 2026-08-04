<?php

namespace App\Models;

use App\Models\DanaTerikatPenerima;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DanaTerikatRealisasiKoreksi extends Model
{
    protected $table = 'dana_terikat_realisasi_koreksi';
    protected $guarded = ['id'];
    protected $casts = [
        'jumlah_koreksi' => 'integer',
        'tahun' => 'integer',
        'bulan' => 'integer',
    ];

    public function program()
    { 
        return $this->belongsTo(DanaTerikatProgram::class); 
    }

    public function user()
    { 
        return $this->belongsTo(User::class, 'created_by'); 
    }

    // 🔥 TAMBAHKAN RELASI INI (optional, jika memang ada field penerima_id)
    public function penerima(): BelongsTo
    {
        return $this->belongsTo(DanaTerikatPenerima::class, 'penerima_id');
    }

    public function getJumlahFormattedAttribute()
    {
        $jumlah = $this->jumlah_koreksi;
        $prefix = $jumlah >= 0 ? '+' : '';
        return $prefix . 'Rp ' . number_format(abs($jumlah), 0, ',', '.');
    }
}
