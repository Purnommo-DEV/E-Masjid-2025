<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaldoAwalDetail extends Model
{
    protected $guarded = ['id'];

    public function periode() { return $this->belongsTo(SaldoAwalPeriode::class, 'saldo_awal_periode_id'); }
    public function akun()    { return $this->belongsTo(AkunKeuangan::class); }

    protected static function booted()
    {
        static::created(function ($detail) {
            if ($detail->periode->status === 'locked' && $detail->jumlah > 0) {
                app(JurnalRepositoryInterface::class)->buatJurnal(
                    $detail->periode->periode,
                    'Jurnal Pembuka – Saldo Awal ' . $detail->akun->nama,
                    [
                        ['akun_id' => $detail->akun_id, 'debit'  => $detail->jumlah],
                        ['akun_id' => 50001,          'kredit' => $detail->jumlah], // 50001 = Surplus/Defisit atau Modal Awal
                    ],
                    $detail
                );
            }
        });
    }
}
