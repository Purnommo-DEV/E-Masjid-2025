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
        Schema::table('laporan_ramadhan_harian', function (Blueprint $table) {
            $table->decimal('santunan_yatim_total_terkumpul', 15, 0)->nullable()->default(0);
            $table->decimal('paket_sembako_total_terkumpul', 15, 0)->nullable()->default(0);
            // sudah ada gebyar_anak_total_terkumpul dari sebelumnya
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laporan_ramadhan_harian', function (Blueprint $table) {
            //
        });
    }
};
