<?php

namespace App\Domain\FinancialV2;

use Closure;
use DomainException;

/** Limits reconciliation state and balance snapshots to the reconciliation service. */
final class ReconciliationStateGuard
{
    private static int $depth = 0;

    public static function withinReconciliation(Closure $callback): mixed
    {
        self::$depth++;

        try {
            return $callback();
        } finally {
            self::$depth--;
        }
    }

    public static function assertReconciliationWrite(): void
    {
        if (self::$depth === 0) {
            throw new DomainException('Reconciliation controls must be changed through the V2 reconciliation service.');
        }
    }
}
