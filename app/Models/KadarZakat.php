<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KadarZakat extends Model
{
    use HasFactory;
    
    protected $table = 'kadar_zakat';
    protected $guarded = ['id'];
}
