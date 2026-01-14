<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('kotak_infaks', function (Blueprint $table) {
            // Hapus foreign key terlebih dahulu (nama constraint harus sesuai)
            $table->dropForeign(['transaksi_id']);

            // Hapus kolom
            $table->dropColumn('transaksi_id');
        });
    }

    public function down()
    {
        Schema::table('kotak_infaks', function (Blueprint $table) {
            // Kembalikan kolom
            $table->unsignedBigInteger('transaksi_id')->nullable();

            // Kembalikan foreign key (ubah sesuai nama tabel FK tujuan)
            $table->foreign('transaksi_id')->references('id')->on('transaksis')->onDelete('cascade');
        });
    }
};
