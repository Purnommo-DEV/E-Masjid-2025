<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'endpoint',
        'keys',
        'user_id',
        'zona_waktu',
        'kota',
    ];

    protected $casts = [
        'keys' => 'array',
    ];
}