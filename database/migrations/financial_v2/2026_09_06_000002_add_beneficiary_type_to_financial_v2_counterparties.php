<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_v2_counterparties', function (Blueprint $table): void {
            $table->enum('beneficiary_type', ['YATIM', 'DHUAFA', 'YATIM_DHUAFA', 'BELUM_DITENTUKAN'])->nullable()->after('party_type');
            $table->index(['accounting_entity_id', 'party_type', 'beneficiary_type'], 'fv2_counterparty_beneficiary_type_ix');
        });

        DB::table('financial_v2_counterparties')
            ->where('party_type', 'beneficiary')
            ->whereNull('beneficiary_type')
            ->update(['beneficiary_type' => 'BELUM_DITENTUKAN']);
    }

    public function down(): void
    {
        Schema::table('financial_v2_counterparties', function (Blueprint $table): void {
            $table->dropIndex('fv2_counterparty_beneficiary_type_ix');
            $table->dropColumn('beneficiary_type');
        });
    }
};
