<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KesehatanRegistration extends Model
{
    protected $table = 'kesehatan_registrations';

    protected $guarded = [];

    protected $casts = [
        'event_date'       => 'date',
        'donor_darah'      => 'boolean',
        'cek_kesehatan'    => 'array',
        'cek_mata_katarak' => 'boolean',
    ];
}