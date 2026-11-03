<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Financial V2 Foundation migration.

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_v2_posting_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreignUuid('transaction_type_id')->constrained('financial_v2_transaction_types')->restrictOnDelete();
            $table->string('code', 40);
            $table->string('name', 160);
            $table->string('rule_family', 80);
            $table->enum('status', ['draft', 'active', 'retired'])->default('draft');
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['accounting_entity_id', 'code'], 'fv2_post_rule_entity_code_uq');
            $table->unique(['transaction_type_id', 'rule_family'], 'fv2_post_rule_type_family_uq');
        });

        Schema::create('financial_v2_posting_rule_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreignUuid('posting_rule_id')->constrained('financial_v2_posting_rules')->restrictOnDelete();
            $table->unsignedInteger('version_no');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('input_contract_ref', 500);
            $table->string('journal_template_ref', 500);
            $table->text('business_rule_refs');
            $table->enum('status', ['draft', 'effective', 'superseded'])->default('draft');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['posting_rule_id', 'version_no'], 'fv2_post_rule_version_uq');
            $table->index(['posting_rule_id', 'effective_from', 'effective_to'], 'fv2_post_rule_effective_ix');
        });

        Schema::table('financial_v2_categories', function (Blueprint $table) {
            $table->foreign('default_posting_rule_id', 'fv2_category_default_rule_fk')->references('id')->on('financial_v2_posting_rules')->restrictOnDelete();
        });

        Schema::create('financial_v2_document_sequences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreignUuid('transaction_type_id')->constrained('financial_v2_transaction_types')->restrictOnDelete();
            $table->string('code', 40);
            $table->string('name', 160);
            $table->string('prefix', 10);
            $table->string('scope_key', 120);
            $table->unsignedBigInteger('next_value')->default(1);
            $table->enum('reset_rule', ['never', 'yearly', 'monthly'])->default('never');
            $table->enum('status', ['draft', 'active', 'retired'])->default('draft');
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['accounting_entity_id', 'code'], 'fv2_doc_seq_entity_code_uq');
            $table->unique(['accounting_entity_id', 'transaction_type_id', 'scope_key'], 'fv2_doc_seq_scope_uq');
        });
    }

    public function down(): void
    {
        Schema::table('financial_v2_categories', function (Blueprint $table) {
            $table->dropForeign('fv2_category_default_rule_fk');
        });
        Schema::dropIfExists('financial_v2_document_sequences');
        Schema::dropIfExists('financial_v2_posting_rule_versions');
        Schema::dropIfExists('financial_v2_posting_rules');
    }
};
