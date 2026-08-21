<?php

namespace App\Domain\FinancialV2;

use Closure;
use DomainException;

/** Limits AccountingPeriod and ClosingRun state writes to the closing service. */
final class PeriodClosingStateGuard
{
    private static int $depth = 0;

    public static function withinClosing(Closure $callback): mixed
    {
        self::$depth++;

        try {
            return $callback();
        } finally {
            self::$depth--;
        }
    }

    public static function assertClosingWrite(): void
    {
        if (self::$depth === 0) {
            throw new DomainException('Accounting period closing controls must be changed through the V2 closing service.');
        }
    }
}
