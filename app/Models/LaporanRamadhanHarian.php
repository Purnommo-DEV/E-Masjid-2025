<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaporanRamadhanHarian extends Model
{
    use HasFactory;

    protected $table = 'laporan_ramadhan_harian';

    protected $guarded = ['id'];

    protected $casts = [
        'santunan_yatim_penerimaan_hari_ini'     => 'array',
        'infaq_ramadan_pengeluaran_detail'       => 'array',
        'ifthor_penerimaan_detail'               => 'array',
        'ifthor_pengeluaran_detail'              => 'array',
        'paket_sembako_penerimaan_hari_ini'      => 'array',
        'sumbangan_barang'                       => 'array',
        'gebyar_anak_penerimaan_hari_ini'        => 'array',
        'tanggal'                                => 'date',
        'lomba_anak_tanggal'                     => 'date',
        'gebyar_anak_tanggal'                    => 'date',
    ];

    public function jadwalImam()
    {
        return $this->belongsTo(JadwalImamTarawih::class, 'jadwal_imam_id');
    }
}