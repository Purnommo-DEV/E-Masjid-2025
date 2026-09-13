<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SantunanPerson extends Model
{
    use HasUuids;

    protected $table = 'santunan_persons';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_lahir' => 'date:Y-m-d',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $person): void {
            $person->normalized_name = self::normalizeName($person->nama_lengkap);
        });
    }

    public function participations(): HasMany
    {
        return $this->hasMany(SantunanParticipation::class, 'person_id');
    }

    public static function normalizeName(?string $name): string
    {
        return Str::lower(Str::squish((string) $name));
    }
}
