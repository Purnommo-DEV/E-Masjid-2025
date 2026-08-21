<?php

namespace App\Domain\FinancialV2;

use Closure;
use DomainException;

/**
 * Runtime boundary for Eloquent financial facts. Controllers and other
 * operational code cannot create or transition Journal, JournalLine, or
 * LedgerEntry unless the canonical PostingEngine owns the write scope.
 */
final class FinancialFactWriteGuard
{
    private static int $depth = 0;

    public static function withinPosting(Closure $operation): mixed
    {
        self::$depth++;

        try {
            return $operation();
        } finally {
            self::$depth--;
        }
    }

    public static function assertPostingEngineWrite(string $fact): void
    {
        if (self::$depth === 0) {
            throw new DomainException("{$fact} can only be written by the Financial V2 Posting Engine.");
        }
    }
}
