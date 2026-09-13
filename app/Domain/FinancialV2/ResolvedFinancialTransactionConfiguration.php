<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\BankMutationPolicy;
use App\Models\FinancialV2\PostingRuleVersion;
use App\Models\FinancialV2\TransactionType;
use Illuminate\Support\Collection;

final class ResolvedFinancialTransactionConfiguration
{
    /**
     * @param  Collection<int, mixed>  $fundPolicies
     * @param  Collection<int, mixed>  $evidenceRequirements
     */
    public function __construct(
        public readonly TransactionType $transactionType,
        public readonly PostingRuleVersion $postingRuleVersion,
        public readonly string $businessAccountId,
        public readonly ?BankMutationPolicy $bankMutationPolicy,
        public readonly Collection $fundPolicies,
        public readonly Collection $evidenceRequirements,
        public readonly int $requiredApprovalSteps,
    ) {}
}
