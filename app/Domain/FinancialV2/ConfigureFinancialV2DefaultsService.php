<?php

namespace App\Domain\FinancialV2;

/**
 * Canonical provisioning entry point for deployment and administration.
 * Transaction resolution deliberately has no dependency on this service.
 */
final class ConfigureFinancialV2DefaultsService
{
    public function __construct(private readonly ConfigureMrjBankMutationsService $mrjBankMutations) {}

    /** @return array<string, mixed> */
    public function configureMrjBankMutations(?int $actorUserId = null): array
    {
        return $this->mrjBankMutations->configure($actorUserId);
    }

    /** @return array<string, mixed> */
    public function mrjBankMutationStatus(): array
    {
        return $this->mrjBankMutations->status();
    }
}
