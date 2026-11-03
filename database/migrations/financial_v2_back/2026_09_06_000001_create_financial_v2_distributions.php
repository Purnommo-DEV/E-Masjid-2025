<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_v2_counterparties', function (Blueprint $table) {
            $table->text('address')->nullable();
            $table->string('rt', 10)->nullable();
            $table->string('rw', 10)->nullable();
            $table->string('rt_coordinator_name', 160)->nullable();
            $table->text('beneficiary_notes')->nullable();
        });
        Schema::create('financial_v2_distributions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('accounting_entity_id')->constrained('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreignUuid('program_id')->constrained('financial_v2_programs')->restrictOnDelete();
            $table->foreignUuid('realization_id')->nullable()->constrained('financial_v2_fund_realizations')->restrictOnDelete();
            $table->unique('realization_id', 'fv2_distribution_realization_uq');
            $table->foreignUuid('copied_from_id')->nullable()->constrained('financial_v2_distributions')->restrictOnDelete();
            $table->string('title', 200);
            $table->string('period_label', 100);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'finalized'])->default('draft');
            $table->unsignedInteger('revision')->default(0);
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('finalized_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['program_id', 'starts_on', 'ends_on'], 'fv2_distribution_period_uq');
            $table->index(['accounting_entity_id', 'status', 'starts_on'], 'fv2_distribution_scope_ix');
        });
        Schema::create('financial_v2_distribution_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('distribution_id')->constrained('financial_v2_distributions')->restrictOnDelete();
            $table->foreignUuid('beneficiary_id')->constrained('financial_v2_counterparties')->restrictOnDelete();
            $table->decimal('amount', 18, 2);
            $table->text('notes')->nullable();
            $table->json('identity_snapshot');
            $table->timestamps();
            $table->unique(['distribution_id', 'beneficiary_id'], 'fv2_distribution_beneficiary_uq');
        });
        DB::statement('ALTER TABLE financial_v2_distribution_items ADD CONSTRAINT fv2_distribution_amount_ck CHECK (amount >= 0)');
        DB::statement('ALTER TABLE financial_v2_distributions ADD CONSTRAINT fv2_distribution_dates_ck CHECK (ends_on >= starts_on)');
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_v2_distribution_items');
        Schema::dropIfExists('financial_v2_distributions');
        Schema::table('financial_v2_counterparties', fn (Blueprint $table) => $table->dropColumn(['address', 'rt', 'rw', 'rt_coordinator_name', 'beneficiary_notes']));
    }
};
