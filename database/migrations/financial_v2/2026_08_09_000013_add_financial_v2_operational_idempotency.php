<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Source-transaction idempotency is separate from posting-request idempotency.
// It prevents one business event from being created as multiple V2 drafts.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_v2_transactions', function (Blueprint $table) {
            $table->unique(['accounting_entity_id', 'idempotency_key'], 'fv2_tx_entity_idempotency_uq');
        });
    }

    public function down(): void
    {
        Schema::table('financial_v2_transactions', function (Blueprint $table) {
            $table->dropUnique('fv2_tx_entity_idempotency_uq');
        });
    }
};
