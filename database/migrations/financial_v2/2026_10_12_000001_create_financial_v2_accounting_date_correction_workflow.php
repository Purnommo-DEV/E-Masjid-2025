<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = [
        'financial_v2_accounting_date_correction_requests',
        'financial_v2_accounting_date_correction_approvals',
        'financial_v2_accounting_date_correction_evaluations',
        'financial_v2_accounting_date_correction_lineages',
    ];

    public function up(): void
    {
        $uuid = $this->preflight();

        Schema::create('financial_v2_accounting_date_correction_requests', function (Blueprint $table) use ($uuid) {
            $table->charset = $uuid['financial_v2_accounting_entities']['charset'];
            $table->collation = $uuid['financial_v2_accounting_entities']['collation'];

            $table->uuid('id')->primary();
            $table->char('accounting_entity_id', 36)->charset($uuid['financial_v2_accounting_entities']['charset'])->collation($uuid['financial_v2_accounting_entities']['collation']);
            $table->char('original_transaction_id', 36)->charset($uuid['financial_v2_transactions']['charset'])->collation($uuid['financial_v2_transactions']['collation']);
            $table->char('active_transaction_guard', 36)->charset($uuid['financial_v2_transactions']['charset'])->collation($uuid['financial_v2_transactions']['collation'])->nullable();
            $table->char('original_journal_id', 36)->charset($uuid['financial_v2_journals']['charset'])->collation($uuid['financial_v2_journals']['collation']);
            $table->char('evidence_attachment_id', 36)->charset($uuid['financial_v2_attachments']['charset'])->collation($uuid['financial_v2_attachments']['collation']);
            $table->date('original_accounting_date');
            $table->date('target_accounting_date');
            $table->text('reason');
            $table->enum('status', ['blocked', 'pending_approval', 'governance_approved', 'rejected', 'cancelled', 'execution_ready', 'executing', 'completed', 'failed']);
            $table->json('preview_snapshot');
            $table->json('blocking_reasons')->nullable();
            $table->string('idempotency_key', 100);
            $table->char('request_fingerprint', 64);
            $table->unsignedBigInteger('requested_by_user_id');
            $table->timestamp('requested_at');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('execution_started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique('idempotency_key', 'fv2_acct_date_corr_idem_uq');
            $table->unique('active_transaction_guard', 'fv2_acct_date_corr_active_uq');
            $table->index(['accounting_entity_id', 'status'], 'fv2_acct_date_corr_status_ix');
            $table->index('original_transaction_id', 'fv2_acct_date_corr_tx_ix');
            $table->foreign('accounting_entity_id', 'fv2_acct_date_corr_entity_fk')->references('id')->on('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreign('original_transaction_id', 'fv2_acct_date_corr_tx_fk')->references('id')->on('financial_v2_transactions')->restrictOnDelete();
            $table->foreign('active_transaction_guard', 'fv2_acct_date_corr_active_fk')->references('id')->on('financial_v2_transactions')->restrictOnDelete();
            $table->foreign('original_journal_id', 'fv2_acct_date_corr_journal_fk')->references('id')->on('financial_v2_journals')->restrictOnDelete();
            $table->foreign('evidence_attachment_id', 'fv2_acct_date_corr_evidence_fk')->references('id')->on('financial_v2_attachments')->restrictOnDelete();
            $table->foreign('requested_by_user_id', 'fv2_acct_date_corr_requester_fk')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::create('financial_v2_accounting_date_correction_approvals', function (Blueprint $table) use ($uuid) {
            $table->charset = $uuid['financial_v2_accounting_entities']['charset'];
            $table->collation = $uuid['financial_v2_accounting_entities']['collation'];
            $table->uuid('id')->primary();
            $table->uuid('correction_request_id');
            $table->enum('decision', ['approved', 'rejected']);
            $table->text('notes')->nullable();
            $table->json('approval_snapshot');
            $table->char('condition_fingerprint', 64);
            $table->unsignedBigInteger('decided_by_user_id');
            $table->timestamp('decided_at');
            $table->timestamp('created_at')->useCurrent();
            $table->unique('correction_request_id', 'fv2_acct_date_corr_approval_uq');
            $table->foreign('correction_request_id', 'fv2_acct_date_corr_approval_req_fk')->references('id')->on('financial_v2_accounting_date_correction_requests')->restrictOnDelete();
            $table->foreign('decided_by_user_id', 'fv2_acct_date_corr_approver_fk')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::create('financial_v2_accounting_date_correction_evaluations', function (Blueprint $table) use ($uuid) {
            $table->charset = $uuid['financial_v2_accounting_entities']['charset'];
            $table->collation = $uuid['financial_v2_accounting_entities']['collation'];
            $table->uuid('id')->primary();
            $table->uuid('correction_request_id');
            $table->json('evaluation_snapshot');
            $table->char('condition_fingerprint', 64);
            $table->unsignedBigInteger('evaluated_by_user_id');
            $table->timestamp('evaluated_at');
            $table->timestamp('created_at')->useCurrent();
            $table->index('correction_request_id', 'fv2_acct_date_corr_eval_req_ix');
            $table->foreign('correction_request_id', 'fv2_acct_date_corr_eval_req_fk')->references('id')->on('financial_v2_accounting_date_correction_requests')->restrictOnDelete();
            $table->foreign('evaluated_by_user_id', 'fv2_acct_date_corr_evaluator_fk')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::create('financial_v2_accounting_date_correction_lineages', function (Blueprint $table) use ($uuid) {
            $table->charset = $uuid['financial_v2_accounting_entities']['charset'];
            $table->collation = $uuid['financial_v2_accounting_entities']['collation'];
            $table->uuid('id')->primary();
            $table->uuid('correction_request_id');
            $table->char('original_transaction_id', 36)->charset($uuid['financial_v2_transactions']['charset'])->collation($uuid['financial_v2_transactions']['collation']);
            $table->char('reversal_transaction_id', 36)->charset($uuid['financial_v2_transactions']['charset'])->collation($uuid['financial_v2_transactions']['collation'])->nullable();
            $table->char('replacement_transaction_id', 36)->charset($uuid['financial_v2_transactions']['charset'])->collation($uuid['financial_v2_transactions']['collation'])->nullable();
            $table->char('original_journal_id', 36)->charset($uuid['financial_v2_journals']['charset'])->collation($uuid['financial_v2_journals']['collation']);
            $table->char('reversal_journal_id', 36)->charset($uuid['financial_v2_journals']['charset'])->collation($uuid['financial_v2_journals']['collation'])->nullable();
            $table->char('replacement_journal_id', 36)->charset($uuid['financial_v2_journals']['charset'])->collation($uuid['financial_v2_journals']['collation'])->nullable();
            $table->char('original_realization_id', 36)->charset($uuid['financial_v2_fund_realizations']['charset'])->collation($uuid['financial_v2_fund_realizations']['collation'])->nullable();
            $table->char('replacement_realization_id', 36)->charset($uuid['financial_v2_fund_realizations']['charset'])->collation($uuid['financial_v2_fund_realizations']['collation'])->nullable();
            $table->timestamps();
            $table->unique('correction_request_id', 'fv2_acct_date_corr_lineage_uq');
            $table->foreign('correction_request_id', 'fv2_acct_date_corr_lineage_req_fk')->references('id')->on('financial_v2_accounting_date_correction_requests')->restrictOnDelete();
            $table->foreign('original_transaction_id', 'fv2_acct_date_corr_lineage_orig_fk')->references('id')->on('financial_v2_transactions')->restrictOnDelete();
            $table->foreign('reversal_transaction_id', 'fv2_acct_date_corr_lineage_rev_fk')->references('id')->on('financial_v2_transactions')->restrictOnDelete();
            $table->foreign('replacement_transaction_id', 'fv2_acct_date_corr_lineage_repl_fk')->references('id')->on('financial_v2_transactions')->restrictOnDelete();
            $table->foreign('original_journal_id', 'fv2_acct_date_corr_lineage_orig_j_fk')->references('id')->on('financial_v2_journals')->restrictOnDelete();
            $table->foreign('reversal_journal_id', 'fv2_acct_date_corr_lineage_rev_j_fk')->references('id')->on('financial_v2_journals')->restrictOnDelete();
            $table->foreign('replacement_journal_id', 'fv2_acct_date_corr_lineage_repl_j_fk')->references('id')->on('financial_v2_journals')->restrictOnDelete();
            $table->foreign('original_realization_id', 'fv2_acct_date_corr_lineage_real_fk')->references('id')->on('financial_v2_fund_realizations')->restrictOnDelete();
            $table->foreign('replacement_realization_id', 'fv2_acct_date_corr_lineage_newreal_fk')->references('id')->on('financial_v2_fund_realizations')->restrictOnDelete();
        });

        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Verifikasi pasca-DDL gagal: {$table} tidak tersedia. Hentikan sebelum migration workflow REV.");
            }
        }
        foreach ([
            ['financial_v2_accounting_date_correction_requests', 'fv2_acct_date_corr_entity_fk'],
            ['financial_v2_accounting_date_correction_requests', 'fv2_acct_date_corr_tx_fk'],
            ['financial_v2_accounting_date_correction_requests', 'fv2_acct_date_corr_journal_fk'],
            ['financial_v2_accounting_date_correction_requests', 'fv2_acct_date_corr_evidence_fk'],
            ['financial_v2_accounting_date_correction_requests', 'fv2_acct_date_corr_requester_fk'],
            ['financial_v2_accounting_date_correction_approvals', 'fv2_acct_date_corr_approval_req_fk'],
            ['financial_v2_accounting_date_correction_evaluations', 'fv2_acct_date_corr_eval_req_fk'],
            ['financial_v2_accounting_date_correction_lineages', 'fv2_acct_date_corr_lineage_req_fk'],
        ] as [$table, $constraint]) {
            if (! $this->foreignKeyExists($table, $constraint)) {
                throw new RuntimeException("Verifikasi pasca-DDL gagal: {$constraint} tidak tersedia pada {$table}. Hentikan sebelum migration workflow REV.");
            }
        }
    }

    /** @return array<string,array{charset:string,collation:string}> */
    private function preflight(): array
    {
        $existing = array_values(array_filter(self::TABLES, fn (string $table): bool => Schema::hasTable($table)));
        if ($existing !== []) {
            throw new RuntimeException('Terdeteksi instalasi accounting-date correction parsial/konflik sebelum DDL: '.implode(', ', $existing).'. Jangan drop atau rerun; lakukan inspeksi dan recovery forward-only.');
        }

        $uuid = $this->referencedUuidDefinitions();
        $this->assertUserKeyCompatibility();

        return $uuid;
    }

    public function down(): void
    {
        throw new RuntimeException('Workflow koreksi tanggal akuntansi menyimpan histori governance dan tidak boleh dihapus otomatis melalui down().');
    }

    /** @return array<string,array{charset:string,collation:string}> */
    private function referencedUuidDefinitions(): array
    {
        $tables = ['financial_v2_accounting_entities', 'financial_v2_transactions', 'financial_v2_journals', 'financial_v2_attachments', 'financial_v2_fund_realizations'];
        $definitions = [];
        foreach ($tables as $table) {
            $column = DB::selectOne('SELECT DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, CHARACTER_SET_NAME, COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?', [$table, 'id']);
            if (! $column || strtolower((string) $column->DATA_TYPE) !== 'char' || (int) $column->CHARACTER_MAXIMUM_LENGTH !== 36) {
                throw new RuntimeException("Definisi {$table}.id bukan UUID CHAR(36) yang didukung workflow koreksi.");
            }
            $definitions[$table] = ['charset' => (string) $column->CHARACTER_SET_NAME, 'collation' => (string) $column->COLLATION_NAME];
        }
        return $definitions;
    }

    private function assertUserKeyCompatibility(): void
    {
        $column = DB::selectOne('SELECT DATA_TYPE, COLUMN_TYPE, COLUMN_KEY FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?', ['users', 'id']);
        if (! $column || strtolower((string) $column->DATA_TYPE) !== 'bigint' || ! str_contains(strtolower((string) $column->COLUMN_TYPE), 'unsigned') || strtoupper((string) $column->COLUMN_KEY) !== 'PRI') {
            throw new RuntimeException('Definisi users.id harus primary key BIGINT UNSIGNED untuk workflow koreksi tanggal akuntansi.');
        }
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        return DB::selectOne(
            'SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ? LIMIT 1',
            [$table, $constraint, 'FOREIGN KEY'],
        ) !== null;
    }
};
