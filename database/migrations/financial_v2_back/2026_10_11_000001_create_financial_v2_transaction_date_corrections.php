<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $uuidDefinition = $this->referencedUuidDefinition();
        $this->assertUserKeyCompatibility();

        Schema::create('financial_v2_transaction_date_corrections', function (Blueprint $table) use ($uuidDefinition) {
            $table->charset = $uuidDefinition['charset'];
            $table->collation = $uuidDefinition['collation'];

            $table->uuid('id')->primary();
            $table->uuid('accounting_entity_id');
            $table->uuid('transaction_id');
            $table->unsignedInteger('correction_no');
            $table->date('original_business_date');
            $table->date('corrected_business_date');
            $table->date('accounting_date');
            $table->text('reason');
            $table->uuid('evidence_attachment_id');
            $table->string('idempotency_key', 100);
            $table->char('request_fingerprint', 64);
            $table->unsignedBigInteger('corrected_by_user_id')->nullable();
            $table->timestamp('corrected_at');
            $table->timestamp('created_at')->useCurrent();

            $table->unique('idempotency_key', 'fv2_tx_date_corr_idem_uq');
            $table->unique(['transaction_id', 'correction_no'], 'fv2_tx_date_corr_no_uq');
            $table->index(['accounting_entity_id', 'transaction_id'], 'fv2_tx_date_corr_scope_ix');
            $table->foreign('accounting_entity_id', 'fv2_tx_date_corr_entity_fk')->references('id')->on('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreign('transaction_id', 'fv2_tx_date_corr_tx_fk')->references('id')->on('financial_v2_transactions')->restrictOnDelete();
            $table->foreign('evidence_attachment_id', 'fv2_tx_date_corr_evidence_fk')->references('id')->on('financial_v2_attachments')->restrictOnDelete();
            $table->foreign('corrected_by_user_id', 'fv2_tx_date_corr_user_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_v2_transaction_date_corrections');
    }

    /** @return array{charset: string, collation: string} */
    private function referencedUuidDefinition(): array
    {
        $references = ['financial_v2_accounting_entities', 'financial_v2_transactions', 'financial_v2_attachments'];
        $expected = null;

        foreach ($references as $table) {
            $column = DB::selectOne(
                'SELECT DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, CHARACTER_SET_NAME, COLLATION_NAME
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                [$table, 'id'],
            );
            if (! $column) {
                throw new RuntimeException("Kolom induk {$table}.id tidak ditemukan.");
            }

            $definition = [
                'type' => strtolower((string) $column->DATA_TYPE),
                'length' => (int) $column->CHARACTER_MAXIMUM_LENGTH,
                'charset' => (string) $column->CHARACTER_SET_NAME,
                'collation' => (string) $column->COLLATION_NAME,
            ];
            if ($definition['type'] !== 'char' || $definition['length'] !== 36 || $definition['charset'] === '' || $definition['collation'] === '') {
                throw new RuntimeException("Definisi {$table}.id bukan UUID CHAR(36) yang didukung migration koreksi tanggal.");
            }
            $expected ??= $definition;
            if ($definition !== $expected) {
                throw new RuntimeException('Kolom UUID induk koreksi tanggal memiliki tipe, charset, atau collation yang tidak konsisten.');
            }
        }

        return ['charset' => $expected['charset'], 'collation' => $expected['collation']];
    }

    private function assertUserKeyCompatibility(): void
    {
        $column = DB::selectOne(
            'SELECT DATA_TYPE, COLUMN_TYPE, COLUMN_KEY FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['users', 'id'],
        );
        if (! $column || strtolower((string) $column->DATA_TYPE) !== 'bigint'
            || ! str_contains(strtolower((string) $column->COLUMN_TYPE), 'unsigned')
            || strtoupper((string) $column->COLUMN_KEY) !== 'PRI') {
            $actual = $column ? (string) $column->COLUMN_TYPE : 'tidak ditemukan';
            throw new RuntimeException("Definisi users.id harus primary key BIGINT UNSIGNED; ditemukan {$actual}.");
        }
    }
};
