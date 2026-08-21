<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Adds only V2 opening-position governance. It neither reads nor changes legacy tables.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_v2_opening_balance_batches', function (Blueprint $table) {
            $table->timestamp('reviewed_at')->nullable()->after('evidence_package_ref');
            $table->foreignId('reviewed_by_user_id')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
        });

        Schema::table('financial_v2_opening_balance_lines', function (Blueprint $table) {
            $table->foreignUuid('program_id')->nullable()->after('financial_account_id')->constrained('financial_v2_programs')->restrictOnDelete();
            $table->string('source_reference', 240)->nullable()->after('mapping_ref');
            $table->decimal('source_debit_amount', 19, 2)->default(0)->after('source_reference');
            $table->decimal('source_credit_amount', 19, 2)->default(0)->after('source_debit_amount');
            $table->decimal('reconciliation_difference', 19, 2)->default(0)->after('source_credit_amount');
            $table->enum('reconciliation_status', ['draft', 'reconciled', 'difference', 'exception'])->default('draft')->after('reconciliation_difference');
            $table->index(['accounting_entity_id', 'financial_account_id'], 'fv2_open_line_fin_acc_ix');
            $table->index(['accounting_entity_id', 'program_id'], 'fv2_open_line_program_ix');
            $table->index(['opening_balance_batch_id', 'reconciliation_status'], 'fv2_open_line_recon_status_ix');
        });
    }

    public function down(): void
    {
        Schema::table('financial_v2_opening_balance_lines', function (Blueprint $table) {
            $table->dropIndex('fv2_open_line_fin_acc_ix');
            $table->dropIndex('fv2_open_line_program_ix');
            $table->dropIndex('fv2_open_line_recon_status_ix');
            $table->dropForeign(['program_id']);
            $table->dropColumn(['program_id', 'source_reference', 'source_debit_amount', 'source_credit_amount', 'reconciliation_difference', 'reconciliation_status']);
        });

        Schema::table('financial_v2_opening_balance_batches', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by_user_id']);
            $table->dropColumn(['reviewed_at', 'reviewed_by_user_id']);
        });
    }
};
