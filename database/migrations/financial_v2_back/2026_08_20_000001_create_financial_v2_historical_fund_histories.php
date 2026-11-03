<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_v2_historical_fund_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('accounting_entity_id');
            $table->uuid('fund_id');
            $table->string('source_key', 64);
            $table->string('source_fund_code', 40)->nullable();
            $table->string('source_filename', 255);
            $table->string('source_worksheet', 160)->nullable();
            $table->string('source_reference', 400)->nullable();
            $table->string('source_hash', 128)->nullable();
            $table->string('import_batch_reference', 100)->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->json('source_payload')->nullable();
            $table->unsignedInteger('source_sequence')->default(0);
            $table->enum('entry_kind', ['opening', 'receipt', 'usage', 'adjustment_in', 'adjustment_out', 'account_position', 'closing']);
            $table->date('effective_date')->nullable();
            $table->string('date_label', 100);
            $table->string('description', 500);
            $table->text('notes')->nullable();
            $table->decimal('amount', 19, 2);
            $table->enum('status', ['active', 'corrected', 'void'])->default('active');
            $table->text('correction_reason')->nullable();
            $table->timestamp('corrected_at')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->unsignedBigInteger('updated_by_user_id')->nullable();

            $table->foreign('accounting_entity_id', 'fv2_hist_entity_fk')->references('id')->on('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreign('fund_id', 'fv2_hist_fund_fk')->references('id')->on('financial_v2_funds')->restrictOnDelete();
            $table->foreign('created_by_user_id', 'fv2_hist_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by_user_id', 'fv2_hist_updated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->unique(['accounting_entity_id', 'source_key'], 'fv2_hist_fund_source_key_uq');
            $table->index(['accounting_entity_id', 'fund_id', 'status', 'source_sequence'], 'fv2_hist_fund_scope_ix');
            $table->index(['accounting_entity_id', 'source_filename', 'source_reference'], 'fv2_hist_fund_lineage_ix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_v2_historical_fund_histories');
    }
};
