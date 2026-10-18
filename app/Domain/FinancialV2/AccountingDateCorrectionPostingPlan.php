<?php

namespace App\Domain\FinancialV2;

use JsonSerializable;

final readonly class AccountingDateCorrectionPostingPlan implements JsonSerializable
{
    /** @param array<string,mixed> $snapshot */
    public function __construct(private array $snapshot) {}

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return $this->snapshot;
    }

    /** @return array<string,mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
