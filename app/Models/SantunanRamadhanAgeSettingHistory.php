<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SantunanRamadhanAgeSettingHistory extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_acuan_sebelumnya' => 'date:Y-m-d',
        'tanggal_acuan_baru' => 'date:Y-m-d',
        'changed_at' => 'datetime',
    ];

    public function setting(): BelongsTo
    {
        return $this->belongsTo(SantunanRamadhanAgeSetting::class, 'setting_id');
    }
}
