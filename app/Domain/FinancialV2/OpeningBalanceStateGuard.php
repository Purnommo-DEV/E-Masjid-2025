<?php

namespace App\Domain\FinancialV2;

use Closure;
use DomainException;

/** Restricts opening-position state changes to the governed workflow or Posting Engine. */
final class OpeningBalanceStateGuard
{
    private static int $depth = 0;

    public static function withinOpeningBalance(Closure $callback): mixed
    {
        self::$depth++;

        try {
            return $callback();
        } finally {
            self::$depth--;
        }
    }

    public static function assertOpeningBalanceWrite(): void
    {
        if (self::$depth === 0) {
            throw new DomainException('Opening balance controls must be changed through the V2 opening-balance workflow.');
        }
    }
}
