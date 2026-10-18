<?php

namespace Tests\Unit\FinancialV2;

use App\Domain\FinancialV2\AccountingDateCorrectionWorkflowClassifier;
use App\Domain\FinancialV2\AccountingDateCorrectionGovernancePolicy;
use App\Domain\FinancialV2\FinancialDomainException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AccountingDateCorrectionCandidateEvaluatorClassificationTest extends TestCase
{
    #[DataProvider('replacementStates')]
    public function test_replacement_classification_executes_pure_domain_logic(array $state, string $expected): void
    {
        $codes = array_column((new AccountingDateCorrectionWorkflowClassifier)->replacement($state), 'code');
        self::assertSame([$expected], $codes);
        self::assertNotContains('REPLACEMENT_REALIZATION_WORKFLOW_UNAVAILABLE', $codes);
    }

    public static function replacementStates(): array
    {
        return [
            'source unavailable' => [['source_available' => false], 'REPLACEMENT_REALIZATION_SOURCE_UNAVAILABLE'],
            'schema unavailable' => [['source_available' => true, 'schema_available' => false], 'REPLACEMENT_REALIZATION_SCHEMA_MISSING'],
            'instance missing' => [['source_available' => true, 'schema_available' => true, 'correction_request_exists' => true, 'instance_exists' => false], 'REPLACEMENT_REALIZATION_INSTANCE_MISSING'],
            'instance not approved' => [['source_available' => true, 'schema_available' => true, 'correction_request_exists' => true, 'instance_exists' => true, 'instance_status' => 'pending_review'], 'REPLACEMENT_REALIZATION_NOT_APPROVED'],
            'approved but not materialized' => [['source_available' => true, 'schema_available' => true, 'correction_request_exists' => true, 'instance_exists' => true, 'instance_status' => 'approved', 'lineage_materialized' => false], 'REPLACEMENT_REALIZATION_NOT_MATERIALIZED'],
            'distribution lineage incomplete' => [['source_available' => true, 'schema_available' => true, 'correction_request_exists' => true, 'instance_exists' => true, 'instance_status' => 'materialized', 'lineage_materialized' => true, 'distribution_lineage_complete' => false], 'DISTRIBUTION_CORRECTION_LINEAGE_INCOMPLETE'],
        ];
    }

    #[DataProvider('batchStates')]
    public function test_bank_batch_classification_executes_pure_domain_logic(array $state, string $expected): void
    {
        $codes = array_column((new AccountingDateCorrectionWorkflowClassifier)->bankBatch($state), 'code');
        self::assertSame([$expected], $codes);
        self::assertNotContains('BANK_BATCH_GROUP_WORKFLOW_REQUIRED', $codes);
    }

    public static function batchStates(): array
    {
        return [
            'source unavailable' => [['source_available' => false], 'BANK_BATCH_SOURCE_UNAVAILABLE'],
            'schema unavailable' => [['source_available' => true, 'schema_available' => false], 'BANK_BATCH_SCHEMA_MISSING'],
            'batch missing' => [['source_available' => true, 'schema_available' => true, 'instance_exists' => false], 'BANK_BATCH_INSTANCE_MISSING'],
            'batch not approved' => [['source_available' => true, 'schema_available' => true, 'instance_exists' => true, 'instance_status' => 'pending_review'], 'BANK_BATCH_NOT_APPROVED'],
            'batch approved not ready' => [['source_available' => true, 'schema_available' => true, 'instance_exists' => true, 'instance_status' => 'approved', 'execution_ready' => false], 'BANK_BATCH_NOT_EXECUTION_READY'],
        ];
    }

    public function test_global_readiness_remains_fail_closed(): void
    {
        $codes = array_column((new AccountingDateCorrectionWorkflowClassifier)->finalReadiness([
            'governance_policy_implementable' => false,
            'three_distinct_resolved' => false,
            'canonical_plan_execution_ready' => false,
            'individual_executor_available' => false,
            'requires_bank_batch' => true,
            'batch_executor_available' => false,
        ]), 'code');

        self::assertSame([
            'GOVERNANCE_POLICY_NOT_IMPLEMENTABLE',
            'THREE_DISTINCT_UNRESOLVED',
            'CANONICAL_PLAN_NOT_EXECUTION_READY',
            'INDIVIDUAL_EXECUTOR_UNAVAILABLE',
            'BATCH_EXECUTOR_UNAVAILABLE',
        ], $codes);
    }

    public function test_available_workflow_does_not_hide_canonical_plan_blocker(): void
    {
        $classifier = new AccountingDateCorrectionWorkflowClassifier;
        self::assertSame([], $classifier->replacement([
            'source_available' => true, 'schema_available' => true,
            'correction_request_exists' => true, 'instance_exists' => true,
            'instance_status' => 'materialized', 'lineage_materialized' => true,
            'distribution_lineage_complete' => true,
        ]));
        self::assertSame(['CANONICAL_PLAN_NOT_EXECUTION_READY'], array_column($classifier->finalReadiness([
            'governance_policy_implementable' => true,
            'three_distinct_resolved' => true,
            'canonical_plan_execution_ready' => false,
            'individual_executor_available' => true,
            'requires_bank_batch' => false,
        ]), 'code'));
    }

    public function test_unanimous_approval_without_selected_policies_remains_non_implementable(): void
    {
        $policy = new AccountingDateCorrectionGovernancePolicy;
        $decisions = array_fill_keys(AccountingDateCorrectionGovernancePolicy::DECISION_IDS, [
            'status' => 'APPROVED_UNANIMOUSLY',
            'selected_policy' => null,
        ]);

        self::assertFalse($policy->implementationReady($decisions));
        self::assertSame(AccountingDateCorrectionGovernancePolicy::DECISION_IDS, array_column($policy->blockers($decisions), 'decision_id'));
        self::assertSame(['GOVERNANCE_SELECTION_UNDOCUMENTED'], array_values(array_unique(array_column($policy->blockers($decisions), 'code'))));
    }

    public function test_missing_approval_is_distinct_from_missing_selection(): void
    {
        $policy = new AccountingDateCorrectionGovernancePolicy;
        $blockers = $policy->blockers([]);

        self::assertSame(['GOVERNANCE_DECISION_NOT_APPROVED'], array_values(array_unique(array_column($blockers, 'code'))));
        self::assertCount(8, $blockers);
    }

    public function test_ratified_decision_package_is_complete_and_executable(): void
    {
        $policy = new AccountingDateCorrectionGovernancePolicy;
        $decisions = $policy->ratifiedDecisions();

        self::assertSame(AccountingDateCorrectionGovernancePolicy::DECISION_IDS, array_keys($decisions));
        self::assertTrue($policy->implementationReady($decisions));
        self::assertSame([], $policy->blockers($decisions));
        self::assertStringContainsString('without_backdating', $decisions['G-04']['selected_policy']);
        self::assertStringContainsString('verified_bni', $decisions['G-05']['selected_policy']);
        self::assertStringContainsString('atomic_batch', $decisions['G-06']['selected_policy']);
    }

    public function test_three_distinct_accepts_three_different_existing_actor_ids(): void
    {
        (new AccountingDateCorrectionGovernancePolicy)->assertThreeDistinct(11, 22, 33);
        self::addToAssertionCount(1);
    }

    public function test_three_distinct_rejects_any_reused_actor_id(): void
    {
        $this->expectException(FinancialDomainException::class);
        $this->expectExceptionMessage('tiga user yang berbeda');
        (new AccountingDateCorrectionGovernancePolicy)->assertThreeDistinct(11, 22, 11);
    }

    public function test_three_distinct_rejects_invalid_actor_id(): void
    {
        $this->expectException(FinancialDomainException::class);
        $this->expectExceptionMessage('user aplikasi yang valid');
        (new AccountingDateCorrectionGovernancePolicy)->assertThreeDistinct(11, 22, 0);
    }

    public function test_superadmin_override_is_scoped_and_fingerprinted(): void
    {
        $policy = new AccountingDateCorrectionGovernancePolicy;
        $result = $policy->authorizeSuperAdminOverride(
            1, 1, 1, true,
            AccountingDateCorrectionGovernancePolicy::OVERRIDE_TRANSACTION_IDS[0],
            null,
            AccountingDateCorrectionGovernancePolicy::OVERRIDE_REASON,
        );

        self::assertSame('superadmin_override', $result['mode']);
        self::assertSame('transaction', $result['scope_type']);
        self::assertSame(64, strlen($result['fingerprint']));
    }

    public function test_superadmin_override_accepts_only_the_ratified_bank_batch(): void
    {
        $result = (new AccountingDateCorrectionGovernancePolicy)->authorizeSuperAdminOverride(
            2, 2, 2, true, null,
            AccountingDateCorrectionGovernancePolicy::BANK_CORRELATION_ID,
            AccountingDateCorrectionGovernancePolicy::OVERRIDE_REASON,
        );
        self::assertSame('bank_batch', $result['scope_type']);
        self::assertSame(AccountingDateCorrectionGovernancePolicy::BANK_CORRELATION_ID, $result['scope_id']);
    }

    public function test_superadmin_override_rejects_out_of_scope_transaction(): void
    {
        $this->expectException(FinancialDomainException::class);
        $this->expectExceptionMessage('di luar sembilan transaksi');
        (new AccountingDateCorrectionGovernancePolicy)->authorizeSuperAdminOverride(
            1, 1, 1, true, '00000000-0000-0000-0000-000000000000', null,
            AccountingDateCorrectionGovernancePolicy::OVERRIDE_REASON,
        );
    }

    public function test_superadmin_override_rejects_non_superadmin(): void
    {
        $this->expectException(FinancialDomainException::class);
        $this->expectExceptionMessage('role SuperAdmin aktual');
        (new AccountingDateCorrectionGovernancePolicy)->authorizeSuperAdminOverride(
            1, 1, 1, false,
            AccountingDateCorrectionGovernancePolicy::OVERRIDE_TRANSACTION_IDS[0], null,
            AccountingDateCorrectionGovernancePolicy::OVERRIDE_REASON,
        );
    }
}
