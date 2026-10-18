<?php

namespace App\Domain\FinancialV2;

use Closure;
use DomainException;

final class AccountingDateCorrectionStateGuard
{
    private static array $transition = [];

    public static function governance(string $from, string $to, Closure $callback): mixed
    {
        $allowed = [
            'blocked' => ['pending_approval', 'cancelled'],
            'pending_approval' => ['governance_approved', 'rejected', 'cancelled'],
        ];
        if (! in_array($to, $allowed[$from] ?? [], true)) {
            throw new DomainException("Transisi governance {$from} ke {$to} tidak diizinkan.");
        }
        return self::within($from, $to, 'governance', $callback);
    }

    public static function execution(string $from, string $to, Closure $callback): mixed
    {
        $allowed = ['governance_approved' => ['execution_ready'], 'execution_ready' => ['executing'], 'executing' => ['completed', 'failed']];
        if (! in_array($to, $allowed[$from] ?? [], true)) {
            throw new DomainException("Transisi eksekusi {$from} ke {$to} tidak diizinkan.");
        }
        return self::within($from, $to, 'execution', $callback);
    }

    public static function assertTransition(string $from, string $to): void
    {
        if (self::$transition !== [$from, $to]) {
            throw new DomainException('Status koreksi tanggal akuntansi hanya dapat diubah melalui state machine domain.');
        }
    }

    private static function within(string $from, string $to, string $scope, Closure $callback): mixed
    {
        if (self::$transition !== []) {
            throw new DomainException("Transisi {$scope} tidak dapat dinesting.");
        }
        self::$transition = [$from, $to];
        try { return $callback(); } finally { self::$transition = []; }
    }
}
