<?php

namespace App\Domain\FinancialV2;

use App\Domain\FinancialV2\FinancialDomainException;

/**
 * Pure validator for a ratified governance package. Unanimous approval is a
 * fact, but implementation remains fail-closed until every selected policy is
 * represented explicitly. This class never supplies governance defaults.
 */
final class AccountingDateCorrectionGovernancePolicy
{
    public const DECISION_IDS = ['G-01', 'G-02', 'G-03', 'G-04', 'G-05', 'G-06', 'G-07', 'G-08'];
    public const OVERRIDE_REASON = 'Unanimous governance exception for the nine September 2026 accounting-date corrections.';
    public const BANK_CORRELATION_ID = '147cab75-4255-430e-8bae-6e4c7f869830';
    public const RATIFIED_SELECTIONS = [
        'G-01' => 'scoped_authenticated_superadmin_override_recorded_as_segregation_of_duties_exception',
        'G-02' => 'authenticated_superadmin_authority_scoped_to_ratified_transactions_and_bank_batch',
        'G-03' => 'accounting_date_correction_reason_active_evidence_fingerprint_approval_and_audit_required',
        'G-04' => 'preserve_approved_historical_allocation_and_fund_policy_by_scoped_exception_without_backdating_or_economic_change',
        'G-05' => 'preserve_original_archived_or_inactive_counterparty_by_scoped_exception_and_use_verified_bni_master_for_statement_proven_interest',
        'G-06' => 'all_five_members_of_ratified_bank_correlation_execute_as_one_atomic_batch',
        'G-07' => 'append_only_replacement_realization_and_distribution_overlay_preserving_original_facts',
        'G-08' => 'all_canonical_execution_ready_gates_must_pass_from_locked_current_facts',
    ];
    public const OVERRIDE_TRANSACTION_IDS = [
        'a2eec515-6015-4c9d-8344-2deb3819385a',
        'a2eef5b0-6485-40d1-9f03-8e9fd0510f98',
        'a2eefd9f-9976-4348-b42f-7b66d50879a6',
        'a2ef0da6-1ca3-4216-ac8f-38ac6f3ab571',
        'a2ef0da6-2662-4b31-a8d9-8b79c2c084d3',
        'a2ef0da6-2c04-4474-9524-44f5a0358295',
        'a2ef0da6-31ad-41d5-a6f4-e4c185175aa0',
        'a2ef0da6-3756-4bff-9c90-ef786f9e93d2',
        'a2f1ce2d-e855-44c6-afdc-13a43b5802cb',
    ];

    /** @param array<string,array<string,mixed>> $decisions @return list<array<string,mixed>> */
    public function blockers(array $decisions): array
    {
        $blockers = [];
        foreach (self::DECISION_IDS as $id) {
            $decision = $decisions[$id] ?? null;
            if (! is_array($decision) || ($decision['status'] ?? null) !== 'APPROVED_UNANIMOUSLY') {
                $blockers[] = $this->blocker('GOVERNANCE_DECISION_NOT_APPROVED', "{$id} belum tercatat approved secara bulat.", ['decision_id' => $id]);
                continue;
            }
            if (! is_string($decision['selected_policy'] ?? null) || trim($decision['selected_policy']) === '') {
                $blockers[] = $this->blocker('GOVERNANCE_SELECTION_UNDOCUMENTED', "Pilihan kebijakan final {$id} belum terdokumentasi untuk implementasi.", ['decision_id' => $id]);
            }
        }

        return $blockers;
    }

    /** @param array<string,array<string,mixed>> $decisions */
    public function implementationReady(array $decisions): bool
    {
        return $this->blockers($decisions) === [];
    }

    /** @return array<string,array{status:string,selected_policy:string}> */
    public function ratifiedDecisions(): array
    {
        return collect(self::RATIFIED_SELECTIONS)->mapWithKeys(
            fn (string $selection, string $id): array => [$id => [
                'status' => 'APPROVED_UNANIMOUSLY',
                'selected_policy' => $selection,
            ]]
        )->all();
    }

    /**
     * G-01 ratified policy: maker, checker, and executor are three distinct,
     * existing application users. The caller remains responsible for checking
     * permissions for the requested operation.
     */
    public function assertThreeDistinct(int $makerUserId, int $checkerUserId, int $executorUserId): void
    {
        $actors = [$makerUserId, $checkerUserId, $executorUserId];
        if (collect($actors)->contains(fn (int $actor): bool => $actor <= 0)) {
            throw new FinancialDomainException(
                'E-GOVERNANCE-ACTOR-INVALID',
                'Maker, checker, dan executor harus merujuk user aplikasi yang valid.'
            );
        }

        if (count(array_unique($actors, SORT_NUMERIC)) !== 3) {
            throw new FinancialDomainException(
                'E-GOVERNANCE-THREE-DISTINCT',
                'Maker, checker, dan executor wajib merupakan tiga user yang berbeda.',
                ['maker_user_id' => $makerUserId, 'checker_user_id' => $checkerUserId, 'executor_user_id' => $executorUserId]
            );
        }
    }

    /**
     * Explicit deviation from segregation of duties. It is intentionally
     * narrow and never means that three_distinct has been satisfied.
     *
     * @return array{mode:string,actor_user_id:int,scope_type:string,scope_id:string,reason:string,fingerprint:string}
     */
    public function authorizeSuperAdminOverride(
        int $makerUserId,
        int $checkerUserId,
        int $executorUserId,
        bool $executorIsSuperAdmin,
        ?string $transactionId,
        ?string $correlationId,
        string $reason,
    ): array {
        if ($makerUserId <= 0 || $checkerUserId <= 0 || $executorUserId <= 0) {
            throw new FinancialDomainException('E-GOVERNANCE-ACTOR-INVALID', 'Seluruh tahap override harus merujuk user aplikasi yang valid.');
        }
        if ($makerUserId !== $checkerUserId || $checkerUserId !== $executorUserId) {
            throw new FinancialDomainException('E-GOVERNANCE-OVERRIDE-ACTOR', 'SuperAdmin Override hanya berlaku ketika satu akun yang sama tercatat pada maker, checker, dan executor.');
        }
        if (! $executorIsSuperAdmin) {
            throw new FinancialDomainException('E-GOVERNANCE-OVERRIDE-ROLE', 'SuperAdmin Override hanya dapat digunakan akun dengan role SuperAdmin aktual.');
        }
        if (trim($reason) !== self::OVERRIDE_REASON) {
            throw new FinancialDomainException('E-GOVERNANCE-OVERRIDE-REASON', 'Alasan SuperAdmin Override tidak cocok dengan keputusan governance yang disahkan.');
        }

        $isTransaction = $transactionId !== null && in_array($transactionId, self::OVERRIDE_TRANSACTION_IDS, true);
        $isBatch = $transactionId === null && $correlationId === self::BANK_CORRELATION_ID;
        if (! $isTransaction && ! $isBatch) {
            throw new FinancialDomainException('E-GOVERNANCE-OVERRIDE-SCOPE', 'SuperAdmin Override berada di luar sembilan transaksi atau batch bank yang disahkan.');
        }

        $scopeType = $isBatch ? 'bank_batch' : 'transaction';
        $scopeId = $isBatch ? (string) $correlationId : (string) $transactionId;
        $facts = [
            'mode' => 'superadmin_override',
            'actor_user_id' => $executorUserId,
            'scope_type' => $scopeType,
            'scope_id' => $scopeId,
            'reason' => self::OVERRIDE_REASON,
        ];

        return $facts + ['fingerprint' => hash('sha256', json_encode($facts, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES))];
    }

    private function blocker(string $code, string $message, array $context = []): array
    {
        return ['code' => $code, 'message' => $message] + $context;
    }
}

