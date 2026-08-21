<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Financial V2 Foundation migration.

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_v2_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreignUuid('transaction_type_id')->constrained('financial_v2_transaction_types')->restrictOnDelete();
            $table->enum('status', ['draft', 'submitted', 'verified', 'approved', 'posting', 'posted', 'rejected', 'cancelled', 'reversed'])->default('draft');
            $table->string('source_reference', 160)->nullable();
            $table->date('business_date');
            $table->date('accounting_date');
            $table->text('description')->nullable();
            $table->char('currency_code', 3)->default('IDR');
            $table->decimal('gross_amount', 19, 2);
            $table->uuid('primary_financial_account_id')->nullable();
            $table->uuid('counterparty_id')->nullable();
            $table->uuid('category_id')->nullable();
            $table->uuid('reason_code_id')->nullable();
            $table->uuid('related_transaction_id')->nullable();
            $table->string('idempotency_key', 160);
            $table->string('policy_version_ref', 80)->nullable();
            $table->uuid('correlation_id');
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreign('primary_financial_account_id', 'fv2_tx_primary_fin_acc_fk')->references('id')->on('financial_v2_financial_accounts')->restrictOnDelete();
            $table->foreign('counterparty_id', 'fv2_tx_counterparty_fk')->references('id')->on('financial_v2_counterparties')->restrictOnDelete();
            $table->foreign('category_id', 'fv2_tx_category_fk')->references('id')->on('financial_v2_categories')->restrictOnDelete();
            $table->foreign('reason_code_id', 'fv2_tx_reason_fk')->references('id')->on('financial_v2_reason_codes')->restrictOnDelete();
            $table->foreign('related_transaction_id', 'fv2_tx_related_fk')->references('id')->on('financial_v2_transactions')->restrictOnDelete();
            $table->unique(['accounting_entity_id', 'transaction_type_id', 'source_reference'], 'fv2_tx_source_ref_uq');
            $table->index(['accounting_entity_id', 'status', 'accounting_date'], 'fv2_tx_status_date_ix');
            $table->index(['accounting_entity_id', 'idempotency_key'], 'fv2_tx_idempotency_ix');
        });

        Schema::create('financial_v2_transaction_splits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreignUuid('transaction_id')->constrained('financial_v2_transactions')->restrictOnDelete();
            $table->unsignedInteger('line_no');
            $table->decimal('split_amount', 19, 2);
            $table->foreignUuid('account_id')->constrained('financial_v2_accounts')->restrictOnDelete();
            $table->uuid('fund_id')->nullable();
            $table->uuid('financial_account_id')->nullable();
            $table->uuid('program_id')->nullable();
            $table->uuid('cost_center_id')->nullable();
            $table->uuid('counterparty_id')->nullable();
            $table->uuid('category_id')->nullable();
            $table->text('purpose_note')->nullable();
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreign('fund_id', 'fv2_tx_split_fund_fk')->references('id')->on('financial_v2_funds')->restrictOnDelete();
            $table->foreign('financial_account_id', 'fv2_tx_split_fin_acc_fk')->references('id')->on('financial_v2_financial_accounts')->restrictOnDelete();
            $table->foreign('program_id', 'fv2_tx_split_program_fk')->references('id')->on('financial_v2_programs')->restrictOnDelete();
            $table->foreign('cost_center_id', 'fv2_tx_split_cost_center_fk')->references('id')->on('financial_v2_cost_centers')->restrictOnDelete();
            $table->foreign('counterparty_id', 'fv2_tx_split_counterparty_fk')->references('id')->on('financial_v2_counterparties')->restrictOnDelete();
            $table->foreign('category_id', 'fv2_tx_split_category_fk')->references('id')->on('financial_v2_categories')->restrictOnDelete();
            $table->unique(['transaction_id', 'line_no'], 'fv2_tx_split_line_no_uq');
            $table->index(['accounting_entity_id', 'fund_id'], 'fv2_tx_split_fund_ix');
        });

        Schema::create('financial_v2_approval_decisions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreignUuid('transaction_id')->constrained('financial_v2_transactions')->restrictOnDelete();
            $table->unsignedTinyInteger('step_no');
            $table->enum('decision', ['pending', 'approved', 'rejected', 'expired', 'superseded'])->default('pending');
            $table->timestamp('decision_at')->nullable();
            $table->foreignId('approver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['transaction_id', 'step_no'], 'fv2_approval_step_uq');
            $table->index(['accounting_entity_id', 'decision'], 'fv2_approval_entity_decision_ix');
        });

        Schema::create('financial_v2_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->string('original_filename', 255);
            $table->string('media_type', 120);
            $table->unsignedBigInteger('byte_size');
            $table->string('content_hash', 128);
            $table->string('storage_reference', 700);
            $table->enum('status', ['pending_scan', 'active', 'superseded', 'archived', 'rejected'])->default('pending_scan');
            $table->timestamp('received_at');
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['accounting_entity_id', 'content_hash'], 'fv2_attachment_content_hash_uq');
            $table->unique('storage_reference', 'fv2_attachment_storage_ref_uq');
        });

        Schema::create('financial_v2_attachment_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreignUuid('attachment_id')->constrained('financial_v2_attachments')->restrictOnDelete();
            $table->enum('target_type', ['transaction', 'journal', 'opening_balance_line', 'exception', 'reconciliation']);
            $table->uuid('target_id');
            $table->enum('evidence_type', ['receipt', 'invoice', 'transfer_proof', 'statement', 'cash_count', 'approval', 'policy', 'other']);
            $table->enum('status', ['active', 'superseded', 'removed_with_audit'])->default('active');
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->index(['accounting_entity_id', 'target_type', 'target_id'], 'fv2_attachment_link_target_ix');
            $table->unique(['attachment_id', 'target_type', 'target_id', 'evidence_type'], 'fv2_attachment_link_uq');
        });

        Schema::create('financial_v2_idempotency_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->string('scope_name', 80);
            $table->string('key_value', 160);
            $table->string('request_fingerprint', 128);
            $table->enum('status', ['reserved', 'completed', 'failed', 'expired'])->default('reserved');
            $table->string('result_reference', 160)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['accounting_entity_id', 'scope_name', 'key_value'], 'fv2_idempotency_scope_key_uq');
            $table->index(['accounting_entity_id', 'status'], 'fv2_idempotency_status_ix');
        });

        Schema::create('financial_v2_posting_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreignUuid('transaction_id')->constrained('financial_v2_transactions')->restrictOnDelete();
            $table->foreignUuid('idempotency_record_id')->constrained('financial_v2_idempotency_keys')->restrictOnDelete();
            $table->enum('status', ['started', 'validated', 'committed', 'failed', 'recovery_required'])->default('started');
            $table->unsignedInteger('attempt_no');
            $table->timestamp('requested_at');
            $table->timestamp('completed_at')->nullable();
            $table->string('failure_code', 80)->nullable();
            $table->text('failure_detail')->nullable();
            $table->uuid('correlation_id');
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['transaction_id', 'attempt_no'], 'fv2_post_attempt_tx_no_uq');
            $table->index(['accounting_entity_id', 'status'], 'fv2_post_attempt_status_ix');
        });

        Schema::create('financial_v2_vouchers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreignUuid('transaction_id')->constrained('financial_v2_transactions')->restrictOnDelete();
            $table->foreignUuid('document_sequence_id')->constrained('financial_v2_document_sequences')->restrictOnDelete();
            $table->string('voucher_number', 80);
            $table->enum('status', ['reserved', 'issued', 'voided', 'referenced'])->default('reserved');
            $table->timestamp('issued_at')->nullable();
            $table->uuid('void_reason_code_id')->nullable();
            $table->text('void_note')->nullable();
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreign('void_reason_code_id', 'fv2_voucher_void_reason_fk')->references('id')->on('financial_v2_reason_codes')->restrictOnDelete();
            $table->unique('transaction_id', 'fv2_voucher_transaction_uq');
            $table->unique(['accounting_entity_id', 'voucher_number'], 'fv2_voucher_entity_number_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_v2_vouchers');
        Schema::dropIfExists('financial_v2_posting_attempts');
        Schema::dropIfExists('financial_v2_idempotency_keys');
        Schema::dropIfExists('financial_v2_attachment_links');
        Schema::dropIfExists('financial_v2_attachments');
        Schema::dropIfExists('financial_v2_approval_decisions');
        Schema::dropIfExists('financial_v2_transaction_splits');
        Schema::dropIfExists('financial_v2_transactions');
    }
};
