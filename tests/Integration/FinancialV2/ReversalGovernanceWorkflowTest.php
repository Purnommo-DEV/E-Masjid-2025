<?php

namespace Tests\Integration\FinancialV2;

use App\Domain\FinancialV2\FinancialDomainException;
use App\Domain\FinancialV2\GovernanceDocumentWorkflowService;
use App\Domain\FinancialV2\ReversalConfigurationWorkflowService;
use App\Domain\FinancialV2\ReversalPrerequisiteWorkflowService;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\AuditEvent;
use App\Models\FinancialV2\GovernanceDocument;
use App\Models\FinancialV2\GovernanceDocumentDecision;
use App\Models\FinancialV2\ReversalConfigurationRequest;
use App\Models\FinancialV2\ReversalPrerequisiteDecision;
use App\Models\FinancialV2\ReversalPrerequisiteProposal;
use App\Models\FinancialV2\TransactionType;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ReversalGovernanceWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    private string $entityId;

    /** @var list<int> */
    private array $actors;

    protected function setUp(): void
    {
        parent::setUp();

        $connection = DB::connection();
        self::assertSame('mysql', $connection->getName());
        self::assertSame('mrj_test_db', (string) $connection->selectOne('SELECT DATABASE() AS database_name')->database_name);
        self::assertStringStartsWith('8.4.9', (string) $connection->selectOne('SELECT VERSION() AS server_version')->server_version);
        self::assertGreaterThan(0, $connection->transactionLevel(), 'DatabaseTransactions harus aktif sebelum fixture ditulis.');

        foreach ([GovernanceDocument::class, GovernanceDocumentDecision::class, ReversalPrerequisiteProposal::class, ReversalPrerequisiteDecision::class, ReversalConfigurationRequest::class, AuditEvent::class] as $model) {
            self::assertSame($connection->getName(), (new $model)->getConnection()->getName());
        }

        Storage::fake('local');
        self::assertStringContainsString('framework', Storage::disk('local')->path(''));

        $users = User::factory()->count(3)->create();
        $this->actors = $users->pluck('id')->map(static fn ($id): int => (int) $id)->all();

        $entity = AccountingEntity::query()->create([
            'code' => 'REVTEST-'.Str::upper(Str::random(12)),
            'name' => 'REV isolated integration test',
            'legal_name' => 'REV isolated integration test',
            'functional_currency' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'fiscal_year_start_month' => 1,
            'status' => 'active',
            'created_by_user_id' => $this->actors[0],
            'updated_by_user_id' => $this->actors[0],
        ]);
        $this->entityId = $entity->id;
    }

    public function test_governance_draft_is_canonical_idempotent_and_blocks_parallel_active_workflow(): void
    {
        $service = app(GovernanceDocumentWorkflowService::class);
        $key = 'gov-'.Str::uuid();
        $document = $service->createDraft($this->entityId, $this->policyFile(), 'reversal_master_configuration', 'Board mandate', $key, $this->actors[0]);

        self::assertSame('draft', $document->status);
        self::assertSame(hash('sha256', json_encode(\Illuminate\Support\Arr::sortRecursive($document->document_snapshot), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)), $document->snapshot_hash);
        self::assertSame($document->id, $service->createDraft($this->entityId, $this->policyFile(), 'reversal_master_configuration', 'Board mandate', $key, $this->actors[0])->id);
        self::assertSame(1, GovernanceDocument::query()->where('accounting_entity_id', $this->entityId)->count());
        self::assertSame(1, AuditEvent::query()->where('target_id', $document->id)->count());

        $this->expectException(FinancialDomainException::class);
        $service->createDraft($this->entityId, $this->policyFile('other.pdf'), 'reversal_master_configuration', 'Other mandate', 'gov-'.Str::uuid(), $this->actors[0]);
    }

    public function test_governance_lifecycle_enforces_maker_checker_append_only_and_model_immutability(): void
    {
        $service = app(GovernanceDocumentWorkflowService::class);
        $document = $this->approvedGovernance($service);

        self::assertSame('approved', $document->status);
        self::assertEqualsCanonicalizing(['submitted', 'approved'], $document->decisions->pluck('decision')->all());
        self::assertSame(3, AuditEvent::query()->where('target_id', $document->id)->count());

        try {
            $document->forceFill(['status' => 'cancelled'])->save();
            self::fail('forceFill()->save() harus ditolak.');
        } catch (DomainException) {
            self::assertSame('approved', $document->fresh()->status);
        }

        $decision = $document->decisions->first();
        $this->expectException(DomainException::class);
        $decision->forceFill(['note' => 'tampered'])->save();
    }

    public function test_governance_integrity_failure_rolls_back_transition_decision_and_audit(): void
    {
        $service = app(GovernanceDocumentWorkflowService::class);
        $document = $service->createDraft($this->entityId, $this->policyFile(), 'reversal_master_configuration', 'Integrity check', 'gov-'.Str::uuid(), $this->actors[0]);
        $service->submit($this->entityId, $document->id, $this->actors[0]);
        $decisionsBefore = GovernanceDocumentDecision::query()->where('governance_document_id', $document->id)->count();
        $auditBefore = AuditEvent::query()->where('target_id', $document->id)->count();
        Storage::disk('local')->delete($document->fresh()->attachment->storage_reference);

        try {
            $service->approve($this->entityId, $document->id, $this->actors[1], 'must fail');
            self::fail('Approval dengan file hilang harus gagal.');
        } catch (FinancialDomainException) {
            self::assertSame('pending_review', $document->fresh()->status);
            self::assertSame($decisionsBefore, GovernanceDocumentDecision::query()->where('governance_document_id', $document->id)->count());
            self::assertSame($auditBefore, AuditEvent::query()->where('target_id', $document->id)->count());
        }
    }

    public function test_governance_reject_supersede_and_cancel_follow_append_only_lifecycle(): void
    {
        $service = app(GovernanceDocumentWorkflowService::class);

        $rejected = $service->createDraft($this->entityId, $this->policyFile('rejected.pdf'), 'reversal_master_configuration', 'Rejected mandate', 'gov-'.Str::uuid(), $this->actors[0]);
        $service->submit($this->entityId, $rejected->id, $this->actors[0]);
        $rejected = $service->reject($this->entityId, $rejected->id, $this->actors[1], 'insufficient authority');
        self::assertSame('rejected', $rejected->status);
        self::assertEqualsCanonicalizing(['submitted', 'rejected'], $rejected->decisions->pluck('decision')->all());

        $predecessor = $this->approvedGovernance($service);
        $successor = $service->createDraft($this->entityId, $this->policyFile('successor.pdf'), 'reversal_master_configuration', 'Replacement mandate', 'gov-'.Str::uuid(), $this->actors[0]);
        $service->submit($this->entityId, $successor->id, $this->actors[0]);
        $successor = $service->approve($this->entityId, $successor->id, $this->actors[1], 'replacement approved');
        self::assertSame('superseded', $predecessor->fresh()->status);
        self::assertSame('approved', $successor->status);

        $cancelled = $service->createDraft($this->entityId, $this->policyFile('cancelled.pdf'), 'reversal_master_configuration', 'Cancelled mandate', 'gov-'.Str::uuid(), $this->actors[0]);
        $cancelled = $service->cancel($this->entityId, $cancelled->id, $this->actors[0], 'withdrawn');
        self::assertSame('cancelled', $cancelled->status);
        self::assertSame(['cancelled'], $cancelled->decisions->pluck('decision')->all());
    }

    public function test_prerequisite_materialization_creates_only_draft_masters_and_freezes_lineage(): void
    {
        $governance = app(GovernanceDocumentWorkflowService::class);
        $prerequisites = app(ReversalPrerequisiteWorkflowService::class);
        $document = $this->approvedGovernance($governance);
        $proposal = $prerequisites->createDraft($this->entityId, $this->prerequisiteInput($document->id, 'maker_checker'), $this->actors[0]);
        $prerequisites->submit($this->entityId, $proposal->id, $this->actors[0]);
        $prerequisites->approve($this->entityId, $proposal->id, $this->actors[1], 'approved');
        $proposal = $prerequisites->materialize($this->entityId, $proposal->id, $this->actors[2]);

        self::assertSame('materialized', $proposal->status);
        self::assertSame('draft', $proposal->transactionType->status);
        self::assertSame('draft', $proposal->documentSequence->status);
        self::assertSame('REV', $proposal->transactionType->code);

        $this->expectException(DomainException::class);
        $proposal->forceFill(['transaction_type_id' => (string) Str::uuid()])->save();
    }

    public function test_three_distinct_remains_fail_closed_without_partial_materialization(): void
    {
        $governance = app(GovernanceDocumentWorkflowService::class);
        $prerequisites = app(ReversalPrerequisiteWorkflowService::class);
        $document = $this->approvedGovernance($governance);
        $proposal = $prerequisites->createDraft($this->entityId, $this->prerequisiteInput($document->id, 'three_distinct'), $this->actors[0]);
        $prerequisites->submit($this->entityId, $proposal->id, $this->actors[0]);
        $prerequisites->approve($this->entityId, $proposal->id, $this->actors[1], 'approved');
        $decisionCount = ReversalPrerequisiteDecision::query()->where('prerequisite_proposal_id', $proposal->id)->count();

        try {
            $prerequisites->materialize($this->entityId, $proposal->id, $this->actors[2]);
            self::fail('three_distinct harus fail-closed.');
        } catch (FinancialDomainException) {
            self::assertSame('approved', $proposal->fresh()->status);
            self::assertNull($proposal->fresh()->transaction_type_id);
            self::assertSame(0, TransactionType::query()->where('accounting_entity_id', $this->entityId)->where('code', 'REV')->count());
            self::assertSame($decisionCount, ReversalPrerequisiteDecision::query()->where('prerequisite_proposal_id', $proposal->id)->count());
        }
    }

    public function test_prerequisite_idempotency_active_guard_and_maker_checker_are_enforced(): void
    {
        $governance = app(GovernanceDocumentWorkflowService::class);
        $prerequisites = app(ReversalPrerequisiteWorkflowService::class);
        $document = $this->approvedGovernance($governance);
        $input = $this->prerequisiteInput($document->id, 'maker_checker');
        $proposal = $prerequisites->createDraft($this->entityId, $input, $this->actors[0]);
        self::assertSame($proposal->id, $prerequisites->createDraft($this->entityId, $input, $this->actors[0])->id);

        try {
            $prerequisites->createDraft($this->entityId, $this->prerequisiteInput($document->id, 'maker_checker'), $this->actors[0]);
            self::fail('Proposal aktif kedua harus ditolak.');
        } catch (FinancialDomainException) {
            self::assertSame(1, ReversalPrerequisiteProposal::query()->where('accounting_entity_id', $this->entityId)->count());
        }

        $prerequisites->submit($this->entityId, $proposal->id, $this->actors[0]);
        try {
            $prerequisites->approve($this->entityId, $proposal->id, $this->actors[0], 'self approval');
            self::fail('Maker tidak boleh menyetujui proposal sendiri.');
        } catch (FinancialDomainException) {
            self::assertSame('pending_review', $proposal->fresh()->status);
        }
    }

    public function test_configuration_draft_is_private_immutable_and_not_activated(): void
    {
        $governance = app(GovernanceDocumentWorkflowService::class);
        $prerequisites = app(ReversalPrerequisiteWorkflowService::class);
        $configuration = app(ReversalConfigurationWorkflowService::class);
        $document = $this->approvedGovernance($governance);
        $proposal = $prerequisites->createDraft($this->entityId, $this->prerequisiteInput($document->id, 'maker_checker'), $this->actors[0]);
        $prerequisites->submit($this->entityId, $proposal->id, $this->actors[0]);
        $prerequisites->approve($this->entityId, $proposal->id, $this->actors[1], 'approved');
        $proposal = $prerequisites->materialize($this->entityId, $proposal->id, $this->actors[2]);
        $request = $configuration->createDraft($this->entityId, $this->configurationInput($proposal->id), $this->actors[0]);

        self::assertSame('draft', $request->status);
        self::assertNull($request->activated_at);
        self::assertSame(0, DB::table('financial_v2_posting_rules')->where('accounting_entity_id', $this->entityId)->count());

        try {
            ReversalConfigurationRequest::query()->create($request->getAttributes());
            self::fail('Pembuatan model langsung harus ditolak.');
        } catch (DomainException) {
            self::assertSame(1, ReversalConfigurationRequest::query()->where('accounting_entity_id', $this->entityId)->count());
        }

        $this->expectException(DomainException::class);
        $request->forceFill(['status' => 'activated'])->save();
    }

    public function test_configuration_lifecycle_enforces_maker_checker_and_cancellation_without_activation(): void
    {
        $governance = app(GovernanceDocumentWorkflowService::class);
        $prerequisites = app(ReversalPrerequisiteWorkflowService::class);
        $configuration = app(ReversalConfigurationWorkflowService::class);
        $document = $this->approvedGovernance($governance);
        $proposal = $prerequisites->createDraft($this->entityId, $this->prerequisiteInput($document->id, 'maker_checker'), $this->actors[0]);
        $prerequisites->submit($this->entityId, $proposal->id, $this->actors[0]);
        $prerequisites->approve($this->entityId, $proposal->id, $this->actors[1], 'approved');
        $proposal = $prerequisites->materialize($this->entityId, $proposal->id, $this->actors[2]);
        $request = $configuration->createDraft($this->entityId, $this->configurationInput($proposal->id), $this->actors[0]);
        $configuration->submit($this->entityId, $request->id, $this->actors[0]);

        try {
            $configuration->approve($this->entityId, $request->id, $this->actors[0], 'self approval');
            self::fail('Maker tidak boleh menyetujui konfigurasi sendiri.');
        } catch (FinancialDomainException) {
            self::assertSame('pending_review', $request->fresh()->status);
        }

        $request = $configuration->approve($this->entityId, $request->id, $this->actors[1], 'approved');
        self::assertSame('approved', $request->status);
        $request = $configuration->cancel($this->entityId, $request->id, $this->actors[2], 'activation intentionally not tested');
        self::assertSame('cancelled', $request->status);
        self::assertNull($request->activated_at);
        self::assertEqualsCanonicalizing(['submitted', 'approved', 'cancelled'], $request->decisions->pluck('decision')->all());
        self::assertSame(0, DB::table('financial_v2_posting_rules')->where('accounting_entity_id', $this->entityId)->count());
    }

    private function approvedGovernance(GovernanceDocumentWorkflowService $service): GovernanceDocument
    {
        $document = $service->createDraft($this->entityId, $this->policyFile(), 'reversal_master_configuration', 'Approved mandate', 'gov-'.Str::uuid(), $this->actors[0]);
        $service->submit($this->entityId, $document->id, $this->actors[0]);
        return $service->approve($this->entityId, $document->id, $this->actors[1], 'approved');
    }

    private function policyFile(string $name = 'governance.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\nREV governance integration fixture\n%%EOF");
    }

    /** @return array<string, mixed> */
    private function prerequisiteInput(string $documentId, string $separationRule): array
    {
        $suffix = Str::upper(Str::random(8));
        return ['governance_document_id' => $documentId, 'transaction_type_code' => 'REV', 'transaction_type_name' => 'Reversal '.$suffix, 'transaction_type_description' => 'Canonical reversal type', 'voucher_prefix' => 'RV', 'sequence_code' => 'REVSEQ-'.$suffix, 'sequence_name' => 'REV Sequence '.$suffix, 'scope_key' => 'REV-'.$suffix, 'reset_rule' => 'yearly', 'initial_number' => 1, 'effective_from' => '2026-10-01', 'effective_to' => null, 'activation_separation_rule' => $separationRule, 'idempotency_key' => 'prereq-'.Str::uuid()];
    }

    /** @return array<string, mixed> */
    private function configurationInput(string $proposalId): array
    {
        $suffix = Str::upper(Str::random(8));
        return ['prerequisite_proposal_id' => $proposalId, 'posting_rule_code' => 'REV-RULE-'.$suffix, 'posting_rule_name' => 'REV Rule '.$suffix, 'input_contract_ref' => 'rev-input-v1', 'journal_template_ref' => 'source-journal-reversal', 'business_rule_refs' => 'REV-CANONICAL', 'reason_code' => 'REV-'.$suffix, 'reason_name' => 'Reversal correction', 'required_approval_steps' => 2, 'evidence_requirements' => [['evidence_type' => 'approval', 'minimum_count' => 1], ['evidence_type' => 'policy', 'minimum_count' => 1]], 'effective_from' => '2026-10-01', 'effective_to' => null, 'idempotency_key' => 'config-'.Str::uuid()];
    }
}
