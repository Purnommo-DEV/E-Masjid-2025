<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SantunanRamadhanAgeSetting extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'tahun_program' => 'integer',
        'tanggal_acuan' => 'date:Y-m-d',
        'batas_tahun' => 'integer',
        'batas_bulan' => 'integer',
    ];

    protected $appends = ['configured'];

    public function getConfiguredAttribute(): bool
    {
        return $this->exists;
    }

    public function histories(): HasMany
    {
        return $this->hasMany(SantunanRamadhanAgeSettingHistory::class, 'setting_id');
    }
}
