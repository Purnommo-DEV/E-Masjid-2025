<?php

namespace App\Support;

use App\Domain\FinancialV2\DecimalAmount;

/** Exact presentation only; never convert financial decimals to a PHP float. */
final class ZiswafAmount
{
    public static function rupiah(int|string|null $amount, bool $fixedDecimals = false): string
    {
        $normalized = DecimalAmount::normalize($amount ?? '0');
        $negative = str_starts_with($normalized, '-');
        [$integer, $fraction] = explode('.', ltrim($normalized, '-'));
        $grouped = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $integer);

        return ($negative ? '-' : '').'Rp'.$grouped
            .($fixedDecimals || $fraction !== '00' ? ','.$fraction : '');
    }
}
