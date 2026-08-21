<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Financial V2 Foundation migration.

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_v2_accounting_entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 40)->unique();
            $table->string('name', 160);
            $table->string('legal_name', 240);
            $table->char('functional_currency', 3)->default('IDR');
            $table->string('timezone', 64)->default('Asia/Jakarta');
            $table->unsignedTinyInteger('fiscal_year_start_month')->default(1);
            $table->enum('status', ['draft', 'active', 'suspended', 'archived'])->default('draft');
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::create('financial_v2_accounting_calendars', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->string('code', 40);
            $table->string('name', 160);
            $table->string('fiscal_year_label', 20);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['draft', 'active', 'retired'])->default('draft');
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['accounting_entity_id', 'code'], 'fv2_calendar_entity_code_uq');
            $table->unique(['accounting_entity_id', 'fiscal_year_label'], 'fv2_calendar_entity_year_uq');
        });

        Schema::create('financial_v2_accounting_periods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreignUuid('accounting_calendar_id')->constrained('financial_v2_accounting_calendars')->restrictOnDelete();
            $table->unsignedTinyInteger('period_no');
            $table->string('period_name', 40);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['future', 'open', 'soft_closed', 'hard_closed', 'reopened'])->default('future');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('reopen_reason_code_id')->nullable();
            $table->text('reopen_note')->nullable();
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['accounting_calendar_id', 'period_no'], 'fv2_period_calendar_no_uq');
            $table->index(['accounting_entity_id', 'status'], 'fv2_period_entity_status_ix');
        });

        Schema::create('financial_v2_account_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->uuid('parent_group_id')->nullable();
            $table->string('code', 40);
            $table->string('name', 160);
            $table->enum('group_class', ['asset', 'liability', 'net_asset', 'revenue', 'expense', 'transfer', 'control']);
            $table->unsignedInteger('display_order')->default(0);
            $table->enum('status', ['draft', 'active', 'inactive', 'retired'])->default('draft');
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['accounting_entity_id', 'code'], 'fv2_account_group_entity_code_uq');
            $table->foreign('parent_group_id', 'fv2_account_group_parent_fk')->references('id')->on('financial_v2_account_groups')->restrictOnDelete();
        });

        Schema::create('financial_v2_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreignUuid('account_group_id')->constrained('financial_v2_account_groups')->restrictOnDelete();
            $table->string('code', 40);
            $table->string('name', 160);
            $table->enum('account_class', ['asset', 'liability', 'net_asset', 'revenue', 'expense', 'transfer', 'control']);
            $table->enum('normal_balance', ['debit', 'credit']);
            $table->boolean('is_posting_account')->default(true);
            $table->boolean('is_liquidity_account')->default(false);
            $table->boolean('is_control_account')->default(false);
            $table->boolean('allow_manual_posting')->default(false);
            $table->enum('status', ['draft', 'active', 'inactive', 'retired'])->default('draft');
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['accounting_entity_id', 'code'], 'fv2_account_entity_code_uq');
            $table->index(['accounting_entity_id', 'account_class', 'status'], 'fv2_account_entity_class_status_ix');
        });

        Schema::create('financial_v2_account_dimension_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('accounting_entity_id');
            $table->foreignUuid('account_id')->constrained('financial_v2_accounts')->restrictOnDelete();
            $table->enum('dimension_name', ['fund', 'financial_account', 'program', 'cost_center', 'counterparty', 'category']);
            $table->enum('requirement', ['required', 'optional', 'forbidden']);
            $table->boolean('applies_to_debit')->default(true);
            $table->boolean('applies_to_credit')->default(true);
            $table->unsignedInteger('version_no')->default(1);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['account_id', 'dimension_name', 'version_no'], 'fv2_adr_account_dimension_version_uq');
            $table->index(['account_id', 'effective_from', 'effective_to'], 'fv2_adr_account_effective_ix');
            $table->foreign('accounting_entity_id', 'fv2_adr_entity_fk')->references('id')->on('financial_v2_accounting_entities')->restrictOnDelete();
        });

        Schema::create('financial_v2_fund_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->string('code', 40);
            $table->string('name', 160);
            $table->enum('classification', ['unrestricted', 'designated', 'restricted', 'perpetual_restricted', 'custodial', 'syariah']);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['accounting_entity_id', 'code'], 'fv2_fund_type_entity_code_uq');
        });

        Schema::create('financial_v2_fund_restrictions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreignUuid('fund_type_id')->constrained('financial_v2_fund_types')->restrictOnDelete();
            $table->string('code', 40);
            $table->string('name', 160);
            $table->enum('severity', ['low', 'medium', 'high', 'critical']);
            $table->text('policy_basis');
            $table->enum('status', ['draft', 'active', 'retired'])->default('draft');
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['accounting_entity_id', 'code'], 'fv2_fund_restriction_entity_code_uq');
        });

        Schema::create('financial_v2_funds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreignUuid('fund_type_id')->constrained('financial_v2_fund_types')->restrictOnDelete();
            $table->foreignUuid('fund_restriction_id')->constrained('financial_v2_fund_restrictions')->restrictOnDelete();
            $table->string('code', 40);
            $table->string('name', 160);
            $table->text('purpose_statement');
            $table->text('prohibited_use_statement')->nullable();
            $table->decimal('minimum_balance_policy', 19, 2)->nullable();
            $table->boolean('allow_negative_balance')->default(false);
            $table->enum('status', ['draft', 'active', 'suspended', 'closed'])->default('draft');
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['accounting_entity_id', 'code'], 'fv2_fund_entity_code_uq');
            $table->index(['accounting_entity_id', 'status'], 'fv2_fund_entity_status_ix');
        });

        Schema::create('financial_v2_fund_policy_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreignUuid('fund_id')->constrained('financial_v2_funds')->restrictOnDelete();
            $table->unsignedInteger('version_no');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('policy_document_ref', 500);
            $table->string('allowed_matrix_ref', 500)->nullable();
            $table->string('exception_approval_level', 80);
            $table->enum('status', ['draft', 'effective', 'superseded'])->default('draft');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['fund_id', 'version_no'], 'fv2_fund_policy_version_uq');
            $table->index(['fund_id', 'effective_from', 'effective_to'], 'fv2_fund_policy_effective_ix');
        });

        Schema::create('financial_v2_transaction_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->string('code', 20);
            $table->string('name', 160);
            $table->string('voucher_prefix', 10);
            $table->boolean('has_financial_impact')->default(true);
            $table->enum('status', ['draft', 'active', 'retired'])->default('draft');
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['accounting_entity_id', 'code'], 'fv2_tx_type_entity_code_uq');
        });

        Schema::create('financial_v2_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->uuid('transaction_type_id')->nullable();
            $table->uuid('default_posting_rule_id')->nullable();
            $table->string('code', 40);
            $table->string('name', 160);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['accounting_entity_id', 'code'], 'fv2_category_entity_code_uq');
            $table->foreign('transaction_type_id', 'fv2_category_type_fk')->references('id')->on('financial_v2_transaction_types')->restrictOnDelete();
            $table->index('default_posting_rule_id', 'fv2_category_default_rule_ix');
        });

        Schema::create('financial_v2_reason_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->string('code', 40);
            $table->string('name', 160);
            $table->enum('reason_class', ['reversal', 'adjustment', 'exception', 'void', 'override', 'reopen', 'migration']);
            $table->boolean('requires_note')->default(false);
            $table->boolean('requires_attachment')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['accounting_entity_id', 'code'], 'fv2_reason_entity_code_uq');
        });

        Schema::create('financial_v2_business_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->string('rule_code', 20);
            $table->text('rule_text');
            $table->enum('rule_domain', ['master', 'fund', 'rekening', 'program', 'transaction', 'posting', 'closing', 'reconciliation', 'voucher', 'audit', 'reporting']);
            $table->enum('severity', ['block', 'warning', 'review']);
            $table->unsignedInteger('version_no')->default(1);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->enum('status', ['draft', 'effective', 'superseded'])->default('draft');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['accounting_entity_id', 'rule_code', 'version_no'], 'fv2_business_rule_version_uq');
            $table->index(['accounting_entity_id', 'rule_domain', 'status'], 'fv2_business_rule_domain_status_ix');
        });

        Schema::table('financial_v2_accounting_periods', function (Blueprint $table) {
            $table->foreign('reopen_reason_code_id', 'fv2_period_reopen_reason_fk')->references('id')->on('financial_v2_reason_codes')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('financial_v2_accounting_periods', function (Blueprint $table) {
            $table->dropForeign('fv2_period_reopen_reason_fk');
        });
        Schema::dropIfExists('financial_v2_business_rules');
        Schema::dropIfExists('financial_v2_reason_codes');
        Schema::dropIfExists('financial_v2_categories');
        Schema::dropIfExists('financial_v2_transaction_types');
        Schema::dropIfExists('financial_v2_fund_policy_versions');
        Schema::dropIfExists('financial_v2_funds');
        Schema::dropIfExists('financial_v2_fund_restrictions');
        Schema::dropIfExists('financial_v2_fund_types');
        Schema::dropIfExists('financial_v2_account_dimension_rules');
        Schema::dropIfExists('financial_v2_accounts');
        Schema::dropIfExists('financial_v2_account_groups');
        Schema::dropIfExists('financial_v2_accounting_periods');
        Schema::dropIfExists('financial_v2_accounting_calendars');
        Schema::dropIfExists('financial_v2_accounting_entities');
    }
};
