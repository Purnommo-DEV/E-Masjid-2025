<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Saran extends Model
{
    protected $fillable = ['nama', 'kontak', 'pesan', 'status', 'ip_address'];
}
