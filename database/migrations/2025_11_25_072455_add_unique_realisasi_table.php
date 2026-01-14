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
        Schema::table('dana_terikat_realisasi', function (Blueprint $table) {
            $table->unique(['program_id', 'penerima_id', 'tahun', 'bulan'], 'unique_realisasi_bulanan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dana_terikat_realisasi', function (Blueprint $table) {
            //
        });
    }
};
