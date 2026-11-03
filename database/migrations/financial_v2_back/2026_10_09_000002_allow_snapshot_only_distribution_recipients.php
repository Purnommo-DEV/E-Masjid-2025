<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_v2_distribution_items', function (Blueprint $table): void {
            $table->string('recipient_key', 80)->nullable()->after('beneficiary_id');
            $table->index('distribution_id', 'fv2_distribution_item_distribution_ix');
        });

        DB::table('financial_v2_distribution_items')
            ->whereNotNull('beneficiary_id')
            ->update(['recipient_key' => DB::raw("CONCAT('master:', beneficiary_id)")]);

        if (DB::table('financial_v2_distribution_items')->whereNull('recipient_key')->exists()) {
            throw new \RuntimeException('Distribution item recipient keys could not be backfilled safely.');
        }

        Schema::table('financial_v2_distribution_items', function (Blueprint $table): void {
            $table->dropForeign(['beneficiary_id']);
        });
        Schema::table('financial_v2_distribution_items', function (Blueprint $table): void {
            $table->dropUnique('fv2_distribution_beneficiary_uq');
            $table->uuid('beneficiary_id')->nullable()->change();
            $table->string('recipient_key', 80)->nullable(false)->change();
            $table->unique(['distribution_id', 'recipient_key'], 'fv2_distribution_recipient_uq');
            $table->foreign('beneficiary_id', 'fv2_distribution_beneficiary_fk')
                ->references('id')->on('financial_v2_counterparties')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('financial_v2_distribution_items')->whereNull('beneficiary_id')->exists()) {
            throw new \RuntimeException('Cannot restore mandatory beneficiary links while snapshot-only distribution recipients exist.');
        }

        Schema::table('financial_v2_distribution_items', function (Blueprint $table): void {
            $table->dropForeign('fv2_distribution_beneficiary_fk');
        });
        Schema::table('financial_v2_distribution_items', function (Blueprint $table): void {
            $table->uuid('beneficiary_id')->nullable(false)->change();
            $table->unique(['distribution_id', 'beneficiary_id'], 'fv2_distribution_beneficiary_uq');
        });
        Schema::table('financial_v2_distribution_items', function (Blueprint $table): void {
            $table->dropUnique('fv2_distribution_recipient_uq');
            $table->foreign('beneficiary_id')->references('id')->on('financial_v2_counterparties')->restrictOnDelete();
            $table->dropIndex('fv2_distribution_item_distribution_ix');
            $table->dropColumn('recipient_key');
        });
    }
};
