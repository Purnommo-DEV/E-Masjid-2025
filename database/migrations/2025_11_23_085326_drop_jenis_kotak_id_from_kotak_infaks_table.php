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
            // Hapus foreign key terlebih dahulu (nama constraint harus sesuai)
            $table->dropForeign(['jenis_kotak_id']);

            // Hapus kolom
            $table->dropColumn('jenis_kotak_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kotak_infaks', function (Blueprint $table) {
            // Kembalikan kolom
            $table->unsignedBigInteger('jenis_kotak_id')->nullable();

            // Kembalikan foreign key (ubah sesuai nama tabel FK tujuan)
            $table->foreign('jenis_kotak_id')->references('id')->on('jenis_kotak_infaks')->onDelete('cascade');
        });
    }
};
