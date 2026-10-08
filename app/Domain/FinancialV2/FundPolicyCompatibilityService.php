<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\Category;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\Program;

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
        $fundIds = collect($fundIds)->filter()->unique()->values();
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

            $funds = Fund::query()->where('accounting_entity_id', $entityId)->whereIn('id', $fundIds)->pluck('name')->join(', ');
            $category = $categoryId ? Category::query()->where('accounting_entity_id', $entityId)->find($categoryId) : null;
            $program = $programId ? Program::query()->where('accounting_entity_id', $entityId)->find($programId) : null;
            $recommendation = $this->configurationResolver->recommendAllocationCategory($entityId, $fundIds, $programId);
            $context = ' Dana: '.($funds ?: '—').'; Kategori: '.($category?->name ?? '—').'; Program: '.($program?->name ?? '—').'.';
            $status = $exception->details['status'] ?? 'MISSING_CONFIGURATION';
            $introduction = match ($status) {
                'POLICY_DENIED' => 'Alokasi belum dapat diproses karena kombinasi ini dilarang oleh aturan penggunaan dana.',
                'INVALID_CONTEXT' => 'Alokasi belum dapat diproses karena konteks dana, kategori, atau program tidak konsisten.',
                default => 'Alokasi belum dapat diproses karena aturan penggunaan dana untuk kombinasi ini belum tersedia.',
            };
            $suggestion = $status === 'MISSING_CONFIGURATION' && $recommendation && $recommendation->id !== $categoryId
                ? " Untuk Program {$program?->name}, kategori yang sesuai adalah {$recommendation->name}."
                : ' Lengkapi konfigurasi penggunaan dana sebelum alokasi diajukan atau disetujui.';

            throw new FinancialDomainException($code, $introduction.$context.$suggestion, $exception->details);
        }
    }
}
