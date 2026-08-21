<?php

namespace App\Domain\FinancialV2;

use DomainException;

class FinancialPostingException extends DomainException
{
    public function __construct(public readonly string $failureCode, string $message)
    {
        parent::__construct($message);
    }
}
