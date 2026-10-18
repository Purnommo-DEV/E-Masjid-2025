<?php

namespace App\Domain\FinancialV2;

use Closure;
use DomainException;

final class AccountingDateCorrectionLineageGuard
{
    private static int $creationDepth = 0;
    private static int $executionDepth = 0;

    public static function creation(Closure $callback): mixed
    {
        self::$creationDepth++;
        try { return $callback(); } finally { self::$creationDepth--; }
    }

    public static function assertCreationWrite(): void
    {
        if (self::$creationDepth === 0) {
            throw new DomainException('Lineage hanya dapat dibuat oleh workflow koreksi tanggal akuntansi resmi.');
        }
    }

    public static function execution(Closure $callback): mixed
    {
        self::$executionDepth++;
        try { return $callback(); } finally { self::$executionDepth--; }
    }

    public static function assertExecutionWrite(): void
    {
        if (self::$executionDepth === 0) {
            throw new DomainException('Lineage reversal/replacement hanya dapat diisi oleh workflow eksekusi resmi.');
        }
    }
}
