<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DanaTerikatPenerimaan extends Model
{
    protected $table = 'dana_terikat_penerimaan';

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah' => 'decimal:0',
        'is_saldo_awal' => 'boolean',
    ];

    // ===== RELATIONS =====

    public function program(): BelongsTo
    {
        return $this->belongsTo(DanaTerikatProgram::class, 'program_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ===== ACCESSORS =====

    public function getJumlahFormattedAttribute(): string
    {
        return 'Rp '.number_format($this->jumlah, 0, ',', '.');
    }

    public function getIsSaldoAwalLabelAttribute(): string
    {
        return $this->is_saldo_awal ? '✅ Saldo Awal' : 'Donasi';
    }

    // 🔥 Helper untuk label jenis dana
    public function getJenisDanaLabelAttribute(): string
    {
        return match ($this->jenis_dana) {
            'dana_terikat' => 'Infaq Terikat (Yatim/Dhuafa)',
            'zakat_maal' => 'Zakat Maal',
            'zakat_fitrah' => 'Zakat Fitrah',
            'fidyah' => 'Fidyah',
            'infaq_umum' => 'Infaq Umum',
            'shodaqoh' => 'Shodaqoh',
            'dana_titipan' => '📦 Dana Titipan',
            default => $this->jenis_dana,
        };
    }

    // 🔥 Helper untuk akun liabilitas berdasarkan jenis dana
    public function getAkunLiabilitasIdAttribute(): ?int
    {
        return match ($this->jenis_dana) {
            'dana_terikat' => $this->program?->akun_liabilitas_id,
            'zakat_maal' => AkunKeuangan::where('kode', '20002')->first()?->id,
            'zakat_fitrah' => AkunKeuangan::where('kode', '20001')->first()?->id,
            'fidyah' => AkunKeuangan::where('kode', '20003')->first()?->id,
            'infaq_umum' => AkunKeuangan::where('kode', '20005')->first()?->id,
            'shodaqoh' => AkunKeuangan::where('kode', '20006')->first()?->id,
            'dana_titipan' => AkunKeuangan::where('kode', '20099')->first()?->id,
            default => $this->program?->akun_liabilitas_id,
        };
    }
}
