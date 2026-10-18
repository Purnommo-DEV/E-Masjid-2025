<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Additive hardening only. This migration intentionally does not rewrite any
 * existing master or workflow snapshot. Inspect partial DDL before retrying on
 * MySQL because ALTER TABLE statements may commit independently.
 */
return new class extends Migration
{
    private const REV_WORKFLOW_MIGRATION = '2026_10_12_000002_create_financial_v2_reversal_configuration_workflow';

    private const REQUESTS_TABLE = 'financial_v2_reversal_configuration_requests';

    public function up(): void
    {
        [$attachmentUuid, $sequenceUuid] = $this->preflight();

        Schema::table(self::REQUESTS_TABLE, function (Blueprint $table) use ($attachmentUuid, $sequenceUuid) {
            $table->char('governance_attachment_id', 36)->charset($attachmentUuid['charset'])->collation($attachmentUuid['collation'])->nullable()->after('governance_document_ref');
            $table->char('document_sequence_id', 36)->charset($sequenceUuid['charset'])->collation($sequenceUuid['collation'])->nullable()->after('approval_requirement_id');
            $table->json('evidence_requirement_ids')->nullable()->after('document_sequence_id');

            $table->foreign('governance_attachment_id', 'fv2_rev_cfg_gov_attachment_fk')->references('id')->on('financial_v2_attachments')->restrictOnDelete();
            $table->foreign('document_sequence_id', 'fv2_rev_cfg_doc_sequence_fk')->references('id')->on('financial_v2_document_sequences')->restrictOnDelete();
        });

        Schema::table('financial_v2_journals', function (Blueprint $table) {
            $table->unique('reversal_of_journal_id', 'fv2_journal_reversal_once_uq');
        });

        $this->assertInstalled($attachmentUuid, $sequenceUuid);
    }

    /** @return array{0:array{charset:string,collation:string},1:array{charset:string,collation:string}} */
    private function preflight(): array
    {
        if (! $this->migrationRecorded(self::REV_WORKFLOW_MIGRATION)) {
            throw new RuntimeException('Migration workflow konfigurasi REV harus tercatat berhasil sebelum migration hardening dijalankan.');
        }

        foreach ([self::REQUESTS_TABLE, 'financial_v2_reversal_configuration_decisions'] as $dependency) {
            if (! Schema::hasTable($dependency)) {
                throw new RuntimeException("Dependency {$dependency} tidak tersedia meskipun migration workflow REV tercatat. Hentikan dan rekonsiliasi schema secara forward-only.");
            }
        }
        if (! Schema::hasColumn('financial_v2_posting_rule_versions', 'compilation_strategy')
            || ! $this->indexExists('financial_v2_posting_rule_versions', 'fv2_rule_ver_compile_ix')) {
            throw new RuntimeException('Dependency compilation_strategy atau fv2_rule_ver_compile_ix belum lengkap. Migration hardening dihentikan.');
        }
        foreach ([
            [self::REQUESTS_TABLE, 'fv2_rev_cfg_entity_fk'],
            [self::REQUESTS_TABLE, 'fv2_rev_cfg_creator_fk'],
            [self::REQUESTS_TABLE, 'fv2_rev_cfg_type_fk'],
            [self::REQUESTS_TABLE, 'fv2_rev_cfg_rule_fk'],
            [self::REQUESTS_TABLE, 'fv2_rev_cfg_rule_ver_fk'],
            [self::REQUESTS_TABLE, 'fv2_rev_cfg_reason_fk'],
            [self::REQUESTS_TABLE, 'fv2_rev_cfg_approval_req_fk'],
            ['financial_v2_reversal_configuration_decisions', 'fv2_rev_cfg_dec_request_fk'],
            ['financial_v2_reversal_configuration_decisions', 'fv2_rev_cfg_dec_actor_fk'],
        ] as [$table, $constraint]) {
            if (! $this->foreignKeyExists($table, $constraint)) {
                throw new RuntimeException("Dependency {$constraint} pada {$table} tidak tersedia. Migration hardening tidak boleh melanjutkan workflow REV parsial.");
            }
        }

        $partialObjects = [];
        foreach (['governance_attachment_id', 'document_sequence_id', 'evidence_requirement_ids'] as $column) {
            if (Schema::hasColumn(self::REQUESTS_TABLE, $column)) {
                $partialObjects[] = self::REQUESTS_TABLE.'.'.$column;
            }
        }
        foreach (['fv2_rev_cfg_gov_attachment_fk', 'fv2_rev_cfg_doc_sequence_fk'] as $foreignKey) {
            if ($this->foreignKeyExists(self::REQUESTS_TABLE, $foreignKey)) {
                $partialObjects[] = $foreignKey;
            }
        }
        if ($this->indexExists('financial_v2_journals', 'fv2_journal_reversal_once_uq')) {
            $partialObjects[] = 'fv2_journal_reversal_once_uq';
        }
        if ($partialObjects !== []) {
            throw new RuntimeException('Terdeteksi hardening REV parsial/konflik sebelum DDL: '.implode(', ', $partialObjects).'. Jangan drop atau rerun; lakukan inspeksi dan recovery forward-only.');
        }

        $duplicate = DB::selectOne(
            'SELECT reversal_of_journal_id, COUNT(*) AS duplicate_count FROM financial_v2_journals WHERE reversal_of_journal_id IS NOT NULL GROUP BY reversal_of_journal_id HAVING COUNT(*) > 1 LIMIT 1'
        );
        if ($duplicate) {
            throw new RuntimeException('Unique reversal tidak dapat diterapkan: terdapat lebih dari satu jurnal untuk reversal_of_journal_id '.(string) $duplicate->reversal_of_journal_id.'. Audit data wajib dilakukan sebelum recovery forward-only.');
        }

        return [
            $this->uuidDefinition('financial_v2_attachments'),
            $this->uuidDefinition('financial_v2_document_sequences'),
        ];
    }

    public function down(): void
    {
        throw new RuntimeException('Reversal configuration hardening retains governance history and must be reversed through a reviewed forward migration.');
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

    /**
     * @param array{charset:string,collation:string} $attachmentUuid
     * @param array{charset:string,collation:string} $sequenceUuid
     */
    private function assertInstalled(array $attachmentUuid, array $sequenceUuid): void
    {
        foreach (['governance_attachment_id', 'document_sequence_id', 'evidence_requirement_ids'] as $column) {
            if (! Schema::hasColumn(self::REQUESTS_TABLE, $column)) {
                throw new RuntimeException("Verifikasi pasca-DDL gagal: {$column} tidak tersedia. Hentikan penerapan.");
            }
        }
        foreach (['fv2_rev_cfg_gov_attachment_fk', 'fv2_rev_cfg_doc_sequence_fk'] as $foreignKey) {
            if (! $this->foreignKeyExists(self::REQUESTS_TABLE, $foreignKey)) {
                throw new RuntimeException("Verifikasi pasca-DDL gagal: {$foreignKey} tidak tersedia. Hentikan penerapan.");
            }
        }
        if (! $this->indexExists('financial_v2_journals', 'fv2_journal_reversal_once_uq')) {
            throw new RuntimeException('Verifikasi pasca-DDL gagal: fv2_journal_reversal_once_uq tidak tersedia.');
        }

        if ($this->columnDefinition(self::REQUESTS_TABLE, 'governance_attachment_id') !== $attachmentUuid) {
            throw new RuntimeException('governance_attachment_id tidak kompatibel dengan financial_v2_attachments.id.');
        }
        if ($this->columnDefinition(self::REQUESTS_TABLE, 'document_sequence_id') !== $sequenceUuid) {
            throw new RuntimeException('document_sequence_id tidak kompatibel dengan financial_v2_document_sequences.id.');
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
