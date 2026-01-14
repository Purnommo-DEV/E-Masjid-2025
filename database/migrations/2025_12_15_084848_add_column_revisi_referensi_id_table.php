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
        Schema::table('dana_terikat_penerima', function (Blueprint $table) {

            // 1️⃣ Drop foreign key lama (self reference)
            if (Schema::hasColumn('dana_terikat_penerima', 'referensi_id')) {
                $table->dropForeign(['referensi_id']);
                $table->dropColumn('referensi_id');
            }

            // 2️⃣ Buat ulang kolom + foreign key yang BENAR
            $table->unsignedBigInteger('referensi_id')
                  ->nullable()
                  ->after('kategori');

            $table->foreign('referensi_id')
                  ->references('id')
                  ->on('dana_terikat_referensi')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dana_terikat_penerima', function (Blueprint $table) {
            $table->dropForeign(['referensi_id']);
            $table->dropColumn('referensi_id');
        });
    }
};
