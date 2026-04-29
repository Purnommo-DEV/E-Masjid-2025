<?php
// app/Models/QurbanRegistration.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QurbanRegistration extends Model
{
    use HasFactory;

    protected $table = 'qurban_registrations';

    protected $fillable = [
        'masjid_code',
        'qurban_id',
        'kode_registrasi',
        'nama_lengkap',
        'email',
        'telepon',
        'alamat',
        'jumlah_share',
        'total_harga',
        'status',
        'catatan',
        'confirmed_at',
        'confirmed_by',
    ];

    protected $casts = [
        'jumlah_share' => 'integer',
        'total_harga' => 'integer',
        'confirmed_at' => 'datetime',
    ];

    // Accessor untuk status badge HTML
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => '<span class="badge-status badge-pending"><span class="w-2 h-2 rounded-full bg-yellow-500 mr-1"></span> Menunggu</span>',
            'confirmed' => '<span class="badge-status badge-confirmed"><span class="w-2 h-2 rounded-full bg-green-500 mr-1"></span> Dikonfirmasi</span>',
            'cancelled' => '<span class="badge-status badge-cancelled"><span class="w-2 h-2 rounded-full bg-red-500 mr-1"></span> Dibatalkan</span>',
            'completed' => '<span class="badge-status badge-completed"><span class="w-2 h-2 rounded-full bg-blue-500 mr-1"></span> Selesai</span>',
        ];
        return $badges[$this->status] ?? '<span class="badge-status">' . $this->status . '</span>';
    }

    // Accessor untuk status text
    public function getStatusTextAttribute()
    {
        $statuses = [
            'pending' => 'Menunggu Konfirmasi',
            'confirmed' => 'Dikonfirmasi',
            'cancelled' => 'Dibatalkan',
            'completed' => 'Selesai',
        ];
        return $statuses[$this->status] ?? $this->status;
    }

    // Accessor untuk format total harga
    public function getTotalHargaFormattedAttribute()
    {
        return 'Rp' . number_format($this->total_harga, 0, ',', '.');
    }

    // Accessor untuk format tanggal
    public function getTanggalDaftarAttribute()
    {
        return $this->created_at->format('d/m/Y H:i');
    }

    // Relationships
    public function qurban()
    {
        return $this->belongsTo(Qurban::class);
    }

    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    // Scope filters
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    // Boot method untuk generate kode registrasi
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $year = date('Y');
            $lastId = static::where('masjid_code', $model->masjid_code)
                ->whereYear('created_at', $year)
                ->max('id') ?? 0;
            $model->kode_registrasi = 'QRB/' . $year . '/' . str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
        });
    }
}