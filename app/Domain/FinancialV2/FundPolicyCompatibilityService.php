<?php

namespace App\Domain\FinancialV2;

/**
 * Budget-allocation adapter for the canonical transaction configuration
 * resolver. It deliberately contains no policy lookup of its own.
 */
final class FundPolicyCompatibilityService
{
    public function __construct(
        private readonly FinancialTransactionConfigurationResolver $configurationResolver,
    ) {}

    /**
     * Validates whether every planned funding source can be used for the
     * proposed Payment dimensions. PostingEngine repeats the authoritative
     * check against the final transaction when a realization is posted.
     *
     * @param  iterable<string>  $fundIds
     */
    public function assertAllocationCompatible(
        string $entityId,
        iterable $fundIds,
        string $effectiveDate,
        ?string $accountId,
        ?string $categoryId,
        ?string $programId,
    ): void {
        try {
            $this->configurationResolver->assertFundPolicyCompatibility(
                $entityId,
                $fundIds,
                $effectiveDate,
                $accountId,
                $categoryId,
                $programId,
            );
        } catch (FinancialPostingException $exception) {
            $code = $exception->failureCode === 'E-CONFIGURATION-MISSING'
                ? 'E-BUDGET-POLICY'
                : $exception->failureCode;

            throw new FinancialDomainException($code, $exception->getMessage());
        }
    }
}
