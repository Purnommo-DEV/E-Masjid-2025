<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_v2_distribution_item_identity_corrections', function (Blueprint $table) {
            // Every referenced Financial V2 UUID is CHAR(36) using the
            // established Financial V2 collation. The database default can
            // differ, and MySQL rejects string foreign keys whose collations
            // are not identical (error 3780).
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';

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
};
