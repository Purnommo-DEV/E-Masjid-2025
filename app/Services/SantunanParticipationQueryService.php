<?php

namespace App\Services;

use App\Models\SantunanParticipation;
use App\Models\SantunanRamadhanAgeSetting;
use Illuminate\Database\Eloquent\Builder;

class SantunanParticipationQueryService
{
    public function __construct(private readonly SantunanAgeValidationService $ages)
    {
    }

    public function query(): Builder
    {
        return SantunanParticipation::query()
            ->join('santunan_persons', 'santunan_persons.id', '=', 'santunan_participations.person_id')
            ->select([
                'santunan_participations.*',
                'santunan_persons.nama_lengkap',
                'santunan_persons.nama_panggilan',
                'santunan_persons.tanggal_lahir',
                'santunan_persons.jenis_kelamin',
            ]);
    }

    public function apply(Builder $query, array $filters, ?SantunanRamadhanAgeSetting $setting = null): Builder
    {
        $query->forYear((int) $filters['tahun'])
            ->when($filters['sumber_informasi'] ?? null, fn (Builder $q, string $value) => $q->where('santunan_participations.sumber_informasi', $value))
            ->when($filters['kategori'] ?? null, fn (Builder $q, string $value) => $q->where('santunan_participations.kategori', $value))
            ->when($filters['rt'] ?? null, fn (Builder $q, string $value) => $q->where('santunan_participations.rt', $value))
            ->when($filters['rw'] ?? null, fn (Builder $q, string $value) => $q->where('santunan_participations.rw', $value))
            ->when($filters['umur_value'] ?? null, function (Builder $q, int $value) use ($filters): void {
                $q->where('santunan_participations.umur', $value);
                if ($filters['umur_satuan'] ?? null) {
                    $q->where('santunan_participations.umur_satuan', $filters['umur_satuan']);
                }
            })
            ->when($filters['jenis_kelamin'] ?? null, fn (Builder $q, string $value) => $q->where('santunan_persons.jenis_kelamin', $value))
            ->when($filters['search'] ?? null, function (Builder $q, string $value): void {
                $search = '%'.$value.'%';
                $q->where(fn (Builder $nameQuery) => $nameQuery
                    ->where('santunan_persons.nama_lengkap', 'like', $search)
                    ->orWhere('santunan_persons.nama_panggilan', 'like', $search));
            });

        if ($filters['status_usia'] ?? null) {
            $this->ages->applyStatusFilter(
                $query,
                $filters['status_usia'],
                $setting ?? $this->ages->settingForYear((int) $filters['tahun']),
            );
        }

        return $query;
    }
}
