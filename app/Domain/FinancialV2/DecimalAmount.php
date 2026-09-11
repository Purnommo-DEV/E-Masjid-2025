<?php

namespace App\Domain\FinancialV2;

use InvalidArgumentException;

/**
 * Exact fixed-scale decimal arithmetic for Financial V2 monetary amounts.
 *
 * Values are stored and returned at the database scale of two decimal places.
 * PHP float is deliberately rejected so journal, ledger, and balance controls
 * cannot silently lose precision before MySQL receives the value.
 */
final class DecimalAmount
{
    private const SCALE = 2;

    public static function normalize(int|string $amount): string
    {
        $value = trim((string) $amount);
        if (! preg_match('/^([+-]?)(\d+)(?:\.(\d{1,2}))?$/', $value, $matches)) {
            throw new InvalidArgumentException('Financial amount must be an integer or a decimal value with at most two fractional digits.');
        }

        $integer = ltrim($matches[2], '0');
        $integer = $integer === '' ? '0' : $integer;
        $fraction = str_pad($matches[3] ?? '', self::SCALE, '0');
        $normalized = $integer.'.'.$fraction;

        return $matches[1] === '-' && $normalized !== '0.00' ? '-'.$normalized : $normalized;
    }

    /** @param iterable<int, int|string> $amounts */
    public static function sum(iterable $amounts): string
    {
        $result = '0.00';
        foreach ($amounts as $amount) {
            $result = self::add($result, $amount);
        }

        return $result;
    }

    /** @param int|string $left @param int|string $right */
    public static function add(int|string $left, int|string $right): string
    {
        [$leftNegative, $leftDigits] = self::minorUnits($left);
        [$rightNegative, $rightDigits] = self::minorUnits($right);

        if ($leftNegative === $rightNegative) {
            return self::fromMinorUnits($leftNegative, self::addUnsigned($leftDigits, $rightDigits));
        }

        $comparison = self::compareUnsigned($leftDigits, $rightDigits);
        if ($comparison === 0) {
            return '0.00';
        }

        if ($comparison > 0) {
            return self::fromMinorUnits($leftNegative, self::subtractUnsigned($leftDigits, $rightDigits));
        }

        return self::fromMinorUnits($rightNegative, self::subtractUnsigned($rightDigits, $leftDigits));
    }

    /** @param int|string $left @param int|string $right */
    public static function subtract(int|string $left, int|string $right): string
    {
        return self::add($left, self::negate($right));
    }

    public static function multiplyByInteger(int|string $amount, int $multiplier): string
    {
        if ($multiplier < 0) {
            throw new InvalidArgumentException('Financial amount multiplier must not be negative.');
        }

        $result = '0.00';
        $addend = self::normalize($amount);
        while ($multiplier > 0) {
            if (($multiplier & 1) === 1) {
                $result = self::add($result, $addend);
            }
            $multiplier = intdiv($multiplier, 2);
            if ($multiplier > 0) {
                $addend = self::add($addend, $addend);
            }
        }

        return $result;
    }

    /** @param int|string $left @param int|string $right */
    public static function equals(int|string $left, int|string $right): bool
    {
        return self::normalize($left) === self::normalize($right);
    }

    /** @param int|string $left @param int|string $right */
    public static function compare(int|string $left, int|string $right): int
    {
        [$leftNegative, $leftDigits] = self::minorUnits($left);
        [$rightNegative, $rightDigits] = self::minorUnits($right);

        if ($leftNegative !== $rightNegative) {
            return $leftNegative ? -1 : 1;
        }

        $comparison = self::compareUnsigned($leftDigits, $rightDigits);

        return $leftNegative ? -$comparison : $comparison;
    }

    public static function negate(int|string $amount): string
    {
        $normalized = self::normalize($amount);

        return $normalized === '0.00'
            ? $normalized
            : (str_starts_with($normalized, '-') ? substr($normalized, 1) : '-'.$normalized);
    }

    /** Format an exact decimal using Indonesian separators without converting it to float. */
    public static function formatIndonesian(int|string $amount, bool $currency = false): string
    {
        $normalized = self::normalize($amount);
        $negative = str_starts_with($normalized, '-');
        $unsigned = $negative ? substr($normalized, 1) : $normalized;
        [$integer, $fraction] = explode('.', $unsigned, 2);
        $grouped = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $integer) ?? $integer;
        $formatted = $grouped.($fraction === '00' ? '' : ','.$fraction);

        return ($negative ? '-' : '').($currency ? 'Rp' : '').$formatted;
    }

    /** @param int|string $amount @return array{0: bool, 1: string} */
    private static function minorUnits(int|string $amount): array
    {
        $normalized = self::normalize($amount);
        $negative = str_starts_with($normalized, '-');
        $unsigned = $negative ? substr($normalized, 1) : $normalized;
        [$integer, $fraction] = explode('.', $unsigned, 2);

        return [$negative, ltrim($integer.$fraction, '0') ?: '0'];
    }

    private static function fromMinorUnits(bool $negative, string $digits): string
    {
        $digits = ltrim($digits, '0') ?: '0';
        $digits = str_pad($digits, self::SCALE + 1, '0', STR_PAD_LEFT);
        $integer = substr($digits, 0, -self::SCALE);
        $fraction = substr($digits, -self::SCALE);
        $result = $integer.'.'.$fraction;

        return $negative && $result !== '0.00' ? '-'.$result : $result;
    }

    private static function compareUnsigned(string $left, string $right): int
    {
        $left = ltrim($left, '0') ?: '0';
        $right = ltrim($right, '0') ?: '0';

        return strlen($left) <=> strlen($right) ?: strcmp($left, $right);
    }

    private static function addUnsigned(string $left, string $right): string
    {
        $carry = 0;
        $result = '';
        $leftIndex = strlen($left) - 1;
        $rightIndex = strlen($right) - 1;

        while ($leftIndex >= 0 || $rightIndex >= 0 || $carry !== 0) {
            $sum = $carry
                + ($leftIndex >= 0 ? (int) $left[$leftIndex--] : 0)
                + ($rightIndex >= 0 ? (int) $right[$rightIndex--] : 0);
            $result = ($sum % 10).$result;
            $carry = intdiv($sum, 10);
        }

        return $result;
    }

    private static function subtractUnsigned(string $left, string $right): string
    {
        $borrow = 0;
        $result = '';
        $leftIndex = strlen($left) - 1;
        $rightIndex = strlen($right) - 1;

        while ($leftIndex >= 0) {
            $difference = (int) $left[$leftIndex--] - $borrow - ($rightIndex >= 0 ? (int) $right[$rightIndex--] : 0);
            if ($difference < 0) {
                $difference += 10;
                $borrow = 1;
            } else {
                $borrow = 0;
            }
            $result = $difference.$result;
        }

        return ltrim($result, '0') ?: '0';
    }
}
