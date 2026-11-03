<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE financial_v2_fund_policy_versions MODIFY status ENUM('draft','effective','superseded','replaced_unused') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        if (DB::table('financial_v2_fund_policy_versions')->where('status', 'replaced_unused')->exists()) {
            throw new RuntimeException('Cannot remove replaced_unused policy status while governed replacement history exists.');
        }

        DB::statement("ALTER TABLE financial_v2_fund_policy_versions MODIFY status ENUM('draft','effective','superseded') NOT NULL DEFAULT 'draft'");
    }
};
