<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Operational Foundation V2. These records describe business processes only;
// official financial effects remain Journal/JournalLine/LedgerEntry facts
// written exclusively by the canonical PostingEngine.
return new class extends Migration
{
    public function up(): void
    {
        // Extend the approved, data-driven rule vocabulary. No account or fund
        // identifier is embedded in application code.
        DB::statement("ALTER TABLE financial_v2_posting_rule_lines MODIFY financial_account_source ENUM('transaction_primary', 'split', 'fixed', 'none', 'transfer_source', 'transfer_destination') NOT NULL DEFAULT 'none'");
        DB::statement("ALTER TABLE financial_v2_posting_rule_lines MODIFY fund_source ENUM('split', 'fixed', 'none', 'interfund_source', 'interfund_destination') NOT NULL DEFAULT 'none'");

        Schema::create('financial_v2_treasury_transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->uuid('transaction_id');
            $table->uuid('source_financial_account_id');
            $table->uuid('destination_financial_account_id');
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreign('transaction_id', 'fv2_treasury_transfer_tx_fk')->references('id')->on('financial_v2_transactions')->restrictOnDelete();
            $table->foreign('source_financial_account_id', 'fv2_treasury_transfer_source_fk')->references('id')->on('financial_v2_financial_accounts')->restrictOnDelete();
            $table->foreign('destination_financial_account_id', 'fv2_treasury_transfer_dest_fk')->references('id')->on('financial_v2_financial_accounts')->restrictOnDelete();
            $table->unique('transaction_id', 'fv2_treasury_transfer_tx_uq');
            $table->index(['accounting_entity_id', 'source_financial_account_id'], 'fv2_treasury_transfer_source_ix');
            $table->index(['accounting_entity_id', 'destination_financial_account_id'], 'fv2_treasury_transfer_dest_ix');
        });

        Schema::create('financial_v2_interfund_transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->uuid('transaction_id');
            $table->uuid('source_fund_id');
            $table->uuid('destination_fund_id');
            $table->string('policy_basis_ref', 500);
            $table->text('reason');
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreign('transaction_id', 'fv2_interfund_transfer_tx_fk')->references('id')->on('financial_v2_transactions')->restrictOnDelete();
            $table->foreign('source_fund_id', 'fv2_interfund_transfer_source_fk')->references('id')->on('financial_v2_funds')->restrictOnDelete();
            $table->foreign('destination_fund_id', 'fv2_interfund_transfer_dest_fk')->references('id')->on('financial_v2_funds')->restrictOnDelete();
            $table->unique('transaction_id', 'fv2_interfund_transfer_tx_uq');
            $table->index(['accounting_entity_id', 'source_fund_id'], 'fv2_interfund_transfer_source_ix');
            $table->index(['accounting_entity_id', 'destination_fund_id'], 'fv2_interfund_transfer_dest_ix');
        });

        Schema::create('financial_v2_budget_allocations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->uuid('accounting_period_id');
            $table->uuid('fund_id');
            $table->uuid('program_id')->nullable();
            $table->uuid('account_id')->nullable();
            $table->uuid('category_id')->nullable();
            $table->string('allocation_reference', 160);
            $table->string('idempotency_key', 160);
            $table->uuid('correlation_id');
            $table->enum('status', ['draft', 'submitted', 'approved', 'cancelled', 'superseded'])->default('draft');
            $table->text('reason');
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreign('accounting_period_id', 'fv2_budget_allocation_period_fk')->references('id')->on('financial_v2_accounting_periods')->restrictOnDelete();
            $table->foreign('fund_id', 'fv2_budget_allocation_fund_fk')->references('id')->on('financial_v2_funds')->restrictOnDelete();
            $table->foreign('program_id', 'fv2_budget_allocation_program_fk')->references('id')->on('financial_v2_programs')->restrictOnDelete();
            $table->foreign('account_id', 'fv2_budget_allocation_account_fk')->references('id')->on('financial_v2_accounts')->restrictOnDelete();
            $table->foreign('category_id', 'fv2_budget_allocation_category_fk')->references('id')->on('financial_v2_categories')->restrictOnDelete();
            $table->unique(['accounting_entity_id', 'allocation_reference'], 'fv2_budget_allocation_ref_uq');
            $table->unique(['accounting_entity_id', 'idempotency_key'], 'fv2_budget_allocation_idempotency_uq');
            $table->index(['accounting_entity_id', 'accounting_period_id', 'status'], 'fv2_budget_allocation_period_status_ix');
            $table->index(['accounting_entity_id', 'fund_id', 'program_id'], 'fv2_budget_allocation_fund_program_ix');
        });

        Schema::create('financial_v2_budget_allocation_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('accounting_entity_id');
            $table->uuid('budget_allocation_id');
            $table->unsignedInteger('version_no');
            $table->decimal('allocated_amount', 19, 2);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->enum('status', ['draft', 'approved', 'superseded', 'cancelled'])->default('draft');
            $table->text('reason');
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by_user_id')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            $table->foreign('accounting_entity_id', 'fv2_budget_allocation_version_entity_fk')->references('id')->on('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreign('budget_allocation_id', 'fv2_budget_allocation_version_fk')->references('id')->on('financial_v2_budget_allocations')->restrictOnDelete();
            $table->foreign('approved_by_user_id', 'fv2_budget_allocation_version_approved_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by_user_id', 'fv2_budget_allocation_version_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by_user_id', 'fv2_budget_allocation_version_updated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->unique(['budget_allocation_id', 'version_no'], 'fv2_budget_allocation_version_uq');
            $table->index(['budget_allocation_id', 'effective_from', 'effective_to'], 'fv2_budget_allocation_effective_ix');
        });

        // A realization is a non-financial link to an actual posted payment.
        // It carries no independent amount, balance, or posting path.
        Schema::create('financial_v2_fund_realizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->uuid('transaction_id');
            $table->uuid('budget_allocation_version_id')->nullable();
            $table->enum('status', ['draft', 'recorded', 'reversed', 'cancelled'])->default('draft');
            $table->timestamp('recorded_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreign('transaction_id', 'fv2_realization_tx_fk')->references('id')->on('financial_v2_transactions')->restrictOnDelete();
            $table->foreign('budget_allocation_version_id', 'fv2_realization_budget_version_fk')->references('id')->on('financial_v2_budget_allocation_versions')->restrictOnDelete();
            $table->unique('transaction_id', 'fv2_realization_tx_uq');
            $table->index(['accounting_entity_id', 'status'], 'fv2_realization_status_ix');
        });

        DB::statement('ALTER TABLE financial_v2_treasury_transfers ADD CONSTRAINT fv2_treasury_transfer_accounts_ck CHECK (source_financial_account_id <> destination_financial_account_id)');
        DB::statement('ALTER TABLE financial_v2_interfund_transfers ADD CONSTRAINT fv2_interfund_transfer_funds_ck CHECK (source_fund_id <> destination_fund_id)');
        DB::statement('ALTER TABLE financial_v2_budget_allocation_versions ADD CONSTRAINT fv2_budget_allocation_amount_ck CHECK (allocated_amount > 0)');
        DB::statement('ALTER TABLE financial_v2_budget_allocation_versions ADD CONSTRAINT fv2_budget_allocation_dates_ck CHECK (effective_to IS NULL OR effective_to >= effective_from)');
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_v2_fund_realizations');
        Schema::dropIfExists('financial_v2_budget_allocation_versions');
        Schema::dropIfExists('financial_v2_budget_allocations');
        Schema::dropIfExists('financial_v2_interfund_transfers');
        Schema::dropIfExists('financial_v2_treasury_transfers');

        DB::statement("ALTER TABLE financial_v2_posting_rule_lines MODIFY financial_account_source ENUM('transaction_primary', 'split', 'fixed', 'none') NOT NULL DEFAULT 'none'");
        DB::statement("ALTER TABLE financial_v2_posting_rule_lines MODIFY fund_source ENUM('split', 'fixed', 'none') NOT NULL DEFAULT 'none'");
    }
};
