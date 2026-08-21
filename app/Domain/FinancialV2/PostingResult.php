<?php

namespace App\Domain\FinancialV2;

class PostingResult
{
    public function __construct(public readonly string $transactionId, public readonly string $journalId, public readonly string $voucherId) {}
}
