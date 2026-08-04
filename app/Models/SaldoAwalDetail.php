<?php

namespace App\Models;

use App\Interfaces\JurnalRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class SaldoAwalDetail extends Model
{
    protected $guarded = ['id'];

    public function periode() { return $this->belongsTo(SaldoAwalPeriode::class, 'saldo_awal_periode_id'); }
    public function akun()    { return $this->belongsTo(AkunKeuangan::class); }
    public function lawanAkun() { return $this->belongsTo(AkunKeuangan::class, 'lawan_akun_id'); }

    protected static function booted()
    {
        static::created(function ($detail) {
            if ($detail->periode->status === 'locked' && $detail->jumlah > 0) {
                app(JurnalRepositoryInterface::class)->buatJurnal(
                    $detail->periode->periode,
                    'Jurnal Pembuka – Saldo Awal ' . $detail->akun->nama,
                    \createSaldoAwalOpeningEntries($detail),
                    $detail
                );
            }
        });
    }
}
