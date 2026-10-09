<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_v2_transactions', function (Blueprint $table): void {
            $table->string('fund_purpose_code', 64)->nullable()->after('description');
            $table->string('fund_purpose_other', 500)->nullable()->after('fund_purpose_code');
        });
    }

    public function down(): void
    {
        Schema::table('financial_v2_transactions', function (Blueprint $table): void {
            $table->dropColumn(['fund_purpose_code', 'fund_purpose_other']);
        });
    }
};
