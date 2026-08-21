<?php

namespace App\Domain\FinancialV2;

use DomainException;

final class FinancialDomainException extends DomainException
{
    public function __construct(public readonly string $failureCode, string $message)
    {
        parent::__construct($message);
    }
}
