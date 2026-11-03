<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ACCOUNTING_WORKFLOW_MIGRATION = '2026_10_12_000001_create_financial_v2_accounting_date_correction_workflow';

    private const REQUESTS_TABLE = 'financial_v2_reversal_configuration_requests';

    private const DECISIONS_TABLE = 'financial_v2_reversal_configuration_decisions';

    public function up(): void
    {
        $uuid = $this->preflight();

        Schema::create(self::REQUESTS_TABLE, function (Blueprint $table) use ($uuid) {
            $table->charset = $uuid['financial_v2_accounting_entities']['charset'];
            $table->collation = $uuid['financial_v2_accounting_entities']['collation'];

            $this->uuidColumn($table, 'id', $uuid['financial_v2_accounting_entities'])->primary();
            $this->uuidColumn($table, 'accounting_entity_id', $uuid['financial_v2_accounting_entities']);
            $table->json('configuration_snapshot');
            $table->char('snapshot_hash', 64);
            $table->string('governance_document_ref', 500);
            $table->string('idempotency_key', 120);
            $this->uuidColumn($table, 'active_entity_guard', $uuid['financial_v2_accounting_entities'])->nullable();
            $table->enum('status', ['draft', 'pending_review', 'approved', 'rejected', 'activated', 'cancelled'])->default('draft');
            $table->unsignedBigInteger('created_by_user_id');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by_user_id')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->unsignedBigInteger('activated_by_user_id')->nullable();
            $this->uuidColumn($table, 'transaction_type_id', $uuid['financial_v2_transaction_types'])->nullable();
            $this->uuidColumn($table, 'posting_rule_id', $uuid['financial_v2_posting_rules'])->nullable();
            $this->uuidColumn($table, 'posting_rule_version_id', $uuid['financial_v2_posting_rule_versions'])->nullable();
            $this->uuidColumn($table, 'reason_code_id', $uuid['financial_v2_reason_codes'])->nullable();
            $this->uuidColumn($table, 'approval_requirement_id', $uuid['financial_v2_approval_requirements'])->nullable();
            $table->timestamps();

            $table->unique(['accounting_entity_id', 'idempotency_key'], 'fv2_rev_cfg_idem_uq');
            $table->unique('active_entity_guard', 'fv2_rev_cfg_active_entity_uq');
            $table->index(['accounting_entity_id', 'status'], 'fv2_rev_cfg_entity_status_ix');
            $table->foreign('accounting_entity_id', 'fv2_rev_cfg_entity_fk')->references('id')->on('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreign('created_by_user_id', 'fv2_rev_cfg_creator_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('approved_by_user_id', 'fv2_rev_cfg_approver_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('activated_by_user_id', 'fv2_rev_cfg_activator_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('transaction_type_id', 'fv2_rev_cfg_type_fk')->references('id')->on('financial_v2_transaction_types')->restrictOnDelete();
            $table->foreign('posting_rule_id', 'fv2_rev_cfg_rule_fk')->references('id')->on('financial_v2_posting_rules')->restrictOnDelete();
            $table->foreign('posting_rule_version_id', 'fv2_rev_cfg_rule_ver_fk')->references('id')->on('financial_v2_posting_rule_versions')->restrictOnDelete();
            $table->foreign('reason_code_id', 'fv2_rev_cfg_reason_fk')->references('id')->on('financial_v2_reason_codes')->restrictOnDelete();
            $table->foreign('approval_requirement_id', 'fv2_rev_cfg_approval_req_fk')->references('id')->on('financial_v2_approval_requirements')->restrictOnDelete();
        });

        Schema::create(self::DECISIONS_TABLE, function (Blueprint $table) use ($uuid) {
            $table->charset = $uuid['financial_v2_accounting_entities']['charset'];
            $table->collation = $uuid['financial_v2_accounting_entities']['collation'];

            $this->uuidColumn($table, 'id', $uuid['financial_v2_accounting_entities'])->primary();
            $this->uuidColumn($table, 'accounting_entity_id', $uuid['financial_v2_accounting_entities']);
            $this->uuidColumn($table, 'configuration_request_id', $uuid['financial_v2_accounting_entities']);
            $table->enum('decision', ['submitted', 'approved', 'rejected', 'activated', 'cancelled']);
            $table->json('configuration_snapshot');
            $table->char('snapshot_hash', 64);
            $table->text('note')->nullable();
            $table->unsignedBigInteger('actor_user_id');
            $table->timestamp('decided_at');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('accounting_entity_id', 'fv2_rev_cfg_dec_entity_fk')->references('id')->on('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreign('configuration_request_id', 'fv2_rev_cfg_dec_request_fk')->references('id')->on(self::REQUESTS_TABLE)->restrictOnDelete();
            $table->foreign('actor_user_id', 'fv2_rev_cfg_dec_actor_fk')->references('id')->on('users')->restrictOnDelete();
            $table->index(['configuration_request_id', 'decided_at'], 'fv2_rev_cfg_dec_timeline_ix');
        });

        // Add this to the established master table last. An unexpected failure
        // cannot make posting configuration appear upgraded before both workflow
        // tables and all their constraints exist.
        Schema::table('financial_v2_posting_rule_versions', function (Blueprint $table) {
            $table->string('compilation_strategy', 40)->default('rule_lines')->after('business_rule_refs');
            $table->index(['accounting_entity_id', 'compilation_strategy'], 'fv2_rule_ver_compile_ix');
        });

        $this->assertInstalled($uuid);
    }

    public function down(): void
    {
        throw new RuntimeException('Workflow konfigurasi REV menyimpan histori governance dan harus dipulihkan melalui migration forward-only yang ditinjau.');
    }

    /** @return array<string,array{charset:string,collation:string}> */
    private function preflight(): array
    {
        if (! $this->migrationRecorded(self::ACCOUNTING_WORKFLOW_MIGRATION)) {
            throw new RuntimeException('Migration fondasi accounting-date correction harus tercatat berhasil sebelum workflow konfigurasi REV dibuat.');
        }

        foreach ([
            'financial_v2_accounting_date_correction_requests',
            'financial_v2_accounting_date_correction_approvals',
            'financial_v2_accounting_date_correction_evaluations',
            'financial_v2_accounting_date_correction_lineages',
        ] as $dependency) {
            if (! Schema::hasTable($dependency)) {
                throw new RuntimeException("Dependency {$dependency} tidak tersedia meskipun migration fondasi tercatat. Hentikan dan rekonsiliasi schema secara forward-only.");
            }
        }
        foreach ([
            ['financial_v2_accounting_date_correction_requests', 'fv2_acct_date_corr_entity_fk'],
            ['financial_v2_accounting_date_correction_requests', 'fv2_acct_date_corr_tx_fk'],
            ['financial_v2_accounting_date_correction_approvals', 'fv2_acct_date_corr_approval_req_fk'],
            ['financial_v2_accounting_date_correction_evaluations', 'fv2_acct_date_corr_eval_req_fk'],
            ['financial_v2_accounting_date_correction_lineages', 'fv2_acct_date_corr_lineage_req_fk'],
        ] as [$table, $constraint]) {
            if (! $this->foreignKeyExists($table, $constraint)) {
                throw new RuntimeException("Dependency {$constraint} pada {$table} tidak tersedia. Workflow REV tidak boleh dibuat di atas fondasi parsial.");
            }
        }

        $partialObjects = [];
        if (Schema::hasTable(self::REQUESTS_TABLE)) {
            $partialObjects[] = self::REQUESTS_TABLE;
        }
        if (Schema::hasTable(self::DECISIONS_TABLE)) {
            $partialObjects[] = self::DECISIONS_TABLE;
        }
        if (Schema::hasColumn('financial_v2_posting_rule_versions', 'compilation_strategy')) {
            $partialObjects[] = 'financial_v2_posting_rule_versions.compilation_strategy';
        }
        if ($this->indexExists('financial_v2_posting_rule_versions', 'fv2_rule_ver_compile_ix')) {
            $partialObjects[] = 'fv2_rule_ver_compile_ix';
        }
        if ($partialObjects !== []) {
            throw new RuntimeException('Terdeteksi instalasi REV parsial/konflik sebelum DDL: '.implode(', ', $partialObjects).'. Jangan drop atau rerun; lakukan inspeksi dan recovery forward-only.');
        }

        $this->assertUserKeyCompatibility();

        $tables = [
            'financial_v2_accounting_entities',
            'financial_v2_transaction_types',
            'financial_v2_posting_rules',
            'financial_v2_posting_rule_versions',
            'financial_v2_reason_codes',
            'financial_v2_approval_requirements',
        ];

        return collect($tables)->mapWithKeys(fn (string $table): array => [$table => $this->uuidDefinition($table)])->all();
    }

    /** @param array{charset:string,collation:string} $definition */
    private function uuidColumn(Blueprint $table, string $name, array $definition): \Illuminate\Database\Schema\ColumnDefinition
    {
        return $table->char($name, 36)->charset($definition['charset'])->collation($definition['collation']);
    }

    /** @return array{charset:string,collation:string} */
    private function uuidDefinition(string $table): array
    {
        $column = DB::selectOne(
            'SELECT DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, CHARACTER_SET_NAME, COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, 'id'],
        );
        if (! $column || strtolower((string) $column->DATA_TYPE) !== 'char' || (int) $column->CHARACTER_MAXIMUM_LENGTH !== 36
            || blank($column->CHARACTER_SET_NAME) || blank($column->COLLATION_NAME)) {
            throw new RuntimeException("Definisi {$table}.id bukan UUID CHAR(36) yang kompatibel.");
        }

        return ['charset' => (string) $column->CHARACTER_SET_NAME, 'collation' => (string) $column->COLLATION_NAME];
    }

    private function assertUserKeyCompatibility(): void
    {
        $column = DB::selectOne(
            'SELECT DATA_TYPE, COLUMN_TYPE, COLUMN_KEY FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['users', 'id'],
        );
        if (! $column || strtolower((string) $column->DATA_TYPE) !== 'bigint'
            || ! str_contains(strtolower((string) $column->COLUMN_TYPE), 'unsigned')
            || strtoupper((string) $column->COLUMN_KEY) !== 'PRI') {
            throw new RuntimeException('Definisi users.id harus primary key BIGINT UNSIGNED untuk workflow konfigurasi REV.');
        }
    }

    /** @param array<string,array{charset:string,collation:string}> $uuid */
    private function assertInstalled(array $uuid): void
    {
        foreach ([self::REQUESTS_TABLE, self::DECISIONS_TABLE] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Verifikasi pasca-DDL gagal: {$table} tidak tersedia. Hentikan sebelum migration hardening.");
            }
        }
        if (! Schema::hasColumn('financial_v2_posting_rule_versions', 'compilation_strategy')
            || ! $this->indexExists('financial_v2_posting_rule_versions', 'fv2_rule_ver_compile_ix')) {
            throw new RuntimeException('Verifikasi pasca-DDL gagal: compilation_strategy atau indeksnya tidak lengkap. Hentikan sebelum migration hardening.');
        }

        foreach ([
            [self::REQUESTS_TABLE, 'accounting_entity_id', 'financial_v2_accounting_entities'],
            [self::REQUESTS_TABLE, 'transaction_type_id', 'financial_v2_transaction_types'],
            [self::REQUESTS_TABLE, 'posting_rule_id', 'financial_v2_posting_rules'],
            [self::REQUESTS_TABLE, 'posting_rule_version_id', 'financial_v2_posting_rule_versions'],
            [self::REQUESTS_TABLE, 'reason_code_id', 'financial_v2_reason_codes'],
            [self::REQUESTS_TABLE, 'approval_requirement_id', 'financial_v2_approval_requirements'],
        ] as [$childTable, $childColumn, $parentTable]) {
            $child = $this->columnDefinition($childTable, $childColumn);
            if ($child !== $uuid[$parentTable]) {
                throw new RuntimeException("Verifikasi pasca-DDL gagal: {$childTable}.{$childColumn} tidak sama dengan {$parentTable}.id.");
            }
        }

        foreach ([
            [self::REQUESTS_TABLE, 'fv2_rev_cfg_entity_fk'],
            [self::REQUESTS_TABLE, 'fv2_rev_cfg_creator_fk'],
            [self::REQUESTS_TABLE, 'fv2_rev_cfg_approver_fk'],
            [self::REQUESTS_TABLE, 'fv2_rev_cfg_activator_fk'],
            [self::REQUESTS_TABLE, 'fv2_rev_cfg_type_fk'],
            [self::REQUESTS_TABLE, 'fv2_rev_cfg_rule_fk'],
            [self::REQUESTS_TABLE, 'fv2_rev_cfg_rule_ver_fk'],
            [self::REQUESTS_TABLE, 'fv2_rev_cfg_reason_fk'],
            [self::REQUESTS_TABLE, 'fv2_rev_cfg_approval_req_fk'],
            [self::DECISIONS_TABLE, 'fv2_rev_cfg_dec_entity_fk'],
            [self::DECISIONS_TABLE, 'fv2_rev_cfg_dec_request_fk'],
            [self::DECISIONS_TABLE, 'fv2_rev_cfg_dec_actor_fk'],
        ] as [$table, $constraint]) {
            if (! $this->foreignKeyExists($table, $constraint)) {
                throw new RuntimeException("Verifikasi pasca-DDL gagal: {$constraint} tidak tersedia pada {$table}.");
            }
        }
        foreach ([
            [self::REQUESTS_TABLE, 'fv2_rev_cfg_idem_uq'],
            [self::REQUESTS_TABLE, 'fv2_rev_cfg_active_entity_uq'],
            [self::REQUESTS_TABLE, 'fv2_rev_cfg_entity_status_ix'],
            [self::DECISIONS_TABLE, 'fv2_rev_cfg_dec_timeline_ix'],
        ] as [$table, $index]) {
            if (! $this->indexExists($table, $index)) {
                throw new RuntimeException("Verifikasi pasca-DDL gagal: {$index} tidak tersedia pada {$table}.");
            }
        }
    }

    /** @return array{charset:string,collation:string} */
    private function columnDefinition(string $table, string $columnName): array
    {
        $column = DB::selectOne(
            'SELECT DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, CHARACTER_SET_NAME, COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $columnName],
        );
        if (! $column || strtolower((string) $column->DATA_TYPE) !== 'char' || (int) $column->CHARACTER_MAXIMUM_LENGTH !== 36) {
            throw new RuntimeException("Kolom {$table}.{$columnName} bukan CHAR(36).");
        }

        return ['charset' => (string) $column->CHARACTER_SET_NAME, 'collation' => (string) $column->COLLATION_NAME];
    }

    private function migrationRecorded(string $migration): bool
    {
        return Schema::hasTable('migrations') && DB::table('migrations')->where('migration', $migration)->exists();
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::selectOne(
            'SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            [$table, $index],
        ) !== null;
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        return DB::selectOne(
            'SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ? LIMIT 1',
            [$table, $constraint, 'FOREIGN KEY'],
        ) !== null;
    }
};
