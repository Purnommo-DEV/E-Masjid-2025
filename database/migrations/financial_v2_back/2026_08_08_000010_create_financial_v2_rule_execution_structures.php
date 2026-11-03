<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Financial V2 rule execution structures. These tables turn approved, versioned
// policy references into deterministic input for the canonical Posting Engine.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_v2_posting_rule_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->uuid('posting_rule_version_id');
            $table->unsignedInteger('line_no');
            $table->foreignUuid('account_id')->constrained('financial_v2_accounts')->restrictOnDelete();
            $table->enum('entry_side', ['debit', 'credit']);
            $table->enum('amount_source', ['transaction_gross_amount', 'split_amount']);
            $table->enum('financial_account_source', ['transaction_primary', 'split', 'fixed', 'none'])->default('none');
            $table->enum('fund_source', ['split', 'fixed', 'none'])->default('none');
            $table->enum('program_source', ['split', 'fixed', 'none'])->default('none');
            $table->enum('cost_center_source', ['split', 'fixed', 'none'])->default('none');
            $table->enum('counterparty_source', ['transaction', 'split', 'fixed', 'none'])->default('none');
            $table->enum('category_source', ['transaction', 'split', 'fixed', 'none'])->default('none');
            $table->uuid('fixed_financial_account_id')->nullable();
            $table->uuid('fixed_fund_id')->nullable();
            $table->uuid('fixed_program_id')->nullable();
            $table->uuid('fixed_cost_center_id')->nullable();
            $table->uuid('fixed_counterparty_id')->nullable();
            $table->uuid('fixed_category_id')->nullable();
            $table->text('line_description_template')->nullable();
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['posting_rule_version_id', 'line_no'], 'fv2_rule_line_version_no_uq');
            $table->foreign('posting_rule_version_id', 'fv2_rule_line_version_fk')->references('id')->on('financial_v2_posting_rule_versions')->restrictOnDelete();
            $table->foreign('fixed_financial_account_id', 'fv2_rule_line_fixed_fin_fk')->references('id')->on('financial_v2_financial_accounts')->restrictOnDelete();
            $table->foreign('fixed_fund_id', 'fv2_rule_line_fixed_fund_fk')->references('id')->on('financial_v2_funds')->restrictOnDelete();
            $table->foreign('fixed_program_id', 'fv2_rule_line_fixed_program_fk')->references('id')->on('financial_v2_programs')->restrictOnDelete();
            $table->foreign('fixed_cost_center_id', 'fv2_rule_line_fixed_cost_fk')->references('id')->on('financial_v2_cost_centers')->restrictOnDelete();
            $table->foreign('fixed_counterparty_id', 'fv2_rule_line_fixed_party_fk')->references('id')->on('financial_v2_counterparties')->restrictOnDelete();
            $table->foreign('fixed_category_id', 'fv2_rule_line_fixed_category_fk')->references('id')->on('financial_v2_categories')->restrictOnDelete();
        });

        Schema::create('financial_v2_fund_policy_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreignUuid('fund_policy_version_id')->constrained('financial_v2_fund_policy_versions')->restrictOnDelete();
            $table->foreignUuid('transaction_type_id')->constrained('financial_v2_transaction_types')->restrictOnDelete();
            $table->uuid('account_id')->nullable();
            $table->uuid('category_id')->nullable();
            $table->uuid('program_id')->nullable();
            $table->uuid('cost_center_id')->nullable();
            $table->enum('decision', ['allowed', 'prohibited'])->default('prohibited');
            $table->text('rationale')->nullable();
            $table->timestamps();
            $table->unique(['fund_policy_version_id', 'transaction_type_id', 'account_id', 'category_id', 'program_id', 'cost_center_id'], 'fv2_fund_policy_rule_match_uq');
            $table->foreign('account_id', 'fv2_fund_policy_rule_account_fk')->references('id')->on('financial_v2_accounts')->restrictOnDelete();
            $table->foreign('category_id', 'fv2_fund_policy_rule_category_fk')->references('id')->on('financial_v2_categories')->restrictOnDelete();
            $table->foreign('program_id', 'fv2_fund_policy_rule_program_fk')->references('id')->on('financial_v2_programs')->restrictOnDelete();
            $table->foreign('cost_center_id', 'fv2_fund_policy_rule_cost_fk')->references('id')->on('financial_v2_cost_centers')->restrictOnDelete();
        });

        Schema::create('financial_v2_approval_requirements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreignUuid('transaction_type_id')->constrained('financial_v2_transaction_types')->restrictOnDelete();
            $table->unsignedTinyInteger('required_steps')->default(0);
            $table->enum('status', ['draft', 'active', 'retired'])->default('draft');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();
            $table->unique(['accounting_entity_id', 'transaction_type_id', 'effective_from'], 'fv2_approval_requirement_effective_uq');
        });

        Schema::create('financial_v2_evidence_requirements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->uuid('posting_rule_version_id');
            $table->enum('evidence_type', ['receipt', 'invoice', 'transfer_proof', 'statement', 'cash_count', 'approval', 'policy', 'other']);
            $table->unsignedTinyInteger('minimum_count')->default(1);
            $table->timestamps();
            $table->unique(['posting_rule_version_id', 'evidence_type'], 'fv2_evidence_requirement_type_uq');
            $table->foreign('posting_rule_version_id', 'fv2_evidence_rule_version_fk')->references('id')->on('financial_v2_posting_rule_versions')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_v2_evidence_requirements');
        Schema::dropIfExists('financial_v2_approval_requirements');
        Schema::dropIfExists('financial_v2_fund_policy_rules');
        Schema::dropIfExists('financial_v2_posting_rule_lines');
    }
};
