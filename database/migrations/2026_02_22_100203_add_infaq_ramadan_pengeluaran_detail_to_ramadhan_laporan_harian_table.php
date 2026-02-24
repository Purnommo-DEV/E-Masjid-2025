<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_ramadhan_harian', function (Blueprint $table) {
            $table->json('infaq_ramadan_pengeluaran_detail')->nullable()->after('infaq_ramadan_pengeluaran_operasional');
            // Opsional: kalau belum ada kolom total pengeluaran, tambahkan juga
            // $table->bigInteger('infaq_ramadan_pengeluaran_operasional')->default(0)->change(); // kalau mau ubah default
        });
    }

    public function down(): void
    {
        Schema::table('laporan_ramadhan_harian', function (Blueprint $table) {
            $table->dropColumn('infaq_ramadan_pengeluaran_detail');
        });
    }
};