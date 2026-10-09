<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;

final class FinancialDate
{
    public static function date($value, string $fallback = '—'): string
    {
        if ($value === null || $value === '') {
            return $fallback;
        }

        return $value instanceof DateTimeInterface
            ? $value->format('d/m/Y')
            : CarbonImmutable::parse($value)->format('d/m/Y');
    }

    public static function dateTime($value, string $fallback = '—'): string
    {
        if ($value === null || $value === '') {
            return $fallback;
        }

        return $value instanceof DateTimeInterface
            ? $value->format('d/m/Y H:i')
            : CarbonImmutable::parse($value)->format('d/m/Y H:i');
    }
}
