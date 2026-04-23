<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfilMasjid extends Model
{
    protected $table = 'profil_masjids';
    protected $guarded = ['id'];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($profil) {
            if (!$profil->masjid_code) {
                $profil->masjid_code = masjid();
            }
        });

        static::addGlobalScope('masjid', function ($query) {
            $query->where('masjid_code', masjid());
        });
    }

    // Accessor untuk logo
    public function getLogoUrlAttribute(): ?string
    {
        return get_image_url($this->logo_path) ?: asset('images/default-logo.png');
    }

    // Accessor untuk struktur
    public function getStrukturUrlAttribute(): ?string
    {
        return get_image_url($this->struktur_path);
    }

    // Accessor untuk QRIS
    public function getQrisUrlAttribute(): ?string
    {
        return get_image_url($this->qris_path);
    }

    // Format rekening
    public function getRekeningFormattedAttribute(): string
    {
        $rek = $this->rekening ?? '';
        return preg_replace('/(.{4})(?!$)/', '$1 ', $rek);
    }

    public function hasDonationInfo(): bool
    {
        return $this->bank_name || $this->rekening || $this->qris_path;
    }
}