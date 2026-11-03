<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DEPENDENCY = '2026_10_13_000001_harden_financial_v2_reversal_configuration_workflow';

    private const TABLES = [
        'financial_v2_governance_documents',
        'financial_v2_governance_document_decisions',
        'financial_v2_reversal_prerequisite_proposals',
        'financial_v2_reversal_prerequisite_decisions',
    ];

    public function up(): void
    {
        $uuid = $this->preflight();

        Schema::create('financial_v2_governance_documents', function (Blueprint $table) use ($uuid) {
            $table->charset = $uuid['financial_v2_accounting_entities']['charset'];
            $table->collation = $uuid['financial_v2_accounting_entities']['collation'];
            $this->uuid($table, 'id', $uuid['financial_v2_accounting_entities'])->primary();
            $this->uuid($table, 'accounting_entity_id', $uuid['financial_v2_accounting_entities']);
            $this->uuid($table, 'attachment_id', $uuid['financial_v2_attachments']);
            $this->uuid($table, 'supersedes_document_id', $uuid['financial_v2_accounting_entities'])->nullable();
            $table->string('purpose', 80);
            $table->string('document_basis', 500);
            $table->json('document_snapshot');
            $table->char('snapshot_hash', 64);
            $table->string('idempotency_key', 120);
            $this->uuid($table, 'active_purpose_guard', $uuid['financial_v2_accounting_entities'])->nullable();
            $table->enum('status', ['draft', 'pending_review', 'approved', 'rejected', 'superseded', 'cancelled'])->default('draft');
            $table->unsignedBigInteger('created_by_user_id');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by_user_id')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('cancelled_by_user_id')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->unique(['accounting_entity_id', 'idempotency_key'], 'fv2_gov_doc_idem_uq');
            $table->unique(['active_purpose_guard', 'purpose'], 'fv2_gov_doc_active_purpose_uq');
            $table->index(['accounting_entity_id', 'purpose', 'status'], 'fv2_gov_doc_scope_ix');
            $table->foreign('accounting_entity_id', 'fv2_gov_doc_entity_fk')->references('id')->on('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreign('attachment_id', 'fv2_gov_doc_attachment_fk')->references('id')->on('financial_v2_attachments')->restrictOnDelete();
            $table->foreign('supersedes_document_id', 'fv2_gov_doc_supersedes_fk')->references('id')->on('financial_v2_governance_documents')->restrictOnDelete();
            $table->foreign('created_by_user_id', 'fv2_gov_doc_creator_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('approved_by_user_id', 'fv2_gov_doc_approver_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('cancelled_by_user_id', 'fv2_gov_doc_canceller_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('financial_v2_governance_document_decisions', function (Blueprint $table) use ($uuid) {
            $table->charset = $uuid['financial_v2_accounting_entities']['charset'];
            $table->collation = $uuid['financial_v2_accounting_entities']['collation'];
            $this->uuid($table, 'id', $uuid['financial_v2_accounting_entities'])->primary();
            $this->uuid($table, 'accounting_entity_id', $uuid['financial_v2_accounting_entities']);
            $this->uuid($table, 'governance_document_id', $uuid['financial_v2_accounting_entities']);
            $table->enum('decision', ['submitted', 'approved', 'rejected', 'superseded', 'cancelled']);
            $table->json('document_snapshot');
            $table->char('snapshot_hash', 64);
            $table->text('note')->nullable();
            $table->unsignedBigInteger('actor_user_id');
            $table->timestamp('decided_at');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['governance_document_id', 'decided_at'], 'fv2_gov_doc_dec_timeline_ix');
            $table->foreign('accounting_entity_id', 'fv2_gov_doc_dec_entity_fk')->references('id')->on('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreign('governance_document_id', 'fv2_gov_doc_dec_doc_fk')->references('id')->on('financial_v2_governance_documents')->restrictOnDelete();
            $table->foreign('actor_user_id', 'fv2_gov_doc_dec_actor_fk')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::create('financial_v2_reversal_prerequisite_proposals', function (Blueprint $table) use ($uuid) {
            $table->charset = $uuid['financial_v2_accounting_entities']['charset'];
            $table->collation = $uuid['financial_v2_accounting_entities']['collation'];
            $this->uuid($table, 'id', $uuid['financial_v2_accounting_entities'])->primary();
            $this->uuid($table, 'accounting_entity_id', $uuid['financial_v2_accounting_entities']);
            $this->uuid($table, 'governance_document_id', $uuid['financial_v2_accounting_entities']);
            $this->uuid($table, 'governance_document_decision_id', $uuid['financial_v2_accounting_entities']);
            $table->json('proposal_snapshot');
            $table->char('snapshot_hash', 64);
            $table->string('idempotency_key', 120);
            $this->uuid($table, 'active_entity_guard', $uuid['financial_v2_accounting_entities'])->nullable();
            $table->enum('status', ['draft', 'pending_review', 'approved', 'materialized', 'rejected', 'cancelled'])->default('draft');
            $this->uuid($table, 'transaction_type_id', $uuid['financial_v2_transaction_types'])->nullable();
            $this->uuid($table, 'document_sequence_id', $uuid['financial_v2_document_sequences'])->nullable();
            $table->unsignedBigInteger('created_by_user_id');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by_user_id')->nullable();
            $table->timestamp('materialized_at')->nullable();
            $table->unsignedBigInteger('materialized_by_user_id')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('cancelled_by_user_id')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->unique(['accounting_entity_id', 'idempotency_key'], 'fv2_rev_prereq_idem_uq');
            $table->unique('active_entity_guard', 'fv2_rev_prereq_active_entity_uq');
            $table->unique('transaction_type_id', 'fv2_rev_prereq_type_uq');
            $table->unique('document_sequence_id', 'fv2_rev_prereq_sequence_uq');
            $table->index(['accounting_entity_id', 'status'], 'fv2_rev_prereq_status_ix');
            $table->foreign('accounting_entity_id', 'fv2_rev_prereq_entity_fk')->references('id')->on('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreign('governance_document_id', 'fv2_rev_prereq_gov_doc_fk')->references('id')->on('financial_v2_governance_documents')->restrictOnDelete();
            $table->foreign('governance_document_decision_id', 'fv2_rev_prereq_gov_dec_fk')->references('id')->on('financial_v2_governance_document_decisions')->restrictOnDelete();
            $table->foreign('transaction_type_id', 'fv2_rev_prereq_type_fk')->references('id')->on('financial_v2_transaction_types')->restrictOnDelete();
            $table->foreign('document_sequence_id', 'fv2_rev_prereq_sequence_fk')->references('id')->on('financial_v2_document_sequences')->restrictOnDelete();
            $table->foreign('created_by_user_id', 'fv2_rev_prereq_creator_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('approved_by_user_id', 'fv2_rev_prereq_approver_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('materialized_by_user_id', 'fv2_rev_prereq_materializer_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('cancelled_by_user_id', 'fv2_rev_prereq_canceller_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('financial_v2_reversal_prerequisite_decisions', function (Blueprint $table) use ($uuid) {
            $table->charset = $uuid['financial_v2_accounting_entities']['charset'];
            $table->collation = $uuid['financial_v2_accounting_entities']['collation'];
            $this->uuid($table, 'id', $uuid['financial_v2_accounting_entities'])->primary();
            $this->uuid($table, 'accounting_entity_id', $uuid['financial_v2_accounting_entities']);
            $this->uuid($table, 'prerequisite_proposal_id', $uuid['financial_v2_accounting_entities']);
            $table->enum('decision', ['submitted', 'approved', 'rejected', 'materialized', 'cancelled']);
            $table->json('proposal_snapshot');
            $table->char('snapshot_hash', 64);
            $table->text('note')->nullable();
            $table->unsignedBigInteger('actor_user_id');
            $table->timestamp('decided_at');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['prerequisite_proposal_id', 'decided_at'], 'fv2_rev_prereq_dec_timeline_ix');
            $table->foreign('accounting_entity_id', 'fv2_rev_prereq_dec_entity_fk')->references('id')->on('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreign('prerequisite_proposal_id', 'fv2_rev_prereq_dec_proposal_fk')->references('id')->on('financial_v2_reversal_prerequisite_proposals')->restrictOnDelete();
            $table->foreign('actor_user_id', 'fv2_rev_prereq_dec_actor_fk')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('financial_v2_reversal_configuration_requests', function (Blueprint $table) use ($uuid) {
            $this->uuid($table, 'prerequisite_proposal_id', $uuid['financial_v2_accounting_entities'])->nullable()->after('document_sequence_id');
            $this->uuid($table, 'governance_document_decision_id', $uuid['financial_v2_accounting_entities'])->nullable()->after('governance_attachment_id');
            $table->foreign('prerequisite_proposal_id', 'fv2_rev_cfg_prereq_fk')->references('id')->on('financial_v2_reversal_prerequisite_proposals')->restrictOnDelete();
            $table->foreign('governance_document_decision_id', 'fv2_rev_cfg_gov_dec_fk')->references('id')->on('financial_v2_governance_document_decisions')->restrictOnDelete();
            $table->timestamp('cancelled_at')->nullable()->after('activated_at');
            $table->unsignedBigInteger('cancelled_by_user_id')->nullable()->after('activated_by_user_id');
            $table->text('cancellation_reason')->nullable()->after('cancelled_by_user_id');
            $table->foreign('cancelled_by_user_id', 'fv2_rev_cfg_canceller_fk')->references('id')->on('users')->nullOnDelete();
        });

        $this->postflight($uuid);
    }

    public function down(): void
    {
        throw new RuntimeException('Histori prerequisite dan dokumen governance REV hanya boleh dipulihkan melalui migration forward-only yang ditinjau.');
    }

    /** @return array<string,array{type:string,length:int,charset:string,collation:string}> */
    private function preflight(): array
    {
        if (! Schema::hasTable('migrations') || ! DB::table('migrations')->where('migration', self::DEPENDENCY)->exists()) {
            throw new RuntimeException('Migration hardening REV wajib tercatat berhasil sebelum migration prerequisite.');
        }
        foreach (['financial_v2_reversal_configuration_requests', 'financial_v2_reversal_configuration_decisions', 'financial_v2_journals'] as $required) {
            if (! Schema::hasTable($required)) throw new RuntimeException("Dependency schema {$required} tidak tersedia meskipun migration tercatat.");
        }
        $this->assertDependencyContract();
        foreach (['governance_attachment_id', 'document_sequence_id', 'evidence_requirement_ids'] as $column) {
            if (! Schema::hasColumn('financial_v2_reversal_configuration_requests', $column)) throw new RuntimeException("Kolom hardening {$column} belum tersedia.");
        }
        $this->assertForeignContract('fv2_rev_cfg_gov_attachment_fk', 'financial_v2_reversal_configuration_requests', 'governance_attachment_id', 'financial_v2_attachments', 'id', 'RESTRICT', 'RESTRICT');
        $this->assertForeignContract('fv2_rev_cfg_doc_sequence_fk', 'financial_v2_reversal_configuration_requests', 'document_sequence_id', 'financial_v2_document_sequences', 'id', 'RESTRICT', 'RESTRICT');
        $this->assertIndexContract('financial_v2_journals', 'fv2_journal_reversal_once_uq', true, ['reversal_of_journal_id']);
        if (DB::table('financial_v2_reversal_configuration_requests')->exists()) throw new RuntimeException('Request REV lama terdeteksi. Reconciliation forward-only diperlukan sebelum kolom integrasi nullable ditambahkan.');
        $partial = array_values(array_filter(self::TABLES, fn (string $table): bool => Schema::hasTable($table)));
        foreach (['prerequisite_proposal_id', 'governance_document_decision_id', 'cancelled_at', 'cancelled_by_user_id', 'cancellation_reason'] as $column) {
            if (Schema::hasColumn('financial_v2_reversal_configuration_requests', $column)) {
                $partial[] = 'financial_v2_reversal_configuration_requests.'.$column;
            }
        }
        if ($partial !== []) {
            throw new RuntimeException('Terdeteksi schema prerequisite REV parsial/konflik: '.implode(', ', $partial).'. Gunakan inspeksi dan recovery forward-only.');
        }
        foreach (['fv2_rev_cfg_prereq_fk', 'fv2_rev_cfg_gov_dec_fk', 'fv2_rev_cfg_canceller_fk'] as $foreign) {
            if ($this->foreignExists($foreign)) throw new RuntimeException("Terdeteksi foreign key integrasi parsial {$foreign}. Gunakan recovery forward-only.");
        }
        $this->assertUserKey();
        $tables = ['financial_v2_accounting_entities', 'financial_v2_attachments', 'financial_v2_transaction_types', 'financial_v2_document_sequences'];

        return collect($tables)->mapWithKeys(fn (string $table): array => [$table => $this->uuidDefinition($table)])->all();
    }

    private function postflight(array $uuid): void
    {
        foreach (self::TABLES as $table) if (! Schema::hasTable($table)) throw new RuntimeException("Postflight gagal: {$table} tidak terbentuk lengkap. Hentikan dan lakukan recovery forward-only.");
        foreach (['prerequisite_proposal_id', 'governance_document_decision_id', 'cancelled_at', 'cancelled_by_user_id', 'cancellation_reason'] as $column) {
            if (! Schema::hasColumn('financial_v2_reversal_configuration_requests', $column)) throw new RuntimeException("Postflight gagal: kolom integrasi {$column} tidak tersedia.");
        }
        foreach (['fv2_gov_doc_entity_fk', 'fv2_gov_doc_attachment_fk', 'fv2_gov_doc_canceller_fk', 'fv2_rev_prereq_entity_fk', 'fv2_rev_prereq_type_fk', 'fv2_rev_prereq_sequence_fk', 'fv2_rev_prereq_canceller_fk', 'fv2_rev_cfg_prereq_fk', 'fv2_rev_cfg_gov_dec_fk', 'fv2_rev_cfg_canceller_fk'] as $foreign) {
            if (! $this->foreignExists($foreign)) throw new RuntimeException("Postflight gagal: foreign key {$foreign} tidak tersedia.");
        }
        foreach ([['financial_v2_governance_documents', 'id'], ['financial_v2_governance_documents', 'accounting_entity_id'], ['financial_v2_reversal_prerequisite_proposals', 'id'], ['financial_v2_reversal_prerequisite_proposals', 'accounting_entity_id']] as [$table, $column]) {
            if ($this->columnDefinition($table, $column) !== $uuid['financial_v2_accounting_entities']) throw new RuntimeException("Postflight gagal: {$table}.{$column} tidak kompatibel dengan UUID Entity.");
        }
        foreach ([
            ['financial_v2_governance_documents', 'attachment_id', 'financial_v2_attachments'],
            ['financial_v2_reversal_prerequisite_proposals', 'transaction_type_id', 'financial_v2_transaction_types'],
            ['financial_v2_reversal_prerequisite_proposals', 'document_sequence_id', 'financial_v2_document_sequences'],
        ] as [$table, $column, $parent]) {
            if ($this->columnDefinition($table, $column) !== $uuid[$parent]) throw new RuntimeException("Postflight gagal: {$table}.{$column} tidak kompatibel dengan {$parent}.id.");
        }
        $this->assertUserKey();
        $this->assertNewSchemaContract();
    }

    private function assertDependencyContract(): void
    {
        $request = 'financial_v2_reversal_configuration_requests';
        foreach ([
            ['id', 'char', 36, false, null], ['accounting_entity_id', 'char', 36, false, null],
            ['configuration_snapshot', 'json', null, false, null], ['snapshot_hash', 'char', 64, false, null],
            ['governance_document_ref', 'varchar', 500, false, null], ['idempotency_key', 'varchar', 120, false, null],
            ['active_entity_guard', 'char', 36, true, null], ['created_by_user_id', 'bigint', null, false, null],
            ['approved_by_user_id', 'bigint', null, true, null], ['activated_by_user_id', 'bigint', null, true, null],
            ['submitted_at', 'timestamp', null, true, null], ['approved_at', 'timestamp', null, true, null],
            ['activated_at', 'timestamp', null, true, null], ['created_at', 'timestamp', null, true, null],
            ['updated_at', 'timestamp', null, true, null],
            ['transaction_type_id', 'char', 36, true, null], ['posting_rule_id', 'char', 36, true, null],
            ['posting_rule_version_id', 'char', 36, true, null], ['reason_code_id', 'char', 36, true, null],
            ['approval_requirement_id', 'char', 36, true, null],
            ['governance_attachment_id', 'char', 36, true, null], ['document_sequence_id', 'char', 36, true, null],
            ['evidence_requirement_ids', 'json', null, true, null],
        ] as $definition) $this->assertColumnContract($request, ...$definition);
        $this->assertEnumContract($request, 'status', ['draft', 'pending_review', 'approved', 'rejected', 'activated', 'cancelled'], false, 'draft');
        $decision = 'financial_v2_reversal_configuration_decisions';
        foreach ([['id', 'char', 36, false, null], ['accounting_entity_id', 'char', 36, false, null], ['configuration_request_id', 'char', 36, false, null], ['configuration_snapshot', 'json', null, false, null], ['snapshot_hash', 'char', 64, false, null], ['note', 'text', null, true, null], ['actor_user_id', 'bigint', null, false, null], ['decided_at', 'timestamp', null, false, null], ['created_at', 'timestamp', null, false, '__CURRENT_TIMESTAMP__']] as $definition) $this->assertColumnContract($decision, ...$definition);
        $this->assertEnumContract($decision, 'decision', ['submitted', 'approved', 'rejected', 'activated', 'cancelled'], false, null);
        foreach ([
            ['fv2_rev_cfg_entity_fk', $request, 'accounting_entity_id', 'financial_v2_accounting_entities', 'id', 'RESTRICT', 'RESTRICT'],
            ['fv2_rev_cfg_creator_fk', $request, 'created_by_user_id', 'users', 'id', 'RESTRICT', 'RESTRICT'],
            ['fv2_rev_cfg_approver_fk', $request, 'approved_by_user_id', 'users', 'id', 'SET NULL', 'RESTRICT'],
            ['fv2_rev_cfg_activator_fk', $request, 'activated_by_user_id', 'users', 'id', 'SET NULL', 'RESTRICT'],
            ['fv2_rev_cfg_type_fk', $request, 'transaction_type_id', 'financial_v2_transaction_types', 'id', 'RESTRICT', 'RESTRICT'],
            ['fv2_rev_cfg_rule_fk', $request, 'posting_rule_id', 'financial_v2_posting_rules', 'id', 'RESTRICT', 'RESTRICT'],
            ['fv2_rev_cfg_rule_ver_fk', $request, 'posting_rule_version_id', 'financial_v2_posting_rule_versions', 'id', 'RESTRICT', 'RESTRICT'],
            ['fv2_rev_cfg_reason_fk', $request, 'reason_code_id', 'financial_v2_reason_codes', 'id', 'RESTRICT', 'RESTRICT'],
            ['fv2_rev_cfg_approval_req_fk', $request, 'approval_requirement_id', 'financial_v2_approval_requirements', 'id', 'RESTRICT', 'RESTRICT'],
            ['fv2_rev_cfg_dec_request_fk', $decision, 'configuration_request_id', $request, 'id', 'RESTRICT', 'RESTRICT'],
            ['fv2_rev_cfg_dec_entity_fk', $decision, 'accounting_entity_id', 'financial_v2_accounting_entities', 'id', 'RESTRICT', 'RESTRICT'],
            ['fv2_rev_cfg_dec_actor_fk', $decision, 'actor_user_id', 'users', 'id', 'RESTRICT', 'RESTRICT'],
        ] as $fk) $this->assertForeignContract(...$fk);
        $this->assertIndexContract($request, 'fv2_rev_cfg_idem_uq', true, ['accounting_entity_id', 'idempotency_key']);
        $this->assertIndexContract($request, 'fv2_rev_cfg_active_entity_uq', true, ['active_entity_guard']);
        $this->assertIndexContract($request, 'fv2_rev_cfg_entity_status_ix', false, ['accounting_entity_id', 'status']);
        $this->assertIndexContract($decision, 'fv2_rev_cfg_dec_timeline_ix', false, ['configuration_request_id', 'decided_at']);
        $this->assertColumnContract('financial_v2_posting_rule_versions', 'compilation_strategy', 'varchar', 40, false, 'rule_lines');
        $this->assertIndexContract('financial_v2_posting_rule_versions', 'fv2_rule_ver_compile_ix', false, ['accounting_entity_id', 'compilation_strategy']);
        $this->assertCharacterColumnsUseTableDefault('financial_v2_posting_rule_versions', ['compilation_strategy']);
        $this->assertCharacterColumnsUseTableDefault($request, ['configuration_snapshot','snapshot_hash','governance_document_ref','idempotency_key','status','evidence_requirement_ids']);
        $this->assertCharacterColumnsUseTableDefault($decision, ['decision','configuration_snapshot','snapshot_hash','note']);
        $this->assertActorColumns([$request => ['created_by_user_id', 'approved_by_user_id', 'activated_by_user_id'], $decision => ['actor_user_id']]);
    }

    private function assertNewSchemaContract(): void
    {
        $entityCollation = $this->columnDefinition('financial_v2_accounting_entities', 'id')['collation'];
        foreach (self::TABLES as $table) $this->assertTableContract($table, $entityCollation);
        $columns = [
            'financial_v2_governance_documents' => [['id','char',36,false,null],['accounting_entity_id','char',36,false,null],['attachment_id','char',36,false,null],['supersedes_document_id','char',36,true,null],['purpose','varchar',80,false,null],['document_basis','varchar',500,false,null],['document_snapshot','json',null,false,null],['snapshot_hash','char',64,false,null],['idempotency_key','varchar',120,false,null],['active_purpose_guard','char',36,true,null],['created_by_user_id','bigint',null,false,null],['submitted_at','timestamp',null,true,null],['approved_at','timestamp',null,true,null],['approved_by_user_id','bigint',null,true,null],['cancelled_at','timestamp',null,true,null],['cancelled_by_user_id','bigint',null,true,null],['cancellation_reason','text',null,true,null],['created_at','timestamp',null,true,null],['updated_at','timestamp',null,true,null]],
            'financial_v2_governance_document_decisions' => [['id','char',36,false,null],['accounting_entity_id','char',36,false,null],['governance_document_id','char',36,false,null],['document_snapshot','json',null,false,null],['snapshot_hash','char',64,false,null],['note','text',null,true,null],['actor_user_id','bigint',null,false,null],['decided_at','timestamp',null,false,null],['created_at','timestamp',null,false,'__CURRENT_TIMESTAMP__']],
            'financial_v2_reversal_prerequisite_proposals' => [['id','char',36,false,null],['accounting_entity_id','char',36,false,null],['governance_document_id','char',36,false,null],['governance_document_decision_id','char',36,false,null],['proposal_snapshot','json',null,false,null],['snapshot_hash','char',64,false,null],['idempotency_key','varchar',120,false,null],['active_entity_guard','char',36,true,null],['transaction_type_id','char',36,true,null],['document_sequence_id','char',36,true,null],['created_by_user_id','bigint',null,false,null],['submitted_at','timestamp',null,true,null],['approved_at','timestamp',null,true,null],['approved_by_user_id','bigint',null,true,null],['materialized_at','timestamp',null,true,null],['materialized_by_user_id','bigint',null,true,null],['cancelled_at','timestamp',null,true,null],['cancelled_by_user_id','bigint',null,true,null],['cancellation_reason','text',null,true,null],['created_at','timestamp',null,true,null],['updated_at','timestamp',null,true,null]],
            'financial_v2_reversal_prerequisite_decisions' => [['id','char',36,false,null],['accounting_entity_id','char',36,false,null],['prerequisite_proposal_id','char',36,false,null],['proposal_snapshot','json',null,false,null],['snapshot_hash','char',64,false,null],['note','text',null,true,null],['actor_user_id','bigint',null,false,null],['decided_at','timestamp',null,false,null],['created_at','timestamp',null,false,'__CURRENT_TIMESTAMP__']],
        ];
        foreach ($columns as $table => $definitions) foreach ($definitions as $definition) $this->assertColumnContract($table, ...$definition);
        foreach ([['prerequisite_proposal_id','char',36,true,null],['governance_document_decision_id','char',36,true,null],['cancelled_at','timestamp',null,true,null],['cancelled_by_user_id','bigint',null,true,null],['cancellation_reason','text',null,true,null]] as $definition) $this->assertColumnContract('financial_v2_reversal_configuration_requests', ...$definition);
        $this->assertCharacterColumnsUseTableDefault('financial_v2_reversal_configuration_requests', ['cancellation_reason']);
        $this->assertEnumContract('financial_v2_governance_documents', 'status', ['draft','pending_review','approved','rejected','superseded','cancelled'], false, 'draft');
        $this->assertEnumContract('financial_v2_governance_document_decisions', 'decision', ['submitted','approved','rejected','superseded','cancelled'], false, null);
        $this->assertEnumContract('financial_v2_reversal_prerequisite_proposals', 'status', ['draft','pending_review','approved','materialized','rejected','cancelled'], false, 'draft');
        $this->assertEnumContract('financial_v2_reversal_prerequisite_decisions', 'decision', ['submitted','approved','rejected','materialized','cancelled'], false, null);
        $this->assertCharacterColumnsUseTableDefault('financial_v2_governance_documents', ['purpose','document_basis','document_snapshot','snapshot_hash','idempotency_key','status','cancellation_reason']);
        $this->assertCharacterColumnsUseTableDefault('financial_v2_governance_document_decisions', ['decision','document_snapshot','snapshot_hash','note']);
        $this->assertCharacterColumnsUseTableDefault('financial_v2_reversal_prerequisite_proposals', ['proposal_snapshot','snapshot_hash','idempotency_key','status','cancellation_reason']);
        $this->assertCharacterColumnsUseTableDefault('financial_v2_reversal_prerequisite_decisions', ['decision','proposal_snapshot','snapshot_hash','note']);
        foreach ([
            ['financial_v2_governance_documents','fv2_gov_doc_idem_uq',true,['accounting_entity_id','idempotency_key']], ['financial_v2_governance_documents','fv2_gov_doc_active_purpose_uq',true,['active_purpose_guard','purpose']], ['financial_v2_governance_documents','fv2_gov_doc_scope_ix',false,['accounting_entity_id','purpose','status']],
            ['financial_v2_governance_document_decisions','fv2_gov_doc_dec_timeline_ix',false,['governance_document_id','decided_at']], ['financial_v2_reversal_prerequisite_proposals','fv2_rev_prereq_idem_uq',true,['accounting_entity_id','idempotency_key']], ['financial_v2_reversal_prerequisite_proposals','fv2_rev_prereq_active_entity_uq',true,['active_entity_guard']], ['financial_v2_reversal_prerequisite_proposals','fv2_rev_prereq_type_uq',true,['transaction_type_id']], ['financial_v2_reversal_prerequisite_proposals','fv2_rev_prereq_sequence_uq',true,['document_sequence_id']], ['financial_v2_reversal_prerequisite_proposals','fv2_rev_prereq_status_ix',false,['accounting_entity_id','status']], ['financial_v2_reversal_prerequisite_decisions','fv2_rev_prereq_dec_timeline_ix',false,['prerequisite_proposal_id','decided_at']]
        ] as $index) $this->assertIndexContract(...$index);
        foreach ([
            ['fv2_gov_doc_entity_fk','financial_v2_governance_documents','accounting_entity_id','financial_v2_accounting_entities','id','RESTRICT','RESTRICT'], ['fv2_gov_doc_attachment_fk','financial_v2_governance_documents','attachment_id','financial_v2_attachments','id','RESTRICT','RESTRICT'], ['fv2_gov_doc_supersedes_fk','financial_v2_governance_documents','supersedes_document_id','financial_v2_governance_documents','id','RESTRICT','RESTRICT'], ['fv2_gov_doc_creator_fk','financial_v2_governance_documents','created_by_user_id','users','id','RESTRICT','RESTRICT'], ['fv2_gov_doc_approver_fk','financial_v2_governance_documents','approved_by_user_id','users','id','SET NULL','RESTRICT'], ['fv2_gov_doc_canceller_fk','financial_v2_governance_documents','cancelled_by_user_id','users','id','SET NULL','RESTRICT'],
            ['fv2_gov_doc_dec_entity_fk','financial_v2_governance_document_decisions','accounting_entity_id','financial_v2_accounting_entities','id','RESTRICT','RESTRICT'], ['fv2_gov_doc_dec_doc_fk','financial_v2_governance_document_decisions','governance_document_id','financial_v2_governance_documents','id','RESTRICT','RESTRICT'], ['fv2_gov_doc_dec_actor_fk','financial_v2_governance_document_decisions','actor_user_id','users','id','RESTRICT','RESTRICT'],
            ['fv2_rev_prereq_entity_fk','financial_v2_reversal_prerequisite_proposals','accounting_entity_id','financial_v2_accounting_entities','id','RESTRICT','RESTRICT'], ['fv2_rev_prereq_gov_doc_fk','financial_v2_reversal_prerequisite_proposals','governance_document_id','financial_v2_governance_documents','id','RESTRICT','RESTRICT'], ['fv2_rev_prereq_gov_dec_fk','financial_v2_reversal_prerequisite_proposals','governance_document_decision_id','financial_v2_governance_document_decisions','id','RESTRICT','RESTRICT'], ['fv2_rev_prereq_type_fk','financial_v2_reversal_prerequisite_proposals','transaction_type_id','financial_v2_transaction_types','id','RESTRICT','RESTRICT'], ['fv2_rev_prereq_sequence_fk','financial_v2_reversal_prerequisite_proposals','document_sequence_id','financial_v2_document_sequences','id','RESTRICT','RESTRICT'], ['fv2_rev_prereq_creator_fk','financial_v2_reversal_prerequisite_proposals','created_by_user_id','users','id','RESTRICT','RESTRICT'], ['fv2_rev_prereq_approver_fk','financial_v2_reversal_prerequisite_proposals','approved_by_user_id','users','id','SET NULL','RESTRICT'], ['fv2_rev_prereq_materializer_fk','financial_v2_reversal_prerequisite_proposals','materialized_by_user_id','users','id','SET NULL','RESTRICT'], ['fv2_rev_prereq_canceller_fk','financial_v2_reversal_prerequisite_proposals','cancelled_by_user_id','users','id','SET NULL','RESTRICT'],
            ['fv2_rev_prereq_dec_entity_fk','financial_v2_reversal_prerequisite_decisions','accounting_entity_id','financial_v2_accounting_entities','id','RESTRICT','RESTRICT'], ['fv2_rev_prereq_dec_proposal_fk','financial_v2_reversal_prerequisite_decisions','prerequisite_proposal_id','financial_v2_reversal_prerequisite_proposals','id','RESTRICT','RESTRICT'], ['fv2_rev_prereq_dec_actor_fk','financial_v2_reversal_prerequisite_decisions','actor_user_id','users','id','RESTRICT','RESTRICT'],
            ['fv2_rev_cfg_prereq_fk','financial_v2_reversal_configuration_requests','prerequisite_proposal_id','financial_v2_reversal_prerequisite_proposals','id','RESTRICT','RESTRICT'], ['fv2_rev_cfg_gov_dec_fk','financial_v2_reversal_configuration_requests','governance_document_decision_id','financial_v2_governance_document_decisions','id','RESTRICT','RESTRICT'], ['fv2_rev_cfg_canceller_fk','financial_v2_reversal_configuration_requests','cancelled_by_user_id','users','id','SET NULL','RESTRICT'],
        ] as $fk) $this->assertForeignContract(...$fk);
        $this->assertActorColumns(['financial_v2_governance_documents'=>['created_by_user_id','approved_by_user_id','cancelled_by_user_id'],'financial_v2_governance_document_decisions'=>['actor_user_id'],'financial_v2_reversal_prerequisite_proposals'=>['created_by_user_id','approved_by_user_id','materialized_by_user_id','cancelled_by_user_id'],'financial_v2_reversal_prerequisite_decisions'=>['actor_user_id'],'financial_v2_reversal_configuration_requests'=>['cancelled_by_user_id']]);
    }

    private function assertColumnContract(string $table, string $column, string $type, ?int $length, bool $nullable, mixed $default): void
    {
        $actual = DB::selectOne('SELECT DATA_TYPE, COLUMN_TYPE, CHARACTER_MAXIMUM_LENGTH, CHARACTER_SET_NAME, COLLATION_NAME, IS_NULLABLE, COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?', [$table, $column]);
        $actualType = strtolower((string) ($actual->DATA_TYPE ?? ''));
        // MariaDB exposes JSON aliases as LONGTEXT plus a JSON_VALID check,
        // while MySQL exposes the native JSON type. Both are valid only when
        // the MariaDB representation retains its generated check constraint.
        $typeMatches = $type === 'json'
            ? in_array($actualType, ['json', 'longtext'], true)
            : $actualType === $type;
        if ($type === 'json' && $actualType === 'longtext' && ! $this->jsonCheckExists($table, $column)) {
            $typeMatches = false;
        }
        $actualDefault = $this->normalizedDefault($actual->COLUMN_DEFAULT ?? null);
        $defaultMatches = $default === '__CURRENT_TIMESTAMP__'
            ? str_starts_with(strtoupper((string) $actualDefault), 'CURRENT_TIMESTAMP')
            : ($default === null
            ? $actualDefault === null
            : (string) $actualDefault === (string) $default);
        $expectedColumnType = match ($type) {
            'char', 'varchar' => $type.'('.$length.')',
            'bigint' => 'bigint unsigned',
            default => $type,
        };
        $actualColumnType = strtolower((string) ($actual->COLUMN_TYPE ?? ''));
        $columnTypeMatches = $type === 'json'
            ? in_array($actualColumnType, ['json', 'longtext'], true)
            : ($type === 'bigint'
                ? preg_match('/^bigint(?:\(\d+\))? unsigned$/', $actualColumnType) === 1
                : $actualColumnType === $expectedColumnType);
        if (! $actual || ! $typeMatches || ! $columnTypeMatches || ($length !== null && (int) $actual->CHARACTER_MAXIMUM_LENGTH !== $length)
            || (($actual->IS_NULLABLE === 'YES') !== $nullable) || ! $defaultMatches) {
            throw new RuntimeException("Schema contract gagal untuk {$table}.{$column}.");
        }
    }

    private function assertTableContract(string $table, string $expectedCollation): void
    {
        $actual = DB::selectOne('SELECT TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?', [$table]);
        if (! $actual || (string) $actual->TABLE_COLLATION !== $expectedCollation) throw new RuntimeException("Table collation contract gagal untuk {$table}.");
    }

    private function normalizedDefault(mixed $value): ?string
    {
        if ($value === null || in_array(strtoupper((string) $value), ['NULL', "'NULL'"], true)) return null;
        $text = (string) $value;
        if (strlen($text) >= 2 && $text[0] === "'" && $text[strlen($text) - 1] === "'") return substr($text, 1, -1);
        return $text;
    }

    /** @param array<int,string> $columns */
    private function assertCharacterColumnsUseTableDefault(string $table, array $columns): void
    {
        $tableMeta = DB::selectOne('SELECT t.TABLE_COLLATION, c.CHARACTER_SET_NAME FROM information_schema.TABLES t JOIN information_schema.COLLATION_CHARACTER_SET_APPLICABILITY c ON c.COLLATION_NAME=t.TABLE_COLLATION WHERE t.TABLE_SCHEMA=DATABASE() AND t.TABLE_NAME=?', [$table]);
        if (! $tableMeta) throw new RuntimeException("Metadata charset tabel {$table} tidak tersedia.");
        foreach ($columns as $column) {
            $actual = DB::selectOne('SELECT DATA_TYPE, CHARACTER_SET_NAME, COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?', [$table, $column]);
            if (! $actual) throw new RuntimeException("Metadata charset {$table}.{$column} tidak tersedia.");
            if (strtolower((string) $actual->DATA_TYPE) === 'json') {
                if ($actual->CHARACTER_SET_NAME !== null || $actual->COLLATION_NAME !== null) throw new RuntimeException("Native JSON {$table}.{$column} tidak boleh memiliki charset/collation string.");
                continue;
            }
            if (strtolower((string) $actual->DATA_TYPE) === 'longtext' && $this->jsonCheckExists($table, $column)) {
                if ((string) $actual->CHARACTER_SET_NAME !== (string) $tableMeta->CHARACTER_SET_NAME || blank($actual->COLLATION_NAME)) throw new RuntimeException("MariaDB JSON alias {$table}.{$column} tidak memakai charset tabel atau tidak mempunyai collation.");
                continue;
            }
            if ((string) $actual->CHARACTER_SET_NAME !== (string) $tableMeta->CHARACTER_SET_NAME || (string) $actual->COLLATION_NAME !== (string) $tableMeta->TABLE_COLLATION) throw new RuntimeException("Charset/collation contract gagal untuk {$table}.{$column}.");
        }
    }

    private function jsonCheckExists(string $table, string $column): bool
    {
        $checks = DB::select('SELECT cc.CHECK_CLAUSE FROM information_schema.TABLE_CONSTRAINTS tc JOIN information_schema.CHECK_CONSTRAINTS cc ON cc.CONSTRAINT_SCHEMA=tc.CONSTRAINT_SCHEMA AND cc.CONSTRAINT_NAME=tc.CONSTRAINT_NAME WHERE tc.CONSTRAINT_SCHEMA=DATABASE() AND tc.TABLE_NAME=? AND tc.CONSTRAINT_TYPE=\'CHECK\'', [$table]);
        foreach ($checks as $check) {
            $clause = strtolower((string) $check->CHECK_CLAUSE);
            if (str_contains($clause, 'json_valid') && str_contains($clause, strtolower($column))) return true;
        }
        return false;
    }

    private function assertEnumContract(string $table, string $column, array $values, bool $nullable, ?string $default): void
    {
        $actual = DB::selectOne('SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?', [$table, $column]);
        $expected = "enum('".implode("','", $values)."')";
        if (! $actual || strtolower((string) $actual->COLUMN_TYPE) !== $expected || (($actual->IS_NULLABLE === 'YES') !== $nullable) || $this->normalizedDefault($actual->COLUMN_DEFAULT) !== $default) throw new RuntimeException("Enum contract gagal untuk {$table}.{$column}.");
    }

    private function assertForeignContract(string $name, string $table, string $column, string $parentTable, string $parentColumn, string $deleteRule, string $updateRule): void
    {
        $rows = DB::select('SELECT k.TABLE_NAME, k.COLUMN_NAME, k.ORDINAL_POSITION, k.REFERENCED_TABLE_NAME, k.REFERENCED_COLUMN_NAME, r.DELETE_RULE, r.UPDATE_RULE FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND r.CONSTRAINT_NAME=k.CONSTRAINT_NAME AND r.TABLE_NAME=k.TABLE_NAME WHERE k.CONSTRAINT_SCHEMA=DATABASE() AND k.CONSTRAINT_NAME=? ORDER BY k.ORDINAL_POSITION', [$name]);
        $actual = count($rows) === 1 ? $rows[0] : null;
        if (! $actual || (int) $actual->ORDINAL_POSITION !== 1 || $actual->TABLE_NAME !== $table || $actual->COLUMN_NAME !== $column || $actual->REFERENCED_TABLE_NAME !== $parentTable || $actual->REFERENCED_COLUMN_NAME !== $parentColumn
            || $this->canonicalDeleteRule((string) $actual->DELETE_RULE) !== $this->canonicalDeleteRule($deleteRule)
            || $this->canonicalUpdateRule((string) $actual->UPDATE_RULE) !== $this->canonicalUpdateRule($updateRule)
            || $this->columnDefinition($table, $column) !== $this->columnDefinition($parentTable, $parentColumn)) {
            throw new RuntimeException("Foreign key contract gagal untuk {$name}: arah, rules, atau definisi tipe/charset/collation child-parent tidak identik.");
        }
    }

    private function canonicalUpdateRule(string $rule): string
    {
        $normalized = strtoupper(trim($rule));
        if (in_array($normalized, ['RESTRICT', 'NO ACTION'], true)) return 'RESTRICT';
        if (in_array($normalized, ['CASCADE', 'SET NULL', 'SET DEFAULT'], true)) return $normalized;
        throw new RuntimeException("UPDATE_RULE foreign key tidak dikenal: {$rule}.");
    }

    private function canonicalDeleteRule(string $rule): string
    {
        $normalized = strtoupper(trim($rule));
        if (! in_array($normalized, ['RESTRICT', 'NO ACTION', 'CASCADE', 'SET NULL', 'SET DEFAULT'], true)) {
            throw new RuntimeException("DELETE_RULE foreign key tidak dikenal: {$rule}.");
        }
        return $normalized;
    }

    private function assertIndexContract(string $table, string $name, bool $unique, array $columns): void
    {
        $rows = DB::select('SELECT COLUMN_NAME, NON_UNIQUE FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND INDEX_NAME=? ORDER BY SEQ_IN_INDEX', [$table, $name]);
        if (array_map(fn ($row) => $row->COLUMN_NAME, $rows) !== $columns || $rows === [] || (((int) $rows[0]->NON_UNIQUE === 0) !== $unique)) throw new RuntimeException("Index contract gagal untuk {$table}.{$name}.");
    }

    private function assertActorColumns(array $contracts): void
    {
        foreach ($contracts as $table => $columns) foreach ($columns as $column) {
            $actual = DB::selectOne('SELECT DATA_TYPE, COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?', [$table, $column]);
            if (! $actual || strtolower($actual->DATA_TYPE) !== 'bigint' || ! str_contains(strtolower($actual->COLUMN_TYPE), 'unsigned')) throw new RuntimeException("Actor column {$table}.{$column} bukan BIGINT UNSIGNED.");
        }
    }

    private function foreignExists(string $name): bool
    {
        return DB::selectOne('SELECT 1 AS found FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND CONSTRAINT_NAME=?', [$name]) !== null;
    }

    private function indexExists(string $table, string $name): bool
    {
        return DB::selectOne('SELECT 1 AS found FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND INDEX_NAME=?', [$table, $name]) !== null;
    }

    private function columnDefinition(string $table, string $column): array
    {
        $value = DB::selectOne('SELECT DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, CHARACTER_SET_NAME, COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?', [$table, $column]);
        return ['type' => strtolower((string) ($value->DATA_TYPE ?? '')), 'length' => (int) ($value->CHARACTER_MAXIMUM_LENGTH ?? 0), 'charset' => (string) ($value->CHARACTER_SET_NAME ?? ''), 'collation' => (string) ($value->COLLATION_NAME ?? '')];
    }

    /** @param array{type:string,length:int,charset:string,collation:string} $definition */
    private function uuid(Blueprint $table, string $name, array $definition): \Illuminate\Database\Schema\ColumnDefinition
    {
        return $table->char($name, 36)->charset($definition['charset'])->collation($definition['collation']);
    }

    /** @return array{type:string,length:int,charset:string,collation:string} */
    private function uuidDefinition(string $table): array
    {
        $column = DB::selectOne('SELECT DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, CHARACTER_SET_NAME, COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?', [$table, 'id']);
        if (! $column || strtolower((string) $column->DATA_TYPE) !== 'char' || (int) $column->CHARACTER_MAXIMUM_LENGTH !== 36 || blank($column->CHARACTER_SET_NAME) || blank($column->COLLATION_NAME)) {
            throw new RuntimeException("{$table}.id bukan UUID CHAR(36) yang kompatibel.");
        }

        return ['type' => strtolower((string) $column->DATA_TYPE), 'length' => (int) $column->CHARACTER_MAXIMUM_LENGTH, 'charset' => (string) $column->CHARACTER_SET_NAME, 'collation' => (string) $column->COLLATION_NAME];
    }

    private function assertUserKey(): void
    {
        $column = DB::selectOne('SELECT DATA_TYPE, COLUMN_TYPE, COLUMN_KEY FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?', ['users', 'id']);
        if (! $column || strtolower((string) $column->DATA_TYPE) !== 'bigint' || ! str_contains(strtolower((string) $column->COLUMN_TYPE), 'unsigned') || strtoupper((string) $column->COLUMN_KEY) !== 'PRI') {
            throw new RuntimeException('users.id wajib primary key BIGINT UNSIGNED.');
        }
    }
};
