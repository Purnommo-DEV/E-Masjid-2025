<?php

namespace App\Domain\FinancialV2;

use Closure;
use DomainException;

/** Keeps operational distribution deletion behind the audited service workflow. */
final class DistributionDeletionGuard
{
    private static int $depth = 0;

    public static function withinDeletion(Closure $callback): mixed
    {
        self::$depth++;

        try {
            return $callback();
        } finally {
            self::$depth--;
        }
    }

    public static function assertDeletionWrite(): void
    {
        if (self::$depth === 0) {
            throw new DomainException('Distribution drafts must be deleted through the audited service workflow.');
        }
    }
}
