<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DanaAlokasi extends Model
{
    use HasFactory;

    protected $table = 'dana_alokasi';

    protected $fillable = [
        'program_id',
        'akun_sumber_id',
        'akun_tujuan_id',
        'tanggal',
        'jumlah',
        'keterangan',
        'bulan_cakupan',
        'created_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah' => 'decimal:0',
        'bulan_cakupan' => 'integer',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(DanaTerikatProgram::class, 'program_id');
    }

    public function akunSumber(): BelongsTo
    {
        return $this->belongsTo(AkunKeuangan::class, 'akun_sumber_id');
    }

    public function akunTujuan(): BelongsTo
    {
        return $this->belongsTo(AkunKeuangan::class, 'akun_tujuan_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
