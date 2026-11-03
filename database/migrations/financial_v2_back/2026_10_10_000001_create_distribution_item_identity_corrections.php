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

        Schema::create('financial_v2_distribution_item_identity_corrections', function (Blueprint $table) use ($uuidDefinition) {
            // Use the collation already present on every referenced Financial
            // V2 UUID. This works on both MySQL and MariaDB without requiring
            // a server-specific collation such as utf8mb4_0900_ai_ci.
            $table->charset = $uuidDefinition['charset'];
            $table->collation = $uuidDefinition['collation'];

            $table->uuid('id')->primary();
            $table->uuid('accounting_entity_id');
            $table->uuid('distribution_id');
            $table->uuid('distribution_item_id');
            $table->unsignedInteger('correction_no');
            $table->uuid('original_beneficiary_id')->nullable();
            $table->uuid('corrected_beneficiary_id');
            $table->string('original_recipient_key', 255);
            $table->string('corrected_recipient_key', 255);
            $table->json('original_identity_snapshot');
            $table->json('corrected_identity_snapshot');
            $table->text('reason');
            $table->string('idempotency_key', 100);
            $table->unsignedBigInteger('corrected_by_user_id')->nullable();
            $table->timestamp('corrected_at');
            $table->timestamp('created_at')->useCurrent();
            $table->unique('idempotency_key', 'fv2_dist_item_corr_idem_uq');
            $table->unique(['distribution_item_id', 'correction_no'], 'fv2_dist_item_identity_correction_no_uq');
            $table->index(['accounting_entity_id', 'distribution_id'], 'fv2_dist_identity_correction_scope_ix');
            $table->foreign('accounting_entity_id', 'fv2_dist_identity_corr_entity_fk')->references('id')->on('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreign('distribution_id', 'fv2_dist_identity_corr_dist_fk')->references('id')->on('financial_v2_distributions')->restrictOnDelete();
            $table->foreign('distribution_item_id', 'fv2_dist_identity_corr_item_fk')->references('id')->on('financial_v2_distribution_items')->restrictOnDelete();
            $table->foreign('original_beneficiary_id', 'fv2_dist_identity_corr_orig_ben_fk')->references('id')->on('financial_v2_counterparties')->restrictOnDelete();
            $table->foreign('corrected_beneficiary_id', 'fv2_dist_identity_corr_new_ben_fk')->references('id')->on('financial_v2_counterparties')->restrictOnDelete();
            $table->foreign('corrected_by_user_id', 'fv2_dist_identity_corr_user_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_v2_distribution_item_identity_corrections');
    }

    /** @return array{charset: string, collation: string} */
    private function referencedUuidDefinition(): array
    {
        $references = [
            'financial_v2_accounting_entities',
            'financial_v2_distributions',
            'financial_v2_distribution_items',
            'financial_v2_counterparties',
        ];
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
                throw new RuntimeException("Definisi {$table}.id bukan UUID CHAR(36) yang didukung migration koreksi identitas.");
            }

            $expected ??= $definition;
            if ($definition !== $expected) {
                throw new RuntimeException('Kolom UUID induk Financial V2 memiliki tipe, charset, atau collation yang tidak konsisten. Migration dihentikan sebelum membuat tabel.');
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

        if (! $column) {
            throw new RuntimeException('Kolom induk users.id tidak ditemukan. Migration dihentikan sebelum membuat tabel koreksi identitas.');
        }

        $dataType = strtolower((string) $column->DATA_TYPE);
        $columnType = strtolower((string) $column->COLUMN_TYPE);
        $isUnsignedBigInt = $dataType === 'bigint' && preg_match('/\bunsigned\b/', $columnType) === 1;

        if (! $isUnsignedBigInt || strtoupper((string) $column->COLUMN_KEY) !== 'PRI') {
            throw new RuntimeException(
                "Definisi users.id harus primary key BIGINT UNSIGNED; ditemukan {$columnType}. Migration dihentikan sebelum membuat tabel koreksi identitas."
            );
        }
    }
};
