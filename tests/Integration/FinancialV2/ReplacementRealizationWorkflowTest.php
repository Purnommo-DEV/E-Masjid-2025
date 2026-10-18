<?php

namespace Tests\Integration\FinancialV2;

use App\Domain\FinancialV2\BudgetAllocationService;
use App\Domain\FinancialV2\FinancialDomainException;
use App\Domain\FinancialV2\FinancialTransactionLifecycleService;
use App\Domain\FinancialV2\ReplacementRealizationWorkflowService;
use App\Models\FinancialV2\AccountingDateCorrectionRequest;
use App\Models\FinancialV2\AccountingDateCorrectionLineage;
use App\Models\FinancialV2\AttachmentLink;
use App\Models\FinancialV2\AuditEvent;
use App\Models\FinancialV2\DistributionCorrectionLineage;
use App\Models\FinancialV2\EvidenceRequirement;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\FundRealization;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\JournalLine;
use App\Models\FinancialV2\LedgerEntry;
use App\Models\FinancialV2\ReplacementRealizationDecision;
use App\Models\FinancialV2\ReplacementRealizationLineage;
use App\Models\FinancialV2\ReplacementRealizationRequest;
use DomainException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Support\UatFinancialFixture;

final class ReplacementRealizationWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        self::assertSame('mrj_test_db', DB::selectOne('SELECT DATABASE() AS name')->name);
        self::assertGreaterThan(0, DB::connection()->transactionLevel());
    }

    public function test_workflow_tables_are_additive_and_empty_before_fixture_creation(): void
    {
        foreach (['financial_v2_replacement_realization_requests', 'financial_v2_replacement_realization_decisions', 'financial_v2_replacement_realization_lineages', 'financial_v2_distribution_correction_lineages'] as $table) {
            self::assertTrue(DB::getSchemaBuilder()->hasTable($table));
            self::assertSame(0, DB::table($table)->count(), "{$table} harus kosong sebelum fixture test dibuat.");
        }
    }

    public function test_models_reject_public_creation_and_preserve_append_only_contract(): void
    {
        foreach ([ReplacementRealizationRequest::class, ReplacementRealizationDecision::class, ReplacementRealizationLineage::class, DistributionCorrectionLineage::class] as $model) {
            try {
                (new $model)->forceFill(['id' => fake()->uuid()])->save();
                self::fail("{$model} tidak boleh dibuat melalui Eloquent publik.");
            } catch (DomainException) {
                self::assertTrue(true);
            }
        }
    }

    public function test_workflow_schema_enforces_write_once_unique_lineage_contracts(): void
    {
        $indexes = DB::select("SELECT INDEX_NAME, TABLE_NAME, NON_UNIQUE FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('financial_v2_replacement_realization_requests','financial_v2_replacement_realization_lineages','financial_v2_distribution_correction_lineages')");
        $unique = collect($indexes)->where('NON_UNIQUE', 0)->pluck('INDEX_NAME')->all();
        foreach (['fv2_repl_real_req_idem_uq', 'fv2_repl_real_req_active_uq', 'fv2_repl_real_lin_req_uq', 'fv2_repl_real_lin_corr_uq', 'fv2_repl_real_lin_new_uq', 'fv2_dist_corr_lin_dist_uq', 'fv2_dist_corr_lin_repl_uq'] as $name) self::assertContains($name, $unique);
    }

    public function test_draft_submit_approve_enforces_idempotency_active_guard_and_maker_checker(): void
    {
        $fixture = $this->workflowFixture(); $service = app(ReplacementRealizationWorkflowService::class);
        $input = $this->replacementInput($fixture);
        $draft = $service->createDraft($fixture['entity_id'], $fixture['correction']->id, $input, $fixture['actors'][0]);
        self::assertSame('draft', $draft->status);
        self::assertSame($draft->id, $service->createDraft($fixture['entity_id'], $fixture['correction']->id, $input, $fixture['actors'][0])->id);

        try {
            $service->createDraft($fixture['entity_id'], $fixture['correction']->id, array_merge($input, ['idempotency_key' => (string) Str::uuid()]), $fixture['actors'][0]);
            self::fail('Workflow aktif kedua harus ditolak.');
        } catch (FinancialDomainException $e) { self::assertSame('E-REPL-REAL-ACTIVE', $e->failureCode); }

        try { $service->submit($fixture['entity_id'], $draft->id, $fixture['actors'][1]); self::fail('Non-maker tidak boleh submit.'); }
        catch (FinancialDomainException $e) { self::assertSame('E-REPL-REAL-MAKER', $e->failureCode); }
        $service->submit($fixture['entity_id'], $draft->id, $fixture['actors'][0]);
        try {
            $service->approve($fixture['entity_id'], $draft->id, $fixture['actors'][0]);
            self::fail('Self approval harus ditolak.');
        } catch (FinancialDomainException $e) { self::assertSame('E-REPL-REAL-MAKER-CHECKER', $e->failureCode); }
        $approved = $service->approve($fixture['entity_id'], $draft->id, $fixture['actors'][1], 'reviewed');
        self::assertSame('approved', $approved->status);
        self::assertEqualsCanonicalizing(['submitted', 'approved'], $approved->decisions->pluck('decision')->all());
    }

    public function test_reject_and_cancel_release_active_guard_without_partial_lineage(): void
    {
        $fixture = $this->workflowFixture(); $service = app(ReplacementRealizationWorkflowService::class);
        $first = $service->createDraft($fixture['entity_id'], $fixture['correction']->id, $this->replacementInput($fixture), $fixture['actors'][0]);
        $service->submit($fixture['entity_id'], $first->id, $fixture['actors'][0]);
        $first = $service->reject($fixture['entity_id'], $first->id, $fixture['actors'][1], 'rejected fixture');
        self::assertSame('rejected', $first->status); self::assertNull($first->active_correction_guard);

        $second = $service->createDraft($fixture['entity_id'], $fixture['correction']->id, $this->replacementInput($fixture, (string) Str::uuid()), $fixture['actors'][0]);
        $second = $service->cancel($fixture['entity_id'], $second->id, $fixture['actors'][0], 'withdrawn');
        self::assertSame('cancelled', $second->status); self::assertNull($second->active_correction_guard);
        self::assertSame(0, ReplacementRealizationLineage::query()->count());
    }

    public function test_evidence_drift_fails_before_transition_without_partial_decision_or_audit(): void
    {
        $fixture = $this->workflowFixture(); $service = app(ReplacementRealizationWorkflowService::class);
        $draft = $service->createDraft($fixture['entity_id'], $fixture['correction']->id, $this->replacementInput($fixture), $fixture['actors'][0]);
        $service->submit($fixture['entity_id'], $draft->id, $fixture['actors'][0]);
        AttachmentLink::query()->where('attachment_id', $fixture['replacement_evidence_id'])->update(['status' => 'superseded']);
        $before = [ReplacementRealizationDecision::count(), AuditEvent::where('target_id', $draft->id)->count()];
        try { $service->approve($fixture['entity_id'], $draft->id, $fixture['actors'][1]); self::fail('Evidence drift harus ditolak.'); }
        catch (FinancialDomainException $e) { self::assertSame('E-REPL-REAL-EVIDENCE', $e->failureCode); }
        self::assertSame('pending_review', $draft->fresh()->status);
        self::assertSame($before, [ReplacementRealizationDecision::count(), AuditEvent::where('target_id', $draft->id)->count()]);
    }

    public function test_allocation_date_and_replacement_financial_fact_drift_are_revalidated(): void
    {
        $fixture = $this->workflowFixture(); $service = app(ReplacementRealizationWorkflowService::class);
        $draft = $service->createDraft($fixture['entity_id'], $fixture['correction']->id, $this->replacementInput($fixture), $fixture['actors'][0]);
        $service->submit($fixture['entity_id'], $draft->id, $fixture['actors'][0]);
        $versionId = $fixture['original_realization']->budget_allocation_version_id;
        $originalEffective = DB::table('financial_v2_budget_allocation_versions')->where('id', $versionId)->value('effective_from');
        DB::table('financial_v2_budget_allocation_versions')->where('id', $versionId)->update(['effective_from' => now()->addDays(2)->toDateString()]);
        try { $service->approve($fixture['entity_id'], $draft->id, $fixture['actors'][1]); self::fail('Allocation yang belum efektif harus ditolak.'); }
        catch (FinancialDomainException $e) { self::assertSame('E-REPL-REAL-ALLOCATION', $e->failureCode); }
        DB::table('financial_v2_budget_allocation_versions')->where('id', $versionId)->update(['effective_from' => $originalEffective]);

        DB::table('financial_v2_transactions')->where('id', $fixture['replacement']->id)->update(['gross_amount' => '101.00']);
        try { $service->approve($fixture['entity_id'], $draft->id, $fixture['actors'][1]); self::fail('Nominal replacement yang berubah harus ditolak.'); }
        catch (FinancialDomainException $e) { self::assertSame('E-REPL-REAL-TRANSACTION', $e->failureCode); }
        self::assertSame('pending_review', $draft->fresh()->status);
        self::assertSame(0, ReplacementRealizationLineage::count());
    }

    public function test_three_distinct_materialization_is_atomic_and_leaves_original_facts_unchanged(): void
    {
        $fixture = $this->workflowFixture(); $service = app(ReplacementRealizationWorkflowService::class);
        $draft = $service->createDraft($fixture['entity_id'], $fixture['correction']->id, $this->replacementInput($fixture), $fixture['actors'][0]);
        $service->submit($fixture['entity_id'], $draft->id, $fixture['actors'][0]);
        $approved = $service->approve($fixture['entity_id'], $draft->id, $fixture['actors'][1]);
        $facts = [FundRealization::count(), Journal::count(), JournalLine::count(), LedgerEntry::count(), ReplacementRealizationLineage::count(), DistributionCorrectionLineage::count(), ReplacementRealizationDecision::count(), AuditEvent::where('target_id', $draft->id)->count()];
        $original = $fixture['original_realization']->fresh()->getAttributes();
        try { $service->materialize($fixture['entity_id'], $approved->id, $fixture['actors'][2]); self::fail('three_distinct unresolved harus fail-closed.'); }
        catch (FinancialDomainException $e) { self::assertSame('E-REPL-REAL-THREE-DISTINCT-UNRESOLVED', $e->failureCode); }
        self::assertSame('approved', $approved->fresh()->status);
        self::assertSame($facts, [FundRealization::count(), Journal::count(), JournalLine::count(), LedgerEntry::count(), ReplacementRealizationLineage::count(), DistributionCorrectionLineage::count(), ReplacementRealizationDecision::count(), AuditEvent::where('target_id', $draft->id)->count()]);
        self::assertSame($original, $fixture['original_realization']->fresh()->getAttributes());
    }

    /** @return array<string,mixed> */
    private function workflowFixture(): array
    {
        $context = UatFinancialFixture::context();
        $actors = \App\Models\User::factory()->count(3)->create()->pluck('id')->map(fn ($id) => (int) $id)->all();
        EvidenceRequirement::query()->create(['accounting_entity_id' => $context['entity']->id, 'posting_rule_version_id' => $context['paymentVersion']->id, 'evidence_type' => 'invoice', 'minimum_count' => 1]);
        $receipt = UatFinancialFixture::receipt($context, '500.00'); UatFinancialFixture::advance($receipt); UatFinancialFixture::post($receipt, 'repl-fixture-receipt');
        $allocation = app(BudgetAllocationService::class)->create(['accounting_entity_id' => $context['entity']->id, 'accounting_period_id' => $context['period']->id, 'fund_id' => $context['fund']->id, 'program_id' => $context['program']->id, 'account_id' => $context['expense']->id, 'category_id' => $context['paymentCategory']->id, 'allocation_reference' => 'REPL-'.Str::uuid(), 'idempotency_key' => 'repl-'.Str::uuid(), 'allocated_amount' => '200.00', 'effective_from' => now()->toDateString(), 'reason' => 'Replacement workflow fixture'], $actors[0]);
        $version = $allocation->versions->first(); app(BudgetAllocationService::class)->submit($allocation->id, $actors[0]); app(BudgetAllocationService::class)->approveVersion($allocation->id, $version->id, $actors[1]);
        $original = $this->realization($context, $version->id, now()->toDateString(), $actors[0]);
        UatFinancialFixture::attachTransactionEvidence($context, $original, 'invoice', $actors[0]); UatFinancialFixture::advance($original, $actors[0]); UatFinancialFixture::post($original, 'repl-original-'.Str::uuid(), $actors[0]);
        $replacement = $this->realization($context, $version->id, now()->addDay()->toDateString(), $actors[0]);
        UatFinancialFixture::attachTransactionEvidence($context, $replacement, 'invoice', $actors[0]); UatFinancialFixture::advance($replacement, $actors[0]); UatFinancialFixture::post($replacement, 'repl-new-'.Str::uuid(), $actors[0]);
        $originalEvidence = AttachmentLink::where('target_id', $original->id)->where('status', 'active')->value('attachment_id');
        $correctionId = (string) Str::uuid(); $originalJournal = Journal::where('transaction_id', $original->id)->sole();
        DB::table((new AccountingDateCorrectionRequest)->getTable())->insert(['id' => $correctionId, 'accounting_entity_id' => $context['entity']->id, 'original_transaction_id' => $original->id, 'active_transaction_guard' => $original->id, 'original_journal_id' => $originalJournal->id, 'evidence_attachment_id' => $originalEvidence, 'original_accounting_date' => now()->toDateString(), 'target_accounting_date' => now()->addDay()->toDateString(), 'reason' => 'Runtime replacement test with immutable original facts.', 'status' => 'blocked', 'preview_snapshot' => json_encode(['execution_ready' => false]), 'blocking_reasons' => json_encode([['code' => 'REALIZATION_REPLACEMENT_WORKFLOW_REQUIRED']]), 'idempotency_key' => (string) Str::uuid(), 'request_fingerprint' => hash('sha256', $correctionId), 'requested_by_user_id' => $actors[0], 'requested_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        DB::table((new AccountingDateCorrectionLineage)->getTable())->insert(['id' => (string) Str::uuid(), 'correction_request_id' => $correctionId, 'original_transaction_id' => $original->id, 'original_journal_id' => $originalJournal->id, 'original_realization_id' => FundRealization::where('transaction_id', $original->id)->value('id'), 'created_at' => now(), 'updated_at' => now()]);
        $correction = AccountingDateCorrectionRequest::findOrFail($correctionId);
        return ['context' => $context, 'entity_id' => $context['entity']->id, 'actors' => $actors, 'correction' => $correction, 'original' => $original, 'replacement' => $replacement, 'original_realization' => FundRealization::where('transaction_id', $original->id)->firstOrFail(), 'replacement_evidence_id' => AttachmentLink::where('target_id', $replacement->id)->where('status', 'active')->value('attachment_id')];
    }

    private function realization(array $context, string $versionId, string $date, int $actor): FinancialTransaction
    {
        return app(FinancialTransactionLifecycleService::class)->createRealization(['accounting_entity_id' => $context['entity']->id, 'transaction_type_id' => $context['paymentType']->id, 'business_date' => $date, 'accounting_date' => $date, 'gross_amount' => '100.00', 'source_reference' => 'REPL-'.Str::uuid(), 'idempotency_key' => 'repl-tx-'.Str::uuid(), 'primary_financial_account_id' => $context['accountA']->id, 'counterparty_id' => $context['supplier']->id, 'category_id' => $context['paymentCategory']->id, 'description' => 'Replacement workflow fixture'], [['account_id' => $context['expense']->id, 'split_amount' => '100.00', 'fund_id' => $context['fund']->id, 'program_id' => $context['program']->id]], $versionId, $actor);
    }

    private function replacementInput(array $fixture, ?string $idempotencyKey = null): array
    {
        return ['replacement_transaction_id' => $fixture['replacement']->id, 'evidence_attachment_id' => $fixture['replacement_evidence_id'], 'separation_policy' => 'three_distinct', 'idempotency_key' => $idempotencyKey ?? (string) Str::uuid()];
    }
}
