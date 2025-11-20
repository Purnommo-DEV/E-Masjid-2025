<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kotak_infaks', function (Blueprint $table) {
            $table->unsignedBigInteger('transaksi_id')->nullable()->after('tanggal');
            $table->foreign('transaksi_id')->references('id')->on('transaksis')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kotak_infaks', function (Blueprint $table) {
            //
        });
    }
};
