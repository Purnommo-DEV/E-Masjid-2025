<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_v2_bank_mutation_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('accounting_entity_id');
            $table->uuid('financial_account_id');
            $table->uuid('fund_id');
            $table->uuid('transaction_type_id');
            $table->uuid('category_id');
            $table->uuid('posting_rule_version_id');
            $table->string('policy_document_ref', 500);
            $table->enum('evidence_type', ['statement', 'other'])->default('statement');
            $table->unsignedTinyInteger('required_approval_steps')->default(1);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->enum('status', ['draft', 'active', 'retired'])->default('draft');
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['accounting_entity_id', 'financial_account_id', 'fund_id', 'category_id', 'effective_from'], 'fv2_bank_policy_scope_date_uq');
            $table->index(['accounting_entity_id', 'status', 'effective_from', 'effective_to'], 'fv2_bank_policy_effective_ix');
            $table->foreign('accounting_entity_id', 'fv2_bank_policy_entity_fk')->references('id')->on('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreign('financial_account_id', 'fv2_bank_policy_fin_acc_fk')->references('id')->on('financial_v2_financial_accounts')->restrictOnDelete();
            $table->foreign('fund_id', 'fv2_bank_policy_fund_fk')->references('id')->on('financial_v2_funds')->restrictOnDelete();
            $table->foreign('transaction_type_id', 'fv2_bank_policy_type_fk')->references('id')->on('financial_v2_transaction_types')->restrictOnDelete();
            $table->foreign('category_id', 'fv2_bank_policy_category_fk')->references('id')->on('financial_v2_categories')->restrictOnDelete();
            $table->foreign('posting_rule_version_id', 'fv2_bank_policy_rule_ver_fk')->references('id')->on('financial_v2_posting_rule_versions')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_v2_bank_mutation_policies');
    }
};
