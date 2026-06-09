<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('qurban_settings', function (Blueprint $table) {
            // Data Statistik
            $table->integer('laporan_hewan_sapi')->default(8)->nullable();
            $table->integer('laporan_hewan_kambing')->default(41)->nullable();
            $table->integer('laporan_hewan_total')->default(49)->nullable();
            $table->integer('laporan_paket_daging')->default(1400)->nullable();
            $table->integer('laporan_mustahik')->default(1252)->nullable();
            $table->integer('laporan_daging_kg')->default(1500)->nullable();
            
            // Keuangan - Penerimaan
            $table->bigInteger('laporan_penerimaan_sapi')->default(280000000)->nullable();
            $table->bigInteger('laporan_penerimaan_kambing')->default(62000000)->nullable();
            $table->bigInteger('laporan_penerimaan_potong_sapi')->default(1500000)->nullable();
            $table->bigInteger('laporan_penerimaan_potong_kambing')->default(900000)->nullable();
            $table->bigInteger('laporan_penerimaan_total_peserta')->default(344400000)->nullable();
            $table->bigInteger('laporan_penerimaan_infaq')->default(18750000)->nullable();
            $table->bigInteger('laporan_penerimaan_total')->default(363150000)->nullable();
            
            // Keuangan - Pengeluaran
            $table->bigInteger('laporan_pengeluaran_sapi')->default(280000000)->nullable();
            $table->bigInteger('laporan_pengeluaran_kambing')->default(62000000)->nullable();
            $table->bigInteger('laporan_pengeluaran_pemotongan')->default(4500000)->nullable();
            $table->bigInteger('laporan_pengeluaran_sound')->default(3500000)->nullable();
            $table->bigInteger('laporan_pengeluaran_penyelenggaraan')->default(5500000)->nullable();
            $table->bigInteger('laporan_pengeluaran_survei')->default(1200000)->nullable();
            $table->bigInteger('laporan_pengeluaran_spanduk_qurban')->default(1850000)->nullable();
            $table->bigInteger('laporan_pengeluaran_spanduk_sholat')->default(800000)->nullable();
            $table->bigInteger('laporan_pengeluaran_konsumsi')->default(3200000)->nullable();
            $table->bigInteger('laporan_pengeluaran_total')->default(362550000)->nullable();
            
            // Keuangan - Sisa Dana
            $table->bigInteger('laporan_sisa_dana')->default(600000)->nullable();
            
            // Distribusi
            $table->integer('laporan_distribusi_shohibul')->default(467)->nullable();
            $table->integer('laporan_distribusi_masyarakat')->default(467)->nullable();
            $table->integer('laporan_distribusi_dhuafa')->default(466)->nullable();
        });
    }

    public function down()
    {
        Schema::table('qurban_settings', function (Blueprint $table) {
            $table->dropColumn([
                'laporan_hewan_sapi',
                'laporan_hewan_kambing',
                'laporan_hewan_total',
                'laporan_paket_daging',
                'laporan_mustahik',
                'laporan_daging_kg',
                'laporan_penerimaan_sapi',
                'laporan_penerimaan_kambing',
                'laporan_penerimaan_potong_sapi',
                'laporan_penerimaan_potong_kambing',
                'laporan_penerimaan_total_peserta',
                'laporan_penerimaan_infaq',
                'laporan_penerimaan_total',
                'laporan_pengeluaran_sapi',
                'laporan_pengeluaran_kambing',
                'laporan_pengeluaran_pemotongan',
                'laporan_pengeluaran_sound',
                'laporan_pengeluaran_penyelenggaraan',
                'laporan_pengeluaran_survei',
                'laporan_pengeluaran_spanduk_qurban',
                'laporan_pengeluaran_spanduk_sholat',
                'laporan_pengeluaran_konsumsi',
                'laporan_pengeluaran_total',
                'laporan_sisa_dana',
                'laporan_distribusi_shohibul',
                'laporan_distribusi_masyarakat',
                'laporan_distribusi_dhuafa',
            ]);
        });
    }
};