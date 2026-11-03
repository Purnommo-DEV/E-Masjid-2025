<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Financial V2 Foundation migration.

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_v2_closing_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreignUuid('accounting_period_id')->constrained('financial_v2_accounting_periods')->restrictOnDelete();
            $table->date('business_date');
            $table->date('accounting_date');
            $table->enum('run_type', ['soft_close', 'hard_close', 'reopen']);
            $table->enum('status', ['planned', 'in_progress', 'blocked', 'completed', 'cancelled'])->default('planned');
            $table->string('checklist_version', 40);
            $table->text('result_summary')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->index(['accounting_period_id', 'run_type', 'status'], 'fv2_close_period_type_status_ix');
            $table->index(['accounting_entity_id', 'accounting_period_id', 'status'], 'fv2_close_period_status_ix');
        });

        Schema::create('financial_v2_reconciliations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreignUuid('financial_account_id')->constrained('financial_v2_financial_accounts')->restrictOnDelete();
            $table->foreignUuid('accounting_period_id')->constrained('financial_v2_accounting_periods')->restrictOnDelete();
            $table->date('business_date');
            $table->date('accounting_date');
            $table->decimal('statement_balance', 19, 2)->default(0);
            $table->decimal('ledger_balance', 19, 2)->default(0);
            $table->enum('status', ['draft', 'in_progress', 'reviewed', 'completed', 'exception'])->default('draft');
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['financial_account_id', 'accounting_period_id'], 'fv2_recon_fin_account_period_uq');
            $table->index(['accounting_entity_id', 'status'], 'fv2_recon_entity_status_ix');
        });

        Schema::create('financial_v2_reconciliation_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreignUuid('reconciliation_id')->constrained('financial_v2_reconciliations')->restrictOnDelete();
            $table->enum('item_source', ['statement', 'cash_count', 'ledger', 'adjustment']);
            $table->string('external_reference', 240)->nullable();
            $table->decimal('amount', 19, 2);
            $table->enum('match_status', ['unmatched', 'matched', 'excluded', 'exception'])->default('unmatched');
            $table->uuid('exception_case_id')->nullable();
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreign('exception_case_id', 'fv2_recon_item_exception_fk')->references('id')->on('financial_v2_exception_cases')->restrictOnDelete();
            $table->index(['reconciliation_id', 'match_status'], 'fv2_recon_item_match_ix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_v2_reconciliation_items');
        Schema::dropIfExists('financial_v2_reconciliations');
        Schema::dropIfExists('financial_v2_closing_runs');
    }
};
