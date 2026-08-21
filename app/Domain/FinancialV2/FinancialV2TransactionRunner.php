<?php

namespace App\Domain\FinancialV2;

use Closure;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Executes an all-or-nothing Financial V2 database transaction and retries
 * only database-declared transient lock conflicts. Retrying keeps the
 * original isolation level and never turns a failed partial posting into a
 * successful one: the database rolls the complete attempt back first.
 */
final class FinancialV2TransactionRunner
{
    private const MAX_ATTEMPTS = 12;

    public function run(Closure $callback): mixed
    {
        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            try {
                return DB::transaction($callback);
            } catch (Throwable $exception) {
                if ($attempt === self::MAX_ATTEMPTS || ! $this->isTransientLockConflict($exception)) {
                    throw $exception;
                }

                // Spread retried sessions after an InnoDB deadlock/lock wait;
                // this is retry, not a reduction in transaction isolation.
                $backoff = min(25_000 * (2 ** ($attempt - 1)), 500_000);
                usleep($backoff + random_int(0, 50_000));
            }
        }

        throw new \LogicException('Financial V2 transaction retry loop exhausted unexpectedly.');
    }

    private function isTransientLockConflict(Throwable $exception): bool
    {
        $code = (string) $exception->getCode();
        $message = $exception->getMessage();

        return in_array($code, ['40001', 'HY000'], true)
            && (str_contains($message, '1213 Deadlock found') || str_contains($message, '1205 Lock wait timeout'));
    }
}
