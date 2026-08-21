<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// One source transaction can create one canonical Journal only.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_v2_journals', function (Blueprint $table) {
            $table->unique('transaction_id', 'fv2_journal_transaction_uq');
        });
    }

    public function down(): void
    {
        Schema::table('financial_v2_journals', function (Blueprint $table) {
            $table->dropUnique('fv2_journal_transaction_uq');
        });
    }
};
