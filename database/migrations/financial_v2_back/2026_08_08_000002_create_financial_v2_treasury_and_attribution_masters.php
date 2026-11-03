<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Financial V2 Foundation migration.

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_v2_financial_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreignUuid('account_id')->constrained('financial_v2_accounts')->restrictOnDelete();
            $table->string('code', 40);
            $table->string('name', 160);
            $table->enum('account_type', ['bank', 'cash', 'petty_cash', 'e_wallet']);
            $table->string('custodian_reference', 100)->nullable();
            $table->char('currency_code', 3)->default('IDR');
            $table->date('opening_date');
            $table->date('closing_date')->nullable();
            $table->enum('status', ['draft', 'active', 'suspended', 'closed'])->default('draft');
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['accounting_entity_id', 'code'], 'fv2_fin_account_entity_code_uq');
            $table->index(['accounting_entity_id', 'account_id', 'status'], 'fv2_fin_account_account_status_ix');
        });

        Schema::create('financial_v2_bank_account_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('financial_account_id')->constrained('financial_v2_financial_accounts')->restrictOnDelete();
            $table->string('bank_name', 160);
            $table->string('branch_name', 160)->nullable();
            $table->string('account_number_masked', 80);
            $table->string('account_number_protected_ref', 500)->nullable();
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique('financial_account_id', 'fv2_bank_detail_fin_account_uq');
            $table->unique('account_number_protected_ref', 'fv2_bank_detail_protected_ref_uq');
        });

        Schema::create('financial_v2_cash_account_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('financial_account_id')->constrained('financial_v2_financial_accounts')->restrictOnDelete();
            $table->string('cash_location', 240);
            $table->enum('cash_count_frequency', ['daily', 'weekly', 'monthly', 'ad_hoc']);
            $table->decimal('petty_cash_limit', 19, 2)->nullable();
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique('financial_account_id', 'fv2_cash_detail_fin_account_uq');
        });

        Schema::create('financial_v2_cost_centers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->uuid('parent_cost_center_id')->nullable();
            $table->string('code', 40);
            $table->string('name', 160);
            $table->string('manager_reference', 100)->nullable();
            $table->enum('status', ['draft', 'active', 'inactive'])->default('draft');
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['accounting_entity_id', 'code'], 'fv2_cost_center_entity_code_uq');
            $table->foreign('parent_cost_center_id', 'fv2_cost_center_parent_fk')->references('id')->on('financial_v2_cost_centers')->restrictOnDelete();
        });

        Schema::create('financial_v2_programs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreignUuid('cost_center_id')->nullable()->constrained('financial_v2_cost_centers')->restrictOnDelete();
            $table->string('code', 40);
            $table->string('name', 160);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('program_owner_reference', 100)->nullable();
            $table->enum('status', ['draft', 'active', 'suspended', 'closed'])->default('draft');
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['accounting_entity_id', 'code'], 'fv2_program_entity_code_uq');
            $table->index(['accounting_entity_id', 'status'], 'fv2_program_entity_status_ix');
        });

        Schema::create('financial_v2_counterparties', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->string('code', 40);
            $table->enum('party_type', ['donor', 'supplier', 'beneficiary', 'bank', 'institution', 'other']);
            $table->string('display_name', 240);
            $table->string('external_reference', 160)->nullable();
            $table->string('contact_reference', 500)->nullable();
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['accounting_entity_id', 'code'], 'fv2_counterparty_entity_code_uq');
            $table->index(['accounting_entity_id', 'party_type', 'status'], 'fv2_counterparty_type_status_ix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_v2_counterparties');
        Schema::dropIfExists('financial_v2_programs');
        Schema::dropIfExists('financial_v2_cost_centers');
        Schema::dropIfExists('financial_v2_cash_account_details');
        Schema::dropIfExists('financial_v2_bank_account_details');
        Schema::dropIfExists('financial_v2_financial_accounts');
    }
};
