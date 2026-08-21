<?php

namespace App\Domain\FinancialV2;

use Closure;
use DomainException;

/**
 * Prevents source-transaction state and draft data from being changed through
 * arbitrary Eloquent writes. The lifecycle service and PostingEngine are the
 * only normal application writers.
 */
final class FinancialTransactionStateGuard
{
    private static int $depth = 0;

    public static function withinLifecycle(Closure $callback): mixed
    {
        self::$depth++;

        try {
            return $callback();
        } finally {
            self::$depth--;
        }
    }

    public static function assertLifecycleWrite(): void
    {
        if (self::$depth === 0) {
            throw new DomainException('Financial transactions must be changed through the V2 transaction lifecycle.');
        }
    }
}
