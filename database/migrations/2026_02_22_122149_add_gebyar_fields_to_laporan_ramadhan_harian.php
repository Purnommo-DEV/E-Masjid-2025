<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_ramadhan_harian', function (Blueprint $table) {
            $table->decimal('gebyar_anak_terkumpul_kemarin', 15, 0)->nullable()->default(0)->after('paket_sembako_penerimaan_hari_ini');
            $table->json('gebyar_anak_penerimaan_hari_ini')->nullable()->after('gebyar_anak_terkumpul_kemarin');
            // Opsional: simpan total terkumpul (bisa dihitung ulang, tapi berguna untuk sorting/filter cepat)
            $table->decimal('gebyar_anak_total_terkumpul', 15, 0)->nullable()->default(0)->after('gebyar_anak_penerimaan_hari_ini');
        });
    }

    public function down(): void
    {
        Schema::table('laporan_ramadhan_harian', function (Blueprint $table) {
            $table->dropColumn([
                'gebyar_anak_terkumpul_kemarin',
                'gebyar_anak_penerimaan_hari_ini',
                'gebyar_anak_total_terkumpul',
            ]);
        });
    }
};