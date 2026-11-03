<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Financial V2 Foundation migration.

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_v2_exception_cases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->string('exception_code', 40);
            $table->foreignUuid('reason_code_id')->constrained('financial_v2_reason_codes')->restrictOnDelete();
            $table->enum('severity', ['critical', 'high', 'medium', 'low']);
            $table->enum('status', ['open', 'investigating', 'pending_approval', 'resolved', 'accepted_risk', 'cancelled'])->default('open');
            $table->string('target_type', 80);
            $table->uuid('target_id');
            $table->string('owner_reference', 100);
            $table->date('due_date')->nullable();
            $table->text('description')->nullable();
            $table->uuid('correlation_id')->nullable();
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['accounting_entity_id', 'exception_code'], 'fv2_exception_entity_code_uq');
            $table->index(['accounting_entity_id', 'status', 'severity', 'due_date'], 'fv2_exception_aging_ix');
            $table->index(['accounting_entity_id', 'target_type', 'target_id'], 'fv2_exception_target_ix');
        });

        Schema::create('financial_v2_exception_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreignUuid('exception_case_id')->constrained('financial_v2_exception_cases')->restrictOnDelete();
            $table->enum('event_type', ['created', 'commented', 'evidence_added', 'escalated', 'decision', 'resolved', 'reopened']);
            $table->text('event_note')->nullable();
            $table->timestamp('event_at');
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('correlation_id')->nullable();
            $table->timestamp('created_at');
            $table->index(['exception_case_id', 'event_at'], 'fv2_exception_log_case_event_ix');
        });

        Schema::create('financial_v2_mapping_sets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->string('code', 40);
            $table->string('name', 160);
            $table->string('source_system_name', 160);
            $table->date('cutover_date')->nullable();
            $table->enum('mapping_status', ['draft', 'reviewed', 'approved', 'frozen'])->default('draft');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['accounting_entity_id', 'code'], 'fv2_mapping_set_entity_code_uq');
        });

        Schema::create('financial_v2_legacy_mappings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreignUuid('mapping_set_id')->constrained('financial_v2_mapping_sets')->restrictOnDelete();
            $table->string('legacy_record_ref', 240);
            $table->text('legacy_value')->nullable();
            $table->string('target_entity_type', 80)->nullable();
            $table->uuid('target_entity_id')->nullable();
            $table->foreignUuid('exception_case_id')->nullable()->constrained('financial_v2_exception_cases')->restrictOnDelete();
            $table->enum('mapping_status', ['draft', 'confirmed', 'provisional', 'exception', 'out_of_scope_archive', 'frozen'])->default('draft');
            $table->text('rationale');
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['mapping_set_id', 'legacy_record_ref'], 'fv2_legacy_mapping_source_uq');
            $table->index(['accounting_entity_id', 'mapping_status'], 'fv2_legacy_mapping_status_ix');
        });

        Schema::create('financial_v2_opening_balance_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('accounting_entity_id');
            $table->uuid('accounting_period_id');
            $table->foreignUuid('mapping_set_id')->constrained('financial_v2_mapping_sets')->restrictOnDelete();
            $table->date('cutover_date');
            $table->string('cutover_reference', 120);
            $table->enum('status', ['draft', 'reviewed', 'approved', 'posting', 'posted', 'superseded_by_correction'])->default('draft');
            $table->string('evidence_package_ref', 700);
            $table->uuid('journal_id')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreign('journal_id', 'fv2_open_batch_journal_fk')->references('id')->on('financial_v2_journals')->restrictOnDelete();
            $table->unique(['accounting_entity_id', 'cutover_reference'], 'fv2_open_batch_cutover_ref_uq');
            $table->unique('journal_id', 'fv2_open_batch_journal_uq');
            $table->index(['accounting_entity_id', 'cutover_date', 'status'], 'fv2_open_batch_date_status_ix');
            $table->foreign('accounting_entity_id', 'fv2_open_batch_entity_fk')->references('id')->on('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreign('accounting_period_id', 'fv2_open_batch_period_fk')->references('id')->on('financial_v2_accounting_periods')->restrictOnDelete();
        });

        Schema::create('financial_v2_opening_balance_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->uuid('opening_balance_batch_id');
            $table->unsignedInteger('line_no');
            $table->foreignUuid('account_id')->constrained('financial_v2_accounts')->restrictOnDelete();
            $table->uuid('fund_id')->nullable();
            $table->uuid('financial_account_id')->nullable();
            $table->decimal('debit_amount', 19, 2)->default(0);
            $table->decimal('credit_amount', 19, 2)->default(0);
            $table->string('evidence_ref', 700);
            $table->string('mapping_ref', 240)->nullable();
            $table->text('line_description')->nullable();
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreign('fund_id', 'fv2_open_line_fund_fk')->references('id')->on('financial_v2_funds')->restrictOnDelete();
            $table->foreign('financial_account_id', 'fv2_open_line_fin_acc_fk')->references('id')->on('financial_v2_financial_accounts')->restrictOnDelete();
            $table->unique(['opening_balance_batch_id', 'line_no'], 'fv2_open_line_batch_line_uq');
            $table->index(['accounting_entity_id', 'account_id'], 'fv2_open_line_account_ix');
            $table->index(['accounting_entity_id', 'fund_id'], 'fv2_open_line_fund_ix');
            $table->foreign('opening_balance_batch_id', 'fv2_open_line_batch_fk')->references('id')->on('financial_v2_opening_balance_batches')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_v2_opening_balance_lines');
        Schema::dropIfExists('financial_v2_opening_balance_batches');
        Schema::dropIfExists('financial_v2_legacy_mappings');
        Schema::dropIfExists('financial_v2_mapping_sets');
        Schema::dropIfExists('financial_v2_exception_logs');
        Schema::dropIfExists('financial_v2_exception_cases');
    }
};
