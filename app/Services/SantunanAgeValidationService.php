<?php

namespace App\Services;

use App\Models\SantunanRamadhanAgeSetting;
use App\Models\SantunanRamadhanAgeSettingHistory;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use IntlDateFormatter;

class SantunanAgeValidationService
{
    public const CATEGORY = 'yatim_dhuafa';
    public const ELIGIBLE = 'eligible';
    public const OVER_LIMIT = 'over_limit';
    public const NEEDS_VERIFICATION = 'needs_verification';

    public function settingForYear(int $year): SantunanRamadhanAgeSetting
    {
        $persisted = SantunanRamadhanAgeSetting::query()->where('tahun_program', $year)->first();
        if ($persisted) {
            return $persisted;
        }

        $ramadan = $this->ramadanInfo($year);

        return new SantunanRamadhanAgeSetting([
            'tahun_program' => $year,
            'period_key' => 'ramadhan-'.$ramadan['hijri_year'],
            'periode_ramadhan' => 'Ramadhan '.$ramadan['hijri_year'].' H',
            'tanggal_acuan' => $year === 2027 ? '2027-02-08' : $ramadan['start_date'],
            'batas_tahun' => 13,
            'batas_bulan' => 11,
        ]);
    }

    public function updateReferenceDate(int $year, string $date, $user = null, ?string $ipAddress = null, ?string $userAgent = null): SantunanRamadhanAgeSetting
    {
        return DB::transaction(function () use ($year, $date, $user, $ipAddress, $userAgent): SantunanRamadhanAgeSetting {
            $default = $this->settingForYear($year);
            $setting = SantunanRamadhanAgeSetting::query()->where('tahun_program', $year)->lockForUpdate()->first();
            $previous = $setting?->tanggal_acuan?->toDateString();

            if ($setting && $previous === $date) {
                return $setting;
            }

            if ($setting) {
                $setting->update(['tanggal_acuan' => $date]);
            } else {
                $setting = SantunanRamadhanAgeSetting::query()->create([
                    'tahun_program' => $year,
                    'period_key' => $default->period_key,
                    'periode_ramadhan' => $default->periode_ramadhan,
                    'tanggal_acuan' => $date,
                    'batas_tahun' => 13,
                    'batas_bulan' => 11,
                ]);
            }
            SantunanRamadhanAgeSettingHistory::query()->create([
                'setting_id' => $setting->id,
                'tahun_program' => $setting->tahun_program,
                'periode_ramadhan' => $setting->periode_ramadhan,
                'tanggal_acuan_sebelumnya' => $previous,
                'tanggal_acuan_baru' => $date,
                'changed_by' => $user?->getAuthIdentifier(),
                'changed_by_label' => $user?->name ?? $user?->email,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent ? mb_substr($userAgent, 0, 500) : null,
                'changed_at' => now(),
            ]);

            return $setting->refresh();
        });
    }

    public function evaluate($record, SantunanRamadhanAgeSetting $setting): array
    {
        if ($record->kategori !== self::CATEGORY) {
            return ['status' => null, 'label' => 'Tidak berlaku', 'age' => null];
        }

        $birth = $this->birthDate($record->tanggal_lahir);
        $reference = CarbonImmutable::parse($setting->tanggal_acuan);
        if (! $birth || $birth->greaterThan($reference)) {
            return ['status' => self::NEEDS_VERIFICATION, 'label' => 'Perlu verifikasi', 'age' => null];
        }

        $diff = $birth->diff($reference);
        $age = $diff->y.' tahun '.$diff->m.' bulan'.($diff->d ? ' '.$diff->d.' hari' : '');
        $cutoff = $reference->subYearsNoOverflow($setting->batas_tahun)->subMonthsNoOverflow($setting->batas_bulan);
        $status = $birth->lessThan($cutoff) ? self::OVER_LIMIT : self::ELIGIBLE;

        return [
            'status' => $status,
            'label' => $status === self::ELIGIBLE ? 'Memenuhi batas umur' : 'Melebihi batas umur',
            'age' => $age,
        ];
    }

    public function applyStatusFilter(Builder $query, string $status, SantunanRamadhanAgeSetting $setting): Builder
    {
        $reference = CarbonImmutable::parse($setting->tanggal_acuan)->toDateString();
        $cutoff = CarbonImmutable::parse($setting->tanggal_acuan)
            ->subYearsNoOverflow($setting->batas_tahun)->subMonthsNoOverflow($setting->batas_bulan)->toDateString();

        $query->where('santunan_participations.kategori', self::CATEGORY);

        return match ($status) {
            self::ELIGIBLE => $query->whereNotNull('santunan_persons.tanggal_lahir')
                ->whereDate('santunan_persons.tanggal_lahir', '<=', $reference)
                ->whereDate('santunan_persons.tanggal_lahir', '>=', $cutoff),
            self::OVER_LIMIT => $query->whereNotNull('santunan_persons.tanggal_lahir')
                ->whereDate('santunan_persons.tanggal_lahir', '<', $cutoff),
            self::NEEDS_VERIFICATION => $query->where(fn (Builder $q) => $q
                ->whereNull('santunan_persons.tanggal_lahir')
                ->orWhereDate('santunan_persons.tanggal_lahir', '>', $reference)),
            default => $query,
        };
    }

    private function birthDate($value): ?CarbonImmutable
    {
        if (! $value) {
            return null;
        }
        try {
            return CarbonImmutable::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function ramadanInfo(int $year): array
    {
        $formatter = new IntlDateFormatter('en_US@calendar=islamic', IntlDateFormatter::NONE, IntlDateFormatter::NONE, config('app.timezone'), IntlDateFormatter::TRADITIONAL, 'yyyy-M-d');
        $date = CarbonImmutable::create($year, 1, 1, 12, timezone: config('app.timezone'));
        while ($date->year === $year) {
            [$hijriYear, $month, $day] = array_map('intval', explode('-', $formatter->format($date->timestamp)));
            if ($month === 9 && $day === 1) {
                return ['hijri_year' => $hijriYear, 'start_date' => $date->toDateString()];
            }
            $date = $date->addDay();
        }

        return ['hijri_year' => $year - 579, 'start_date' => CarbonImmutable::create($year, 2, 1)->toDateString()];
    }
}
