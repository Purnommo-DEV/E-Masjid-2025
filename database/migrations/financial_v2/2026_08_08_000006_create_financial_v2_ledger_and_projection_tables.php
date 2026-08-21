<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Financial V2 Foundation migration.

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_v2_ledger_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreignUuid('journal_line_id')->constrained('financial_v2_journal_lines')->restrictOnDelete();
            $table->date('accounting_date');
            $table->unsignedBigInteger('posting_sequence');
            $table->unsignedInteger('line_no');
            $table->foreignUuid('account_id')->constrained('financial_v2_accounts')->restrictOnDelete();
            $table->uuid('fund_id')->nullable();
            $table->uuid('financial_account_id')->nullable();
            $table->uuid('program_id')->nullable();
            $table->decimal('signed_amount', 19, 2);
            $table->timestamp('created_at');
            $table->foreign('fund_id', 'fv2_ledger_fund_fk')->references('id')->on('financial_v2_funds')->restrictOnDelete();
            $table->foreign('financial_account_id', 'fv2_ledger_fin_acc_fk')->references('id')->on('financial_v2_financial_accounts')->restrictOnDelete();
            $table->foreign('program_id', 'fv2_ledger_program_fk')->references('id')->on('financial_v2_programs')->restrictOnDelete();
            $table->unique('journal_line_id', 'fv2_ledger_journal_line_uq');
            $table->unique(['accounting_entity_id', 'posting_sequence', 'line_no'], 'fv2_ledger_sequence_line_uq');
            $table->index(['accounting_entity_id', 'accounting_date', 'posting_sequence'], 'fv2_ledger_date_sequence_ix');
            $table->index(['accounting_entity_id', 'account_id', 'accounting_date', 'posting_sequence', 'line_no'], 'fv2_ledger_account_order_ix');
            $table->index(['accounting_entity_id', 'fund_id', 'accounting_date', 'posting_sequence'], 'fv2_ledger_fund_order_ix');
            $table->index(['accounting_entity_id', 'financial_account_id', 'accounting_date', 'posting_sequence'], 'fv2_ledger_fin_acc_order_ix');
            $table->index(['accounting_entity_id', 'program_id', 'accounting_date', 'posting_sequence'], 'fv2_ledger_program_order_ix');
        });

        Schema::create('financial_v2_balance_projections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->enum('projection_type', ['account', 'financial_account', 'fund', 'account_fund_financial_account', 'program']);
            $table->string('dimension_key', 500);
            $table->date('as_of_accounting_date');
            $table->unsignedBigInteger('through_posting_sequence');
            $table->decimal('debit_total', 19, 2)->default(0);
            $table->decimal('credit_total', 19, 2)->default(0);
            $table->decimal('balance', 19, 2)->default(0);
            $table->enum('projection_status', ['building', 'current', 'stale', 'failed'])->default('building');
            $table->timestamp('built_at')->nullable();
            $table->timestamps();
            $table->unique(['accounting_entity_id', 'projection_type', 'dimension_key', 'as_of_accounting_date'], 'fv2_projection_grain_uq');
            $table->index(['accounting_entity_id', 'projection_type', 'projection_status'], 'fv2_projection_status_ix');
        });

        Schema::create('financial_v2_trial_balance_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('accounting_entity_id');
            $table->uuid('accounting_period_id');
            $table->unsignedBigInteger('as_of_posting_sequence');
            $table->string('filter_signature', 500);
            $table->decimal('total_debit', 19, 2)->default(0);
            $table->decimal('total_credit', 19, 2)->default(0);
            $table->enum('certification_status', ['generated', 'reviewed', 'certified', 'obsolete'])->default('generated');
            $table->timestamp('certified_at')->nullable();
            $table->unsignedBigInteger('certified_by_user_id')->nullable();
            $table->timestamps();
            $table->unique(['accounting_entity_id', 'accounting_period_id', 'filter_signature', 'as_of_posting_sequence'], 'fv2_tbs_snapshot_uq');
            $table->index(['accounting_entity_id', 'accounting_period_id', 'certification_status'], 'fv2_tbs_period_status_ix');
            $table->foreign('accounting_entity_id', 'fv2_tbs_entity_fk')->references('id')->on('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreign('accounting_period_id', 'fv2_tbs_period_fk')->references('id')->on('financial_v2_accounting_periods')->restrictOnDelete();
            $table->foreign('certified_by_user_id', 'fv2_tbs_cert_user_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_v2_trial_balance_snapshots');
        Schema::dropIfExists('financial_v2_balance_projections');
        Schema::dropIfExists('financial_v2_ledger_entries');
    }
};
