<?php

namespace App\Domain\FinancialV2;

use DomainException;

class FinancialPostingException extends DomainException
{
    /** @param array<string, mixed> $details */
    public function __construct(public readonly string $failureCode, string $message, public readonly array $details = [])
    {
        parent::__construct($message);
    }
}
