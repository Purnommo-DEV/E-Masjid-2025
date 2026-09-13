<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SantunanParticipation extends Model
{
    public const CATEGORIES = ['dhuafa', 'yatim_dhuafa'];

    protected $guarded = ['id'];

    protected $casts = [
        'tahun_program' => 'integer',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(SantunanPerson::class, 'person_id');
    }

    public function legacyRegistration(): BelongsTo
    {
        return $this->belongsTo(PendaftaranAnakYatimDhuafa::class, 'legacy_registration_id');
    }

    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->where($query->qualifyColumn('tahun_program'), $year);
    }

    public function getNamaLengkapAttribute(): ?string
    {
        return $this->getAttributeFromArray('nama_lengkap') ?? $this->person?->nama_lengkap;
    }

    public function getNamaPanggilanAttribute(): ?string
    {
        return $this->getAttributeFromArray('nama_panggilan') ?? $this->person?->nama_panggilan;
    }

    public function getTanggalLahirAttribute(): mixed
    {
        $value = $this->getAttributeFromArray('tanggal_lahir');

        return $value !== null ? $this->asDate($value) : $this->person?->tanggal_lahir;
    }

    public function getJenisKelaminAttribute(): ?string
    {
        return $this->getAttributeFromArray('jenis_kelamin') ?? $this->person?->jenis_kelamin;
    }
}
