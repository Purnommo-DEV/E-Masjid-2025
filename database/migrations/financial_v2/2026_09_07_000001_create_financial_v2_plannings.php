<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_v2_plannings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->string('planning_number', 80)->unique();
            $table->string('name', 240);
            $table->date('period_start');
            $table->date('period_end');
            $table->uuid('program_id')->nullable();
            $table->unsignedInteger('target_recipient_count')->nullable();
            $table->decimal('amount_per_recipient', 19, 2)->nullable();
            $table->decimal('total_amount', 19, 2);
            $table->enum('status', ['draft', 'approved', 'converted', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->uuid('correlation_id');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->foreignId('converted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreign('program_id', 'fv2_planning_program_fk')->references('id')->on('financial_v2_programs')->restrictOnDelete();
            $table->index(['accounting_entity_id', 'status', 'period_start'], 'fv2_planning_entity_status_period_ix');
            $table->index(['accounting_entity_id', 'program_id', 'status'], 'fv2_planning_entity_program_status_ix');
        });

        Schema::create('financial_v2_planning_fundings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->uuid('planning_id');
            $table->uuid('fund_id');
            $table->unsignedSmallInteger('line_no');
            $table->decimal('amount', 19, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreign('planning_id', 'fv2_planning_funding_planning_fk')->references('id')->on('financial_v2_plannings')->restrictOnDelete();
            $table->foreign('fund_id', 'fv2_planning_funding_fund_fk')->references('id')->on('financial_v2_funds')->restrictOnDelete();
            $table->unique(['planning_id', 'line_no'], 'fv2_planning_funding_line_uq');
            $table->unique(['planning_id', 'fund_id'], 'fv2_planning_funding_fund_uq');
            $table->index(['accounting_entity_id', 'fund_id', 'planning_id'], 'fv2_planning_funding_entity_fund_ix');
        });

        Schema::table('financial_v2_budget_allocations', function (Blueprint $table): void {
            $table->uuid('planning_id')->nullable()->after('accounting_entity_id');
            $table->foreign('planning_id', 'fv2_budget_allocation_planning_fk')->references('id')->on('financial_v2_plannings')->restrictOnDelete();
            $table->unique('planning_id', 'fv2_budget_allocation_planning_uq');
        });

        DB::statement('ALTER TABLE financial_v2_plannings ADD CONSTRAINT fv2_planning_total_ck CHECK (total_amount >= 0)');
        DB::statement('ALTER TABLE financial_v2_plannings ADD CONSTRAINT fv2_planning_amount_per_recipient_ck CHECK (amount_per_recipient IS NULL OR amount_per_recipient >= 0)');
        DB::statement('ALTER TABLE financial_v2_plannings ADD CONSTRAINT fv2_planning_period_ck CHECK (period_end >= period_start)');
        DB::statement('ALTER TABLE financial_v2_planning_fundings ADD CONSTRAINT fv2_planning_funding_amount_ck CHECK (amount > 0)');
    }

    public function down(): void
    {
        Schema::table('financial_v2_budget_allocations', function (Blueprint $table): void {
            $table->dropForeign('fv2_budget_allocation_planning_fk');
            $table->dropUnique('fv2_budget_allocation_planning_uq');
            $table->dropColumn('planning_id');
        });

        Schema::dropIfExists('financial_v2_planning_fundings');
        Schema::dropIfExists('financial_v2_plannings');
    }
};
