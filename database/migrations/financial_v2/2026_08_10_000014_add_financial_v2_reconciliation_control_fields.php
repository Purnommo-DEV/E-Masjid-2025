<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Activates the reserved reconciliation entity with auditable review/finalisation
// controls. business_date remains the statement/cash-count date, so no second
// competing date concept is introduced.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_v2_reconciliations', function (Blueprint $table) {
            $table->decimal('difference', 19, 2)->default(0)->after('ledger_balance');
            $table->text('notes')->nullable()->after('difference');
            $table->timestamp('reviewed_at')->nullable()->after('notes');
            $table->foreignId('reviewed_by_user_id')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('reconciled_at')->nullable()->after('reviewed_by_user_id');
            $table->foreignId('reconciled_by_user_id')->nullable()->after('reconciled_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('financial_v2_reconciliations', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by_user_id']);
            $table->dropForeign(['reconciled_by_user_id']);
            $table->dropColumn(['difference', 'notes', 'reviewed_at', 'reviewed_by_user_id', 'reconciled_at', 'reconciled_by_user_id']);
        });
    }
};
