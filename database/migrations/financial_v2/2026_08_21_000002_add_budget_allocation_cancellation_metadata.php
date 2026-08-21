<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_v2_budget_allocations', function (Blueprint $table): void {
            $table->timestamp('cancelled_at')->nullable()->after('reason');
            $table->foreignId('cancelled_by_user_id')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable()->after('cancelled_by_user_id');
            $table->index(['accounting_entity_id', 'status', 'cancelled_at'], 'fv2_budget_allocation_cancel_ix');
        });
    }

    public function down(): void
    {
        Schema::table('financial_v2_budget_allocations', function (Blueprint $table): void {
            $table->dropIndex('fv2_budget_allocation_cancel_ix');
            $table->dropConstrainedForeignId('cancelled_by_user_id');
            $table->dropColumn(['cancelled_at', 'cancellation_reason']);
        });
    }
};
