<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Jamaah extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'jamaah';

    protected $fillable = [
        'nama_lengkap',
        'nomor_whatsapp',
        'alamat_singkat',
        'rt',
        'rw',
        'jenis_kelamin',
        'tanggal_lahir',
        'bersedia_info_wa',
        'minat_kajian_rutin',
        'minat_tpa_tpq',
        'minat_remaja_masjid',
        'minat_kegiatan_sosial',
        'minat_zakat_sedekah',
        'minat_qurban',
        'minat_kerelawanan',
        'minat_lainnya',
        'minat_lainnya_text',
        'aspirasi',
        'ip_address',
        'tahun_pendataan',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'bersedia_info_wa' => 'boolean',
        'minat_kajian_rutin' => 'boolean',
        'minat_tpa_tpq' => 'boolean',
        'minat_remaja_masjid' => 'boolean',
        'minat_kegiatan_sosial' => 'boolean',
        'minat_zakat_sedekah' => 'boolean',
        'minat_qurban' => 'boolean',
        'minat_kerelawanan' => 'boolean',
    ];

    public function getUmurAttribute()
    {
        if (!$this->tanggal_lahir) {
            return null;
        }
        return $this->tanggal_lahir->age;
    }

    public function scopeTahun($query, $tahun)
    {
        return $query->where('tahun_pendataan', $tahun);
    }
}