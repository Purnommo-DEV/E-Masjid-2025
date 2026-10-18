<?php

namespace App\Domain\FinancialV2;

/** Pure fail-closed classifier; it never reads or writes application state. */
final class AccountingDateCorrectionWorkflowClassifier
{
    /** @param array<string,mixed> $state @return list<array<string,mixed>> */
    public function replacement(array $state): array
    {
        if (($state['source_available'] ?? false) !== true) return [$this->blocker('REPLACEMENT_REALIZATION_SOURCE_UNAVAILABLE', 'Source workflow Replacement Realization belum tersedia.')];
        if (($state['schema_available'] ?? false) !== true) return [$this->blocker('REPLACEMENT_REALIZATION_SCHEMA_MISSING', 'Source workflow tersedia, tetapi schema Replacement Realization/Distribution Correction belum lengkap.')];
        if (($state['correction_request_exists'] ?? false) !== true || ($state['instance_exists'] ?? false) !== true) return [$this->blocker('REPLACEMENT_REALIZATION_INSTANCE_MISSING', 'Workflow dan schema tersedia, tetapi instance Replacement Realization belum dibuat.')];
        $status = (string) ($state['instance_status'] ?? 'unknown');
        if (! in_array($status, ['approved', 'materialized'], true)) return [$this->blocker('REPLACEMENT_REALIZATION_NOT_APPROVED', 'Instance Replacement Realization tersedia tetapi belum approved.', ['status' => $status])];
        if ($status !== 'materialized' || ($state['lineage_materialized'] ?? false) !== true) return [$this->blocker('REPLACEMENT_REALIZATION_NOT_MATERIALIZED', 'Instance Replacement Realization sudah approved tetapi lineage belum dimaterialisasi lengkap.', ['status' => $status])];
        if (($state['distribution_lineage_complete'] ?? false) !== true) return [$this->blocker('DISTRIBUTION_CORRECTION_LINEAGE_INCOMPLETE', 'Replacement Realization telah dimaterialisasi, tetapi overlay Distribution Correction belum lengkap.')];

        return [];
    }

    /** @param array<string,mixed> $state @return list<array<string,mixed>> */
    public function bankBatch(array $state): array
    {
        if (($state['source_available'] ?? false) !== true) return [$this->blocker('BANK_BATCH_SOURCE_UNAVAILABLE', 'Source workflow koreksi batch bank belum tersedia.')];
        if (($state['schema_available'] ?? false) !== true) return [$this->blocker('BANK_BATCH_SCHEMA_MISSING', 'Source workflow batch tersedia, tetapi schema batch/execution-pair belum lengkap.')];
        if (($state['instance_exists'] ?? false) !== true) return [$this->blocker('BANK_BATCH_INSTANCE_MISSING', 'Workflow dan schema batch tersedia, tetapi instance batch canonical belum dibuat.')];
        $status = (string) ($state['instance_status'] ?? 'unknown');
        if ($status !== 'approved') return [$this->blocker('BANK_BATCH_NOT_APPROVED', 'Instance batch tersedia tetapi belum approved.', ['status' => $status])];
        if (($state['execution_ready'] ?? false) !== true) return [$this->blocker('BANK_BATCH_NOT_EXECUTION_READY', 'Batch approved belum mempunyai anggota dan execution-pair lengkap yang execution-ready.')];

        return [];
    }

    /** @param array<string,mixed> $state @return list<array<string,mixed>> */
    public function finalReadiness(array $state): array
    {
        $blockers = [];
        if (($state['governance_policy_implementable'] ?? false) !== true) $blockers[] = $this->blocker('GOVERNANCE_POLICY_NOT_IMPLEMENTABLE', 'Persetujuan governance telah dikonfirmasi, tetapi pilihan kebijakan teknis belum terdokumentasi lengkap.');
        if (($state['three_distinct_resolved'] ?? false) !== true) $blockers[] = $this->blocker('THREE_DISTINCT_UNRESOLVED', 'Prinsip three_distinct telah disetujui, tetapi actor matrix final belum terdokumentasi sebagai policy executable.');
        if (($state['canonical_plan_execution_ready'] ?? false) !== true) $blockers[] = $this->blocker('CANONICAL_PLAN_NOT_EXECUTION_READY', 'Planner canonical belum menyatakan plan execution-ready.');
        if (($state['individual_executor_available'] ?? false) !== true) $blockers[] = $this->blocker('INDIVIDUAL_EXECUTOR_UNAVAILABLE', 'Executor canonical individual belum tersedia.');
        if (($state['requires_bank_batch'] ?? false) === true && ($state['batch_executor_available'] ?? false) !== true) $blockers[] = $this->blocker('BATCH_EXECUTOR_UNAVAILABLE', 'Executor canonical batch belum tersedia.');

        return $blockers;
    }

    private function blocker(string $code, string $message, array $context = []): array
    {
        return ['code' => $code, 'message' => $message] + $context;
    }
}
