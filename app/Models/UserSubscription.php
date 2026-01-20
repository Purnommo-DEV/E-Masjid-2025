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
    ];

    protected $casts = [
        'keys' => 'array',  // biar json_decode otomatis jadi array
    ];
}