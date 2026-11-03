<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Financial V2 Foundation migration.

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_v2_journals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreignUuid('transaction_id')->constrained('financial_v2_transactions')->restrictOnDelete();
            $table->foreignUuid('posting_attempt_id')->constrained('financial_v2_posting_attempts')->restrictOnDelete();
            $table->foreignUuid('posting_rule_version_id')->constrained('financial_v2_posting_rule_versions')->restrictOnDelete();
            $table->foreignUuid('accounting_period_id')->constrained('financial_v2_accounting_periods')->restrictOnDelete();
            $table->date('business_date');
            $table->date('accounting_date');
            $table->text('description')->nullable();
            $table->enum('journal_status', ['draft', 'posting', 'posted', 'reversed'])->default('draft');
            $table->unsignedBigInteger('posting_sequence')->nullable();
            $table->decimal('total_debit', 19, 2)->default(0);
            $table->decimal('total_credit', 19, 2)->default(0);
            $table->uuid('reversal_of_journal_id')->nullable();
            $table->uuid('correlation_id');
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreign('reversal_of_journal_id', 'fv2_journal_reversal_fk')->references('id')->on('financial_v2_journals')->restrictOnDelete();
            $table->unique('posting_attempt_id', 'fv2_journal_post_attempt_uq');
            $table->unique(['accounting_entity_id', 'posting_sequence'], 'fv2_journal_entity_sequence_uq');
            $table->index(['accounting_entity_id', 'accounting_date', 'journal_status'], 'fv2_journal_entity_date_status_ix');
            $table->index(['accounting_period_id', 'journal_status'], 'fv2_journal_period_status_ix');
        });

        Schema::table('financial_v2_posting_attempts', function (Blueprint $table) {
            $table->uuid('journal_id')->nullable()->after('failure_detail');
            $table->foreign('journal_id', 'fv2_post_attempt_journal_fk')->references('id')->on('financial_v2_journals')->restrictOnDelete();
            $table->unique('journal_id', 'fv2_post_attempt_journal_uq');
        });

        Schema::create('financial_v2_journal_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreignUuid('journal_id')->constrained('financial_v2_journals')->restrictOnDelete();
            $table->unsignedInteger('line_no');
            $table->foreignUuid('account_id')->constrained('financial_v2_accounts')->restrictOnDelete();
            $table->decimal('debit_amount', 19, 2)->default(0);
            $table->decimal('credit_amount', 19, 2)->default(0);
            $table->uuid('fund_id')->nullable();
            $table->uuid('financial_account_id')->nullable();
            $table->uuid('program_id')->nullable();
            $table->uuid('cost_center_id')->nullable();
            $table->uuid('counterparty_id')->nullable();
            $table->uuid('category_id')->nullable();
            $table->string('policy_version_ref', 80)->nullable();
            $table->text('line_description')->nullable();
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreign('fund_id', 'fv2_jl_fund_fk')->references('id')->on('financial_v2_funds')->restrictOnDelete();
            $table->foreign('financial_account_id', 'fv2_jl_fin_account_fk')->references('id')->on('financial_v2_financial_accounts')->restrictOnDelete();
            $table->foreign('program_id', 'fv2_jl_program_fk')->references('id')->on('financial_v2_programs')->restrictOnDelete();
            $table->foreign('cost_center_id', 'fv2_jl_cost_center_fk')->references('id')->on('financial_v2_cost_centers')->restrictOnDelete();
            $table->foreign('counterparty_id', 'fv2_jl_counterparty_fk')->references('id')->on('financial_v2_counterparties')->restrictOnDelete();
            $table->foreign('category_id', 'fv2_jl_category_fk')->references('id')->on('financial_v2_categories')->restrictOnDelete();
            $table->unique(['journal_id', 'line_no'], 'fv2_jl_journal_line_uq');
            $table->index(['accounting_entity_id', 'account_id', 'created_at'], 'fv2_jl_account_created_ix');
            $table->index(['accounting_entity_id', 'fund_id', 'created_at'], 'fv2_jl_fund_created_ix');
            $table->index(['accounting_entity_id', 'financial_account_id', 'created_at'], 'fv2_jl_fin_account_created_ix');
            $table->index(['accounting_entity_id', 'program_id', 'created_at'], 'fv2_jl_program_created_ix');
        });

        Schema::create('financial_v2_audit_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->timestamp('event_at');
            $table->string('event_type', 80);
            $table->string('target_type', 80);
            $table->uuid('target_id');
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('correlation_id');
            $table->text('before_summary')->nullable();
            $table->text('after_summary')->nullable();
            $table->string('integrity_hash', 128)->nullable();
            $table->timestamp('created_at');
            $table->index(['accounting_entity_id', 'target_type', 'target_id'], 'fv2_audit_target_ix');
            $table->index(['accounting_entity_id', 'correlation_id'], 'fv2_audit_correlation_ix');
            $table->index(['accounting_entity_id', 'event_at'], 'fv2_audit_event_at_ix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_v2_audit_events');
        Schema::dropIfExists('financial_v2_journal_lines');
        Schema::table('financial_v2_posting_attempts', function (Blueprint $table) {
            $table->dropForeign('fv2_post_attempt_journal_fk');
            $table->dropUnique('fv2_post_attempt_journal_uq');
            $table->dropColumn('journal_id');
        });
        Schema::dropIfExists('financial_v2_journals');
    }
};
